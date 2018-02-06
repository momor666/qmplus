/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function () {
            log.debug('QMUL Drawer Menu JavaScript initialised');
            var plugin = this;
            $(document).ready(function() {
                $("body").on('click', '.fp-repo-area .caret', function(e) {
                    $(".fp-repo-area").toggleClass('open');
                    var active = 0;
                    $(".fp-repo-area .fp-repo").each(function() {
                        if ($(this).hasClass('active')) {
                            active = 1;
                        }
                    });
                    if (active == 0) {
                        $(".fp-repo-area .fp-repo:first-of-type").addClass('active');
                    }
                });

                $("body").on('click', '.fp-repo-area .fp-repo a', function(e) {
                    $(".fp-repo-area").toggleClass('open');
                });

                $("body").on('click', '.navbar .backpackbutton', function(e) {
                    if (!$("div.outer div.searchcontainer").hasClass('closed')) {
                        $(".navbar .search .closebutton")[0].click();
                    }
                    $("div.outer div.backpack").removeClass('closed');
                    $(".navbar .backpackbutton").addClass('hidden');
                    $(".navbar .logininfo .closebutton").show();
                });

                $("body").on('click', '.navbar .logininfo .closebutton', function(e) {
                    $("div.outer div.backpack").addClass('closed');
                    $(".navbar .backpackbutton").removeClass('hidden');
                    $(".navbar .logininfo .closebutton").hide();
                });

                $("body").on('click', '.navbar .search', function(e) {
                    if (!$('div.outer .backpack').hasClass('closed')) {
                        $('.navbar .logininfo .closebutton')[0].click();
                    }
                    $("div.outer div.searchcontainer").toggleClass('closed');
                    $("div.outer div.searchcontainer input.coursesearch").focus();
                    $(".navbar .search .closebutton").toggle();
                });

                $("body").on('click', '.searchcontainer .overlay', function() {
                    $("div.outer div.searchcontainer").toggleClass('closed');
                    $(".navbar .search .closebutton").toggle();
                });
            });
        }
    }
});
/* jshint ignore:end */