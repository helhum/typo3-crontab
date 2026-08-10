<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Event;

final class ProcessFinished extends Event
{
    private string $taskIdentifier;

    private bool $success;

    public function __construct(string $taskIdentifier, bool $success)
    {
        $this->taskIdentifier = $taskIdentifier;
        $this->success = $success;
    }

    public function getTaskIdentifier(): string
    {
        return $this->taskIdentifier;
    }

    public function hasFinishedSuccessfully(): bool
    {
        return $this->success;
    }
}
