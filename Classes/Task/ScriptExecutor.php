<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ScriptExecutor implements TaskExecutor
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
        $command = str_replace('@php ', '', $this->options['script']);;
        $arguments = $this->options['arguments'] ?? [];
        $commandLine = $arguments;
        array_unshift($commandLine, $command);
        if (strpos($this->options['script'], '@php ') === 0) {
            array_unshift($commandLine, ...$this->getPhpArguments());
        }

        $process = new Process(
            $commandLine,
            getenv('TYPO3_PATH_COMPOSER_ROOT'),
            null,
            null,
            null
        );
        $process->mustRun();

        // If an error occurs an exception is raised
        return true;
    }

    private function getPhpArguments(): array
    {
        $phpFinder = new PhpExecutableFinder();
        if (!($php = $phpFinder->find(false))) {
            throw new \RuntimeException('The "php" binary could not be found.', 1567520378);
        }
        $phpArguments = $phpFinder->findArguments();
        if (getenv('PHP_INI_PATH')) {
            $phpArguments[] = '-c';
            $phpArguments[] = getenv('PHP_INI_PATH');
        }
        // Ensure we do not output PHP startup errors for sub-processes to not have them interfere with process output
        // Later, very early in booting the error reporting is set to an appropriate value anyway
        $phpArguments[] = '-d';
        $phpArguments[] = 'error_reporting=0';
        $phpArguments[] = '-d';
        $phpArguments[] = 'display_errors=0';
        array_unshift($phpArguments, $php);

        return $phpArguments;
    }

    public function getTitle(): ?string
    {
        return null;
    }

    public function getAdditionalInformation(): ?string
    {
        return null;
    }

    public function getProgress(): float
    {
        return 0.0;
    }
}
