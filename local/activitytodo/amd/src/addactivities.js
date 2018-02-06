define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {
    var todolistCmids = [];
    var icons = {};
    var excludedmods = [];

    function addIconsToActivities() {
        $('.activityinstance').each(function(idx, el) {
            var $el = $(el);
            if (isExcludedMod($el)) {
                return;
            }
            var cmid = getCmid($el);
            if (cmid) {
                var isAdd = (todolistCmids.indexOf(cmid) === -1);
                setIcon($el, isAdd, true);
            }
        });
    }

    function isExcludedMod($el) {
        var $holder = $el.closest('.activity');
        var i;
        for (i in excludedmods) {
            if (excludedmods.hasOwnProperty(i)) {
                if ($holder.hasClass(excludedmods[i])) {
                    return true;
                }
            }
        }
        return false;
    }

    function setIcon($el, isAdd, append) {
        templates.render('local_activitytodo/icon', {
            isAdd: isAdd
        }).then(function(html) {
            if (append) {
                $el.prepend(html);
            } else {
                $el.replaceWith(html);
            }
        }).fail(notification.exception);
    }

    function getLoadingIcon() {
        return $(icons.loading);
    }

    function getCmid($el) {
        var $holder = $el.closest('.activity');
        if (!$holder) {
            return null;
        }
        var parts = $holder.attr('id').split('-');
        if (parts.length < 2) {
            return null;
        }
        return parts[1];
    }

    function isAdding($el) {
        return $el.hasClass('activitytodo-add');
    }

    function toggleTodolistItem(e) {
        e.preventDefault();

        var $icon = $(e.currentTarget);
        var cmid = getCmid($icon);
        if (!cmid) {
            return;
        }

        var add = isAdding($icon);
        var $loadingicon = getLoadingIcon();
        $icon.replaceWith($loadingicon);
        $icon = $loadingicon;

        var method = add ? 'local_activitytodo_add' : 'local_activitytodo_remove';
        var promises = ajax.call([{
            methodname: method,
            args: {
                cmid: cmid
            }
        }]);

        promises[0].done(function() {
            setIcon($icon, !add, false);
        }).fail(notification.exception);
    }

    return {
        init: function(opts) {
            todolistCmids = opts.todolistCmids;
            icons = opts.icons;
            excludedmods = opts.excludedmods;
            addIconsToActivities();
            $('body').on('click', '.activitytodo', toggleTodolistItem);
        }
    };
});