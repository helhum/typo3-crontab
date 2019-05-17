<?php
(function () {
    if (empty($GLOBALS['TYPO3_CONF_VARS']['LOG']['Helhum']['TYPO3']['Crontab']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG])) {
        // Disable debug logging to avoid spamming log files, but only if no logging is configured for debug log
        // So if you really want to see debug messages, you need to add global configuration for it
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Helhum']['TYPO3']['Crontab']['writerConfiguration'][\TYPO3\CMS\Core\Log\LogLevel::DEBUG][\TYPO3\CMS\Core\Log\Writer\NullWriter::class] = [];
    }
})();
