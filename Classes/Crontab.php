<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab;

use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Crontab
{
    private const scheduledTable = 'tx_crontab_scheduled';

    public function schedule(TaskDefinition $definition, \DateTimeInterface $executionTime = null): void
    {
        $identifier = $definition->getIdentifier();
        $executionTime = $executionTime ?? $definition->getNextDueExecution();
        $fields = [
            'next_execution' => $executionTime->getTimestamp(),
        ];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::scheduledTable);
        if (!$this->isScheduled($definition)) {
            $fields['identifier'] = $identifier;
            $connection->insert(
                self::scheduledTable,
                $fields
            );
        }

        $connection->update(
            self::scheduledTable,
            $fields,
            ['identifier' => $identifier]
        );
    }

    public function removeFromSchedule(TaskDefinition $definition): void
    {
        $identifier = $definition->getIdentifier();
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::scheduledTable)
            ->delete(
                self::scheduledTable,
                ['identifier' => $identifier]
            );
    }

    public function isScheduled(TaskDefinition $definition): bool
    {
        $identifier = $definition->getIdentifier();
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::scheduledTable);

        return $connection->count(
            'identifier',
            self::scheduledTable,
            ['identifier' => $identifier]
        ) > 0;
    }

    public function nextExecution(TaskDefinition $definition): \DateTimeImmutable
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::scheduledTable);
        $timestamp = $connection->select(
            ['next_execution'],
            self::scheduledTable,
            ['identifier' => $definition->getIdentifier()]
        )->fetchColumn(0);
        $nextExecution = new \DateTime('@' . $timestamp);
        $nextExecution->setTimezone((new \DateTime())->getTimezone());

        return \DateTimeImmutable::createFromMutable($nextExecution);
    }

    public function dueTasks(): \Generator
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::scheduledTable);
        $statement = $connection->select(
            ['identifier', 'next_execution'],
            self::scheduledTable,
            [],
            [],
            ['next_execution' => 'ASC']
        );
        while ($scheduleInformation = $statement->fetch()) {
            if ($scheduleInformation['next_execution'] > time()) {
                break;
            }
            yield $scheduleInformation['identifier'];
        }
    }
}
