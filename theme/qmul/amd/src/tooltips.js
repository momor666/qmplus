/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            $( document ).ready(function() {
        		 $('[data-toggle="tooltip"]').tooltip()
            });
        }
    }
});
/* jshint ignore:end */