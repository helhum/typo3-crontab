<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use Helhum\Typo3Console\Mvc\Cli\FailedSubProcessCommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrontabCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Start Crontab from the command line.')
            ->setHelp('Loops through all pending scheduled commands and executes them');
    }

    /**
     * Execute crontab commands
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $crontab = new Crontab();
        $commandDispatcher = CommandDispatcher::createFromCommandRun();
        foreach ($crontab->dueTasks() as $taskIdentifier) {
            try {
                $commandDispatcher->executeCommand('crontab:execute', [$taskIdentifier]);
            } catch (FailedSubProcessCommandException $e) {
                // What shall we do here?
                throw $e;
            }
        }

        return 0;
    }
}
