<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Helhum\TYPO3\Crontab\Error\ConfigurationValidationFailed;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class SchedulerTaskExecutor implements TaskExecutor
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var AbstractTask
     */
    private $schedulerTask;

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->schedulerTask = $this->createTask($options);
    }

    public static function create(array $options): TaskExecutor
    {
        return new self($options);
    }

    public function run(Application $application, InputInterface $input = null, OutputInterface $output = null): bool
    {
        return $this->schedulerTask->execute();
    }

    public function getTitle(): ?string
    {
        return $this->schedulerTask->getTaskTitle();
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->schedulerTask->getAdditionalInformation();
    }

    public function getProgress(): float
    {
        if (!$this->schedulerTask instanceof ProgressProviderInterface) {
            return 0.0;
        }

        return $this->schedulerTask->getProgress();
    }

    private function createTask(array $options): AbstractTask
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
        $providerClass = $registeredTaskConfig['additionalFields'] ?? null;
        if ($providerClass !== null) {
            GeneralUtility::makeInstance($providerClass)->saveAdditionalFields($arguments, $task);
        }

        return $task;
    }
}
