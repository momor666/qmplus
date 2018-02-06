/* jshint ignore:start */
define([ 'theme_qmul/sticky-table-headers', 'jquery' ], function(STH, $) {
    return {
        init: function() {
            $(document).ready(function() {
                if ($('table:not(#commentstable):not(.user-grade):not(.gradereport-grader-table)').length > 0) {
                    $('table:not(#commentstable):not(.user-grade):not(.gradereport-grader-table)').each(function() {
                        STH.manager.add($(this).get(0), null);
                    });
                }
            });
        }
    }
});
/* jshint ignore:end */