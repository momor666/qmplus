define(['jquery', 'core/ajax', 'core/notification'],
    function ($, ajax, notification) {

        function getCmid($el) {
            var $holder = $el.closest('.todoitem');
            if (!$holder.length) {
                return null;
            }
            return $holder.data('cmid');
        }

        function removeTodo(e) {
            e.preventDefault();
            var $el = $(e.currentTarget);
            var cmid = getCmid($el);
            if (!cmid) {
                return;
            }
            var promises = ajax.call([{
                methodname: 'local_activitytodo_remove',
                args: {
                    cmid: cmid
                }
            }]);
            promises[0].done(function () {
                var $itemroot = $el.closest('.widget-item');
                var activityName = $itemroot.find('.activityname').text();
                var noteContent = $el.data('note').replace(/"/g, '&quot;');
                var duedate = $el.data('duedate');
                var message = M.util.get_string('todoitemremoved', 'local_activitytodo', activityName);
                message += ' <a href="#" class="widget-activitytodo-undoremove"' +
                    ' data-cmid="' + cmid + '"' +
                    ' data-note="' + noteContent + '" ' +
                    ' data-duedate="' + duedate + '" ' +
                    '>' + M.util.get_string('undo', 'local_activitytodo') + '</a>';

                notification.addNotification({
                    type: 'success',
                    message: message
                });

                $itemroot.remove();
            });
        }

        function undoRemove(e) {
            e.preventDefault();
            var $el = $(e.currentTarget);
            var cmid = $el.data('cmid');
            var note = $el.data('note');
            var duedate = $el.data('duedate');
            var calls = [{
                methodname: 'local_activitytodo_add',
                args: {
                    cmid: cmid
                }
            }];
            if (note) {
                // Send the note, if it has some content.
                calls.push({
                    methodname: 'local_activitytodo_update_note',
                    args: {
                        cmid: cmid,
                        note: note
                    }
                });
            }
            if (duedate) {
                // Send the duedate, if it has some content.
                calls.push({
                    methodname: 'local_activitytodo_update_duedate',
                    args: {
                        cmid: cmid,
                        duedate: duedate
                    }
                });
            }

            var promises = ajax.call(calls);

            // Reload the page when all promises completed.
            $.when.apply($, promises).done(function () {
                window.location.reload(true);
            });
        }

        return {
            init: function () {
                var $items = $('.widgettype-activitytodo');
                $items.on('click', '.todoremove', removeTodo);
                $('body').on('click', '.widget-activitytodo-undoremove', undoRemove);
            }
        };
    });
