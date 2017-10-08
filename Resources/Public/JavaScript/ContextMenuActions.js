/**
 * Module: TYPO3/CMS/TemplaVoilaPlus/ContextMenuActions
 *
 * JavaScript to handle TemplaVoilà! Plus actions from context menu
 * @exports TYPO3/CMS/TemplaVoilaPlus/ContextMenuActions
 */
define(function () {
    'use strict';

    /**
     * @exports TYPO3/CMS/TemplaVoilaPlus/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.getReturnUrl = function () {
       return top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
    };

    ContextMenuActions.mappingDb = function (table, uid) {
        top.TYPO3.Backend.ContentContainer.setUrl(
            top.TYPO3.settings.TemplaVoilaPlus.mappingModuleUrl +
            '&table=' + table +
            '&uid=' + uid +
            '&_reload_from=1' +
            '&returnUrl=' + ContextMenuActions.getReturnUrl()
        );
    }

    ContextMenuActions.viewDsTo = function (table, uid) {
        top.TYPO3.Backend.ContentContainer.setUrl(
            top.TYPO3.settings.TemplaVoilaPlus.mappingModuleUrl +
            '&table=' + table +
            '&uid=' + uid +
            '&returnUrl=' + ContextMenuActions.getReturnUrl()
        );
    }


    ContextMenuActions.viewSubelements = function (table, uid) {
        top.TYPO3.Backend.ContentContainer.setUrl(
            top.TYPO3.settings.TemplaVoilaPlus.layoutModuleUrl +
            '&id=' + $(this).data('page-uid') +
            '&altRoot[table]=' + table +
            '&altRoot[uid]=' + uid +
            '&altRoot[field_flex]=tx_templavoilaplus_flex' +
            '&returnUrl=' + ContextMenuActions.getReturnUrl()
        );
    }

    ContextMenuActions.viewFlexformXml = function (table, uid) {
        top.TYPO3.Backend.ContentContainer.setUrl(
            top.TYPO3.settings.TemplaVoilaPlus.flexformCleanerModuleUrl +
            '&id=' + $(this).data('page-uid') +
            '&viewRec[table]=' + table +
            '&viewRec[uid]=' + uid +
            '&viewRec[field_flex]=tx_templavoilaplus_flex' +
            '&returnUrl=' + ContextMenuActions.getReturnUrl()
        );
    }

    return ContextMenuActions;
});
