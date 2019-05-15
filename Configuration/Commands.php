<?php
declare(strict_types=1);
/**
 * Commands to be executed by typo3, where the key of the array
 * is the name of the command (to be called as the first argument after typo3).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 */
return [
    'crontab:run' => [
        'class' => \Helhum\TYPO3\Crontab\Command\CrontabCommand::class,
    ],
    'crontab:execute' => [
        'class' => \Helhum\TYPO3\Crontab\Command\CrontabProcessCommand::class,
    ],
    'crontab:schedule' => [
        'class' => \Helhum\TYPO3\Crontab\Command\CrontabScheduleCommand::class,
    ],
];
