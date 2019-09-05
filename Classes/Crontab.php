<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab;

use Helhum\TYPO3\Crontab\Event\ProcessFinished;
use Helhum\TYPO3\Crontab\Process\ProcessManager;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Crontab
{
    private const scheduledTable = 'tx_crontab_scheduled';
    /**
     * @var TaskRepository
     */
    private $taskRepository;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(TaskRepository $taskRepository = null, Connection $connection = null)
    {
        $this->taskRepository = $taskRepository ?? GeneralUtility::makeInstance(TaskRepository::class);
        $this->connection = $connection ?? GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::scheduledTable);
    }

    public function prepareSchedulingFinishedTasks(ProcessManager $processManager): void
    {
        $processManager->addListener(
            ProcessFinished::class,
            function (ProcessFinished $event) {
                $taskDefinition = $this->taskRepository->findByIdentifier($event->getTaskIdentifier());
                if ($this->isScheduled($taskDefinition)) {
                    $this->schedule($taskDefinition);
                }
            }
        );
    }

    public function schedule(TaskDefinition $definition): void
    {
        $this->addToSchedule($definition, $definition->getNextDueExecution(), false);
    }

    public function scheduleForImmediateExecution(TaskDefinition $definition): void
    {
        $this->addToSchedule($definition, new \DateTimeImmutable(), true);
    }

    private function addToSchedule(TaskDefinition $definition, \DateTimeInterface $executionTime, bool $singleRun): void
    {
        $identifier = $definition->getIdentifier();
        $fields = [
            'next_execution' => $executionTime->getTimestamp(),
        ];
        if ($singleRun === true && $this->isScheduled($definition)) {
            $singleRun = false;
        }
        if (!$this->willRun($definition)) {
            $fields['identifier'] = $identifier;
            $this->connection->insert(
                self::scheduledTable,
                $fields
            );
        }

        $fields['single_run'] = (int)$singleRun;
        $this->connection->update(
            self::scheduledTable,
            $fields,
            ['identifier' => $identifier]
        );
    }

    public function removeFromSchedule(TaskDefinition $definition): void
    {
        $this->removeFromScheduledTable($definition->getIdentifier());
    }

    public function isScheduled(TaskDefinition $definition): bool
    {
        $identifier = $definition->getIdentifier();

        return $this->connection->count(
            'identifier',
            self::scheduledTable,
            [
                'identifier' => $identifier,
                'single_run' => 0,
            ]
        ) > 0;
    }

    public function willRun(TaskDefinition $definition): bool
    {
        $identifier = $definition->getIdentifier();

        return $this->connection->count(
            'identifier',
            self::scheduledTable,
            ['identifier' => $identifier]
        ) > 0;
    }

    public function nextExecution(TaskDefinition $definition): \DateTimeImmutable
    {
        $timestamp = $this->connection->select(
            ['next_execution'],
            self::scheduledTable,
            ['identifier' => $definition->getIdentifier()]
        )->fetchColumn(0);
        $nextExecution = new \DateTime('@' . $timestamp);
        $nextExecution->setTimezone((new \DateTime())->getTimezone());

        return \DateTimeImmutable::createFromMutable($nextExecution);
    }

    public function dueTasks(): \Generator
    {
        $statement = $this->connection->select(
            ['identifier', 'next_execution', 'single_run'],
            self::scheduledTable,
            [],
            [],
            [
                'single_run' => 'DESC',
                'next_execution' => 'ASC',
            ]
        );
        while ($scheduleInformation = $statement->fetch()) {
            if ($scheduleInformation['next_execution'] > time()) {
                break;
            }
            if (!$this->taskRepository->hasTask($scheduleInformation['identifier'])) {
                $this->removeFromScheduledTable($scheduleInformation['identifier']);
                continue;
            }
            if ($scheduleInformation['single_run']) {
                $this->removeFromScheduledTable($scheduleInformation['identifier']);
            }
            yield $scheduleInformation['identifier'];
        }
    }

    private function removeFromScheduledTable(string $identifier): void
    {
        $this->connection->delete(self::scheduledTable, ['identifier' => $identifier]);
    }
}
