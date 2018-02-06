define(['jquery', 'core/ajax', 'core/notification', 'core/modal_factory', 'core/modal_events', 'core/templates', 'jqueryui'],
    function ($, ajax, notification, ModalFactory, ModalEvents, templates) {
        var icons;

        function saveUpdatedSort(e) {
            var cmids = $(e.target).sortable('toArray', {attribute: 'data-cmid'});
            var promises = ajax.call([{
                methodname: 'local_activitytodo_sort',
                args: {
                    cmids: cmids
                }
            }]);
            promises[0].fail(notification.exception);
        }

        function getCmid($el) {
            var $holder = $el.closest('.activitytodo-item');
            if (!$holder.length) {
                return null;
            }
            return $holder.data('cmid');
        }

        function getLoadingIcon() {
            return $(icons.loading);
        }

        function getNoteContent($el) {
            var $noteholder = $el.closest('.activitytodo-note');
            if (!$noteholder.length) {
                $noteholder = $el.find('.activitytodo-note');
            }
            var content = $noteholder.data('content');
            if (content === undefined) {
                content = $noteholder.find('.activitytodo-notecontent').html();
            }
            return content;
        }

        function storeNoteContent($el, content) {
            var $noteholder = $el.closest('.activitytodo-note');
            $noteholder.data('content', content);
        }

        function setNoteIcon($el, hasnote) {
            templates.render('local_activitytodo/noteicon', {
                hasnote: hasnote
            }).then(function (html) {
                $el.replaceWith(html);
            }).fail(notification.exception);
        }

        function updateNote(e) {
            e.preventDefault();
            var $el = $(e.currentTarget);
            var cmid = getCmid($el);
            if (!cmid) {
                return;
            }
            var content = getNoteContent($el);
            ModalFactory.create({
                title: M.util.get_string('updatenote', 'local_activitytodo'),
                body: '<textarea name="notecontent" class="activitytodo-editnote">' + content + '</textarea>',
                type: ModalFactory.types.SAVE_CANCEL
            }).done(function (modal) {
                var $root = modal.getRoot();
                $root.on(ModalEvents.shown, function() {
                    $root.find('textarea[name="notecontent"]').focus();
                });
                $root.on(ModalEvents.save, function () {
                    // Handle clicking on 'Save'.
                    var $textarea = $root.find('textarea[name="notecontent"]');
                    var newContent = $.trim($textarea.val());

                    var $loadingicon = getLoadingIcon();
                    $el.replaceWith($loadingicon);
                    $el = $loadingicon;

                    var promises = ajax.call([{
                        methodname: 'local_activitytodo_update_note',
                        args: {
                            cmid: cmid,
                            note: newContent
                        }
                    }]);

                    promises[0].done(function () {
                        setNoteIcon($el, !!newContent);
                        storeNoteContent($el, newContent);
                    }).fail(notification.exception);
                });
                $root.on(ModalEvents.hidden, function() {
                    modal.destroy();
                });
                modal.show();
            });
        }

        function getDuedate($el) {
            $el = getDuedateElement($el);
            var $ipt = $el.find('input');
            if ($ipt.length) {
                return $ipt.val();
            }
            return $el.text();
        }

        function getDuedateElement($el) {
            return $el.closest('.activitytodo-item').find('.activitytodo-showduedate');
        }

        function setDuedateIcon($el, editDuedate) {
            templates.render('local_activitytodo/duedateicon', {
                editduedate: editDuedate
            }).then(function (html) {
                $el.closest('.activitytodo-item').find('.activitytodo-editduedate').replaceWith(html);
            }).fail(notification.exception);
        }

        function editDuedate(e) {
            e.preventDefault();
            var $el = $(e.currentTarget);
            var $duedate = getDuedateElement($el);
            var $ipt = $duedate.find('input');
            var val;
            if ($ipt.length) {
                saveDuedate($duedate); // Save if icon clicked on a second time.
            } else {
                val = $duedate.text();
                $ipt = $('<input type="text" value="' + val + '">');
                $duedate.html($ipt);
                $duedate.find('input')
                    .datepicker({
                        dateFormat: $duedate.data('format'),
                        onSelect: function () {
                            $(this).focus();
                        }
                    })
                    .keydown(function (e) {
                        if (e.which === 13) {
                            saveDuedate($duedate); // Save if 'Enter' pressed.
                            $('#ui-datepicker-div').css('display', 'none');
                        }
                    })
                    .focus();
                setDuedateIcon($el, true);
            }
        }

        function saveDuedate($el) {
            var cmid = getCmid($el);
            if (!cmid) {
                return;
            }
            var duedate = getDuedate($el);
            var promises = ajax.call([{
                methodname: 'local_activitytodo_update_duedate',
                args: {
                    cmid: cmid,
                    duedate: duedate
                }
            }]);
            promises[0].done(function (resp) {
                getDuedateElement($el).html(resp.duedate);
                if (resp.overdue) {
                    $el.closest('.activitytodo-item').addClass('activitytodo-overdue').addClass('alert-danger');
                } else {
                    $el.closest('.activitytodo-item').removeClass('activitytodo-overdue').removeClass('alert-danger');
                }
                setDuedateIcon($el, false);
            });
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
                var $itemroot = $el.closest('.activitytodo-item');
                var activityName = $itemroot.find('.activitytodo-activityname').text();
                var noteContent = getNoteContent($itemroot).replace(/"/g, '&quot;');
                var duedate = getDuedate($itemroot);
                var message = M.util.get_string('todoitemremoved', 'local_activitytodo', activityName);
                message += ' <a href="#" class="activitytodo-undoremove"' +
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
            init: function (opts) {
                icons = opts.icons;
                var $items = $('.activitytodo-items');
                $items.sortable();
                $items.on('sortupdate', saveUpdatedSort);
                $items.disableSelection();
                $items.on('click', '.activitytodo-noteicon', updateNote);
                $items.on('click', '.activitytodo-editduedate', editDuedate);
                $items.on('click', '.activitytodo-remove', removeTodo);
                $('body').on('click', '.activitytodo-undoremove', undoRemove);
            }
        };
    });
