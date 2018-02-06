/* jshint ignore:start */
define(['jquery', 'jqueryui', 'core/log'], function($, ui, log) {
    return {
        init: function () {
            log.debug('QMUL Slideout Replace Icons JS initialised');
            var plugin = this;
            $(document).ready(function() {
                plugin.replace();
                Y.use('moodle-core-event', function() {
                    Y.Global.on(M.core.globalEvents.BLOCK_CONTENT_UPDATED, function() {
                        plugin.replace();
                        console.log(plugin.done);
                    });
                });

                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type == 'childList') {
                            plugin.replace();
                        }
                    });
                });

                // configuration of the observer:
                var config = { attributes: true, childList: true, characterData: true }

                // pass in the target node, as well as the observer options
                observer.observe(document.querySelector('body'), config);
            });
        },
        replacements: [],
        replace: function() {
            var plugin = this;
            if ($( "img.icon, img.iconsmall, img.smallicon" ).length > 0) {
                var data = {};
                $( "img.icon, img.iconsmall, img.smallicon" ).each(function() {
                    if ($(this).parents('.editor_atto').length) {
                        return true;
                    }
                    var replaceme = $(this);
                    var id = replaceme.attr('id');
                    var sibling = replaceme.next();
                    if (sibling.length === 0 || !$(sibling).hasClass('glyphicon')) {
                        var icon = replaceme.attr('src');
                        icon = icon.split('/');
                        icon = icon.slice(-2);
                        var realicon = icon[0]+'/'+icon[1];
                        replaceme.attr('data-replaceme', 1);
                        replaceme.attr('data-iconname', realicon);
                        var classes = replaceme.attr('class');

                        data[realicon] = { iconname: realicon, classes: classes };
                    }
                });
                $.ajax({
                    url: M.cfg.wwwroot+"/theme/qmul/lib/icon.php",
                    context: document.body,
                    method: "POST",
                    data: {
                        icons: data
                    }
                }).done(function(data) {
                    for (var iconname in data) {
                        var html = data[iconname];
                        $('[data-iconname="'+iconname+'"]').each(function() {
                            if (html == '') {
                                $(this).removeClass('hidden');
                            } else {
                                var sibling = $(this).next();
                                if (sibling.length === 0 || !$(sibling).hasClass('glyphicon')) {
                                    $(this).after(html);
                                    $(this).addClass('hidden');
                                }
                            }
                        });
                    }
                });
            }
        }
    }
});
/* jshint ignore:end */