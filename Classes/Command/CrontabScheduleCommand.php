<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CrontabScheduleCommand extends Command
{
    private const actionMethodMap = [
        'add' => 'addTasksAction',
        'remove' => 'removeTasksAction',
        'add-all' => 'addAllTasksAction',
        'remove-all' => 'removeAllTasksAction',
    ];

    /**
     * @var Crontab
     */
    private $crontab;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure(): void
    {
        $this
            ->setDescription('Adds/removes given tasks to/from schedule')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Allowed actions are: add, remove, add-all, remove-all'
            )
            ->addOption(
                'task',
                '-t',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Task identifier'
            )
            ->addOption(
                'group',
                '-g',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Group name'
            );
    }

    /**
     * Add/remove given tasks to/from schedule
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');
        if (!isset(self::actionMethodMap[$action])) {
            $output->writeln(sprintf('<error>Invalid action "%s" given.</error>', $action));

            return 1;
        }

        $this->crontab = GeneralUtility::makeInstance(Crontab::class);
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);

        return $this->{self::actionMethodMap[$action]}($input, $output);
    }

    private function addTasksAction(InputInterface $input, OutputInterface $output): int
    {
        if (empty($input->getOption('task')) && empty($input->getOption('group'))) {
            $output->writeln('<error>No task or group specified.</error>');

            return 1;
        }
        foreach ($input->getOption('task') as $taskIdentifier) {
            $this->addTask($this->taskRepository->findByIdentifier($taskIdentifier));
            $output->writeln(sprintf('<success>Added "%s" to schedule.</success>', $taskIdentifier));
        }

        $groupedTasks = $this->taskRepository->getGroupedTasks();
        foreach ($input->getOption('group') as $groupName) {
            if (empty($groupedTasks[$groupName])) {
                $output->writeln(sprintf('<error>No group named "%s" is configured.</error>', $groupName));

                return 1;
            }
            foreach ($groupedTasks[$groupName] as $taskDefinition) {
                $this->addTask($taskDefinition);
            }
            $output->writeln(sprintf('<success>Scheduled tasks in group "%s".</success>', $groupName));
        }

        return 0;
    }

    private function removeTasksAction(InputInterface $input, OutputInterface $output): int
    {
        if (empty($input->getOption('task')) && empty($input->getOption('group'))) {
            $output->writeln('<error>No task or group specified.</error>');

            return 1;
        }
        foreach ($input->getOption('task') as $taskIdentifier) {
            $this->removeTask($this->taskRepository->findByIdentifier($taskIdentifier));
            $output->writeln(sprintf('<success>Removed "%s" from schedule.</success>', $taskIdentifier));
        }

        $groupedTasks = $this->taskRepository->getGroupedTasks();
        foreach ($input->getOption('group') as $groupName) {
            if (empty($groupedTasks[$groupName])) {
                $output->writeln(sprintf('<error>No group named "%s" is configured.</error>', $groupName));

                return 1;
            }
            foreach ($groupedTasks[$groupName] as $taskDefinition) {
                $this->removeTask($taskDefinition);
            }
            $output->writeln(sprintf('<success>Removed tasks in group "%s" from schedule.</success>', $groupName));
        }

        return 0;
    }

    private function addAllTasksAction(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->taskRepository->findAll() as $taskDefinition) {
            $this->addTask($taskDefinition);
        }
        $output->writeln('<success>Scheduled all configured tasks.</success>');

        return 0;
    }

    private function removeAllTasksAction(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->taskRepository->findAll() as $taskDefinition) {
            $this->removeTask($taskDefinition);
        }
        $output->writeln('<success>Removed all tasks from schedule</success>');

        return 0;
    }

    private function addTask(TaskDefinition $taskDefinition): void
    {
        if (!$this->crontab->isScheduled($taskDefinition)) {
            $this->crontab->schedule($taskDefinition);
        }
    }

    private function removeTask(TaskDefinition $taskDefinition): void
    {
        if ($this->crontab->isScheduled($taskDefinition)) {
            $this->crontab->removeFromSchedule($taskDefinition);
        }
    }
}
