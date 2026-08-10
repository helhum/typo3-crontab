<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Helhum\TYPO3\Crontab\Task\CommandExecutor;
use Helhum\TYPO3\Crontab\Task\SchedulerTaskExecutor;
use Helhum\TYPO3\Crontab\Task\ScriptExecutor;
use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrontabListCommand extends Command
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly Crontab $crontab,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('List all configured Crontab tasks');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $groupedTasks = $this->taskRepository->getGroupedTasks();

        if ($groupedTasks === []) {
            $output->writeln('<info>No tasks are configured.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<info>Hint: run <comment>crontab:show \<identifier></comment> for full task details.</info>');

        foreach ($groupedTasks as $groupName => $tasks) {
            $output->writeln('');
            $output->writeln(sprintf('<comment>%s</comment>', $groupName));

            $table = new Table($output);
            $table->setHeaders(['Identifier', 'Type', 'Title', 'Arguments', 'Scheduled', 'Next execution']);

            foreach ($tasks as $identifier => $taskDefinition) {
                $scheduled = $this->crontab->isScheduled($taskDefinition);
                $nextExecution = '-';
                if ($this->crontab->willRun($taskDefinition)) {
                    $nextExecution = $this->crontab->nextExecution($taskDefinition)->format('Y-m-d H:i:s');
                }
                $table->addRow([
                    $identifier,
                    $this->resolveType($taskDefinition),
                    $this->resolveTitle($taskDefinition),
                    $this->resolveArguments($taskDefinition),
                    $scheduled ? 'yes' : 'no',
                    $nextExecution,
                ]);
            }

            $table->render();
        }

        return Command::SUCCESS;
    }

    private function resolveType(TaskDefinition $taskDefinition): string
    {
        $executor = $taskDefinition->getProcessDefinition()->getExecutor();

        return match (true) {
            $executor instanceof CommandExecutor => 'Command',
            $executor instanceof SchedulerTaskExecutor => 'Scheduler',
            $executor instanceof ScriptExecutor => 'Script',
            default => get_class($executor),
        };
    }

    private function resolveTitle(TaskDefinition $taskDefinition): string
    {
        $executor = $taskDefinition->getProcessDefinition()->getExecutor();

        if ($executor instanceof CommandExecutor) {
            $additionalInformation = $executor->getAdditionalInformation() ?? '';
            $commandName = strtok($additionalInformation, ' ') ?: '';
            if ($commandName !== '') {
                try {
                    $description = $this->getApplication()?->find($commandName)->getDescription() ?? '';
                } catch (CommandNotFoundException) {
                    $description = '';
                }
                if ($description !== '') {
                    return mb_strimwidth($description, 0, 60, '…');
                }
            }
        }

        return $taskDefinition->getTitle();
    }

    private function resolveArguments(TaskDefinition $taskDefinition): string
    {
        $executor = $taskDefinition->getProcessDefinition()->getExecutor();

        if (!$executor instanceof CommandExecutor
            && !$executor instanceof ScriptExecutor
            && !$executor instanceof SchedulerTaskExecutor
        ) {
            return '';
        }

        $arguments = implode(', ', array_map(
            static fn($value): string => is_scalar($value) ? (string)$value : json_encode($value),
            array_values($executor->getArguments()),
        ));

        return mb_strimwidth($arguments, 0, 25, '…');
    }
}
