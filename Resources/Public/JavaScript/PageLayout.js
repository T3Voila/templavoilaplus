define([
    'jquery'
], function($) {
    'use strict';
    /**
     * @exports Ppi/TemplaVoilaPlus/PageLayout
     */
    var PageLayout = {
    }

    /**
     * Initialize
     */
    PageLayout.initialize = function() {
        $('#moduleWrapper').removeClass('hidden');
        $('#moduleLoadingIndicator').addClass('hidden');
    }

    $(PageLayout.initialize);

    return PageLayout;
});
