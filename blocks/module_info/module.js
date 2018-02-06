
/****************************************************************

 File:       block/module_info/module.js

 Purpose:    Init YUI document area treeview

 ****************************************************************/

M.block_module_info = {};

M.block_module_info.init = function(Y, expand_all, htmlid) {

    Y.use('yui2-treeview', function(Y) {

        var tree = new Y.YUI2.widget.TreeView(htmlid);

        tree.subscribe("clickEvent", function(node, event) {
            // we want normal clicking which redirects to url
            return false;
        });

        if (expand_all) {
            tree.expandAll();
        }

        tree.render();
    });

};

/**
 * Init a collapsible region, see print_collapsible_region in weblib.php
 * @param {YUI} Y YUI3 instance with all libraries loaded
 * @param {String} id the HTML id for the div.
 * @param {String} userpref the user preference that records the state of this box. false if none.
 * @param {String} strtooltip
 */
M.block_module_info.init_collapsible_region = function(Y, id, userpref, strtooltip) {
    Y.use('anim', function(Y) {
        new M.block_module_info.CollapsibleRegion(Y, id, userpref, strtooltip);
    });
};

/**
 * Object to handle a collapsible region : instantiate and forget styled object
 *
 * @class
 * @constructor
 * @param {YUI} Y YUI3 instance with all libraries loaded
 * @param {String} id The HTML id for the div.
 * @param {String} userpref The user preference that records the state of this box. false if none.
 * @param {String} strtooltip
 */
M.block_module_info.CollapsibleRegion = function(Y, id, userpref, strtooltip) {
    // Record the pref name
    this.userpref = userpref;

    // Find the divs in the document.
    this.div = Y.one('#'+id);

    // Get the caption for the collapsible region
    var caption = this.div.one('#'+id + '_caption');

    // Create a link
    var a = Y.one('#'+id+'_caption_anchor');
    a.setAttribute('title', strtooltip);

    // Get the height of the div at this point before we shrink it if required
    var height = this.div.get('offsetHeight');
    if (this.div.hasClass('collapsed')) {
        // Add the correct image and record the YUI node created in the process
        this.icon = Y.Node.create('<img src="'+M.util.image_url('collapsed', 'block_module_info')+'" alt="" />');
        // Shrink the div as it is collapsed by default
        this.div.setStyle('height', caption.get('offsetHeight')+'px');
    } else {
        // Add the correct image and record the YUI node created in the process
        this.icon = Y.Node.create('<img src="'+M.util.image_url('expanded', 'block_module_info')+'" alt="" />');
    }
    //caption.insertBefore(this.icon, a);
    a.prepend(this.icon);

    // Create the animation.
    var animation = new Y.Anim({
        node: this.div,
        duration: 0.3,
        easing: Y.Easing.easeBoth,
        to: {height:caption.get('offsetHeight')},
        from: {height:height}
    });

    // Handler for the animation finishing.
    animation.on('end', function() {
        this.div.toggleClass('collapsed');
        if (this.div.hasClass('collapsed')) {
            this.icon.set('src', M.util.image_url('collapsed', 'block_module_info'));
        } else {
            this.icon.set('src', M.util.image_url('expanded', 'block_module_info'));
        }
    }, this);

    // Hook up the event handler.
    a.on('click', function(e, animation) {
        e.preventDefault();
        // Animate to the appropriate size.
        if (animation.get('running')) {
            animation.stop();
        }
        animation.set('reverse', this.div.hasClass('collapsed'));
        // Update the user preference.
        if (this.userpref) {
            M.util.set_user_preference(this.userpref, !this.div.hasClass('collapsed'));
        }
        animation.run();
    }, this, animation);
};

/**
 * The user preference that stores the state of this box.
 * @property userpref
 * @type String
 */
M.block_module_info.CollapsibleRegion.prototype.userpref = null;

/**
 * The key divs that make up this
 * @property div
 * @type Y.Node
 */
M.block_module_info.CollapsibleRegion.prototype.div = null;

/**
 * The key divs that make up this
 * @property icon
 * @type Y.Node
 */
M.block_module_info.CollapsibleRegion.prototype.icon = null;

/**
 * Test a on click event
 * @param {YUI} Y YUI3 instance with all libraries loaded
 */
M.block_module_info.init_section_display = function(Y) {
    var titleOveride = Y.all('.title-override .checkboxgroup1');
    titleOveride.on('change', function (e) {
        var checkbox = e.target;
        if(checkbox.get('checked')) {
            var settingsWrapper = checkbox.ancestor("div.section-settings-wrap");
            var hideCheckbox = settingsWrapper.all('.section-hide .checkboxgroup1');
            hideCheckbox.set('checked',false);
        }
    });
    //custom convenor heading visibility
    var custom_convenor_heading_wrap = Y.one('.class_config_custom_module_convenor_heading');
    var convenor_heading_select = Y.one('#id_config_module_convenor_heading');
    if(convenor_heading_select.get('value') != 'custom_name'){
        custom_convenor_heading_wrap.hide();
    }
    convenor_heading_select.on('change',function(e){
       var selectedHeading = e.target.get('value');
       if(selectedHeading == 'custom_name') {
           custom_convenor_heading_wrap.show();
       } else {
           custom_convenor_heading_wrap.hide();
       }
    });
};

M.block_module_info.init_staff_search = function (Y) {
    var staffSearch = Y.one('.staffdir-html-heading form.mform');
    staffSearch.on('submit', function(e){
        var keyword  = e.target.one('input#id_terms').get('value');
        window.open(e.target.get('action')+"?terms="+keyword+"&option=people",'_blank');
        e.preventDefault();
    });
};