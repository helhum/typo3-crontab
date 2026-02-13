<?php

return [
    'module-crontab' => [
        'parent' => 'system',
        'position' => [
            'after' => 'backend_user_management',
        ],
        'iconIdentifier' => 'module-crontab',
        'access' => 'admin',
        'labels' => 'LLL:EXT:crontab/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'Crontab',
        'controllerActions' => [
            'Helhum\TYPO3\Crontab\Controller\CrontabModuleController' => [
                'list',
                'toggleSchedule',
                'terminate',
                'edit',
                'delete',
                'scheduleForImmediateExecution',
            ],
        ],
    ],
];
