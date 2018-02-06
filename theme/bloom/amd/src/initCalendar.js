define(['jquery', 'theme_bloom/jquery.scrollTo', 'theme_bloom/onDemand'], function($, scrollTo, ondemand) {

    var calendar = function(){
        if($('.ajaxcalendar').length > 0){
            var $detailBox = $('#event_detail_box'),
                offset = -60;

            $detailBox.on('click', '.backto-calendar', function(e){
                e.preventDefault();

                $(this).removeClass('move');
                
                scrollScreen($(this).attr('href'), offset);
            });

            $('.ajaxcalendar .has-event').on('click', '.event-detail li, .day', function(e){
                e.preventDefault();

                var $event = $(this).find('a'),
                    $eventurl= $event.attr('href'),
                    hash = $eventurl.substring($eventurl.indexOf('#'));

                $event.parents('td')
                      .addClass('loader');

                if($detailBox.find('.event').length > 0){
                    $detailBox.find('.event').fadeOut(300);
                }

                $.ajax({
                    url: $eventurl,
                    dataType: "html"
                    }).done(function(xhr) {               
                        var $html = '',
                            $scrollDestination = $detailBox,
                            $maincalendar = $(xhr).find('.ajax-maincalendar');
                            
                      
                        $html = '<h2>' + $maincalendar.find('.controls span.current').html() + '</h2>';
                        $html += $maincalendar.find('.eventlist').html() 
                                + "<a href='#region-main' title='Back to Calendar' class='btn btn-primary backto-calendar'>Back to Calendar</a>";

                        $detailBox.html($html);
                        $('.btn.backto-calendar').ondemand($('.ajaxcalendar'));
                        
                        if((hash !== ' ')&&(hash !== $eventurl)){
                            $scrollDestination = $(hash);
                        }
                        scrollScreen($scrollDestination, offset);
                    });
            });
        }
    },
    scrollScreen = function(destination, offset){
        $('body').scrollTo(destination, 500, {
            offset:  function() { return {top:offset, left:0}; }, 
            onAfter: function() { 
                $('td.loader').removeClass('loader');
            }
        }); 
    }       
    
    return {
        init: function(){
            "use strict";

            $(document).ready(function() {
                calendar();
            });
        }
    };
});