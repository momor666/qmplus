
/****************************************************************

 File:       block/course_rollover/module.js

 Purpose:    Init YUI document area treeview

 ****************************************************************/

M.block_course_rollover = {};

M.block_course_rollover.init = function(Y, expand_all, htmlid) {
    var course_names_toggle =  Y.all('.course_names_toggle');
    course_names_toggle.on('click', function(e){
        var course_names_wrap = Y.one('#'+e.target.getAttribute('data-id'));
        course_names_wrap.toggleView();
        if(course_names_wrap.getAttribute('hidden')){
            e.target.set('innerHTML','Show More...');
        } else {
            e.target.set('innerHTML','Show Less...');
        }
        e.preventDefault();
    });

};

M.block_course_rollover.default_rollover = function(Y){
    //get values from multiselect list TODO: override YUI get select function for multiple select list
    var get_multi_select_value = function(node) {
        var val = [],
            options = node.options;
        if (options.length) {
            for (var i = 0; i < options.length;i++) {
                option = options[i];
                if (option.selected) {
                    val.push(option.value);
                }
            }
        }
        return val;
    };
    //override YUI set select function for multiple select list
    Y.DOM.VALUE_SETTERS.select = function(node, val) {
        if (node.multiple && !Y.Lang.isArray(val)) val = [val]; // Allow to set value by single value for multiple selects
        for (var i = 0, options = node.getElementsByTagName('option'), option; option = options[i++];) {
            option.selected = (node.multiple && val.indexOf(Y.DOM.getValue(option)) > -1) ||
                (!node.multiple && Y.DOM.getValue(option) === val);
        }
    };
    //default rollover select list reset
    var default_resets_select = Y.all('#id_default_rollover select');
    default_resets_select.on('change',function(e){
        var current_class = e.target.getAttribute('class');
        var course_resets_select = Y.all('.reset_elements select.'+current_class);
        var current_value = null;
        if(e.target.hasAttribute('multiple')){
            current_value = get_multi_select_value(e.target._node);
        } else {
            current_value = e.target.get('value');
        }
        course_resets_select.set('value', current_value);
    });
    //default rollover checkbox reset
    var default_resets_checkbox = Y.all('#id_default_rollover input[type="checkbox"]');
    default_resets_checkbox.on('change',function(e){
        var current_class = e.target.getAttribute('class');
        var course_resets_checkbox = Y.all('.reset_elements input[type="checkbox"].'+current_class);
        var current_value = e.target.get('checked');
        course_resets_checkbox.set('checked', current_value);
    });
};

M.block_course_rollover.description = function (Y,options){
    var desc_toggle = Y.one('#desc_toggle');
    var desc_div = Y.one('div.generalbox.second');
    desc_toggle.on('click', function(e){
        var desc_visibility = desc_div.getAttribute('hidden');
        if(desc_visibility == 'hidden'){
            desc_div.show();
            e.target.set('innerHTML', options.viewless);
        }
        else{
            desc_div.hide();
            e.target.set('innerHTML',options.viewmore);
        }
        e.preventDefault();
    });
};