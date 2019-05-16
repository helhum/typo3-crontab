<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

class ProcessDefinition
{
    /**
     * @var string
     */
    private $taskIdentifier;

    /**
     * @var array
     */
    private $processDefinitionConfig;

    public function __construct(string $taskIdentifier, array $processDefinitionConfig)
    {
        $this->taskIdentifier = $taskIdentifier;
        $this->processDefinitionConfig = $processDefinitionConfig;
    }

    public function createProcess(int $processId): Process
    {
        if ($this->processDefinitionConfig['type'] === 'command') {
            $process = CommandProcess::create($this->taskIdentifier, $this->processDefinitionConfig, $processId);
        } elseif ($this->processDefinitionConfig['type'] === 'scheduler') {
            $process = SchedulerTaskProcess::create($this->taskIdentifier, $this->processDefinitionConfig, $processId);
        }

        return $process;
    }

    private function validate(array $options): bool
    {
        // TODO
        return true;
    }
}
