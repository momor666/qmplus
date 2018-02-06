define(['jquery', 'theme_bloom/jquery.doubleScroll'], function($, doubleScroll) {

    var scroll = function(){

        $(document).ready(function() {
            
            $('.gradingtable  table').wrap('<div class="table-wrapper"></>');
            
        });
        $(window).on('load', function(){
            var options = {
                    contentElement: undefined, // Widest element, if not specified first child element will be used
                    scrollCss: {                
                        'overflow-x': 'auto',
                        'overflow-y': 'hidden'
                    },
                    contentCss: {
                        'overflow-x': 'auto',
                        'overflow-y': 'hidden'
                    },
                    onlyIfScroll: true, // top scrollbar is not shown if the bottom one is not present
                    resetOnWindowResize: true // recompute the top ScrollBar requirements when the window is resized
                }

            $('.table-wrapper').doubleScroll(options);
        })
    },
    triggerScroll = function(){
        $(window).trigger('resize');
    }
    
    return {
        init: function(){
            "use strict";
            if($('#page-mod-assign-grading').length){
                scroll();
                //$(window).trigger('resize'); //didn't work first time on the test site

                $('.hide-nav').on('click', function(){
                    setTimeout(triggerScroll, 500);
                })
            }
        }
    };
});