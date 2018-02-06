(function($, window) {
    var ns = window.setZoom || {},
        LABELSHOW = 'Show blocks',
        LABELHIDE = 'Full screen',
        setZoom = {
            init: function(){
                "use strict";

                setZoom.detectShowHide(); //has to load asap

                $(window).on('orientationchange', function(){
                    setZoom.detectShowHide();
                });

                $(document).ready(function() {
                    var $hideBtn = $('.hide-nav');

                    setZoom.attachEvent($hideBtn);
                });
            },
            detectShowHide: function() { 
                var zoom = localStorage.getItem('_zoomIn');
                if ((zoom === "true") && (window.matchMedia('(min-width: 992px)').matches)) {
                    $('body').addClass('hidden-drawer');
                    setZoom.setLabel(LABELSHOW, 'zoomIn');
                }else{
                    setZoom.setLabel(LABELHIDE);
                }
            },
            setLabel:function(LABEL, classname){
                $(document).ready(function() {
                    var $hideBtn = $('.hide-nav');

                    if(typeof(classname)!== 'undefined'){
                        $('body').addClass(classname);
                    }else{
                        $('body').removeClass('zoomIn');
                        localStorage.setItem('_zoomIn', false);
                        $('body').removeClass('hidden-drawer');
                    }
                    $hideBtn.text(LABEL);
                });
            },
            attachEvent: function(el){
                el.on('click', function (e) {
                    e.preventDefault();
                    if (window.matchMedia('(min-width: 992px)').matches) {
                        $('body').toggleClass('zoomIn');

                        if ($('body').hasClass('zoomIn')) {
                            el.text(LABELSHOW);
                            localStorage.setItem('_zoomIn', true);
                        } else {
                            el.text(LABELHIDE);
                            localStorage.setItem('_zoomIn', false);
                            $('body').removeClass('hidden-drawer');
                        }
                    }
                })
            }
        };

    setZoom.cache = ns.cache || {};

    setZoom.init($); // initialises the calls

    window.setZoom = $.extend(ns, setZoom);
}) (jQuery, window);

