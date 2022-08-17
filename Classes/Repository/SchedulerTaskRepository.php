<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Repository;

use Helhum\TYPO3\Crontab\Task\TaskDefinition;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

class SchedulerTaskRepository
{
    private ConnectionPool $connectionPool;
    private LoggerInterface $logger;

    public function __construct(ConnectionPool $connectionPool, LoggerInterface $logger)
    {
        $this->connectionPool = $connectionPool;
        $this->logger = $logger;
    }

    /**
     * @return TaskDefinition[]
     */
    public function findAll(): array
    {
        $tasks = [];
        foreach ($this->fetchSchedulerTasksFromDatabase() as $row) {
            /** @var AbstractTask $task */
            $task = @unserialize($row['serialized_task_object']);
            if (!$task) {
                $this->logger->warning('Could not unserialize task', ['taskUid' => $row['uid']]);
                continue;
            }
            $this->logger->debug('Processing task', ['taskUid' => $row['uid'], 'class' => get_class($task)]);
            $taskIdentifier = "scheduler_{$task->getTaskUid()}";
            $taskConfig = [
                'group' => $row['taskGroupName'],
                'description' => $task->getDescription(),
                'multiple' => (bool)$task->areMultipleExecutionsAllowed(),
                'cron' => $task->getExecution()->getCronCmd() ?: '* * * * *',
                'process' => $task instanceof ExecuteSchedulableCommandTask ? $this->getProcessConfigurationForCommandTask($task) : $this->getProcessConfigurationForSchedulerTask($task),
            ];
            $this->logger->debug('Converted task config', ['taskIdentifier' => $taskIdentifier, 'taskConfig' => $taskConfig]);
            $tasks[] = TaskDefinition::createFromConfig($taskIdentifier, $taskConfig);
        }

        return $tasks;
    }

    private function getProcessConfigurationForSchedulerTask(AbstractTask $task): array
    {
        return [
            'type' => 'scheduler',
            'className' => get_class($task),
            'arguments' => $this->getProcessArgumentsForSchedulerTask($task),
        ];
    }

    private function getProcessArgumentsForSchedulerTask(AbstractTask $task): array
    {
        $taskClass = get_class($task);
        $providerClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$taskClass]['additionalFields'] ?? null;
        if ($providerClass === null) {
            return [];
        }
        /** @var AdditionalFieldProviderInterface $provider */
        $provider = GeneralUtility::makeInstance($providerClass);
        $taskInfo = [];
        $fakeController = $this->createFakeSchedulerController();
        $rawAdditionalFields = $provider->getAdditionalFields($taskInfo, $task, $fakeController);
        $url = '';
        foreach ($rawAdditionalFields as $additionalField) {
            $domCrawler = new Crawler($additionalField['code']);
            $inputs = $domCrawler->filter('input');
            foreach ($inputs as $domNode) {
                $name = $domNode->getAttribute('name');
                $value = rawurlencode($domNode->getAttribute('value'));
                $url .= "&$name=$value";
                $this->logger->debug('input', ['name' => $domNode->nodeName]);
            }
            $options = $domCrawler->filter('option[selected]');
            foreach ($options as $domNode) {
                $name = $domNode->parentNode->getAttribute('name');
                $value = rawurlencode($domNode->getAttribute('value'));
                $url .= "&$name=$value";
                $this->logger->debug('input', ['option' => $domNode->parentNode->nodeName]);
            }
            $textAreas = $domCrawler->filter('textarea');
            foreach ($textAreas as $domNode) {
                $name = $domNode->getAttribute('name');
                $value = rawurlencode($domNode->textContent);
                $url .= "&$name=$value";
                $this->logger->debug('input', ['textarea' => $domNode->nodeName]);
            }
        }
        parse_str($url, $schedulerArguments);
        $arguments = $schedulerArguments['tx_scheduler'] ?? [];
        $provider->validateAdditionalFields($arguments, $fakeController);

        return $arguments;
    }

    private function getProcessConfigurationForCommandTask(ExecuteSchedulableCommandTask $task): array
    {
        return [
            'type' => 'command',
            'command' => $task->getCommandIdentifier(),
            'arguments' => $this->getProcessArgumentsForCommandTask($task),
        ];
    }

    private function createFakeSchedulerController(): SchedulerModuleController
    {
        $classReflection = new \ReflectionClass(SchedulerModuleController::class);
        /** @var SchedulerModuleController $controller */
        $controller = $classReflection->newInstanceWithoutConstructor();
        $methodReflection = new \ReflectionMethod($controller, 'setCurrentAction');
        $methodReflection->setAccessible(true);
        $methodReflection->invoke($controller, new Action(Action::EDIT));

        return $controller;
    }

    private function getProcessArgumentsForCommandTask(ExecuteSchedulableCommandTask $task): array
    {
        $arguments = array_values($task->getArguments());
        foreach ($task->getOptions() as $name => $enabled) {
            if ($enabled) {
                continue;
            }
            $arguments[] = '--' . $name;
            $value = $this->optionValues[$name] ?? null;
            if ($value !== null) {
                $arguments[] = $value;
            }
        }

        return $arguments;
    }

    private function fetchSchedulerTasksFromDatabase(): \Generator
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $result = $queryBuilder->select(
            't.*',
            'g.groupName AS taskGroupName',
            'g.description AS taskGroupDescription',
            'g.uid AS taskGroupId',
            'g.deleted AS isTaskGroupDeleted',
        )
            ->from('tx_scheduler_task', 't')
            ->leftJoin(
                't',
                'tx_scheduler_task_group',
                'g',
                $queryBuilder->expr()->eq('t.task_group', $queryBuilder->quoteIdentifier('g.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('t.deleted', 0)
            )
            ->orderBy('g.sorting')
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            yield $row;
        }
    }
}
