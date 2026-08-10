<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\Process\ProcessManager;
use Helhum\TYPO3\Crontab\Process\TaskProcess;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CrontabCommand extends Command
{
    public function __construct(
        private readonly Crontab $crontab,
        private readonly TaskRepository $taskRepository,
    ) {
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure(): void
    {
        $this
            ->setDescription('Start Crontab from the command line.')
            ->setHelp('Loops through all pending scheduled tasks and executes them')
            ->addOption(
                'timeout',
                '-t',
                InputOption::VALUE_REQUIRED,
                'Loops and runs due tasks until timeout (in seconds) is reached. Default is to look for due tasks and then quit.',
            )
            ->addOption(
                'forks',
                '-f',
                InputOption::VALUE_REQUIRED,
                'Number of due tasks allowed to be run in parallel',
            );
    }

    /**
     * Execute crontab tasks
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = GeneralUtility::makeInstance(LockFactory::class)->createLocker('crontab_process_manager', LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);

        try {
            $lock->acquire(LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);
            $output->writeln('<info>Executing scheduled tasks…</info>');
            $defaultWorkerTimeout = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['workerTimeout'] ?? 0;
            $defaultWorkerForks = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['workerForks'] ?? 1;
            $idleSleep = (int)($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['idleSleep'] ?? 5);
            $processManager = GeneralUtility::makeInstance(ProcessManager::class, (int)($input->getOption('forks') ?? $defaultWorkerForks));
            $this->crontab->prepareSchedulingFinishedTasks($processManager);

            $runUntil = time() + (int)($input->getOption('timeout') ?? $defaultWorkerTimeout);
            do {
                $tasksFound = false;
                foreach ($this->crontab->dueTasks() as $taskIdentifier) {
                    $processManager->add(
                        TaskProcess::createFromTaskDefinition($this->taskRepository->findByIdentifier($taskIdentifier))
                    );
                    $tasksFound = true;
                }

                // Monitor processes to finish and wait for free spot
                $processManager->wait();

                // If no tasks were found, sleep a bit longer to reduce CPU usage
                if (!$tasksFound) {
                    sleep($idleSleep);
                }
            } while (time() < $runUntil);
            $processManager->finish();
            $lock->release();
            $output->writeln('<info>done.</info>');
        } catch (LockAcquireWouldBlockException $e) {
            $output->writeln('<info>Skipped executing tasks because another crontab command is already running.</info>');
        }

        return 0;
    }
}
