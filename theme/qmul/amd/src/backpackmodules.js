/* jshint ignore:start */
define(['jquery', 'core/templates', 'core/log'], function($, templates, log) {
    return {
        init: function () {
            log.debug('QMUL Backpack Modules JavaScript initialised');
            var plugin = this;
            $(document).ready(function() {
                $('body').on('click', '.backpack .modules .module .pincourse', function(e) {
                    e.preventDefault();
                    if ($(this).hasClass('disabled')) {
                        return false;
                    }
                    $(this).addClass('disabled');
                    var module = $(this).parent('.module');
                    var courseid = module.data('courseid');
                    var state = module.data('state');
                    var userid = module.data('userid');
                    var button = $(this);
                    $.ajax({
                        url: M.cfg.wwwroot+"/theme/qmul/lib/pincourse.php",
                        context: document.body,
                        data: {courseid: courseid, state:state, userid:userid},
                        method: 'GET'
                    }).done(function(response) {
                        if (response.success == 1) {
                            plugin.process_pinned_course(module, courseid, response.newpreference);
                            button.toggleClass('pinned');
                            module.toggleClass('pinned');
                            if (response.newpreference == "1") {
                                module.data('state', 1);
                            } else {
                                module.data('state', 0);
                            }
                            button.removeClass('disabled');
                        }
                    }).fail(function( xhr, textStatus, errorThrown) {

                    });
                });
                $('body').on('click', '.backpack .allmodules a.loadmore', function(e) {
                    if ($(this).hasClass('disabled')) {
                        return false;
                    }
                    $(this).addClass('disabled');
                    var userid = $(this).data('userid');
                    var currentloaded = $(this).data('currentloaded');
                    var button = $(this);
                    $(this).append('<i class="glyphicon glyphicon-refresh spin"></i>');
                    $.ajax({
                        url: M.cfg.wwwroot+"/theme/qmul/lib/loadmorecourses.php",
                        context: document.body,
                        data: {userid: userid, currentloaded: currentloaded},
                        method: 'GET'
                    }).done(function(response) {
                        button.before(response.html);
                        if (response.loadmore == false) {
                            button.remove();
                        } else {
                            button.data('currentloaded', currentloaded + 10);
                            button.removeClass('disabled');
                            button.find('i').remove();
                        }
                    }).fail(function( xhr, textStatus, errorThrown) {

                    });
                });
                $('body').on('click', '.backpack .modules .modulebox .pincourse', function(e) {
                    e.preventDefault();
                    if ($(this).hasClass('disabled')) {
                        return false;
                    }
                    $(this).addClass('disabled');
                    var module = $(this).parent('.modulebox');
                    var courseid = module.data('courseid');
                    var state = module.data('state');
                    var userid = module.data('userid');
                    var button = $('.backpack .allmodules .module[data-courseid='+courseid+']');
                    $.ajax({
                        url: M.cfg.wwwroot+"/theme/qmul/lib/pincourse.php",
                        context: document.body,
                        data: {courseid: courseid, state:state, userid:userid},
                        method: 'GET'
                    }).done(function(response) {
                        if (response.success == 1) {
                            plugin.process_pinned_course(module, courseid, response.newpreference);
                            button.toggleClass('pinned');
                            module = button.parent('.module');
                            module.toggleClass('pinned');
                            if (response.newpreference == "1") {
                                module.data('state', 1);
                            } else {
                                module.data('state', 0);
                            }
                            button.removeClass('disabled');
                        }
                    }).fail(function( xhr, textStatus, errorThrown) {

                    });
                });
                $('body').on('click', '.qmultabitem.pincoursetab .pincourse', function(e) {
                    e.preventDefault();
                    if ($(this).hasClass('disabled')) {
                        return false;
                    }
                    $(this).addClass('disabled');
                    var courseid = $(this).data('courseid');
                    var state = $(this).data('state');
                    var userid = $(this).data('userid');
                    var button = $(this);
                    $.ajax({
                        url: M.cfg.wwwroot+"/theme/qmul/lib/pincourse.php",
                        context: document.body,
                        data: {courseid: courseid, state:state, userid:userid},
                        method: 'GET'
                    }).done(function(response) {
                        if (response.success == 1) {
                            button.toggleClass('pinned');
                            if (response.newpreference == "1") {
                                button.data('state', 1);
                                button.attr('title', M.util.get_string('unpinthismodule', 'theme_qmul'));
                                button.attr('original-title', M.util.get_string('unpinthismodule', 'theme_qmul'));
                                button.attr('data-original-title', M.util.get_string('unpinthismodule', 'theme_qmul'));
                            } else {
                                button.data('state', 0);
                                button.attr('title', M.util.get_string('pinthismodule', 'theme_qmul'));
                                button.attr('original-title', M.util.get_string('pinthismodule', 'theme_qmul'));
                                button.attr('data-original-title', M.util.get_string('pinthismodule', 'theme_qmul'));
                            }
                            button.removeClass('disabled');
                        }
                    }).fail(function( xhr, textStatus, errorThrown) {

                    });
                });
            });
        },
        process_pinned_course: function(module, courseid, pinned) {
            if (pinned == 0) {
                $(".pinnedmodules .modulebox[data-courseid="+courseid+"]").remove();
            } else {
                var coursename = module.data('name');
                var categoryname = module.data('categoryname');
                var warning = module.data('warning');
                var invisible = module.data('invisible');
                var userid = module.data('userid');
                var url = module.data('url');

                var overviewfile = module.data('overviewfile');

                var context = { id: courseid, userid: userid, fullname: coursename, categoryname: categoryname, warning: warning, invisible: invisible, url: url, overviewfile: overviewfile };

                if ($(".pinnedmodules .nopins").length > 0) {
                    $(".pinnedmodules .nopins").remove();
                }

                if ($(".pinnedmodules .modulebox[data-courseid="+courseid+"]").length > 0) {
                    $(".pinnedmodules .modulebox[data-courseid="+courseid+"]").remove();
                }
                var promise = templates.render('theme_qmul/pinnedmodule', context);
                promise.done(function(source, javascript) {
                    templates.appendNodeContents($(".backpack .pinnedmodules"), source, javascript);
                });
            }
        }
    }
});
/* jshint ignore:end */