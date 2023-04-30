<?php

return [
    'web_module' => [
        'parent' => 'system',
        'position' => ['after' => 'backend_user_management'],
        'access' => 'admin',
        'workspaces' => '*',
        'path' => '/module/system/crontab',
        'iconIdentifier' => 'module-crontab',
        'labels' => 'LLL:EXT:crontab/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'crontab',
        'controllerActions' => [
            \Helhum\TYPO3\Crontab\Controller\CrontabModuleController::class => [
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
