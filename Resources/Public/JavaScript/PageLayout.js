define([
    'bootstrap',
    'jquery',
    'TYPO3/CMS/Templavoilaplus/Tooltipster',
    'TYPO3/CMS/Templavoilaplus/Sortable.min'
], function(bootstrap, $, Tooltipster, Sortable) {
    'use strict';

    /**
     * @exports Tvp/TemplaVoilaPlus/PageLayout
     */
    var PageLayout = {
    }

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
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                            instance.content('Request failed because of error: ' + textStatus);
                            $('#moduleWrapper').data('loadedContentElementWizard', false);
                        }
                    });
                }
            },
            functionReady: function(instance, helper) {
                // Init Drag&Drop
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
            },
            functionAfter: function(instance, helper) {
                $('#moduleShadowing').addClass('hidden');
            }
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
                    pull: function (to, from) {
//                         to.el.addClass('green');
                        if (to.el.id === 'navbarClipboard') {
                            return 'clone';
                        }
                        if (to.el.id === 'navbarTrash') {
                            return true;
                        }
                        return true;
                    },
                    put: function (to, from, el) {
//                         console.log(el);
//                         $(to.el).addClass('green');
                    },
                    revertClone: true
                },
                ghostClass: "iAmGhost",
                dragable: '.sortableItem',
                animation: 150,
                swapThreshold: 0.65,
                onStart: function (/**Event*/evt) {
console.log('onStart');
                    $('#navbarClipboard').removeClass('disabled');
                    $('#navbarTrash').removeClass('disabled');
                },
                onEnd: function (/**Event*/evt) {
console.log('onEnd');
                    $('#navbarClipboard').addClass('disabled');
                    $('#navbarTrash').addClass('disabled');
                },
                onMove: function (/**Event*/evt, /**Event*/originalEvent) {
console.log('onMove');
                      $('.iAmGhost').addClass('blue');
//                      $(evt.to).addClass('blue');
                     console.log(evt);
//                     return false;
                },
                onAdd: function (/**Event*/evt) {
console.log('onAdd');
console.log(evt);
                    if (evt.pullMode === 'clone') {
                        // Insert from somewhere
                        // source/destination pages:694:sDEF:lDEF:field_breitOben:vDEF:1
                        $.ajax({
                            type: 'POST',
                            data: {
                                destinationPointer: 'pages:1:sDEF:lDEF:field_content:vDEF:' + evt.newDraggableIndex.toString(),
                                elementRow: []
                            },
                            url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElement_insert'],
                            success: function(data) {
                                // Add data to content
//                                 instance.content(data);
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                            }
                        });
                    }
                    var el = evt.item;
                    el.parentNode.removeChild(el);
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
                var el = evt.item;
                el.parentNode.removeChild(el);
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
    }

    $(PageLayout.initialize);

    return PageLayout;
});
