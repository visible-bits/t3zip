/**
 * Module: TYPO3/CMS/T3zip/ContextMenuActions
 *
 * JavaScript to handle the click action of the "unzip" context menu item
 * @exports TYPO3/CMS/T3zip/ContextMenuActions
 */
define(function () {
    'use strict';

    /**
     * @exports TYPO3/CMS/T3zip/ContextMenuActions
     */
    var ContextMenuActions = {};

    /**
     * Say hello
     *
     * @param {string} table
     * @param {int} uid of the page
     */
    ContextMenuActions.unpackFile = function (table, uid) {
        if (table === 'sys_file') {
            //If needed, you can access other 'data' attributes here from $(this).data('someKey')
            //see item provider getAdditionalAttributes method to see how to pass custom data attributes
            //top.TYPO3.Notification.error('T3zip', 'Unpacking!', 5);
            var label_success_head = $(this).data('label_success_head');
            var label_success_sub = $(this).data('label_success_sub');

            uid = encodeURIComponent(uid);
            console.log('Unpack file uid: ', uid);
            var url = TYPO3.settings.ajaxUrls['vibi_unpack_contextmenu_unzip'];
            //url += '&file=' + top.rawurlencode(uid);
            url += '&file=' + uid;

            $.ajax(url).always(function (data) {
                //top.nav_frame.location.reload(true);
                //top.list_frame.location.reload(true);
                console.log('Unpack file: ', data);

                top.TYPO3.Backend.NavigationContainer.refresh(true);
                top.TYPO3.Backend.ContentContainer.refresh(true);

                if (data !== 'ERROR') {
                    top.TYPO3.Notification.success(label_success_head, label_success_sub, 5);
                }

                //var urlReloadFileTree = TYPO3.settings.ajaxUrls['filestorage_tree_data'];
                //$.ajax({ url: urlReloadFileTree });
            });
        }
    };

    return ContextMenuActions;
});