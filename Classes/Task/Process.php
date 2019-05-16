<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Task;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Process
{
    public static function create(string $identifier, array $options, int $processId): self;

    public function getTaskIdentifier(): string;

    public function getId(): int;

    public function run(Application $application, InputInterface $input = null, OutputInterface $output = null): bool;
}
