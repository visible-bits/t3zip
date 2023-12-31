<?php

declare(strict_types=1);

use Vibi\T3zip\Controller\File\FileUnzipController;

return [
    'vibi_unpack_contextmenu_unzip' => [
        'path' => '/file/unzip',
        //'referrer' => 'required,refresh-empty',
        'target' => FileUnzipController::class . '::mainAction',
    ],
];
