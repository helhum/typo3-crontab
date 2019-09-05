<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Cron\CronExpression;

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
     * @var bool
     */
    private $retryOnFailure;

    private function __construct(
        string $identifier,
        ?string $title,
        ?string $additionalInformation,
        string $description,
        bool $allowMultipleExecutions,
        bool $retryOnFailure,
        CronExpression $cronExpression,
        ProcessDefinition $processDefinition
    ) {
        $this->identifier = $identifier;
        $this->title = $title ?? $processDefinition->getTitle() ?? $identifier;
        $this->additionalInformation = $additionalInformation ?? $processDefinition->getAdditionalInformation() ?? '';
        $this->description = $description;
        $this->allowMultipleExecutions = $allowMultipleExecutions;
        $this->retryOnFailure = $retryOnFailure;
        $this->cronExpression = $cronExpression;
        $this->processDefinition = $processDefinition;
    }

    public static function createFromConfig(string $identifier, array $config): self
    {
        return new self(
            $identifier,
            $config['title'] ?? null,
            $config['additionalInformation'] ?? null,
            $config['description'] ?? '',
            $config['multiple'] ?? false,
            $config['retryOnFailure'] ?? false,
            CronExpression::factory($config['cron'] ?? ''),
            new ProcessDefinition($config['process'] ?? [])
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

    public function shouldRetryOnFailure(): bool
    {
        return $this->retryOnFailure;
    }

    public function getCrontabExpression(): string
    {
        return (string)$this->cronExpression->getExpression();
    }

    public function getNextDueExecution(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->cronExpression->getNextRunDate());
    }

    public function getProcessDefinition(): ProcessDefinition
    {
        return $this->processDefinition;
    }

    public function getProgress(): float
    {
        return $this->processDefinition->getProgress();
    }
}
