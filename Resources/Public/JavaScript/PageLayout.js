import Sortable from 'sortablejs';
import { Popover as BootstrapPopover } from 'bootstrap';

import Modal from "@typo3/backend/modal.js";
import Notification from "@typo3/backend/notification.js";
import Popover from "@typo3/backend/popover.js";
import Severity from "@typo3/backend/severity.js";

class PageLayout {

    popoverConfig = {
        html: true,
        placement: 'left',
        fallbackPlacements: ['left'],
        trigger: 'focus',
        content: 'Loading...',
        sanitize: false,
        customClass: 'tvpPopover',
    };
    myModal = null;
    clipboard = null;
    trash = null;

    baseElement = null;

    /**
     * Initialize
     */
    initialize = function() {
        let that = this;
        this.baseElement = document.querySelector('#moduleWrapper');
        this.clipboard = document.querySelector('#navbarClipboard');
        this.trash = document.querySelector('#navbarTrash');

        // Enable Sidebar Elements
        if (this.baseElement.dataset.tvpPageEditRights
            && this.baseElement.dataset.tvpPageDokType == 1
        ) {
            document.querySelector('#navbarContentElementWizard').classList.remove('disabled');
        }

         window.addEventListener('message', function(event) {
            if (this.myModal) {
                document.querySelector('.t3js-modal-iframe', this.myModal).contentWindow.postMessage(event.data, event.source);
            }
        });

        // Initialize drag&drop
        var allDropzones = [].slice.call(document.querySelectorAll('.tvjs-dropzone'))

        for (var i = 0; i < allDropzones.length; i++) {
            this.initSortable(document);
        }

        new Sortable(document.getElementById('navbarTrash'), {
            group: {
                name: 'dropzones',
                put: true
            },
            draggable: 'none',
            ghostClass: "hidden",
            onAdd: function (evt) {
                // Remove from container later we may give the possibility for restoring before leaving page
                var el = evt.item;
                el.parentNode.removeChild(el);

                var params = new URLSearchParams();
                params.set('sourcePointer', evt.from.dataset.parentPointer + ':' + evt.oldDraggableIndex.toString());
                params.set('pid', that.baseElement.dataset.tvpPageId);
                fetch(TYPO3.settings.ajaxUrls['templavoilaplus_trash_unlink'], {
                    method: 'POST',
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: params,
                }).then(async function(response) {
                    if (!response.ok) {
                        var errorJson = await response.json();
                        throw new Error("Trash error", {cause: {response: response, errorJson: errorJson}});
                    }
                    return response.json()
                }).then(function(data) {
                    that.updateTrashNumber(data.trash);
                }).catch(function(errorException) {
                    that.showError(evt.item);
                    that.showErrorNotification(errorException);
                });
            },
        });

        new Sortable(document.getElementById('navbarClipboard'), {
            group: {
                name: 'dropzones',
                put: true
            },
            draggable: 'none',
            ghostClass: "hidden",
            onAdd: function (evt) {
                // Remove from container later we may give the possibility for restoring before leaving page
                var el = evt.item;
                el.parentNode.removeChild(el);

                that.clipboardAdd(el.dataset.recordTable, el.dataset.recordUid);
            },
        });

        this.baseElement.classList.remove('hidden');
        document.querySelector('#moduleLoadingIndicator').classList.add('hidden');
        document.querySelector('#moduleShadowing').classList.add('hidden');

        this.addTooltipster();
        this.initElements(document);
        this.disableEmptyClipboard();
        this.disableEmptyTrash();
    }


    initElements = function (base) {
        this.initSortable(base);
        this.initEditRecordListener(base);
        this.initClipboardAddListener(base);
        this.initMakeLocalCopy(base);
        this.initSwitchVisibilityListener(base);
        this.initLocalizeListener(base);
    }

