<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CommandExecutor implements TaskExecutor
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public static function create(array $options): TaskExecutor
    {
        return new self($options);
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

    public function getTitle(): ?string
    {
        return 'Execute console command';
    }

    public function getAdditionalInformation(): ?string
    {
        $additionalInformation = $this->options['command'];
        if (!empty($this->options['arguments'])) {
            $additionalInformation .= ' "' . implode('","', $this->options['arguments']) . '"';
        }

        return $additionalInformation;
    }

    public function getProgress(): float
    {
        return 0.0;
    }
}
