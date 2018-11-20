<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class TaskDefinition
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $additionalInformation;
    /**
     * @var string
     */
    private $description;
    /**
     * @var bool
     */
    private $allowMultipleExecutions;
    /**
     * @var CronSchedule
     */
    private $cronSchedule;
    /**
     * @var array
     */
    private $processDefinition;

    public function __construct(
        string $identifier,
        string $title,
        string $additionalInformation,
        string $description,
        bool $allowMultipleExecutions,
        CronSchedule $cronSchedule,
        ProcessDefinition $processDefinition
    ) {
        $this->identifier = $identifier;
        $this->title = $title;
        $this->additionalInformation = $additionalInformation;
        $this->description = $description;
        $this->allowMultipleExecutions = $allowMultipleExecutions;
        $this->cronSchedule = $cronSchedule;
        $this->processDefinition = $processDefinition;
    }

    public static function createFromConfig(string $identifier, array $config): self
    {
        $task = self::createTask($config['process'] ?? []);

        return new self(
            $identifier,
            $config['title'] ?? ($task !== null ? $task->getTaskTitle() : $identifier),
            $config['additionalInformation'] ?? ($task !== null ? $task->getAdditionalInformation() : ''),
            $config['description'] ?? '',
            $config['multiple'] ?? false,
            new CronSchedule($config['cron'] ?? ''),
            new ProcessDefinition($identifier, $config['process'] ?? [])
        );
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAdditionalInformation(): string
    {
        return $this->additionalInformation;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function allowsMultipleExecutions(): bool
    {
        return $this->allowMultipleExecutions;
    }

    public function getCrontabExpression(): string
    {
        return (string)$this->cronSchedule;
    }

    public function getNextDueExecution(): \DateTimeImmutable
    {
        return $this->cronSchedule->getNextDueExecution();
    }

    public function createProcess(int $processId): Process
    {
        return $this->processDefinition->createProcess($processId);
    }

    public function getProgress(): float
    {
        return $this->processDefinition->createProcess(0)->getProgress();
    }

    private static function createTask(array $options): ?AbstractTask
    {
        $className = $options['className'] ?? null;
        if ($className === null || !\in_array(AbstractTask::class, class_parents($className), true)) {
            return null;
        }
        $arguments = $options['arguments'] ?? [];
        /** @var AbstractTask $task */
        $task = GeneralUtility::makeInstance($className);
        $registeredTaskConfig = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][get_class($task)] ?? [];
        $provider = $registeredTaskConfig['additionalFields'] ?? null;
        if ($provider !== null) {
            GeneralUtility::makeInstance($provider)->saveAdditionalFields($arguments, $task);
        }

        return $task;
    }
}
