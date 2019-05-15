<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Repository;

use Helhum\TYPO3\Crontab\Error\TaskNotFound;
use Helhum\TYPO3\Crontab\Task\TaskDefinition;

class TaskRepository
{
    /**
     * @var array
     */
    private $taskConfiguration;

    public function __construct(array $taskConfiguration = null)
    {
        $this->taskConfiguration = $taskConfiguration ?? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['crontab'] ?? [];
    }

    /**
     * @return TaskDefinition[]
     */
    public function findAll(): array
    {
        return array_map(
            function (string $identifier) {
                return TaskDefinition::createFromConfig($identifier, $this->taskConfiguration[$identifier]);
            },
            array_keys($this->taskConfiguration)
        );
    }

    public function getGroupedTasks(): array
    {
        $groupedTasks = [];
        foreach ($this->taskConfiguration as $identifier => $taskConfig) {
            $groupName = $taskConfig['group'] ?? 'N/A';
            $groupedTasks[$groupName][$identifier] = TaskDefinition::createFromConfig($identifier, $taskConfig);
        }

        return $groupedTasks;
    }

    public function hasTask(string $identifier): bool
    {
        return isset($this->taskConfiguration[$identifier]);
    }

    public function findByIdentifier(string $identifier): TaskDefinition
    {
        if (!isset($this->taskConfiguration[$identifier])) {
            throw new TaskNotFound(sprintf('Task with identifier "%s" is not defined', $identifier), 1542737003);
        }

        return TaskDefinition::createFromConfig($identifier, $this->taskConfiguration[$identifier]);
    }
}
