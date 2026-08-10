<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\Error\TaskNotFound;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Helhum\TYPO3\Crontab\Task\CommandExecutor;
use Helhum\TYPO3\Crontab\Task\SchedulerTaskExecutor;
use Helhum\TYPO3\Crontab\Task\ScriptExecutor;
use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrontabShowCommand extends Command
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly Crontab $crontab,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setDescription('Show all details about a single Crontab task')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Task identifier');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $identifier = $input->getArgument('identifier');

        try {
            $taskDefinition = $this->taskRepository->findByIdentifier($identifier);
        } catch (TaskNotFound) {
            $output->writeln(sprintf('<error>Task "%s" not found.</error>', $identifier));

            return Command::FAILURE;
        }

        $executor = $taskDefinition->getProcessDefinition()->getExecutor();
        $scheduled = $this->crontab->isScheduled($taskDefinition);
        $willRun = $this->crontab->willRun($taskDefinition);
        $nextExecution = $willRun
            ? $this->crontab->nextExecution($taskDefinition)->format('Y-m-d H:i:s')
            : '-';

        $rows = [
            ['Identifier', $taskDefinition->getIdentifier()],
            ['Group', $this->resolveGroup($identifier)],
            ['Type', $this->resolveType($executor)],
            ['Title', $this->resolveTitle($taskDefinition)],
            ['Description', $taskDefinition->getDescription()],
            ['Additional information', $taskDefinition->getAdditionalInformation()],
            ['Cron expression', $taskDefinition->getCrontabExpression()],
            ['Next due execution', $taskDefinition->getNextDueExecution()->format('Y-m-d H:i:s')],
            ['Scheduled', $scheduled ? 'yes' : 'no'],
            ['Next execution', $nextExecution],
            ['Allows multiple executions', $taskDefinition->allowsMultipleExecutions() ? 'yes' : 'no'],
            ['Retry on failure', $taskDefinition->shouldRetryOnFailure() ? 'yes' : 'no'],
            ['Progress', sprintf('%.2f %%', $taskDefinition->getProgress())],
            ['Executor class', get_class($executor)],
        ];

        if ($executor instanceof CommandExecutor) {
            $commandName = strtok((string)$executor->getAdditionalInformation(), ' ') ?: '';
            $rows[] = ['Command', $commandName];
        }

        $arguments = $this->resolveArguments($executor);
        if ($arguments !== []) {
            $rows[] = new \Symfony\Component\Console\Helper\TableSeparator();
            foreach ($arguments as $key => $value) {
                $rows[] = [
                    is_int($key) ? 'Argument #' . $key : $key,
                    is_scalar($value) ? (string)$value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        $table = new Table($output);
        $table->setHeaders(['Property', 'Value']);
        $table->setColumnMaxWidth(1, 80);
        $table->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }

    private function resolveType(object $executor): string
    {
        return match (true) {
            $executor instanceof CommandExecutor => 'Command',
            $executor instanceof SchedulerTaskExecutor => 'Scheduler',
            $executor instanceof ScriptExecutor => 'Script',
            default => get_class($executor),
        };
    }

    private function resolveGroup(string $identifier): string
    {
        foreach ($this->taskRepository->getGroupedTasks() as $groupName => $tasks) {
            if (isset($tasks[$identifier])) {
                return (string)$groupName;
            }
        }

        return '';
    }

    private function resolveTitle(TaskDefinition $taskDefinition): string
    {
        $executor = $taskDefinition->getProcessDefinition()->getExecutor();

        if ($executor instanceof CommandExecutor) {
            $commandName = strtok((string)$executor->getAdditionalInformation(), ' ') ?: '';
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

    private function resolveArguments(object $executor): array
    {
        if ($executor instanceof CommandExecutor
            || $executor instanceof ScriptExecutor
            || $executor instanceof SchedulerTaskExecutor
        ) {
            return $executor->getArguments();
        }

        return [];
    }
}
