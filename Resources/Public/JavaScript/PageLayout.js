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
                    name: 'dropzones',
                    pull: function (to, from, evt, dragEvent) {
// console.log(evt);
console.log(dragEvent.ctrlKey);
//                         to.el.addClass('green');
                        if (to.el.id === 'navbarClipboard') {
                            return 'clone';
                        }
                        if (to.el.id === 'navbarTrash') {
                            return true;
                        }
                        return true;
                    },
                    put: function (to, from, el, evt, dragEvent) {
console.log(evt.ctrlKey);
//                         console.log(el);
//                         $(to.el).addClass('green');
                    },
                    revertClone: true
                },
                ghostClass: "iAmGhost",
                dragable: '.sortableItem',
                animation: 150,
                swapThreshold: 0.65,
                onUpdate: function (/**Event*/evt) {
console.log('onUpdate');
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
                    $('#navbarClipboard').addClass('disabled');
                    $('#navbarTrash').addClass('disabled');
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
                        // source/destination pages:694:sDEF:lDEF:field_breitOben:vDEF:1
                        $.ajax({
                            type: 'POST',
                            data: {
                                destinationPointer: evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString(),
                                elementRow: JSON.parse(evt.item.dataset.elementrow)
                            },
                            url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_insert'],
                            success: function(data) {
                                var div = document.createElement('div');
                                div.innerHTML = data.nodeHtml;
                                PageLayout.showSuccess(div.firstChild);
                                PageLayout.initEditRecordListener(div.firstChild);
                                evt.item.parentNode.replaceChild(div.firstChild, evt.item);
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                var el = evt.item;
                                el.parentNode.removeChild(el);
                            }
                        });
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

    }

    PageLayout.initEditRecordListener = function(base) {
        var allItems = base.querySelectorAll('.sortableItem .tvp-edit-record');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.parentNode.parentNode.parentNode;
                PageLayout.openRecordEdit(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    PageLayout.initWizardDrag = function(instance) {
        var allDragzones = [].slice.call(instance.elementTooltip().querySelectorAll('.tvjs-drag'))

        for (var i = 0; i < allDragzones.length; i++) {
            new Sortable(allDragzones[i], {
                group: {
                    name: 'dropzones',
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

    PageLayout.reloadRecord = function(table, uid) {
        var items = $('.sortableItem[data-record-table="' + table +'"][data-record-uid="' + uid +'"]');
        PageLayout.showInProgress(items[0]);

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
                PageLayout.showSuccess(div.firstChild);
                PageLayout.initEditRecordListener(div.firstChild);
                items[0].parentNode.replaceChild(div.firstChild, items[0]);

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                PageLayout.showError(items[0]);
            }
        });
    }

    PageLayout.showInProgress = function(item)
    {
        $('.tpm-titlebar', item)
            .addClass("toYellow");
    }

    PageLayout.showSuccess = function(item)
    {
        // flash green
        $('.tpm-titlebar', item)
            .off()
            .addClass("flashGreen")
            .removeClass("toYellow")
            .one("animationend webkitAnimationEnd", function(){ $('.tpm-titlebar', item).removeClass("flashGreen"); });
    }

    PageLayout.showError = function(item)
    {
        // flash red
        $('.tpm-titlebar', item)
            .off()
            .addClass("flashRed")
            .removeClass("toYellow")
            .one("animationend webkitAnimationEnd", function(){ $('.tpm-titlebar', item).removeClass("flashRed"); });
    }


    $(PageLayout.initialize);

    return PageLayout;
});
