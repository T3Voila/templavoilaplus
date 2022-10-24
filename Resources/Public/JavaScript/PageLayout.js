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
        var userSettings = $('#moduleWrapper').data('tvpUsersettings');
        if (userSettings.enableDarkMode) {
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
            PageLayout.initSortable(document);
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
                        sourcePointer: evt.from.dataset.parentPointer + ':' + evt.oldDraggableIndex.toString(),
                        pid: $('#moduleWrapper').data('tvpPageId')
                    },
                    url: TYPO3.settings.ajaxUrls['templavoilaplus_trash_unlink'],
                    success: function(data) {
                        PageLayout.updateTrashNumber(data.trash);
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
            onAdd: function (evt) {
                // Remove from container later we may give the possibility for restoring before leaving page
                var el = evt.item;
                el.parentNode.removeChild(el);

                PageLayout.clipboardAdd(el.dataset.recordTable, el.dataset.recordUid);
            },
        });

        $('#moduleWrapper').removeClass('hidden');
        $('#moduleLoadingIndicator').addClass('hidden');
        $('#moduleShadowing').addClass('hidden');

        PageLayout.addTooltipster();
        PageLayout.initElements(document);
        PageLayout.disableEmptyClipboard();
        PageLayout.disableEmptyTrash();
    }


    PageLayout.initElements = function (base) {
        PageLayout.initSortable(base);
        PageLayout.initEditRecordListener(base);
        PageLayout.initClipboardAddListener(base);
        PageLayout.initSwitchVisibilityListener(base);
    }

    PageLayout.initSortable = function (base) {
      var allItems = base.querySelectorAll('.tvjs-dropzone');
      for (const el of allItems) {
        new Sortable(el, {
          revertOnSpill: true,
          group: {
            name: 'dropzones_' + el.dataset.childAllowed,
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
              // console.log(el);
              // $(to.el).addClass('green');
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
              success: function (data) {
                // @TODO Elements need to update their parenPointer after move
                PageLayout.showSuccess(evt.item);
              },
              error: function (XMLHttpRequest, textStatus, errorThrown) {
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
                    url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_create'],
                    success: function (data) {
                      var div = document.createElement('div');
                      div.innerHTML = data.nodeHtml;
                      PageLayout.initElements(div.firstElementChild);
                      PageLayout.showSuccess(div.firstElementChild);
                      evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                      var el = evt.item;
                      el.parentNode.removeChild(el);
                      PageLayout.showErrorNotification(XMLHttpRequest);
                    }
                  });
                  break;
                case 'clipboard':
                  // Check clipboard mode copy/move/reference
                  // Non tt_content can only be referenced (if target allows them!
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
                    success: function (data) {
                      var div = document.createElement('div');
                      div.innerHTML = data.nodeHtml;
                      PageLayout.initElements(div.firstElementChild);
                      PageLayout.showSuccess(div.firstElementChild);
                      evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);

                      PageLayout.updateClipboardNumber(data.clipboard);
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                      var el = evt.item;
                      el.parentNode.removeChild(el);
                      PageLayout.showErrorNotification(XMLHttpRequest);
                    },
                  });
                  break;
                case 'trash':
                  console.log(evt.item, evt.target);
                  $.ajax({
                    type: 'POST',
                    data: {
                      destinationPointer: evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString(),
                      sourceTable: evt.item.dataset.recordTable,
                      sourceUid: evt.item.dataset.recordUid,
                      pid: $('#moduleWrapper').data('tvpPageId')
                    },
                    url: TYPO3.settings.ajaxUrls['templavoilaplus_trash_link'],
                    success: function (data) {
                      var div = document.createElement('div');
                      div.innerHTML = data.nodeHtml;
                      PageLayout.initElements(div.firstElementChild);
                      PageLayout.showSuccess(div.firstElementChild);
                      evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);
                      PageLayout.updateTrashNumber(data.trash);
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                      var el = evt.item;
                      el.parentNode.removeChild(el);
                      PageLayout.showErrorNotification(XMLHttpRequest);
                    }
                  });
                  return false;
                  break;
                default:
                  return false;
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
                success: function (data) {
                  // @TODO Elements need to update their parenPointer after move
                  PageLayout.showSuccess(evt.item);
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                  PageLayout.showError(evt.item);
                  PageLayout.showErrorNotification(XMLHttpRequest);
                }
              });
            }
          }
        });
      }
    }
    PageLayout.showErrorNotification = function (XMLHttpRequest) {
        if (XMLHttpRequest.status === 400) {
            require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
                Notification.error('Templavoilà! Plus Error', XMLHttpRequest.responseJSON.error);
            });
            return;
        } else if (XMLHttpRequest.status === 500) {
            require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
                var el = document.createElement( 'html' );
                el.innerHTML = XMLHttpRequest.responseText;
                var errorMessage = el.getElementsByClassName('trace-message')[0].innerText;
                Notification.error('Templavoilà! Plus Error', errorMessage);
            });
            return;
        }
        require(['TYPO3/CMS/Backend/Notification'], function (Notification) {
            Notification.error('Templavoilà! Plus Error', XMLHttpRequest.statusText);
        });
        console.log(XMLHttpRequest);
    }
    PageLayout.addTooltipster = function() {
        // Add tooltip functionality to Sidebar
        PageLayout.addTooltipsterContentElementWizard();
        PageLayout.addTooltipsterClipboard();
        PageLayout.addTooltipsterTrash();
        PageLayout.addTooltipsterConfig();
    }
    PageLayout.addTooltipsterContentElementWizard = function() {
        if (!$('#navbarContentElementWizard:not(.disabled)').hasClass("tooltipstered")) {
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
        }
    }
    PageLayout.addTooltipsterClipboard = function() {
        if (!$('#navbarClipboard:not(.disabled)').hasClass("tooltipstered")) {
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
                            PageLayout.initClipboardReleaseListener(instance);
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
        }
    }
    PageLayout.addTooltipsterTrash = function() {
        if (!$('#navbarTrash:not(.disabled)').hasClass("tooltipstered")) {
            $('#navbarTrash:not(.disabled)').tooltipster({
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
                            pid: $('#moduleWrapper').data('tvpPageId')
                        },
                        url: TYPO3.settings.ajaxUrls['templavoilaplus_trash_load'],
                        success: function(data) {
                            // Add data to content
                            instance.content(data);
                            PageLayout.initWizardDrag(instance);
                            PageLayout.initTrashDeleteListener(instance);
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
        }
    }
    PageLayout.addTooltipsterConfig = function() {
        if (!$('#navbarConfig:not(.disabled)').hasClass("tooltipstered")) {
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
        }
    }

    PageLayout.disableEmptyClipboard = function() {
        var clipboard = $('#navbarClipboard');
        if (
          !clipboard[0].dataset.clipboardCount
          || clipboard[0].dataset.clipboardCount == 0
        ) {
            clipboard.addClass('disabled');
            if ($('#navbarClipboard').hasClass("tooltipstered")) {
                $('#navbarClipboard').tooltipster('destroy');
            }
        }
    }

    PageLayout.disableEmptyTrash = function() {
        var trash = $('#navbarTrash');
        if (!trash[0].dataset.unusedCount
          || trash[0].dataset.unusedCount == 0
        ) {
            trash.addClass('disabled');
            if ($('#navbarTrash').hasClass("tooltipstered")) {
                $('#navbarTrash').tooltipster('destroy');
            }
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

    PageLayout.initClipboardAddListener = function(base) {
        var allItems = base.querySelectorAll('div.tvp-node .tvp-clipboard-add');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                PageLayout.clipboardAdd(origItem.dataset.recordTable, origItem.dataset.recordUid);
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

    PageLayout.initClipboardReleaseListener = function(instance) {
        var allButtons = [].slice.call(instance.elementTooltip().querySelectorAll('.tvjs-clipboard-release'))

        for (const item of allButtons) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node-clipboard');
                PageLayout.clipboardRelease(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    PageLayout.initTrashDeleteListener = function(instance) {
        var allButtons = [].slice.call(instance.elementTooltip().querySelectorAll('.tvjs-trash-delete'))

        for (const item of allButtons) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node-trash');
                PageLayout.trashDelete(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    PageLayout.updateClipboardNumber = function(clipboardData) {
        if (clipboardData.tt_content) {
            $('#navbarClipboard')[0].dataset.clipboardCount = clipboardData.tt_content.count;
            $('#navbarClipboard .badge').html(clipboardData.tt_content.count);
            $('#navbarClipboard').removeClass('disabled');
            PageLayout.addTooltipsterClipboard();
        } else {
            $('#navbarClipboard')[0].dataset.clipboardCount = 0;
            $('#navbarClipboard .badge').html(0);
            PageLayout.disableEmptyClipboard();
        }
    }

    PageLayout.updateTrashNumber = function(trashData) {
        if (trashData.totalCount) {
            $('#navbarTrash')[0].dataset.unusedCount = trashData.totalCount;
            $('#navbarTrash .badge').html(trashData.totalCount);
            $('#navbarTrash').removeClass('disabled');
            PageLayout.addTooltipsterTrash();
        } else {
            $('#navbarTrash')[0].dataset.unusedCount = 0;
            $('#navbarTrash .badge').html(0);
            PageLayout.disableEmptyTrash();
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
                        Modal.currentModal.find('.t3js-modal-title').html(form.querySelector('h1').innerHTML);
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

    PageLayout.clipboardRelease = function(table, uid) {
        $.ajax({
            type: 'POST',
            data: {
                table: table,
                uid: uid,
            },
            url: TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_release'],
            success: function(data) {
                $('#navbarClipboard').tooltipster('close');
                PageLayout.updateClipboardNumber(data.clipboard);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                PageLayout.showError(items);
            }
        });
    }

    PageLayout.trashDelete = function(table, uid) {
        $.ajax({
            type: 'POST',
            data: {
                table: table,
                uid: uid,
                pid: $('#moduleWrapper').data('tvpPageId')
            },
            url: TYPO3.settings.ajaxUrls['templavoilaplus_trash_delete'],
            success: function(data) {
                $('#navbarTrash').tooltipster('close');
                PageLayout.updateTrashNumber(data.trash);
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                PageLayout.showError(items);
            }
        });
    }

    PageLayout.clipboardAdd = function(table, uid) {
        $.ajax({
            type: 'POST',
            data: {
                table: table,
                uid: uid,
            },
            url: TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_add'],
            success: function(data) {
                PageLayout.updateClipboardNumber(data.clipboard);
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
                    PageLayout.initElements(newItem);
                    PageLayout.showSuccess(newItem);
                    item.parentNode.replaceChild(newItem, item);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                PageLayout.showError(items);
                require(['TYPO3/CMS/Backend/Notification'], function(Notification) {
                  Notification.error('Templavoilà! Plus Error', XMLHttpRequest.responseJSON.error);
                });
            }
        });
    }

    PageLayout.showInProgress = function(items)
    {
        $('.tvp-node-header', items)
            .addClass("toYellow");
    }

    PageLayout.showSuccess = function(items)
    {
        // flash green
        $('.tvp-node-header', items)
            .off()
            .addClass("flashGreen")
            .removeClass("toYellow")
            .one("animationend webkitAnimationEnd", function(){ $('.tvp-node-header', items).removeClass("flashGreen"); });
    }

    PageLayout.showError = function(items)
    {
        // flash red
        $('.tvp-node-header', items)
            .off()
            .addClass("flashRed")
            .removeClass("toYellow")
            .one("animationend webkitAnimationEnd", function(){ $('.tvp-node-header', items).removeClass("flashRed"); });
    }


    $(PageLayout.initialize);

    return PageLayout;
});
