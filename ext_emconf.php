<?php
$EM_CONF['crontab'] = [
    'title' => 'Crontab',
    'description' => 'Advanced scheduling for TYPO3 commands (and TYPO3 Scheduler tasks)',
    'category' => 'misc',
    'version' => '0.7.0',
    'state' => 'stable',
    'author' => 'Helmut Hummel',
    'author_email' => 'info@helhum.io',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'scheduler' => ''
        ],
    ],
];
