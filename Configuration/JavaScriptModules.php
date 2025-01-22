<?php

declare(strict_types=1);

return [
    'dependencies' => ['core', 'backend'],
    'tags' => [
        'backend.contextmenu',
    ],
    'imports' => [
        '@vibi/t3zip/' => 'EXT:t3zip/Resources/Public/JavaScript/',
    ],
];
