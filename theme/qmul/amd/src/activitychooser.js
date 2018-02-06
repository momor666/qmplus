/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            log.debug('QMUL Activity Chooser JavaScript initialised');
            $(document).ready(function() {
                setTimeout(function() {
                    $(document.body).on('click', '.choosercontainer a.moreinfo', function(e) {
                        e.preventDefault();
                        var text = $(e.currentTarget).siblings('label').find('.typesummary').html();
                        $('.choosercontainer .instruction').html(text);
                        $('.choosercontainer .instruction').css('display', 'block');
                        $('.choosercontainer .instructionok').css('display', 'block');
                    });

                    $(document.body).on('click', '.choosercontainer .instructionok a', function(e) {
                        e.preventDefault();
                        $('.choosercontainer .instruction').css('display', 'none');
                        $('.choosercontainer .instructionok').css('display', 'none');
                    });
                }, 1000);
            });
        }
    }
});
/* jshint ignore:end */