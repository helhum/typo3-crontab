<?php
(function () {
    // replaced by \Helhum\TYPO3\Crontab\EventListener\BeforeModuleCreationEventListener can be removed if TYPO3 11 is not supported anymore:
    if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['hideSchedulerModule'])) {
        if ($GLOBALS['TBE_MODULES']['system'] ?? null) {
            $GLOBALS['TBE_MODULES']['system'] = str_replace([',txschedulerM1', ',,'], ['', ','], $GLOBALS['TBE_MODULES']['system']);
        }
    }
})();
