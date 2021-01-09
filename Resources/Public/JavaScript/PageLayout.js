define([
    'bootstrap',
    'jquery',
    'TYPO3/CMS/Templavoilaplus/Tooltipster'
], function(bootstrap, $, Tooltipster) {
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
            updateAnimation: 'scale',
            side: 'left',
            interactive: true,
            trackTooltip: true,
            trigger: 'click',
            content: 'Loading...',
            contentAsHTML: true,
            functionBefore: function(instance, helper) {
                if (!$('#moduleWrapper').data('loadedContentElementWizard')) {
                    $('#moduleWrapper').data('loadedContentElementWizard', true);
                    $.ajax({
                        type: 'POST',
                        data: {
                            id: $('#moduleWrapper').data('tvpPageId')
                        },
                        url: TYPO3.settings.ajaxUrls['templavoilaplus_contentElementWizard'],
                        success: function(data) {
                            instance.content(data);
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                            instance.content('Request failed because of error: ' + textStatus);
                            $('#moduleWrapper').data('loadedContentElementWizard', false);
                        }
                    });
                }
            }
        });
        $('#navbarConfig').tooltipster({
            side: 'left',
            interactive: true,
            trigger: 'click'
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

        $('#moduleWrapper').removeClass('hidden');
        $('#moduleLoadingIndicator').addClass('hidden');
    }

    $(PageLayout.initialize);

    return PageLayout;
});
