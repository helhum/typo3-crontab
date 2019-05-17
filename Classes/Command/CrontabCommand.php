<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\Error\ThereIsAnotherOne;
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
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Start Crontab from the command line.')
            ->setHelp('Loops through all pending scheduled tasks and executes them')
            ->addOption(
                'timeout',
                '-t',
                InputOption::VALUE_REQUIRED,
                'Loops and runs due tasks until timeout (in seconds) is reached. Default is to look for due tasks and then quit.',
                0
            )
            ->addOption(
                'forks',
                '-f',
                InputOption::VALUE_REQUIRED,
                'Number of sub processes allowed to be run in parallel',
                1
            );
    }

    /**
     * Execute crontab tasks
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = GeneralUtility::makeInstance(LockFactory::class)->createLocker('crontab_process_manager', LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);
        try {
            $lock->acquire(LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);
            $crontab = GeneralUtility::makeInstance(Crontab::class);
            $processManager = GeneralUtility::makeInstance(ProcessManager::class, (int)$input->getOption('forks'));
            $output->writeln('<info>Executing scheduled tasks…</info>');
            $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
            foreach ($crontab->dueTasks((int)$input->getOption('timeout')) as $taskIdentifier) {
                $processManager->add(
                    TaskProcess::createFromTaskDefinition($taskRepository->findByIdentifier($taskIdentifier))
                );
            }
            $processManager->finish();
            $lock->release();
            $output->writeln('<info>done.</info>');
        } catch (LockAcquireWouldBlockException $e) {
            $output->writeln('<info>Skipped executing tasks because another crontab command is already running.</info>');
        }

        return 0;
    }
}
