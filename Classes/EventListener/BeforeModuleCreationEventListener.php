<?php

declare(strict_types=1);

namespace Helhum\TYPO3\Crontab\EventListener;

use TYPO3\CMS\Backend\Module\BeforeModuleCreationEvent;

final class BeforeModuleCreationEventListener
{
    public function __invoke(BeforeModuleCreationEvent $event): void
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['hideSchedulerModule'])) {
            return;
        }
        if ($event->getIdentifier() !== 'scheduler') {
            return;
        }
        $appearance = $event->getConfigurationValue('appearance', []);
        $appearance['renderInModuleMenu'] = false;
        $event->setConfigurationValue('appearance', $appearance);
    }
}
