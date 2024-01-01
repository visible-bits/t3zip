<?php

declare(strict_types=1);

$_EXTKEY = 't3zip';
$EM_CONF[$_EXTKEY] = [
    'title' => 'Unzip and zip-files',
    'description' => 'Unzip and zip-files in filelist',
    'category' => 'be',
    'author' => 'Mike',
    'author_email' => 'admin@visiblebits.de',
    'state' => 'stable',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'classmap' => ['Classes'],
    ],
];
