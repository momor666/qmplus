/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            $( document ).ready(function() {
                log.debug('QMUL Forum Icons JavaScript initialised');
                if ($( ".forumpost" ).length > 0) {
                    $(".forumpost .side.options .commands a, .forumpost .side .options .commands a").each(function() {
                        if ($(this).html() == M.util.get_string('permalink', 'forum')) {
                            $(this).addClass('permalink');
                            $(this).attr('title', M.util.get_string('permalink', 'forum'));
                        } else if ($(this).html() == M.util.get_string('reply', 'forum')) {
                            $(this).addClass('reply');
                            $(this).attr('title', M.util.get_string('reply', 'forum'));
                        } else if ($(this).html() == M.util.get_string('edit', 'forum')) {
                            $(this).addClass('edit');
                            $(this).attr('title', M.util.get_string('edit', 'forum'));
                        } else if ($(this).html() == M.util.get_string('prune', 'forum')) {
                            $(this).addClass('split');
                            $(this).attr('title', M.util.get_string('prune', 'forum'));
                        } else if ($(this).html() == M.util.get_string('delete', 'forum')) {
                            $(this).addClass('delete');
                            $(this).attr('title', M.util.get_string('delete', 'forum'));
                        } else if ($(this).html() == M.util.get_string('parent', 'forum')) {
                            $(this).addClass('parent');
                            $(this).attr('title', M.util.get_string('parent', 'forum'));
                        } else {
                            $(this).addClass('noicon');
                        }
                    });

                    $(".forumpost .side.options").each(function() {
                        var trimmed = $(this).html().replace(/&nbsp;/gi,'');
                        if (!trimmed) {
                            $(this).html(trimmed);
                        }
                    });
                }
            });
        }
    }
});
/* jshint ignore:end */