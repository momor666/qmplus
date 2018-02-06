/* globals define */
 
define(['jquery'], function($) {
    //usage:
    // $('.nav').ondemand(options);
    $.fn.ondemand = function(offsetObj) {
        var $nav = $(this),
            orgNavpos = $nav.offset().top,
            scrollHeight = 0,
            scrollTop = 0,
            $window = $(window),
            $document = $(document),
 
            init = function(){
                orgNavpos = $nav.offset().top;
            },
 
            scrollNav = function(){
                scrollTop = $document.scrollTop();
                scrollHeight = scrollTop + $window.height();
 
                if((scrollTop > offsetObj.height())&&(scrollHeight < orgNavpos)){
                    $nav.addClass("move"); 

                }else{
                    $nav.removeClass("move");
                }
            };
 
        $window.resize(function(){
            init();
            scrollNav();
        });
 
        $window.scroll(function(){
            scrollNav();
        });
 
        init();
 
    }
 
});