<?php
(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Crontab',
        'system',
        'Module',
        'after:BeuserTxBeuser',
        [
            \Helhum\TYPO3\Crontab\Controller\CrontabModuleController::class => 'list, toggleSchedule, terminate, edit, delete, scheduleForImmediateExecution',
        ],
        [
            'access' => 'admin',
            'icon' => 'EXT:crontab/Resources/Public/Icons/module-crontab.svg',
            'labels' => 'LLL:EXT:crontab/Resources/Private/Language/locallang_mod.xlf'
        ]
    );
    if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['hideSchedulerModule'])) {
        $GLOBALS['TBE_MODULES']['system'] = str_replace([',txschedulerM1', ',,'], ['',','], $GLOBALS['TBE_MODULES']['system']);
    }
})();
