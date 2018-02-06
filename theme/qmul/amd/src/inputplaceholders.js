/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            $( document ).ready(function() {
                log.debug('QMUL Input Placeholders JavaScript initialised');
                if ($( ".felement.ftext" ).length > 0) {
                    var element = $( ".felement.ftext" );
                    var inputs = $(element).children('input');

                    $(inputs).each(function() {
                        if ($(this).attr('placeholder') == undefined) {
                            var label = $(this).closest('.fitem').find('.fitemtitle').find('label');
                            var text = $(label).text().trim();
                            $(this).attr('placeholder', text);
                        }
                    });
                }
                if ($( ".felement.fgroup" ).length > 0) {
                    var elements = $( ".felement.group input" );

                    $(elements).each(function() {
                        var label = $(this).closest('.fitem').find('.fitemtitle').find('label');
                        var text = $(label).text().trim();
                        $(this).attr('placeholder', text);
                    });
                }
                if ($('.form-input').length > 0) {
                    var elements = $( ".form-input" );

                    $(elements).each(function() {
                        var input = $(this).find('input')[0];
                        if ($(input).attr('placeholder') == undefined) {
                            var label = $(this).prev().find('label');
                            var text = $(label).text().trim();
                            $(input).attr('placeholder', text);
                            $(label).remove();
                        }
                    });
                }
                if ($('.form-item').length > 0) {
                    var parents = $('.form-item');

                    $(parents).each(function() {
                        var input = $(this).find('input')[0];
                        if ($(input).attr('placeholder') == undefined) {
                            var label = $(this).find('label');
                            var text = $(label).text().trim();
                            $(input).attr('placeholder', text);
                        }
                        var textarea = $(this).find('textarea')[0];
                        if ($(textarea).attr('placeholder') == undefined) {
                            var label = $(this).find('label');
                            var text = $(label).text().trim();
                            $(textarea).attr('placeholder', text);
                        }
                    });
                }
                if ($( ".felement textarea" ).length > 0) {
                    var elements = $( ".felement textarea" );

                    $(elements).each(function() {
                        if ($(this).attr('placeholder') == undefined) {
                            var id = $(this).attr('id');
                            var label = $('label[for='+id+']');
                            var text = $(label).text().trim();
                            $(this).attr('placeholder', text);
                        }
                    });
                }
                var elements = $( "input" );
                $(elements).each(function() {
                    if ($(this).attr('placeholder') == undefined) {
                        var id = $(this).attr('id');
                        if ($('label[for="'+id+'"]').length > 0) {
                            var label = $('label[for="'+id+'"]');
                            var text = $(label).text().trim();
                            $(this).attr('placeholder', text);
                        } else if ($(this).attr('alt') != undefined) {
                            var text = $(this).attr('alt');
                            $(this).attr('placeholder', text);
                        }
                    }
                });
            });
        }
    }
});
/* jshint ignore:end */