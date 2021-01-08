define([
    'jquery',
    'TYPO3/CMS/Templavoilaplus/Tooltipster'
], function($) {
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
        $('#moduleWrapper').removeClass('hidden');
        $('#moduleLoadingIndicator').addClass('hidden');

        $('#navbarConfig').tooltipster({
            theme: 'tooltipster-noir',
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
                setTimeout(() => {
                    $('body').removeClass('animationTransition');
                }, 500);
            } else {
                $('body').addClass('animationTransition');
                setTimeout(() => {
                    $('body').removeClass('dark-mode-on');
                }, 150);
                setTimeout(() => {
                    $('body').removeClass('animationTransition');
                }, 500);
            }
        });
    }

    $(PageLayout.initialize);

    return PageLayout;
});
