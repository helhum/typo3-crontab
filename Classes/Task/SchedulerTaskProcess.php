<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class SchedulerTaskProcess implements Process
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var array
     */
    private $options;
    /**
     * @var int
     */
    private $processId;

    public function __construct(string $identifier, array $options, int $processId)
    {
        $this->identifier = $identifier;
        $this->options = $options;
        $this->processId = $processId;
    }

    public function getTaskIdentifier(): string
    {
        return $this->identifier;
    }

    public function getId(): int
    {
        return $this->processId;
    }

    public static function create(string $identifier, array $options, int $processId): Process
    {
        return new self($identifier, $options, $processId);
    }

    public function run(Application $application, InputInterface $input = null, OutputInterface $output = null): bool
    {
        return $this->createTask()->execute();
    }

    public function getProgress(): float
    {
        $task = $this->createTask();
        if (!$task instanceof ProgressProviderInterface) {
            return 0.0;
        }

        return $task->getProgress();
    }

    private function createTask(): AbstractTask
    {
        $className = $this->options['className'];
        $arguments = $this->options['arguments'] ?? [];
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
