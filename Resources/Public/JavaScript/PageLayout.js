define([
    'bootstrap',
    'jquery',
    'TYPO3/CMS/Templavoilaplus/Tooltipster',
    'TYPO3/CMS/Templavoilaplus/Sortable.min',
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Severity'
], function(bootstrap, $, Tooltipster, Sortable, Modal, Severity) {
    'use strict';

    /**
     * @exports Tvp/TemplaVoilaPlus/PageLayout
     */
    var PageLayout = {
    }

    PageLayout.myModal = null;

    /**
     * Initialize
     */
    PageLayout.initialize = function() {

        // Check for Dark Mode
        var settings = $('#moduleWrapper').data('tvpSettings');
        if (settings.userSettings.enableDarkMode) {
            $('body').addClass('dark-mode-on');
        }
        // Enable Sidebar Elements
        if ($('#moduleWrapper').data('tvpPageEditRights')
            && $('#moduleWrapper').data('tvpPageDokType') === 1
        ) {
            $('#navbarContentElementWizard').removeClass('disabled');
        }

        window.addEventListener('message', function(event) {
            if (PageLayout.myModal) {
                PageLayout.myModal.find('.t3js-modal-iframe').get(0).contentWindow.postMessage(event.data, event.source);
            }
        });

        // Add tooltip functionality to Sidebar
        $('#navbarContentElementWizard:not(.disabled)').tooltipster({
            updateAnimation: false,
            side: 'left',
            interactive: true,
            trackTooltip: true,
            trigger: 'click',
            content: 'Loading...',
            contentAsHTML: true,
            functionBefore: function(instance, helper) {
                $('#moduleShadowing').removeClass('hidden');
                if (!$('#moduleWrapper').data('loadedContentElementWizard')) {
                    $('#moduleWrapper').data('loadedContentElementWizard', true);
                    $.ajax({
                        type: 'POST',
                        data: {
                            id: $('#moduleWrapper').data('tvpPageId')
                        },
                        url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElementWizard'],
                        success: function(data) {
                            // Add data to content
                            instance.content(data);
                            PageLayout.initWizardDrag(instance);
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                            instance.content('Request failed because of error: ' + textStatus);
                            $('#moduleWrapper').data('loadedContentElementWizard', false);
                        }
                    });
                }
            },
            functionReady: function(instance, helper) {
                 PageLayout.initWizardDrag(instance);
            },
            functionAfter: function(instance, helper) {
                $('#moduleShadowing').addClass('hidden');
            },
        });
        $('#navbarClipboard:not(.disabled)').tooltipster({
            updateAnimation: false,
            side: 'left',
            interactive: true,
            trackTooltip: true,
            trigger: 'click',
            content: 'Loading...',
            contentAsHTML: true,
            functionBefore: function(instance, helper) {
                $('#moduleShadowing').removeClass('hidden');
                $.ajax({
                    type: 'POST',
                    data: {
                        id: $('#moduleWrapper').data('tvpPageId')
                    },
                    url: TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_load'],
                    success: function(data) {
                        // Add data to content
                        instance.content(data);
                        PageLayout.initWizardDrag(instance);
                        PageLayout.initClipboardModeListener(instance);
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        instance.content('Request failed because of error: ' + textStatus);
                        $('#moduleWrapper').data('loadedContentElementWizard', false);
                    }
                });
            },
            functionReady: function(instance, helper) {
                 PageLayout.initWizardDrag(instance);
            },
            functionAfter: function(instance, helper) {
                $('#moduleShadowing').addClass('hidden');
            },
        });
        $('#navbarConfig').tooltipster({
            side: 'left',
            interactive: true,
            trigger: 'click',
            functionBefore: function(instance, helper) {
                $('#moduleShadowing').removeClass('hidden');
            },
            functionAfter: function(instance, helper) {
                $('#moduleShadowing').addClass('hidden');
            }

        });
        $('#dark-mode-switch').change(function() {
            if (this.checked) {
                $('body').addClass('animationTransition');
                setTimeout(() => {
                    $('body').addClass('dark-mode-on');
                }, 150);
                $.get(TYPO3.settings.ajaxUrls['templavoilaplus_usersettings_enableDarkMode'], {enable: 1});
                setTimeout(() => {
                    $('body').removeClass('animationTransition');
                }, 500);
            } else {
                $('body').addClass('animationTransition');
                setTimeout(() => {
                    $('body').removeClass('dark-mode-on');
                }, 150);
                $.get(TYPO3.settings.ajaxUrls['templavoilaplus_usersettings_enableDarkMode'], {enable: 0});
                setTimeout(() => {
                    $('body').removeClass('animationTransition');
                }, 500);
            }
        });

        // Initialize drag&drop
        var allDropzones = [].slice.call(document.querySelectorAll('.tvjs-dropzone'))

        for (var i = 0; i < allDropzones.length; i++) {
            new Sortable(allDropzones[i], {
                revertOnSpill: true,
                group: {
                    name: 'dropzones_' + allDropzones[i].dataset.childAllowed,
                    pull: function (to, from, el, evt) {
                        if (to.el.id === 'navbarClipboard') {
                            return 'clone';
                        }
                        if (to.el.id === 'navbarTrash') {
                            return true;
                        }
                        return true;
                    },
                    put: function (to, from, el, evt) {
//                         console.log(el);
//                         $(to.el).addClass('green');
                    },
                    revertClone: true
                },
                ghostClass: "iAmGhost",
                dragable: '.sortableItem',
                animation: 150,
                swapThreshold: 0.65,
                emptyInsertThreshold: 30,
                onUpdate: function (/**Event*/evt) {
console.log('onUpdate');
                    // Move inside field
                    PageLayout.showInProgress(evt.item);
                    $.ajax({
                      type: 'POST',
                      data: {
                          sourcePointer: evt.from.dataset.parentPointer + ':' + evt.oldDraggableIndex.toString(),
                          destinationPointer: evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString()
                      },
                      url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_move'],
                      success: function(data) {
                          // @TODO Elements need to update their parenPointer after move
                          PageLayout.showSuccess(evt.item);
                      },
                      error: function(XMLHttpRequest, textStatus, errorThrown) {
                          PageLayout.showError(evt.item);
                          return false;
                      }
                    });
                },
                onSort: function (/**Event*/evt) {
console.log('onSort');
                },
                onRemove: function (/**Event*/evt) {
console.log('onRemove');
                },
                onFilter: function (/**Event*/evt) {
console.log('onFilter');
                },
                onClone: function (/**Event*/evt) {
console.log('onClone');
                },
                onChange: function (/**Event*/evt) {
console.log('onChange');
                },
                onStart: function (/**Event*/evt) {
console.log('onStart');
                    $('#navbarClipboard').removeClass('disabled');
                    $('#navbarTrash').removeClass('disabled');
                },
                onEnd: function (/**Event*/evt) {
console.log('onEnd');
                    PageLayout.disableEmptyClipboard();
                    PageLayout.disableEmptyTrash();
                    $(evt.item).removeClass('blue');
                },
                onMove: function (/**Event*/evt, /**Event*/originalEvent) {
console.log('onMove');
                    $('.iAmGhost').addClass('blue');
                },
                onAdd: function (/**Event*/evt) {
console.log('onAdd');
                    if (evt.pullMode === 'clone') {
                        // Insert from NewContentElementWizard (later also clipboard/trash)
                        switch (evt.item.dataset.panel) {
                            case 'newcontent':
                                // source/destination pages:694:sDEF:lDEF:field_breitOben:vDEF:1
                                $.ajax({
                                    type: 'POST',
                                    data: {
                                        destinationPointer: evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString(),
                                        elementRow: JSON.parse(evt.item.dataset.elementRow)
                                    },
                                    url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_insert'],
                                    success: function(data) {
                                        var div = document.createElement('div');
                                        div.innerHTML = data.nodeHtml;
                                        PageLayout.initEditRecordListener(div.firstElementChild);
                                        PageLayout.initSwitchVisibilityListener(div.firstElementChild);
                                        PageLayout.showSuccess(div.firstElementChild);
                                        evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);
                                    },
                                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                                        var el = evt.item;
                                        el.parentNode.removeChild(el);
                                    }
                                });
                                break;
                            case 'clipboard':
                                // Check clipboard mode copy/move/reference
                                // Non tt_content can only be referenced (if target allows them!
                                console.log(evt);
                                var mode = evt.from.parentNode.querySelector('input[name="clipboardMode"]:checked').value;
                                $.ajax({
                                    type: 'POST',
                                    data: {
                                        destinationPointer: evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString(),
                                        mode: mode,
                                        sourceTable: evt.item.dataset.recordTable,
                                        sourceUid: evt.item.dataset.recordUid
                                    },
                                    url: TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_action'],
                                    success: function(data) {
                                        var div = document.createElement('div');
                                        div.innerHTML = data.nodeHtml;
                                        PageLayout.initEditRecordListener(div.firstElementChild);
                                        PageLayout.initSwitchVisibilityListener(div.firstElementChild);
                                        PageLayout.showSuccess(div.firstElementChild);
                                        evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);
                                        if (data.clipboard.tt_content) {
                                            $('#navbarClipboard')[0].dataset.clipboardCount = data.clipboard.tt_content.count;
                                            $('#navbarClipboard .badge').html(data.clipboard.tt_content.count);
                                        } else {
                                            $('#navbarClipboard')[0].dataset.clipboardCount = 0;
                                            $('#navbarClipboard .badge').html(0);
                                            PageLayout.disableEmptyClipboard();
                                        }
                                    },
                                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                                        var el = evt.item;
                                        el.parentNode.removeChild(el);
                                    }
                                });

                        }
                    } else {
                        // Move from another field
                        // source/destination pages:694:sDEF:lDEF:field_breitOben:vDEF:1
                        PageLayout.showInProgress(evt.item);
                        $.ajax({
                            type: 'POST',
                            data: {
                                sourcePointer: evt.from.dataset.parentPointer + ':' + evt.oldDraggableIndex.toString(),
                                destinationPointer: evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString()
                            },
                            url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_move'],
                            success: function(data) {
                                // @TODO Elements need to update their parenPointer after move
                                PageLayout.showSuccess(evt.item);
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                PageLayout.showError(evt.item);
                            }
                        });
                    }
                }
            });
        }

        new Sortable(document.getElementById('navbarTrash'), {
            group: {
                name: 'dropzones',
                put: true
            },
            ghostClass: "hidden",
            onAdd: function (evt) {
                // Remove from container later we may give the possibility for restoring before leaving page
                var el = evt.item;
                el.parentNode.removeChild(el);
                $.ajax({
                    type: 'POST',
                    data: {
                        sourcePointer: evt.from.dataset.parentPointer + ':' + evt.oldDraggableIndex.toString()
                    },
                    url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_remove'],
                    success: function(data) {
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                    }
                });
            },
        });

        new Sortable(document.getElementById('navbarClipboard'), {
            group: {
                name: 'dropzones',
                put: true
            },
            ghostClass: "hidden",
        });

        $('#moduleWrapper').removeClass('hidden');
        $('#moduleLoadingIndicator').addClass('hidden');
        $('#moduleShadowing').addClass('hidden');

        PageLayout.initEditRecordListener(document);
        PageLayout.initSwitchVisibilityListener(document);
        PageLayout.disableEmptyClipboard();
        PageLayout.disableEmptyTrash();
    }

    PageLayout.disableEmptyClipboard = function() {
        var clipboard = $('#navbarClipboard');
        if (
          !clipboard[0].dataset.clipboardCount
          || clipboard[0].dataset.clipboardCount == 0
        ) {
            clipboard.addClass('disabled');
        }
    }

    PageLayout.disableEmptyTrash = function() {
        var trash = $('#navbarTrash');
        if (!trash[0].dataset.unusedCount) {
            trash.addClass('disabled');
        }
    }

    PageLayout.initEditRecordListener = function(base) {
        var allItems = base.querySelectorAll('div.tvp-node .tvp-record-edit');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                PageLayout.openRecordEdit(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    PageLayout.initSwitchVisibilityListener = function(base) {
        var allItems = base.querySelectorAll('div.tvp-node  button.tvp-record-switch-visibility');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                PageLayout.recordSwitchVisibility(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    PageLayout.initWizardDrag = function(instance) {
        var allDragzones = [].slice.call(instance.elementTooltip().querySelectorAll('.tvjs-drag'))

        for (var i = 0; i < allDragzones.length; i++) {
            new Sortable(allDragzones[i], {
                group: {
                    name: 'dropzones_tt_content',
                    pull: 'clone',
                    put: false
                },
                handle: '.dragHandle',
                animation: 150,
                sort: false,
                onStart: function (/**Event*/evt) {
                    instance.close();
                }
            });
        }
    }

    PageLayout.initClipboardModeListener = function(instance) {
        var allRadios = [].slice.call(instance.elementTooltip().querySelectorAll('input[type="radio"]'))

        for (const item of allRadios) {
            item.addEventListener('click', function(event) {
                $.get(TYPO3.settings.ajaxUrls['templavoilaplus_usersettings_setClipboardMode'], {mode: event.originalTarget.value});
            })
        }
    }

    PageLayout.openRecordEdit = function(table, uid) {
        var url = TYPO3.settings.ajaxUrls['templavoilaplus_record_edit'];
        var separator = (url.indexOf('?') > -1) ? '&' : '?';
        var params = 'table=' + table + '&uid=' + uid;

        PageLayout.myModal = Modal.advanced({
            type: Modal.types.ajax,
            title: 'Loading',
            content: url + separator + params,
            severity: Severity.notice,
            buttons: [],
            size: Modal.sizes.full,
            ajaxCallback: function() {
                Modal.currentModal.addClass('tvp-modal-record-edit');
                Modal.currentModal.find('.t3js-modal-iframe').on('load', function() {
                  var iframeDocument = Modal.currentModal.find('.t3js-modal-iframe').get(0).contentDocument;
                    var form = iframeDocument.getElementById('EditDocumentController');
                    if (form) {
                        Modal.currentModal.find('.t3js-modal-title').text(form.querySelector('h1').innerHTML);
                        form.querySelector('h1').style.display = 'none';
                    }
                    var closeModal = iframeDocument.getElementById('CloseModal');
                    if (closeModal) {
                        Modal.currentModal.trigger('modal-dismiss');
                        PageLayout.reloadRecord(table, uid);
                        PageLayout.myModal = null;
                    }
                })
            }
        });
    }

    PageLayout.recordSwitchVisibility = function(table, uid) {
        var items = $('div.tvp-node[data-record-table="' + table +'"][data-record-uid="' + uid +'"]');
        PageLayout.showInProgress(items);

        $.ajax({
            type: 'POST',
            data: {
                table: table,
                uid: uid,
            },
            url: TYPO3.settings.ajaxUrls['templavoilaplus_record_switch_visibility'],
            success: function(data) {
                PageLayout.reloadRecord(table, uid);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                PageLayout.showError(items);
            }
        });
    }

    PageLayout.reloadRecord = function(table, uid) {
        var items = $('div.tvp-node[data-record-table="' + table +'"][data-record-uid="' + uid +'"]');
        PageLayout.showInProgress(items);

        $.ajax({
            type: 'POST',
            data: {
                table: table,
                uid: uid,
            },
            url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_reload'],
            success: function(data) {
                var div = document.createElement('div');
                div.innerHTML = data.nodeHtml;
                for (var item of items) {
                    var newItem = div.firstElementChild.cloneNode(true)
                    PageLayout.initEditRecordListener(newItem);
                    PageLayout.initSwitchVisibilityListener(newItem);
                    PageLayout.showSuccess(newItem);
                    item.parentNode.replaceChild(newItem, item);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                PageLayout.showError(items);
            }
        });
    }

    PageLayout.showInProgress = function(items)
    {
        $('nav.navbar', items)
            .addClass("toYellow")
            .removeClass("bg-light");
    }

    PageLayout.showSuccess = function(items)
    {
        // flash green
        $('nav.navbar', items)
            .off()
            .addClass("flashGreen")
            .removeClass("bg-light")
            .removeClass("toYellow")
            .one("animationend webkitAnimationEnd", function(){ $('nav.navbar', items).addClass("bg-light").removeClass("flashGreen"); });
    }

    PageLayout.showError = function(items)
    {
        // flash red
        $('nav.navbar', items)
            .off()
            .addClass("flashRed")
            .removeClass("bg-light")
            .removeClass("toYellow")
            .one("animationend webkitAnimationEnd", function(){ $('nav.navbar', items).addClass("bg-light").removeClass("flashRed"); });
    }


    $(PageLayout.initialize);

    return PageLayout;
});
