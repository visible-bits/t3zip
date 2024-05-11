<?php

declare(strict_types=1);

use Vibi\T3zip\ContextMenu\ItemProviders\ItemProvider;

defined('TYPO3') || die();

// not needed anymore in TYPO3 v12
// https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Backend/ContextualMenu.html
$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1703336651] =
    ItemProvider::class;