    initSortable = function (base) {
      var allItems = base.querySelectorAll('.tvjs-dropzone');
      var that = this;
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
            },
            revertClone: true
          },
          ghostClass: "iAmGhost",
          dragable: '.sortableItem',
          animation: 150,
          swapThreshold: 0.95,
          invertedSwapThreshold: 0.25,
          emptyInsertThreshold: 5,
          onUpdate: function (/**Event*/evt) {
            console.log('onUpdate');
            // Move inside field
              that.doAjaxMove(evt);
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
            document.querySelector('#navbarClipboard').classList.remove('disabled');
            document.querySelector('#navbarTrash').classList.remove('disabled');
          },
          onEnd: function (/**Event*/evt) {
            console.log('onEnd');
            that.disableEmptyClipboard();
            that.disableEmptyTrash();
            evt.item.classList.remove('blue');
          },
          onMove: function (/**Event*/evt, /**Event*/originalEvent) {
            console.log('onMove');
            let ghost = document.querySelector('.iAmGhost');
            if (ghost) {
                ghost.classList.add('blue');
            }
          },
          onAdd: function (/**Event*/evt) {
            console.log('onAdd');
            if (evt.pullMode === 'clone') {
              switch (evt.item.dataset.panel) {
                case 'newcontent':
                    that.doAjaxNewcontent(evt);
                  break;
                case 'clipboard':
                    that.doAjaxClipboard(evt);
                  break;
                case 'trash':
                    that.doAjaxTrash(evt);
                    return false;
                  break;
                default:
                  return false;
              }
            } else {
              // Move from another field
              that.doAjaxMove(evt);
            }
          }
        });
      }
    }

    doAjaxNewcontent = function(evt) {
        var that = this;
        this.showInProgress(evt.item);

        // source/destination pages:694:sDEF:lDEF:field_breitOben:vDEF:1
        var params = new URLSearchParams();
        params.set('destinationPointer', evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString());
        params.set('elementRow', evt.item.dataset.elementRow);
        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_create'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Move error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            var div = document.createElement('div');
            div.innerHTML = data.nodeHtml;
            that.initElements(div.firstElementChild);
            that.showSuccess(div.firstElementChild);
            evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);
        }).catch(function(errorException) {
            that.showError(evt.item);
            that.showErrorNotification(errorException);
            var el = evt.item;
            el.parentNode.removeChild(el);
        });
    }

    doAjaxMove = function(evt) {
        var that = this;
        this.showInProgress(evt.item);

        // source/destination pages:694:sDEF:lDEF:field_breitOben:vDEF:1
        var params = new URLSearchParams();
        params.set('sourcePointer', evt.from.dataset.parentPointer + ':' + evt.oldDraggableIndex.toString());
        params.set('destinationPointer', evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString());
        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_move'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Move error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            // @TODO Elements need to update their parenPointer after move
            that.showSuccess(evt.item);
        }).catch(function(errorException) {
            that.showError(evt.item);
            that.showErrorNotification(errorException);
        });
    }

    doAjaxClipboard = function(evt) {
        let that = this;
        // Check clipboard mode copy/move/reference
        // Non tt_content can only be referenced (if target allows them!
        var params = new URLSearchParams();
        params.set('destinationPointer', evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString());
        params.set('mode', evt.from.parentNode.querySelector('input[name="clipboardMode"]:checked').value);
        params.set('sourceTable', evt.item.dataset.recordTable);
        params.set('sourceUid', evt.item.dataset.recordUid);

        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_action'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Move error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            var div = document.createElement('div');
            div.innerHTML = data.nodeHtml;
            that.initElements(div.firstElementChild);
            that.showSuccess(div.firstElementChild);
            evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);

            that.updateClipboardNumber(data.clipboard);
        }).catch(function(errorException) {
            that.showErrorNotification(errorException);
            var el = evt.item;
            el.parentNode.removeChild(el);
        });
    }

    doAjaxTrash = function(evt) {
        let that = this;
        // Check clipboard mode copy/move/reference
        // Non tt_content can only be referenced (if target allows them!
        var params = new URLSearchParams();
        params.set('destinationPointer', evt.target.dataset.parentPointer + ':' + evt.newDraggableIndex.toString());
        params.set('sourceTable', evt.item.dataset.recordTable);
        params.set('sourceUid', evt.item.dataset.recordUid);
        params.set('pid', this.baseElement.dataset.tvpPageId);

        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_trash_link'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Move error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            var div = document.createElement('div');
            div.innerHTML = data.nodeHtml;
            that.initElements(div.firstElementChild);
            that.showSuccess(div.firstElementChild);
            evt.item.parentNode.replaceChild(div.firstElementChild, evt.item);

            that.updateTrashNumber(data.trash);
        }).catch(function(errorException) {
            var el = evt.item;
            el.parentNode.removeChild(el);
            that.showErrorNotification(errorException);
        });
    }

    showErrorNotification = function (errorException) {
        if (errorException.cause instanceof Object && errorException.cause.response instanceof Response) {
            if (errorException.cause.response.status === 400) {
                Notification.error('Templavoilà! Plus Error', errorException.message + ": " + errorException.cause.errorJson.error);
                return;
            } else if (errorException.cause.response.status === 500) {
                var el = document.createElement( 'html' );
                el.innerHTML = errorException.cause.response.text;
                var errorMessage = el.getElementsByClassName('trace-message')[0].innerText;
                Notification.error('Templavoilà! Plus Error', errorMessage);
                return;
            }
            Notification.error('Templavoilà! Plus Error', errorException.cause.response.statusText);
            return;
        }
        Notification.error('Templavoilà! Plus Error', errorException.message);
    }

    addTooltipster = function() {
        // Add tooltip functionality to Sidebar
        this.addTooltipsterContentElementWizard();
        this.addTooltipsterClipboard();
        this.addTooltipsterTrash();
    }

    addTooltipsterContentElementWizard = function() {
        let that = this;
        let contentElementWizard = document.querySelector('#navbarContentElementWizard:not(.disabled)');
        if (contentElementWizard) {
            const popover = BootstrapPopover.getInstance(contentElementWizard);
            if (popover === null) {
                Popover.popover(contentElementWizard);
                Popover.setOptions(contentElementWizard, that.popoverConfig);
                contentElementWizard.addEventListener('show.bs.popover', function () {
                    //document.querySelector('#moduleShadowing').classList.remove('hidden');
                    if (!that.baseElement.dataset.loadedContentElementWizard) {
                        that.baseElement.dataset.loadedContentElementWizard = true;
                        var params = new URLSearchParams();
                        params.set('id', that.baseElement.dataset.tvpPageId);
                        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_contentElementWizard'], {
                            method: 'POST',
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: params,
                        }).then(async function(response) {
                            if (!response.ok) {
                                var errorJson = await response.json();
                                throw new Error("Wizard error", {cause: {response: response, errorJson: errorJson}});
                            }
                            return response.text()
                        }).then(function(data) {

                            Popover.setOptions(contentElementWizard, {
                                html: true,
                                content: data,
                            });

                            const instance = BootstrapPopover.getInstance(contentElementWizard);
                            document.addEventListener('click', function(event) {
                                const withinBoundaries = event.composedPath().includes(instance.tip)
                                if (!withinBoundaries) {
                                    instance.hide();
                                }
                            })
                        }).catch(function(errorException) {
                            that.showErrorNotification(errorException);
                        });
                    }
                });
                contentElementWizard.addEventListener('shown.bs.popover', function () {
                    const instance = BootstrapPopover.getInstance(contentElementWizard);
                    that.initWizardDrag(instance);
                });
                contentElementWizard.addEventListener('hide.bs.popover', function() {
                    //document.querySelector('#moduleShadowing').classList.add('hidden');
                });
            };
        }
    }

    addTooltipsterClipboard = function() {
        let that = this;
        let clipboard = document.querySelector('#navbarClipboard:not(.disabled)');
        if (clipboard) {
            const popover = BootstrapPopover.getInstance(clipboard);
            if (popover === null) {

                Popover.popover(clipboard);
                Popover.setOptions(clipboard, that.popoverConfig);
                clipboard.addEventListener('show.bs.popover', function () {
                    //document.querySelector('#moduleShadowing').classList.remove('hidden');
                    if (!that.baseElement.dataset.loadedClipboard) {
                        that.baseElement.dataset.loadedClipboard = true;

                        var params = new URLSearchParams();
                        params.set('id', that.baseElement.dataset.tvpPageId);
                        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_load'], {
                            method: 'POST',
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: params,
                        }).then(async function(response) {
                            if (!response.ok) {
                                var errorJson = await response.json();
                                throw new Error("Clipboard error", {cause: {response: response, errorJson: errorJson}});
                            }
                            return response.text()
                        }).then(function(data) {

                            Popover.setOptions(clipboard, {
                                html: true,
                                content: data,
                            });
                            const instance = BootstrapPopover.getInstance(clipboard);
                            document.addEventListener('click', function(event) {
                                const withinBoundaries = event.composedPath().includes(instance.tip)
                                if (!withinBoundaries) {
                                    instance.hide();
                                }
                            })
                        }).catch(function(errorException) {
                            that.showErrorNotification(errorException);
                        });
                    }
                });
                clipboard.addEventListener('shown.bs.popover', function () {
                    const instance = BootstrapPopover.getInstance(clipboard);
                    that.initWizardDrag(instance);
                    that.initClipboardModeListener(instance);
                    that.initClipboardReleaseListener(instance);
                });
                clipboard.addEventListener('hide.bs.popover', function () {
                    //document.querySelector('#moduleShadowing').classList.add('hidden');
                });
            }
        }
    }

    addTooltipsterTrash = function() {
        let that = this;
        let trash = document.querySelector('#navbarTrash:not(.disabled)');
        if (trash) {
            const popover = BootstrapPopover.getInstance(trash);
            if (popover === null) {

                Popover.popover(trash);
                Popover.setOptions(trash, that.popoverConfig);
                trash.addEventListener('show.bs.popover', function () {
                    //document.querySelector('#moduleShadowing').classList.remove('hidden');
                    if (!that.baseElement.dataset.loadedTrash) {
                        that.baseElement.dataset.loadedTrash = true;
                        var params = new URLSearchParams();
                        params.set('pid', that.baseElement.dataset.tvpPageId);
                        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_trash_load'], {
                            method: 'POST',
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: params,
                        }).then(async function(response) {
                            if (!response.ok) {
                                var errorJson = await response.json();
                                throw new Error("Trash error", {cause: {response: response, errorJson: errorJson}});
                            }
                            return response.text()
                        }).then(function(data) {

                            Popover.setOptions(trash, {
                                html: true,
                                content: data,
                            });

                            const instance = BootstrapPopover.getInstance(trash);
                            document.addEventListener('click', function(event) {
                                const withinBoundaries = event.composedPath().includes(instance.tip)
                                if (!withinBoundaries) {
                                    instance.hide();
                                }
                            })
                        }).catch(function(errorException) {
                            that.showErrorNotification(errorException);
                        });
                    }
                });
                trash.addEventListener('shown.bs.popover', function () {
                    const instance = BootstrapPopover.getInstance(trash);
                    that.initWizardDrag(instance);
                    that.initTrashDeleteListener(instance);
                });
                trash.addEventListener('hide.bs.popover', function () {
                    //document.querySelector('#moduleShadowing').classList.add('hidden');
                });
            }
        }
    }

    disableEmptyClipboard = function() {
        let clipboard = document.querySelector('#navbarClipboard');
        if (
          !clipboard.dataset.clipboardCount
          || clipboard.dataset.clipboardCount == 0
        ) {
            clipboard.classList.add('disabled');
        }
    }

    disableEmptyTrash = function() {
        let trash = document.querySelector('#navbarTrash');
        if (!trash.dataset.unusedCount
          || trash.dataset.unusedCount == 0
        ) {
            trash.classList.add('disabled');
        }
    }

    initEditRecordListener = function(base) {
        let that = this;
        var allItems = base.querySelectorAll('div.tvp-node .tvp-record-edit');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                that.openRecordEdit(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }

        var allPreviews = base.querySelectorAll('div.tvp-node .tvp-record-preview:not(:has(.disable-tvp-preview-onclick))');

        for (const item of allPreviews) {
            item.addEventListener('click', function (event) {
                var origItem = item.closest('.tvp-node');
                that.openRecordEdit(origItem.dataset.recordTable, origItem.dataset.recordUid);
            });
        }
    }

    initClipboardAddListener = function(base) {
        let that = this;
        var allItems = base.querySelectorAll('div.tvp-node .tvp-clipboard-add');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                that.clipboardAdd(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    initMakeLocalCopy = function(base) {
        let that = this;
        var allItems = base.querySelectorAll('div.tvp-node .tvp-make-localcopy');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                that.makeLocalCopy(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    initSwitchVisibilityListener = function(base) {
        let that = this;
        var allItems = base.querySelectorAll('div.tvp-node button.tvp-record-switch-visibility');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                that.recordSwitchVisibility(origItem.dataset.recordTable, origItem.dataset.recordUid);
            })
        }
    }

    initLocalizeListener = function(base) {
        let that = this;
        var allItems = base.querySelectorAll('div.tvp-node button.tvp-record-localize');

        for (const item of allItems) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node');
                that.recordLocalize(origItem.dataset.recordTable, origItem.dataset.recordUid, event.srcElement.dataset.languageUid);
            })
        }
    }

    initWizardDrag = function(instance) {
        var allDragzones = [].slice.call(instance.tip.querySelectorAll('.tvjs-drag'))

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
                    instance.hide();
                }
            });
        }
    }

    initClipboardModeListener = function(instance) {
        var allRadios = [].slice.call(instance.tip.querySelectorAll('input[type="radio"]'))

        for (const item of allRadios) {
            item.addEventListener('click', function(event) {
                var params = new URLSearchParams();
                params.set('mode', event.originalTarget.value);
                fetch(TYPO3.settings.ajaxUrls['templavoilaplus_usersettings_setClipboardMode'] + '&mode=' + event.originalTarget.value, {
                    method: 'POST',
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                });
            })
        }
    }

    initClipboardReleaseListener = function(instance) {
        let that = this;
        var allButtons = [].slice.call(instance.tip.querySelectorAll('.tvjs-clipboard-release'))

        for (const item of allButtons) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node-clipboard');
                that.clipboardRelease(origItem.dataset.recordTable, origItem.dataset.recordUid, origItem);
            })
        }
    }

    initTrashDeleteListener = function(instance) {
        let that = this;
        var allButtons = [].slice.call(instance.tip.querySelectorAll('.tvjs-trash-delete'))

        for (const item of allButtons) {
            item.addEventListener('click', function(event) {
                var origItem = item.closest('.tvp-node-trash');
                that.trashDelete(origItem.dataset.recordTable, origItem.dataset.recordUid, origItem);
            })
        }
    }

    updateClipboardNumber = function(clipboardData) {
        delete this.baseElement.dataset.loadedClipboard;
        if (clipboardData.tt_content) {
            document.querySelector('#navbarClipboard').clipboardCount = clipboardData.tt_content.count;
            document.querySelector('#navbarClipboard .badge').innerText = clipboardData.tt_content.count;
            document.querySelector('#navbarClipboard').classList.remove('disabled');
            this.addTooltipsterClipboard();
        } else {
            document.querySelector('#navbarClipboard').dataset.clipboardCount = 0;
            document.querySelector('#navbarClipboard .badge').innerText = 0;
            this.disableEmptyClipboard();
        }
    }

    updateTrashNumber = function(trashData) {
        delete this.baseElement.dataset.loadedTrash;
        if (trashData.totalCount) {
            document.querySelector('#navbarTrash').dataset.unusedCount = trashData.totalCount;
            document.querySelector('#navbarTrash .badge').innerText = trashData.totalCount;
            document.querySelector('#navbarTrash').classList.remove('disabled');
            this.addTooltipsterTrash();
        } else {
            document.querySelector('#navbarTrash').dataset.unusedCount = 0;
            document.querySelector('#navbarTrash .badge').innerText = 0;
            this.disableEmptyTrash();
        }
    }

    openRecordEdit = function(table, uid) {
        var url = TYPO3.settings.ajaxUrls['templavoilaplus_record_edit'];
        var separator = (url.indexOf('?') > -1) ? '&' : '?';
        var params = 'table=' + table + '&uid=' + uid;
        let that = this;

        this.myModal = Modal.advanced({
            type: Modal.types.ajax,
            title: 'Loading',
            content: url + separator + params,
            severity: Severity.notice,
            buttons: [],
            staticBackdrop: true,
            hideCloseButton: true,
            size: Modal.sizes.full,
            callback: function(typo3modal) { typo3modal.bootstrapModal._config.keyboard = false; },
            ajaxCallback: function() {
                Modal.currentModal.classList.add('tvp-modal-record-edit');
                Modal.currentModal.querySelector('.t3js-modal-iframe').addEventListener('load', function() {
                    var iframeDocument = Modal.currentModal.querySelector('.t3js-modal-iframe').contentDocument;
                    var form = iframeDocument.getElementById('EditDocumentController');
                    if (form) {
                        Modal.currentModal.querySelector('.t3js-modal-title').innerHTML = form.querySelector('h1').innerHTML;
                        form.querySelector('h1').style.display = 'none';
                    }
                    var closeModal = iframeDocument.getElementById('CloseModal');
                    if (closeModal) {
                        Modal.currentModal.bootstrapModal.hide();
                        that.reloadRecord(table, uid);
                        that.myModal = null;
                    }
                })
            }
        });
    }

    recordSwitchVisibility = function(table, uid) {
        var that = this;
        var item = document.querySelector('div.tvp-node[data-record-table="' + table +'"][data-record-uid="' + uid +'"]');
        this.showInProgress(item);

        var params = new URLSearchParams();
        params.set('table', table);
        params.set('uid', uid);
        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_record_switch_visibility'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Visibility error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            that.reloadRecord(table, uid);
        }).catch(function(errorException) {
            that.showError(item);
            that.showErrorNotification(errorException);
        });
    }

    recordLocalize = function(table, uid, langUid) {
        var that = this;
        var item = document.querySelector('div.tvp-node[data-record-table="' + table +'"][data-record-uid="' + uid +'"]');
        this.showInProgress(item);

        var params = new URLSearchParams();
        params.set('table', table);
        params.set('uid', uid);
        params.set('langUid', langUid);
        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_record_localize'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Visibility error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            that.reloadRecord(table, uid);
        }).catch(function(errorException) {
            that.showError(item);
            that.showErrorNotification(errorException);
        });
    }

    clipboardRelease = function(table, uid, origItem) {
        let that = this;
        var params = new URLSearchParams();
        params.set('table', table);
        params.set('uid', uid);
        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_release'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Clipboard error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            origItem.remove();
            that.updateClipboardNumber(data.clipboard);
        }).catch(function(errorException) {
            that.showErrorNotification(errorException);
        });
    }

    trashDelete = function(table, uid, origItem) {
        let that = this;
        var params = new URLSearchParams();
        params.set('table', table);
        params.set('uid', uid);
        params.set('pid', this.baseElement.dataset.tvpPageId);
        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_trash_delete'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Clipboard error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            origItem.remove();
            that.updateTrashNumber(data.trash);
        }).catch(function(errorException) {
            that.showErrorNotification(errorException);
        });
    }

    clipboardAdd = function(table, uid) {
        var that = this;
        var params = new URLSearchParams();
        params.set('table', table);
        params.set('uid', uid);
        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_clipboard_add'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Clipboard error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            that.updateClipboardNumber(data.clipboard);
        }).catch(function(errorException) {
            //that.showError(evt.item);
            that.showErrorNotification(errorException);
        });
    }

    makeLocalCopy = function(table, uid) {
        let that = this;
        var item = document.querySelector('div.tvp-node[data-record-table="' + table +'"][data-record-uid="' + uid +'"]');
        this.showInProgress(item);

        var params = new URLSearchParams();
        params.set('sourcePointer', item.dataset.parentPointer);
        params.set('uid', uid);

        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_makelocal'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Reload error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            var div = document.createElement('div');
            div.innerHTML = data.nodeHtml;
            that.initElements(div.firstElementChild);
            that.showSuccess(div.firstElementChild);
            item.parentNode.replaceChild(div.firstElementChild, item);
        }).catch(function(errorException) {
            that.showError(item);
            that.showErrorNotification(errorException);
        });
    }

    reloadRecord = function(table, uid) {
        let that = this;
        var items = document.querySelectorAll('div.tvp-node[data-record-table="' + table +'"][data-record-uid="' + uid +'"]');
        this.showInProgress(items);

        var params = new URLSearchParams();
        params.set('table', table);
        params.set('uid', uid);

        fetch(TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_reload'], {
            method: 'POST',
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params,
        }).then(async function(response) {
            if (!response.ok) {
                var errorJson = await response.json();
                throw new Error("Reload error", {cause: {response: response, errorJson: errorJson}});
            }
            return response.json()
        }).then(function(data) {
            var div = document.createElement('div');
            div.innerHTML = data.nodeHtml;
            for (var item of items) {
                var newItem = div.firstElementChild.cloneNode(true)
                that.initElements(newItem);
                that.showSuccess(newItem);
                item.parentNode.replaceChild(newItem, item);
            }
        }).catch(function(errorException) {
            that.showError(items);
            that.showErrorNotification(errorException);
        });
    }

    showInProgress = function(items)
    {
        if (items instanceof NodeList) {
            for (var item of items) {
                this.showInProgress(item);
            }
        } else {
            items.querySelector('.tvp-node-header').classList.add("toYellow");
        }
    }

    showSuccess = function(items)
    {
        if (items instanceof NodeList) {
            for (var item of items) {
                this.showSuccess(item);
            }
        } else {
            // flash green
            items.querySelector('.tvp-node-header').classList.remove('toYellow');
            items.querySelector('.tvp-node-header').classList.add('flashGreen');
        }
    }

    showError = function(items)
    {
        if (items instanceof NodeList) {
            for (var item of items) {
                this.showError(item);
            }
        } else {
            // flash red
            items.querySelector('.tvp-node-header').classList.remove('toYellow');
            items.querySelector('.tvp-node-header').classList.add('flashRed');
        }
    }
}

let pageLayout;
pageLayout = new PageLayout();
pageLayout.initialize();

export default pageLayout;
