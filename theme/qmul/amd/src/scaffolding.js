/* jshint ignore:start */
define([ 'jquery', 'core/log' ], function($, log) {
    var rebuildForms = function() {
        var modSettingsIDRegex = /^page-mod-.*-mod$/;
        var onModSettings = modSettingsIDRegex.test($('body').attr('id')) && location.href.indexOf("modedit") > -1;
        var onCourseSettings = $('body').attr('id') === 'page-course-edit';
        var onSectionSettings = $('body').attr('id') === 'page-course-editsection';
        var onUsereditPage = $('body').attr('id') === 'page-admin-user-editadvanced';

        if (onModSettings || onCourseSettings || onSectionSettings || onUsereditPage) {
            var vital = [
                ':first',
                '#page-course-edit #id_descriptionhdr',
                '#id_contentsection',
                '#id_content',
                '#page-mod-choice-mod #id_optionhdr',
                '#page-mod-assign-mod #id_availability',
                '#page-mod-assign-mod #id_submissiontypes',
                '#page-mod-workshop-mod #id_gradingsettings',
                '#page-mod-choicegroup-mod #id_miscellaneoussettingshdr',
                '#page-mod-choicegroup-mod #id_groups',
                '#page-mod-scorm-mod #id_packagehdr',
            ];
            vital = vital.join();

            $('#mform1').addClass('row');
            $('#mform1 > fieldset').not(vital).wrapAll('<div class="sl-advanced col-lg-4" />');

            $(".sl-advanced").append($(".collapsible-actions"));

            var mainForm = $("#mform1 fieldset:first");
            var appendTo = $("#mform1 fieldset:first .fcontainer");

            var required = $("#mform1 > fieldset").not("#mform1 > fieldset:first");
            for (var i = 0; i < required.length; i++) {
                var content = $(required[i]).find('.fcontainer');
                $(appendTo).append(content);
                $(required[i]).remove();
            }
            $(mainForm).wrap('<div class="sl-main col-lg-8" />');

            var description = $("#mform1 fieldset:first .fitem_feditor:not(.required)");

            if (onModSettings && description) {
                var editingassignment = $('body').attr('id') == 'page-mod-assign-mod';
                var editingchoice = $('body').attr('id') == 'page-mod-choice-mod';
                var editingturnitin = $('body').attr('id') == 'page-mod-turnitintool-mod';
                var editingworkshop = $('body').attr('id') == 'page-mod-workshop-mod';
                if (!editingchoice && !editingassignment && !editingturnitin && !editingworkshop) {
                    $(appendTo).append(description);
                    $(appendTo).append($('#fitem_id_showdescription'));
                }
            }

            $("#mform1 fieldset:first legend").css('display', 'none');

            var savebuttons = $("#mform1 #fgroup_id_buttonar");
            $(mainForm).append(savebuttons);
        }
    };
    var tocSearchCourse = function(dataList) {
        var i;
        var ua = window.navigator.userAgent;
        if (ua.indexOf('MSIE ') || ua.indexOf('Trident/')) {
            // We have reclone datalist over again for IE, or the same search fails the second time round.
            dataList = $("#toc-searchables").find('li').clone(true);
        }
        var input = $("#toc-search-input");
        var results = $('#toc-search-results');

        // TODO - for 2.7 process search string called too many times?
        var searchString = input.val();
        searchString = processSearchString(searchString);

        if (searchString.length === 0) {
            results.html('');
            input.removeClass('state-active');

        } else {
            input.addClass('state-active');
            var matches = [];
            for (i = 0; i < dataList.length; i++) {
                var dataItem = dataList[i];
                if (processSearchString($(dataItem).text()).indexOf(searchString) > -1) {
                    matches.push(dataItem);
                }
            }
            results.html(matches);
        }
    }
    var processSearchString = function(searchString) {
        searchString = searchString.trim().toLowerCase();
        return (searchString);
    }
    var courseSearch = function(type, searchString) {
        var i;
        if (type == 'all') {
            var userid = 0;
        }
        if (type == 'my') {
            var userid = $('#userid').data('userid');
        }

        searchString = processSearchString(searchString);
        var results = $('.searchbox .searchresults');
        var search = $(".searchbox .coursesearch");

        var data = {
            userid: userid,
            search: searchString
        };

        if (searchString.length === 0) {
            results.html('');
            search.removeClass('state-active');
        } else {
            var matches = new Array();
            $.ajax({
                url: M.cfg.wwwroot+"/theme/qmul/lib/searchcourses.php",
                context: document.body,
                method: "POST",
                data: data,
            }).done(function(courses) {
                for (i = 0; i < courses.length; i++) {
                    var course = courses[i];
                    var rx = new RegExp('('+searchString+')',"gi");
                    var text = course.fullname.replace(rx, '<span>$1</span>');
                    var html = "<li data-id='"+course.id+"'>";
                    html += "<a href='"+course.url+"'>";
                    html += "<div class='coursename'>";
                    html += text;
                    html += "</div>";
                    html += "</a>";
                    html += "</li>";
                    matches.push(html);

                }
                results.html(matches);
                $('.searchbox .searchresults li:first-child').addClass('selected');
                return;
            });
        }
    };
    return {
        init: function() {
            var plugin = this;
            $(document).ready(function() {
                //rebuildForms();

                var dataList = $("#toc-searchables").find('li').clone(true);
                $('.courseheader').on('keyup', '#toc-search-input', function() {
                    tocSearchCourse(dataList);
                });

                $('body').on('click', '.searchbox .searchoptions > div', function() {
                    if ($(this).hasClass('active')) {
                        return;
                    }
                    $('.searchbox .searchoptions > div').toggleClass('active');
                    $('.searchcontainer .searchbox .coursesearch').keyup();
                });

                $("#mod_quiz_navblock.block .header").on('click', function() {
                    $("#mod_quiz_navblock.block .header").toggleClass("minimize");
                    $("#mod_quiz_navblock.block .card-block").slideToggle();
                });

                var timeout;

                var allModulesList = $(".searchcontainer .options .allmodules").find('li').clone(true);
                var myModulesList = $(".searchcontainer .options .mymodules").find('li').clone(true);
                $('.searchcontainer').on('keyup', '.searchbox .coursesearch', function(e) {
                    clearTimeout(timeout);
                    if( this.value.length < 3 ) {
                        if (e.which == 8) {
                            $('.searchbox .searchresults').html('');
                        }
                        return;
                    }
                    if (e.which == 40) {
                        var selected = $('.searchbox .searchresults li.selected');
                        var next = selected.next();
                        if (next.length > 0) {
                            next.addClass('selected');
                            selected.removeClass('selected');
                        }
                        return;
                    }
                    if (e.which == 38) {
                        var selected = $('.searchbox .searchresults li.selected');
                        var prev = selected.prev();
                        if (prev.length > 0) {
                            prev.addClass('selected');
                            selected.removeClass('selected');
                        }
                        return;
                    }
                    if (e.which == 13) {
                        var selectedlink = $('.searchbox .searchresults li.selected a');
                        if (selectedlink.length > 0) {
                            var link = selectedlink.attr('href');
                            window.location.href = link;
                        }
                        return;
                    }

                    var searchString = this.value;
                    var type = $('.searchbox .searchoptions div.active').data('type');
                    if (type == 'my') {
                        var dataList = myModulesList;
                    } else {
                        var dataList = allModulesList;
                    }

                    timeout = setTimeout(function(){
                        courseSearch(type, searchString);
                    }, 400);
                });
            });
        },
    }
});
/* jshint ignore:end */