/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            var plugin = this;
            $(document).ready(function () {
                var $gradeParent, pageHeaderHeight = 0, headerRowTop = 0;
                $gradeParent = $('.path-grade-report-grader div.gradeparent');
                if ($gradeParent.length>0) {
                    // Initialise the page layout details.
                    var header = $('nav');
                    var fixed = false;
                    if (header.length) {
                        if (header.css('position') === 'fixed') {
                            pageHeaderHeight = header.outerHeight(true);
                            var fixed = true;
                        } else {
                            var navbar = $('.navbar');
                            if (navbar && navbar.css('position') === 'fixed') {
                                pageHeaderHeight = navbar.outerHeight(true);
                            }
                        }
                    }
                    headerRowTop = $('#user-grades tr.heading').offset().top;

                    // Duplicate the existing floaters, then hide the originals.
                    window.setTimeout(plugin.initFloater, 500);
                    window.setTimeout(plugin.initFloater, 1000);

                    // Update the floater positions whenever the gradebook scolls, the page scrolls or the page resizes.
                    $gradeParent.scroll(function () {
                        plugin.updateFloaterPos(pageHeaderHeight, headerRowTop, fixed);
                    });
                    $(window).scroll(function () {
                        plugin.updateFloaterPos(pageHeaderHeight, headerRowTop, fixed);
                    }).resize(function () {
                        plugin.updateFloaterPos(pageHeaderHeight, headerRowTop, fixed);
                    });
                }
            });
        },
        updateFloaterPos: function(pageHeaderHeight, headerRowTop, fixed) {
            var $gradeParent = $('.path-grade-report-grader div.gradeparent');
            $gradeParent.each(function () {
                var $this = $(this);

                var $sidebars = $this.find('.floater.sideonly.slgradebook');
                var $headers = $this.find('.floater.heading.slgradebook');

                // Update the position of the user headings.
                var gradePos = $this.scrollLeft();
                $sidebars.css('left', gradePos + 'px');
                if (gradePos) {
                    $sidebars.addClass('floating');
                } else {
                    $sidebars.removeClass('floating');
                }

                // Update the position of the top headings.
                var scrollTop = $(window).scrollTop();
                if (fixed == true) {
                    scrollTop += pageHeaderHeight;
                }
                if (scrollTop + pageHeaderHeight > headerRowTop) {
                    $headers.offset({top: scrollTop});
                    $headers.not($sidebars).addClass('floating');
                } else {
                    $headers.offset({top: headerRowTop});
                    $headers.not($sidebars).removeClass('floating');
                }
            });
        },
        initFloater: function() {
            var plugin = this;
            var $gradeParent = $('.path-grade-report-grader div.gradeparent');
            var ok = true;
            $gradeParent.each(function () {
                var $this = $(this);
                var $oldels = $this.find('.floater');
                if (!$oldels.length) {
                    // Moodle floaters not yet initialised, wait until they are.
                    ok = false;
                    return;
                }
                var $newels = $oldels.clone().appendTo($this);
                $newels.addClass('slgradebook');
                $oldels.hide();
            });
            if (!ok) {
                window.setTimeout(plugin.initFloater, 100);
            }
        }
    }
});
/* jshint ignore:end */