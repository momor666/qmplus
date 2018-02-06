/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            log.debug('QMUL Block Icons JavaScript initialised');
            $(document).ready(function() {
                if ($(".block-hider-hide").length > 0) {
                    $(".block-hider-hide").each(function() {
                        $(this).before('<i class="glyphicon glyphicon-minus block-hide"></i>');
                    });
                }
                if ($(".block-hider-show").length > 0) {
                    $(".block-hider-show").each(function() {
                        $(this).before('<i class="glyphicon glyphicon-plus block-show"></i>');
                    });
                }
                if ($(".block_action .moveto").length > 0) {
                    $(".block_action .moveto").each(function() {
                        $(this).before('<i class="glyphicon glyphicon-chevron-left block-dock"></i>');
                    });
                }
            });
        }
    }
});
/* jshint ignore:end */