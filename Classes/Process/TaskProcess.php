<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Process;

use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use Helhum\Typo3Console\Mvc\Cli\Symfony\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class TaskProcess extends Process
{
    /**
     * @var string
     */
    private const command = 'crontab:execute';

    /**
     * @var TaskDefinition
     */
    private $task;

    /**
     * Keep process id for usage after the process has been stopped
     * @var int
     */
    private $processId;

    /**
     * Don't allow object creation without factory method
     *
     * @param TaskDefinition $task
     * @param array $commandLine
     * @param array $environmentVars
     */
    private function __construct(TaskDefinition $task, array $commandLine, array $environmentVars = [])
    {
        $this->task = $task;
        parent::__construct($commandLine, null, $environmentVars, null, null);
        $this->inheritEnvironmentVariables();
    }

    public static function createFromTaskDefinition(TaskDefinition $task): self
    {
        if (!isset($_SERVER['argv'][0]) && strpos($_SERVER['argv'][0], Application::COMMAND_NAME) === false) {
            throw new RuntimeException('Tried to create typo3 command runner from wrong context', 1557940706);
        }
        $typo3CommandPath = $_SERVER['argv'][0];

        $phpFinder = new PhpExecutableFinder();
        if (!($php = $phpFinder->find(false))) {
            throw new RuntimeException('The "php" binary could not be found.', 1557940709);
        }
        $commandLine = [$task->getIdentifier()];
        array_unshift($commandLine, self::command);
        array_unshift($commandLine, $typo3CommandPath);
        $phpArguments = $phpFinder->findArguments();
        if (getenv('PHP_INI_PATH')) {
            $phpArguments[] = '-c';
            $phpArguments[] = getenv('PHP_INI_PATH');
        }
        // Ensure we do not output PHP startup errors for sub-processes to not have them interfere with process output
        // Later, very early in booting the error reporting is set to an appropriate value anyway
        $phpArguments[] = '-d';
        $phpArguments[] = 'error_reporting=0';
        array_unshift($commandLine, ...$phpArguments);
        array_unshift($commandLine, $php);

        return new self($task, $commandLine, ['TYPO3_CONSOLE_SUB_PROCESS' => true]);
    }

    public function start(callable $callback = null, array $env = []): void
    {
        parent::start($callback, $env);
        $this->processId = $this->getPid();
    }

    public function getFormerPid(): int
    {
        return $this->processId;
    }

    public function getTaskIdentifier(): string
    {
        return $this->task->getIdentifier();
    }

    public function allowsMultipleExecutions(): bool
    {
        return $this->task->allowsMultipleExecutions();
    }
}
