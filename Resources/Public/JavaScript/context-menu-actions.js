/**
 * Module: @TYPO3/CMS/T3zip/ContextMenuActions
 *
 * JavaScript to handle the click action of the "unzipFile" context menu item
 */
class ContextMenuActions {

    unzipFile(table, uid) {

        if (table === 'sys_file') {
            //If needed, you can access other 'data' attributes here from $(this).data('someKey')
            //see item provider getAdditionalAttributes method to see how to pass custom data attributes
            //top.TYPO3.Notification.error('T3zip', 'Unpacking!', 5);
            var label_success_head = $(this).data('label_success_head');
            var label_success_sub = $(this).data('label_success_sub');

            uid = encodeURIComponent(uid);
            var url = TYPO3.settings.ajaxUrls['vibi_unpack_contextmenu_unzip'];
            //url += '&file=' + top.rawurlencode(uid);
            url += '&file=' + uid;

            $.ajax(url).always(function (data) {
                //top.nav_frame.location.reload(true);
                //top.list_frame.location.reload(true);
                //console.log('Unpack data: ', data);
                //console.log('Unpack uid: ', uid);

                top.TYPO3.Backend.NavigationContainer.refresh(true);
                top.TYPO3.Backend.ContentContainer.refresh(true);

                if (data !== 'ERROR') {
                    top.TYPO3.Notification.success(label_success_head, label_success_sub, 5);
                }

                //var urlReloadFileTree = TYPO3.settings.ajaxUrls['filestorage_tree_data'];
                //$.ajax({ url: urlReloadFileTree });
            });
        }
    }
}
export default new ContextMenuActions();
