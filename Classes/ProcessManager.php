<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab;

use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Helhum\TYPO3\Crontab\Task\Process;
use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const runningTable = 'tx_crontab_running';
    /**
     * @var TaskRepository
     */
    private $taskRepository;
    /**
     * @var Crontab
     */
    private $crontab;
    /**
     * @var Connection
     */
    private $databaseConnection;

    public function __construct(
        TaskRepository $taskRepository = null,
        Crontab $crontab = null,
        Connection $databaseConnection = null
    ) {
        $this->taskRepository = $taskRepository ?? new TaskRepository();
        $this->crontab = $crontab ?? new Crontab();
        $this->databaseConnection = $databaseConnection ?? GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::runningTable);
    }

    public function isRunning(string $taskIdentifier): bool
    {
        $isRunning = false;

        $result = $this->databaseConnection->select(
            ['process_id'],
            self::runningTable,
            ['identifier' => $taskIdentifier]
        );

        foreach ($result as $row) {
            $processId = (int)$row['process_id'];
            $isRunning = posix_getpgid($processId) !== false;
            if (!$isRunning) {
                // The process has crashed or was terminated by us, remove it from our list
                $this->finish($taskIdentifier, $processId);
            }
        }

        return $isRunning;
    }

    public function terminate(string $taskIdentifier): void
    {
        $result = $this->databaseConnection->select(
            ['process_id'],
            self::runningTable,
            ['identifier' => $taskIdentifier]
        );

        foreach ($result as $row) {
            $processId = (int)$row['process_id'];
            $isRunning = posix_getpgid($processId) !== false;
            if ($isRunning) {
                posix_kill($processId, 2 /*SIGINT*/);
            }
        }
    }

    public function run(string $taskIdentifier, Application $application, InputInterface $input = null, OutputInterface $output = null): int
    {
        $taskDefinition = $this->taskRepository->findByIdentifier($taskIdentifier);
        if (!$taskDefinition->allowsMultipleExecutions() && $this->isRunning($taskIdentifier)) {
            $this->logger->info(sprintf('Task "%s" is running and is not configured for parallel execution, skipping.', $taskIdentifier));

            return 0;
        }

        // Re-schedule for next execution if it was scheduled before
        // TODO: Should we rather throw an exception here?
        if ($this->crontab->isScheduled($taskIdentifier)) {
            $this->crontab->schedule($taskIdentifier, $taskDefinition->getNextDueExecution());
        }

        $process = $this->start($taskDefinition);
        $this->logger->info(sprintf('Starting task "%s" in process with id "%d".', $taskIdentifier, $process->getId()));

        $success = true;
        try {
            $success = $process->run($application, $input, $output);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Task "%s" failed with exception.', $taskIdentifier), ['exception' => $e]);
            throw $e;
        } finally {
            $this->finish($taskIdentifier, $process->getId());
        }
        if (!$success) {
            $this->logger->error(sprintf('Task "%s" did not complete successfully.', $taskIdentifier));
        }

        return 0;
    }

    public function runIsolated(string $taskIdentifier): void
    {
        $commandDispatcher = CommandDispatcher::create(getenv('TYPO3_PATH_COMPOSER_ROOT') . '/vendor/helhum/typo3-console/typo3cms');
        try {
            $commandDispatcher->executeCommand(
                'crontab:execute',
                [
                    $taskIdentifier,
                ]
            );
        } catch (FailedSubProcessCommandException $e) {
            $originalException = $e->getPrevious();
            if ($originalException) {
                throw $originalException;
            }
            throw $e;
        }
    }

    private function start(TaskDefinition $taskDefinition): Process
    {
        $processId = getmypid();
        $this->markAsRunning($taskDefinition->getIdentifier(), $processId);

        return $taskDefinition->createProcess($processId);
    }

    private function finish(string $taskIdentifier, int $processId): void
    {
        $this->databaseConnection->delete(
            self::runningTable,
            [
                'identifier' => $taskIdentifier,
                'process_id' => $processId,
            ]
        );
    }

    private function markAsRunning(string $taskIdentifier, int $processId): void
    {
        $this->databaseConnection->insert(
            self::runningTable,
            [
                'identifier' => $taskIdentifier,
                'process_id' => $processId,
            ]
        );
    }
}
