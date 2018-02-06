// Put this file in path/to/plugin/amd/src
// You can call it anything you like

define(['jquery'], function($) {

    return {
        init: function() {
            $("#id_barcode").focus().val('');
            $("#id_updategrade").val('');
        },
        updatemessage: function (updatemessage, element) {
            $("." + element).after(updatemessage);
        }
    };
});