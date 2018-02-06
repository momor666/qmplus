/* jshint ignore:start */
define([ 'jquery' ], function($) {
    return {
        init: function() {
            $(document).ready(function() {
                if ($('table.generaltable, table.calendartable, table.table, table.forumheaderlist').length > 0) {
                    $('table.generaltable, table.calendartable, table.table, table.forumheaderlist').each(function() {
                        var parent = $(this).parent();
                        if (!parent.hasClass('no-overflow')) {
                            $(this).wrap('<div class="no-overflow"></div>');
                        }
                    });
                }
            });
        }
    }
});
/* jshint ignore:end */
