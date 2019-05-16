<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Helhum\Typo3Console\Mvc\Cli\Symfony\Input\ArgvInput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CommandProcess implements Process
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var array
     */
    private $options;
    /**
     * @var int
     */
    private $processId;

    public function __construct(string $identifier, array $options, int $processId)
    {
        $this->identifier = $identifier;
        $this->options = $options;
        $this->processId = $processId;
    }

    public function getTaskIdentifier(): string
    {
        return $this->identifier;
    }

    public function getId(): int
    {
        return $this->processId;
    }

    public static function create(string $identifier, array $options, int $processId): Process
    {
        return new self($identifier, $options, $processId);
    }

    public function run(Application $application, InputInterface $input = null, OutputInterface $output = null): bool
    {
        $command = $this->options['command'];
        $arguments = $this->options['arguments'] ?? [];
        $argv = $arguments;
        array_unshift($argv, $command);
        array_unshift($argv, '_');
        $output = $output ?? new NullOutput();

        return $application->run(new ArgvInput($argv), $output) === 0;
    }
}
