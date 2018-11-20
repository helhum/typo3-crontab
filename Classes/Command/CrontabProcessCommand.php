<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\ProcessManager;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CrontabProcessCommand extends Command
{
    public function configure(): void
    {
        $this->setDescription('Runs a Crontab process.')
            ->addArgument('taskIdentifier', InputArgument::REQUIRED, 'Identifier of task that should be run');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskIdentifier = $input->getArgument('taskIdentifier');

        $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $crontab = GeneralUtility::makeInstance(Crontab::class);
        $processManager = GeneralUtility::makeInstance(ProcessManager::class, $crontab);
        $taskDefinition = $taskRepository->findByIdentifier($taskIdentifier);

        return $processManager->run($taskDefinition, $this->getApplication(), $input, $output);
    }
}
