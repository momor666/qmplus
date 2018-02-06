/* jshint ignore:start */
define(['jquery', 'theme_qmul/easy-ticker'], function($, ticker) {
    return {
        init: function() {
            $(document).ready(function() {
                if ($('.news-ticker').length > 0) {
                    $('.news-ticker').easyTicker({
                        direction: 'up',
                        easing: 'swing',
                        speed: 'slow',
                        interval: 6000,
                        height: 'auto',
                        visible: 1,
                        mousePause: 0,
                        controls: {
                            up: '.news-ticker-next',
                            down: '.news-ticker-prev',
                            toggle: '.news-ticker-pause',
                            playText: '|>',
                            stopText: '||'
                        }
                    });
                }
            });
        }
    }
});
/* jshint ignore:end */