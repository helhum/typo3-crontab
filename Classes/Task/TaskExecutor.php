<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface TaskExecutor
{
    public static function create(array $options): self;

    public function run(Application $application, InputInterface $input = null, OutputInterface $output = null): bool;

    public function getTitle(): ?string;

    public function getAdditionalInformation(): ?string;

    public function getProgress(): float;
}
