<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

//namespace TYPO3\CMS\Impexp\ContextMenu;
namespace Vibi\T3zip\ContextMenu\ItemProviders;

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Context menu item provider adding export and import items
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class ItemProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @var File|Folder|null
     */
    protected $record;

    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'unzip' => [
            'type' => 'item',
            'label' => 'LLL:EXT:t3zip/Resources/Private/Language/locallang.xlf:tx_t3zip.be.context_menu_label_unzip',
            'iconIdentifier' => 'actions-t3zip-unzip',
            'callbackAction' => 'unzipFile',
        ],
    ];

    /**
     * check if the current table is handled by this provider
     */
    public function canHandle(): bool
    {
        return $this->table === 'sys_file';
    }

    /**
     * Initialize file object
     */
    protected function initialize(): void
    {
        parent::initialize();
        try {
            /** @var ResourceFactory $rf */
            $rf = GeneralUtility::makeInstance(ResourceFactory::class);
            $this->record = $rf->retrieveFileOrFolderObject($this->identifier);
        } catch (ResourceDoesNotExistException $resourceDoesNotExistException) {
            $this->record = null;
        }

        //$unzip_label = LocalizationUtility::translate(
        //    key: 'tx_t3zip.be.context_menu_label_unzip',
        //    extensionName: 't3zip',
        //    //arguments: [$count, $tablename]
        //);
        //$this->itemsConfiguration['unzip']['label'] = $unzip_label;
    }

    /**
     * This needs to be lower than priority of the RecordProvider
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 55;
    }

    /**
     * Registers the additional JavaScript RequireJS callback-module which will allow to display a notification
     * whenever the user tries to click on the "Unzip" item.
     * The method is called from AbstractProvider::prepareItems() for each context menu item.
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $label_success_head = LocalizationUtility::translate(
            key: 'tx_t3zip.be.context_menu_label_unzip_success_head',
            extensionName: 't3zip',
        );

        $label_success_sub = LocalizationUtility::translate(
            key: 'tx_t3zip.be.context_menu_label_unzip_success_sub',
            extensionName: 't3zip',
        );

        return [
            'data-callback-module' => 'TYPO3/CMS/T3zip/ContextMenuActions',
            'data-label_success_head' => $label_success_head,
            'data-label_success_sub' => $label_success_sub,
        ];
    }

    /**
     * This method adds custom item to list of items generated by item providers with higher priority value (PageProvider)
     * You could also modify existing items here.
     * The new item is added after the 'info' item.
     *
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        $this->initialize();

        $this->initDisabledItems();
        // renders an item based on the configuration from $this->itemsConfiguration
        $localItems = $this->prepareItems($this->itemsConfiguration);

        if (isset($items['info'])) {
            //finds a position of the item after which 'unzip' item should be added
            $position = array_search('info', array_keys($items), true);
            if ($position === false) {
                $position = 0;
            }

            //slices array into two parts
            $beginning = array_slice($items, 0, $position + 1, true);
            $end = array_slice($items, $position, null, true);

            // adds custom item in the correct position
            $items = $beginning + $localItems + $end;
        } else {
            $items += $localItems;
        }

        //passes array of items to the next item provider
        return $items;
    }

    /**
     * Checks whether certain item can be rendered (e.g. check for disabled items or permissions)
     *
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($type, ['divider', 'submenu'], true)) {
            return true;
        }

        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }

        $canRender = false;
        if ($itemName === 'unzip') {
            $canRender = $this->canBeUnpacked();
        }

        return $canRender;
    }

    /**
     * @return bool
     */
    protected function canBeUnpacked(): bool
    {
        if ($this->record === null) {
            return false;
        }

        $is_zip_file = false;
        $is_file = $this->isFile();
        $check_permissions = $this->record->checkActionPermission('read');
        if ($is_file) {
            $is_zip_file = $this->isZipFile();
        }

        return $is_file && $check_permissions && $is_zip_file;
    }

    /**
     * @return bool
     */
    protected function isFile(): bool
    {
        return $this->record instanceof File;
    }

    protected function isZipFile(): bool
    {
        // if $record is null or folder, it cannot be a zip file
        if ($this->record === null || !$this->record instanceof File) {
            return false;
        }

        $rec_ext = $this->record->getExtension();

        return $rec_ext == 'zip';
    }
}
