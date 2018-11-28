<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use TYPO3\CMS\Scheduler\CronCommand\CronCommand;

class CronSchedule
{
    /**
     * @var string
     */
    private $crontabExpression;

    public function __construct(string $crontabExpression)
    {
        $this->crontabExpression = $crontabExpression;
    }

    public function __toString(): string
    {
        return $this->crontabExpression;
    }

    public function getNextDueExecution(): \DateTimeImmutable
    {
        $calculator = new CronCommand($this->crontabExpression);
        $calculator->calculateNextValue();
        $nextExecution = new \DateTime('@' . $calculator->getTimestamp());
        $nextExecution->setTimezone((new \DateTime())->getTimezone());

        return \DateTimeImmutable::createFromMutable($nextExecution);
    }
}
