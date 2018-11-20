<?php
(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Helhum.TYPO3.Crontab',
        'system',
        'tx_Crontab',
        '',
        [
            'CrontabModule' => 'list, toggleSchedule, execute, terminate, edit, delete, schedule',
        ],
        [
            'access' => 'admin',
            'icon' => 'EXT:crontab/Resources/Public/Icons/module-crontab.svg',
            'labels' => 'LLL:EXT:crontab/Resources/Private/Language/locallang_mod.xlf'
        ]
    );
})();
