/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function() {
            $(document).ready(function() {
                pospopover = function() {
                    if ($(window).width() < 768) {
                        $("form .helpbutton a, h2 .helpbutton a").attr("data-placement", "right");
                        $('[data-toggle="popover"]').popover();
                    }
                    $("#page-admin-upgradesettings .helpbutton a").attr("data-placement", "right");
                }
                pospopover();
            });
            $(document).ajaxComplete(function () {
                if ($("#page-mod-assign-grader .gradeform .helpbutton .btn-link").length > 0) {
                    $("#page-mod-assign-grader .gradeform .helpbutton .btn-link").attr("data-placement", "left");
                }
            });
        }
    }
});
/* jshint ignore:end */
