YUI.add('moodle-block_qmul_my_qmplus-ddslick', function(Y) {

    M.block_qmul_my_qmplus = M.block_qmul_my_qmplus || {};
    M.block_qmul_my_qmplus.init_ddslick = function(param) {

        function initModules(param){
            $('#myModulesWrapper').ddslick({
                width:'100%',
                background:'#E6DED1',
                selectText:param,
                onSelected: function(data){
                    //callback function: do something with selectedData;
                    if (data.selectedData) {
                        window.location.href = data.selectedData.value;
                    }
                }
            });
        }

        $('#showSampleData').on('click', function () {
            $('#dd-display-data').html('<pre>' + $('#JSONData').html() + '</pre>');
            $('#dd-modal').fadeIn();
        });

        $('#showSampleSelectList').on('click', function () {
            $('#dd-display-data').html('<pre>' + $('#sampleHtmlSelect').html() + '</pre>');
            $('#dd-modal').fadeIn();
        });

        $('#myModulesWrapper').parent().width('100%');


        initModules(param);


        $( window ).resize(function() { //On browser resize re-render plugin;
            $('#myModulesWrapper').ddslick('destroy');
            initModules(param);
        });



    }

}, '@VERSION@', {
    requires:['base']
});