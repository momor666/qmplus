/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            $( document ).ready(function() {
                log.debug('QMUL Question Flag JavaScript initialised');
                if ($( ".questionflag" ).length > 0) {
                    $(".questionflag").on('click', function() {
                        if ($(this).hasClass('label-warning')) {
                            $(this).removeClass('label-warning').addClass('label-danger');
                        } else if ($(this).hasClass('label-danger')) {
                            $(this).removeClass('label-danger').addClass('label-warning');
                        }

                    })
                }
            });
        }
    }
});
/* jshint ignore:end */