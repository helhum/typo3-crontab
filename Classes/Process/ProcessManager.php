<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Process;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const runningTable = 'tx_crontab_running';

    /**
     * @var int
     */
    private $forks;

    /**
     * @var TaskProcess[]|\SplObjectStorage
     */
    private $processes;

    /**
     * @var Connection
     */
    private $databaseConnection;

    public function __construct(int $forks, Connection $databaseConnection = null)
    {
        $this->forks = $forks;
        $this->processes = new \SplObjectStorage();
        $this->databaseConnection = $databaseConnection ?? GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::runningTable);
    }

    public function add(TaskProcess $subProcess): void
    {
        $this->wait();
        if ($this->hasRunningProcesses($subProcess->getTaskIdentifier()) && !$subProcess->allowsMultipleExecutions()) {
            $this->logger->debug(sprintf('A process for task "%s" is running and task is not configured for parallel execution, skipping.', $subProcess->getTaskIdentifier()));

            return;
        }

        $this->startProcess($subProcess);
        $this->processes->attach($subProcess);
    }

    public function wait(): void
    {
        $this->updateStatus();
        while ($this->processes->count() >= $this->forks) {
            usleep(1000);
            $this->updateStatus();
        }
    }

    private function updateStatus(): void
    {
        foreach ($this->processes as $process) {
            if (!$this->isProcessRunning($process)) {
                if (!$process->isSuccessful()) {
                    $this->logger->error(sprintf('Failed to successfully execute task "%s" process with pid "%d".', $process->getTaskIdentifier(), $process->getFormerPid()));
                } else {
                    $this->logger->info(sprintf('Task "%s" process with pid "%d" finished successfully.', $process->getTaskIdentifier(), $process->getFormerPid()));
                }
                $this->removeFromRunningTable($process->getTaskIdentifier(), $process->getFormerPid());
                $this->processes->detach($process);
            }
        }
    }

    private function isProcessRunning(TaskProcess $process): bool
    {
        if (!$process->isRunning()) {
            return false;
        }
        $pid = $process->getPid() ?? $process->getFormerPid();
        $trackedCount = $this->databaseConnection->count(
            'process_id',
            self::runningTable,
            [
                'identifier' => $process->getTaskIdentifier(),
                'process_id' => $pid,
            ]
        );
        if ($trackedCount === 0) {
            // Process is running, but as removed from our tracking table, which means termination was requested
            // so we're going to stop the task here.
            $process->stop();

            return false;
        }

        return true;
    }

    public function finish(): void
    {
        $this->forks = 1;
        $this->wait();
    }

    public function terminateAllProcesses(string $taskIdentifier): void
    {
        $result = $this->databaseConnection->select(
            ['process_id'],
            self::runningTable,
            ['identifier' => $taskIdentifier]
        );

        foreach ($result as $row) {
            $processId = (int)$row['process_id'];
            $this->logger->info(sprintf('Terminating task "%s" process with pid "%d".', $taskIdentifier, $processId));
            // We're only removing it from our process tracking table and let the parent process
            // deal with terminating the process (see self::isProcessRunning)
            $this->removeFromRunningTable($taskIdentifier, $processId);
        }
    }

    public function hasRunningProcesses(string $taskIdentifier): bool
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
                $this->removeFromRunningTable($taskIdentifier, $processId);
            }
        }

        return $isRunning;
    }

    private function startProcess(TaskProcess $subProcess): void
    {
        $subProcess->start();
        $this->logger->info(sprintf('Starting task "%s" process with pid "%d".', $subProcess->getTaskIdentifier(), $subProcess->getPid()));
        $this->addToRunningTable($subProcess->getTaskIdentifier(), $subProcess->getPid());
    }

    private function removeFromRunningTable(string $identifier, int $pid): void
    {
        $this->databaseConnection->delete(
            self::runningTable,
            [
                'identifier' => $identifier,
                'process_id' => $pid,
            ]
        );
    }

    private function addToRunningTable(string $identifier, int $pid): void
    {
        $this->databaseConnection->insert(
            self::runningTable,
            [
                'identifier' => $identifier,
                'process_id' => $pid,
            ]
        );
    }
}
