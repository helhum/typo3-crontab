<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Cron\CronExpression;
use Helhum\TYPO3\Crontab\Error\ConfigurationValidationFailed;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
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
     * @var CronExpression
     */
    private $cronExpression;

    /**
     * @var ProcessDefinition
     */
    private $processDefinition;

    /**
     * @var AbstractTask|null
     */
    private $schedulerTask;

    public function __construct(
        string $identifier,
        ?string $title,
        ?string $additionalInformation,
        string $description,
        bool $allowMultipleExecutions,
        CronExpression $cronExpression,
        ProcessDefinition $processDefinition,
        ?AbstractTask $schedulerTask
    ) {
        $this->identifier = $identifier;
        $this->title = $title ?? ($schedulerTask === null ? $identifier : $schedulerTask->getTaskTitle());
        $this->additionalInformation = $additionalInformation ?? ($schedulerTask === null ? '' : $schedulerTask->getAdditionalInformation());;
        $this->description = $description;
        $this->allowMultipleExecutions = $allowMultipleExecutions;
        $this->cronExpression = $cronExpression;
        $this->processDefinition = $processDefinition;
        $this->schedulerTask = $schedulerTask;
    }

    public static function createFromConfig(string $identifier, array $config): self
    {
        $task = self::createTask($config['process'] ?? []);

        return new self(
            $identifier,
            $config['title'] ?? null,
            $config['additionalInformation'] ?? null,
            $config['description'] ?? '',
            $config['multiple'] ?? false,
            CronExpression::factory($config['cron'] ?? ''),
            new ProcessDefinition($identifier, $config['process'] ?? []),
            $task
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
        return (string)$this->cronExpression->getExpression();
    }

    public function getNextDueExecution(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->cronExpression->getNextRunDate());
    }

    public function createProcess(int $processId): Process
    {
        return $this->processDefinition->createProcess($processId);
    }

    public function getProgress(): float
    {
        if (!$this->schedulerTask instanceof ProgressProviderInterface) {
            return 0.0;
        }

        return $this->schedulerTask->getProgress();
    }

    private static function createTask(array $options): ?AbstractTask
    {
        if (empty($options['className'])) {
            return null;
        }
        $className = $options['className'];
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$className])) {
            throw new ConfigurationValidationFailed(sprintf('Class "%s" is not a registered scheduler task.', $className), 1552511749);
        }
        if (!\class_exists($className) || !\in_array(AbstractTask::class, class_parents($className), true)) {
            throw new ConfigurationValidationFailed(sprintf('Class "%s" does not inherit from scheduler AbstractTask', $className), 1552511788);
        }
        $registeredTaskConfig = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$className];
        $arguments = $options['arguments'] ?? [];
        /** @var AbstractTask $task */
        $task = GeneralUtility::makeInstance($className);
        $provider = $registeredTaskConfig['additionalFields'] ?? null;
        if ($provider !== null) {
            GeneralUtility::makeInstance($provider)->saveAdditionalFields($arguments, $task);
        }

        return $task;
    }
}
