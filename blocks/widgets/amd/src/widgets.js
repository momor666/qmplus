define(['jquery', 'core/ajax', 'core/notification', 'core/templates'], function ($, ajax, notification, templates) {
    var maxWidgets;

    function get_blockinstanceid($el) {
        var $blockel = $el.closest('.block');
        if (!$blockel.length) {
            return null;
        }
        var id = $blockel.attr('id');
        id = parseInt(id.substring(4), 10);
        return id;
    }

    function get_widgets_element($el) {
        return $el.closest('.widgets-main').find('.widgets');
    }

    function update_no_widgets_message($el) {
        var $nowidgets = $el.closest('.widgets-main').find('.nowidgets');
        var $widgets = get_widgets_element($el);
        if ($widgets.find('.widgets-widget').length) {
            $nowidgets.removeClass('show');
        } else {
            $nowidgets.addClass('show');
        }
    }

    function add_widget(blockinstanceid, type, $targ) {
        var promises = ajax.call([{
            methodname: 'block_widgets_add',
            args: {
                blockinstanceid: blockinstanceid,
                type: type
            }
        }]);
        promises[0].done(function (resp) {
            if (resp.widget) {
                // Add the new widget to the 'My widgets' panel.
                templates.render('block_widgets/widget', resp.widget)
                    .then(function (html) {
                        get_widgets_element($targ).append(html);
                        update_no_widgets_message($targ);
                        $targ.removeClass('loading').addClass('inuse');
                        check_max();
                    });
            }
        }).fail(notification.exception);
    }

    function remove_widget(blockinstanceid, type, $targ) {
        var promises = ajax.call([{
            methodname: 'block_widgets_remove',
            args: {
                blockinstanceid: blockinstanceid,
                type: type
            }
        }]);
        promises[0].done(function () {
            // Remove the widget from the panel.
            get_widgets_element($targ).find('.widgettype-'+type).remove();
            update_no_widgets_message($targ);
            $targ.removeClass('loading').removeClass('inuse');
            check_max();
        }).fail(notification.exception);
    }

    function toggle_widget(e) {
        e.preventDefault();
        var $targ = $(e.currentTarget);
        var blockinstanceid = get_blockinstanceid($targ);
        var type = $targ.data('type');
        if (!blockinstanceid || !type) {
            return; // Missing vital details - give up.
        }
        $targ.addClass('loading');
        if ($targ.hasClass('inuse')) {
            remove_widget(blockinstanceid, type, $targ);
        } else {
            if (!$targ.closest('.widgets-availableholder').hasClass('maxreached')) {
                add_widget(blockinstanceid, type, $targ);
            }
        }
    }

    /**
     * Prevent more widgets from being added if we've already reached the maximum allowed.
     */
    function check_max() {
        $('.widgets-main').each(function(idx, main) {
            var $main = $(main);
            var $holder = $main.find('.widgets-availableholder');
            var usedCount = $main.find('.widgets-widget').length;
            var availCount = $holder.find('.widgets-available').length;
            if (usedCount >= maxWidgets && availCount > usedCount) {
                $holder.addClass('maxreached');
            } else {
                $holder.removeClass('maxreached');
            }
        });
    }

    return {
        init: function(opts) {
            maxWidgets = opts.maxwidgets;
            check_max();
            var $body = $('body');
            $body.on('click', '.widgets-available', toggle_widget);
        }
    };
});