define(['jquery'], function($) {

    var search = function(){

        $(document).ready(function() {
            var $globalSearch = $('.search-input-wrapper-custom'),
                $search = $globalSearch.find('div[role="button"]');

            if($globalSearch.length){
                $.each($search, function(){
                    var $searchBtn = $(this);

                    $searchBtn.on('click', function(){ 
                        $(this).closest('.search-input-wrapper-custom').toggleClass('expanded');
                    })
                })
            
                $('body').on('click', function(e){
                    var el = e.target;  
                    if(!el.closest('.search-input-wrapper-custom')){
                        $globalSearch.removeClass('expanded');
                    }
                });
            }
        });
    }
    return {
        init: function(){
            "use strict";

             search();
        }
    };
});