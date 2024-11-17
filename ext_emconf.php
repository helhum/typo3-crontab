<?php
$EM_CONF['crontab'] = [
    'title' => 'Crontab',
    'description' => 'Advanced scheduling for TYPO3 commands (and TYPO3 Scheduler tasks)',
    'category' => 'misc',
    'version' => '0.7.0',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Helmut Hummel',
    'author_email' => 'info@helhum.io',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => ''
        ],
    ],
];
