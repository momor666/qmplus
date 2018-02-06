define(['jquery', 'theme_bloom/hammer'], function($, Hammer) {

    var hammerTime = function() {

        var hammerOpts = {
            userSelect: false
        };

        var $drawer = $('#sidenav'),
            $overlay = $drawer.find('.overlay'),
            $message = $('#nav-message-popover-container'); // for adding new message notification

        if ($drawer.length) {

            

            $('.navbar-toggler').on('click', function (e) {
                e.preventDefault();

                var $newMsg = $message.find('.count-container'),
                    $newMsgInDrawer = $drawer.find('a[data-title="messages,message"]');

                if (!$drawer.hasClass('in')) {
                    $drawer.addClass('in')
                        .removeClass('out');
                    
                    if($newMsg.text() !== ""){ 
                        $newMsgInDrawer.append($newMsg.clone());
                    }

                } else {
                    $drawer.removeClass('in')
                        .addClass('out');
                    
                    if($newMsgInDrawer.find('.count-container').length >0){ 
                        $newMsgInDrawer.find('.count-container').remove();
                    }
                }
            });

            $overlay.on('click', function () {
                $drawer.removeClass('in')
                    .addClass('out');
            });

            var itsHammerTime,
                stop;

            function initHammer() {
                if($('body').hasClass('path-my')||$('body').hasClass('path-login')||$('body').hasClass('path-user')){
                    if (window.innerWidth < 992) { //swipe for mobile
                        delete Hammer.defaults.cssProps.userSelect;
                         stop = new Hammer(document.body, hammerOpts).on('swiperight', function (e) {
                             var el = e.target;

                            if(el.closest('table') == null){
                                 $drawer.addClass('out')
                                     .removeClass('in');
                            }
                        });

                         itsHammerTime = new Hammer(document.body, hammerOpts).on('swipeleft', function (e) {
                             var el = e.target;

                             if(el.closest('table') == null) {
                                 $drawer.removeClass('out')
                                     .addClass('in');
                             }
                        });
                    }
                }
            }

            initHammer();

            $(window).on('orientationchange', function(){
                if(typeof(itsHammerTime)==='undefined') {
                    initHammer();
                }
            });
        }
    }

    return {
        init: function(){
            "use strict";

            $(document).ready(function() {
                
                hammerTime();                      

            });
        }
    };
});