<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Repository\SchedulerTaskRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrontabImportSchedulerTasksCommand extends Command
{
    private SchedulerTaskRepository $schedulerTaskRepository;

    public function __construct(SchedulerTaskRepository $schedulerTaskRepository)
    {
        parent::__construct();
        $this->schedulerTaskRepository = $schedulerTaskRepository;
    }

    public function configure(): void
    {
        $this->setDescription('Import Scheduler Tasks.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->schedulerTaskRepository->findAll();

        return self::SUCCESS;
    }
}
