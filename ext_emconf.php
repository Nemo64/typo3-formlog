<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form log',
    'description' => 'Form log for TYPO3',
    'category' => 'misc',
    'author' => 'Mathias Brodala',
    'author_email' => 'mbrodala@pagemachine.de',
    'author_company' => 'Pagemachine AG',
    'state' => 'stable',
    'version' => '1.1.2',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.99.99',
            'typo3' => '8.7.0-9.3.99',
        ],
    ],
];
