/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            log.debug('QMUL Alert JavaScript initialised');
            $(document).ready(function() {
                $('.useralerts').on('closed.bs.alert', function () {
                    var cookiename = 'theme_qmul_alert'+$(this).data('number');
                    document.cookie = cookiename+"=closed; expires=0; path=/";
                })
            });
        }
    }
});
/* jshint ignore:end */