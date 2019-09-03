<?php
(function () {
    if (empty($GLOBALS['TYPO3_CONF_VARS']['LOG']['Helhum']['TYPO3']['Crontab']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG])) {
        // If no logging is configured at all, make sure we log warnings to the default configured logfile
        // or to a dedicated log file in case there is no default log file configured
        if (empty($GLOBALS['TYPO3_CONF_VARS']['LOG']['Helhum']['TYPO3']['Crontab']['writerConfiguration'])) {
            $warningWriter = $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::WARNING] ?? [\TYPO3\CMS\Core\Log\Writer\FileWriter::class => ['logFile' => getenv('TYPO3_PATH_APP') . '/var/log/crontab-warning.log']];
            $GLOBALS['TYPO3_CONF_VARS']['LOG']['Helhum']['TYPO3']['Crontab']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::WARNING] = $warningWriter;
        }
        // Disable debug logging to avoid spamming log files, but only if no logging is configured for debug log
        // So if you really want to see debug messages, you need to add global configuration for it
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Helhum']['TYPO3']['Crontab']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG][\TYPO3\CMS\Core\Log\Writer\NullWriter::class] = [];
    }
})();
