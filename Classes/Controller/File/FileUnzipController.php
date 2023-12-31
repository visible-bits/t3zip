<?php

declare(strict_types=1);

namespace Vibi\T3zip\Controller\File;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Vibi\T3zip\Utils\Filesystem;

class FileUnzipController
{
    /**
     * The folder object which is the target directory for the upload
     *
     * @var FolderInterface|null
     */
    protected $folderObject;

    protected IconFactory $iconFactory;

    protected PageRenderer $pageRenderer;

    protected UriBuilder $uriBuilder;

    protected ResourceFactory $resourceFactory;

    protected ModuleTemplate $moduleTemplate;

    protected ModuleTemplateFactory $moduleTemplateFactory;

    protected string $target;

    protected string $returnUrl;

    protected string $content;

    private readonly ExtensionConfiguration $extConfiguration;

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        ResourceFactory $resourceFactory,
        ModuleTemplateFactory $moduleTemplateFactory,
        ExtensionConfiguration $extConfiguration
    ) {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->resourceFactory = $resourceFactory;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->extConfiguration = $extConfiguration;
    }

    /**
     * Adds a flash message to the default flash message queue
     *
     * @param string $message
     * @param string $title
     * @param int $severity
     */
    protected function addFlashMessage(string $message, string $title = '', int $severity = AbstractMessage::INFO): void
    {
        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);

        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Processes the request, currently everything is handled and put together via "renderContent()"
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $status =  $this->init($request);
        //$this->main();
        return new HtmlResponse($status);
    }

    /**
     * Initialize
     *
     * @param ServerRequestInterface $request
     * @throws InsufficientFolderAccessPermissionsException
     */
    public function init(ServerRequestInterface $request): string
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        /** @var Filesystem $my_filesystem */
        $my_filesystem = GeneralUtility::makeInstance(Filesystem::class);

        $fal_identifier = $queryParams['file'];
        $fal_object = $this->resourceFactory->getFileObjectFromCombinedIdentifier($fal_identifier);
        if ($fal_object === null) {
            return "";
        }

        $this->folderObject = $fal_object->getParentFolder();

        // absolute path from fal object
        $abs_path_to_zipfile = $fal_object->getForLocalProcessing(false);

        // fileinfo (dirname, basename, extension, filename)
        $file_info = pathinfo($abs_path_to_zipfile);

        // get relative  folder path from fal object
        $rel_path_folder = $fal_object->getParentFolder()->getIdentifier();

        // relative path (to storage) of folder containing the zip file
        // i.e. /user_upload/.../filder_with_zipfile/
        $rel_path_parent_folder = $fal_object->getParentFolder();

        $storage = $rel_path_parent_folder->getStorage();

        // absolute path folder to extract zip file into
        $abs_path_extract_folder = "";
        if (isset($file_info['dirname']) && isset($file_info['filename'])) {
            $abs_path_extract_folder = $file_info['dirname'] . '/' . $file_info['filename'];
        }

        // relative path folder to extract zip file into
        $rel_path_extract_folder = $rel_path_folder  . $file_info['filename'];

        // check if folder to extract zip file into already exists
        // if so, return error message to ContextMenuActions.js
        if (\is_dir($abs_path_extract_folder)) {
            $this->addFlashMessage(
                $this->getLanguageService()->sL(
                    'LLL:EXT:t3zip/Resources/Private/Language/locallang.xlf:tx_t3zip.be.flash_message_extract_folder_exists'
                ) . ' ' . $rel_path_extract_folder,
                '',
                AbstractMessage::ERROR
            );

            return "ERROR";
        }

        // create folder to extract zip file into
        $extract_folder = $storage->createFolder($rel_path_extract_folder);

        $abs_path_temp_folder = $my_filesystem->extractZipToTempAndReturnTempName($abs_path_to_zipfile);
        if ($abs_path_temp_folder && \is_string($abs_path_temp_folder)) {
            $files_in_temp_folder = $my_filesystem->getFilesRecursive($abs_path_temp_folder);
            $invalid_files_list = $this->checkFileDenyPatternAndRenameInvalid($files_in_temp_folder);

            $my_filesystem->recursiveCopyFolder($abs_path_temp_folder, $abs_path_extract_folder);
            $my_filesystem->deleteDirectory($abs_path_temp_folder);
        }

        // only add extracted files to index (sys_file) if enabled in the ext-settings
        $dont_add_extracted_files_to_index = (bool)$this->extConfiguration->get('t3zip', 'doNotAddExtractedToSysFile');
        if (!$dont_add_extracted_files_to_index) {
            /** @var Indexer $indexer */
            $indexer = GeneralUtility::makeInstance(Indexer::class, $storage);
            //$indexer->updateIndexEntry($extract_folder);
            $indexer->processChangesInStorages();
        }

        return "";
    }

    private function checkFileDenyPatternAndRenameInvalid(array $file_list): array
    {
        $renamed_files = [];
        /** @var FileNameValidator $file_name_validator */
        $file_name_validator =  GeneralUtility::makeInstance(FileNameValidator::class);
        foreach ($file_list as $file) {
            $file_info = pathinfo($file);
            $is_valid_filename = $file_name_validator->isValid($file_info['basename']);

            if (!$is_valid_filename) {
                rename($file, $file . "_invalid_file_ext");
                $renamed_files[] = $file_info['basename'];
            }
        }

        return $renamed_files;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
