<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Event;

final class ProcessStarted extends Event
{
    private string $taskIdentifier;

    public function __construct(string $taskIdentifier)
    {
        $this->taskIdentifier = $taskIdentifier;
    }

    public function getTaskIdentifier(): string
    {
        return $this->taskIdentifier;
    }
}
