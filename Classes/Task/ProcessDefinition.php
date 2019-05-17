<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Helhum\TYPO3\Crontab\Error\ConfigurationValidationFailed;

class ProcessDefinition
{
    /**
     * @var TaskExecutor
     */
    private $executor;

    public function __construct(array $config)
    {
        $this->validate($config);
        $this->executor = $this->createExecutor($config);
    }

    public function getExecutor(): TaskExecutor
    {
        return $this->executor;
    }

    public function getTitle(): ?string
    {
        return $this->executor->getTitle();
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->executor->getAdditionalInformation();
    }

    public function getProgress(): float
    {
        return $this->executor->getProgress();
    }

    private function createExecutor(array $config): TaskExecutor
    {
        if ($config['type'] === 'command') {
            return CommandExecutor::create($config);
        }
        if ($config['type'] === 'scheduler') {
            return SchedulerTaskExecutor::create($config);
        }

        throw new ConfigurationValidationFailed('Task type must be "command" or "scheduler"', 1558097793);
    }

    private function validate(array $config): void
    {
        if (empty($config['type'])) {
            throw new ConfigurationValidationFailed('Task type must not be empty', 1558097917);
        }
        if ($config['type'] !== 'command' && $config['type'] !== 'scheduler') {
            throw new ConfigurationValidationFailed('Task type must be "command" or "scheduler"', 1558097974);
        }
    }
}
