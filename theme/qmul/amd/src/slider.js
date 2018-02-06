/* jshint ignore:start */
define(['jquery', 'theme_qmul/slick'], function($, slick) {
    return {
        init: function() {
            var plugin = this;
            $(document).ready(function() {
                if ($('.synergyslider').length > 0) {
                    $('.synergyslider').on('init', function(slick) {
                        plugin.dotsposition();
                    });
                    $(window).on('resize', function() {
                        plugin.dotsposition();
                    });
                    $('.synergyslider').slick({
                        dots: true,
                        speed: 500,
                        autoplay: true,
                        autoplaySpeed: 5000,
                        appendDots: $('.synergyslider'),
                        slide: '.slide',
                    });
                    $('.synergyslider').on('beforeChange', function(event, slick, direction) {
                        $('.synergyslider .carousel-caption').removeClass('active');
                    });
                    $('.synergyslider').on('afterChange', function(event, slick, direction) {
                        var currentSlide = $('.synergyslider .carousel-caption[data-slide="' + slick.currentSlide + '"]');
                        var captionheight = currentSlide.outerHeight();
                        currentSlide.addClass('active');
                        if (currentSlide.hasClass('active')) {
                            $('.slick-dots').css('max-height', captionheight);
                        } else {
                            $('.slick-dots').css('max-height', '55px');
                        }
                    });
                }
            });
        },
        dotsposition: function() {
            var activeSlide = $('.synergyslider .carousel-caption.active');
            var captionheight = activeSlide.outerHeight();
            if (activeSlide.length > 0) {
                $('.slick-dots').css('max-height', captionheight);
            }
        }
    }
});
/* jshint ignore:end */
