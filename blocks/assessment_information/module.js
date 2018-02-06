M.block_assessment_information = M.block_assessment_information || {}

SELECTORS = {
        ACTIVITYCHOOSER : '.block_assessment_information .section-modchooser-text',
        TRIGGERLINK : 'li#section-52 .section-modchooser-link a',
        RESOURCE : '.block_assessment_information ul div.resource',
        DROPBOUNDARY : '.block_assessment_information .section-wrap',
        DROPBOX: '.block_assessment_information ul.resource-list',
        MOVELINK : '.move a',
        MOVEIMAGE : '.move a img',
    }

M.block_assessment_information.add_handles = function(Y) {
    M.block_assessment_information.Y = Y;
    var MOVEICON = {
        pix: "i/move_2d",
        component: 'moodle'
    };

    YUI().use('node','node-event-simulate',function(Y){
        
        //remove link
        var removelink = Y.all('.block_assessment_information .section-wrap.editing .remove-link');
        removelink.on('click', function(e){
            e.preventDefault();
            M.block_assessment_information.changelinkstate(e);
        })
    });

    //create color picker
    YUI().use('node',function(Y){
        if(select = Y.Node.one('#id_config_subheadings_background')){
            var selectedindex = select.get('selectedIndex');
            select.get('options').each(function(v,k){
                var color = v.get('value');
                var node = Y.Node.create("<div data-val='"+color+"'></div>");
                node.addClass( color ? 'option' : 'option transparent' );
                if(k == selectedindex){
                    node.addClass('selected');
                }
                node.setStyles({
                    'background-color' : color,
                });
                node.on('click', function(e){
                    if(!e.target.hasClass('selected')){
                        var selectedoption = Y.Node.one('#fitem_id_config_subheadings_background div.option.selected');
                        selectedoption.removeClass('selected');
                        e.target.addClass('selected')

                        var value = e.target.getAttribute('data-val');;
                        select.set('value',value);
                    }
                })
                v.get('parentNode').get('parentNode').appendChild(node);
            });
            select.hide();
        }
    });
    
    //create drag-drop
    YUI().use('dd-constrain', 'dd-proxy', 'dd-drop', 'dd-plugin', function(Y) {
        var goingUp = false, lastY = 0;

        //prepare drag resources
        var resourcelist = Y.Node.all(SELECTORS.RESOURCE);
        resourcelist.each(function(v, k) {
            var imagenode = v.one(SELECTORS.MOVEIMAGE);
            imagenode.setAttribute('src', M.util.image_url(MOVEICON.pix, 
                MOVEICON.component));
            imagenode.addClass('cursor');
            v.one(SELECTORS.MOVELINK).replace(imagenode);

            var dd = new Y.DD.Drag({
                node: v,
                target: {
                    padding: '0 0 0 20'
                }
            }).plug(Y.Plugin.DDProxy, {
                moveOnEnd: false
            }).plug(Y.Plugin.DDConstrained, {
                constrain2node: SELECTORS.RESOURCEBOX
            });
            dd.addHandle('.move');
        });

        //prepare drop locations
        var dropboxes = Y.Node.all(SELECTORS.DROPBOX);
        dropboxes.each(function(v, k) {
            var tar = new Y.DD.Drop({
                node: v
            });
        });

        Y.DD.DDM.on('drag:start', function(e) {
            //Get our drag object
            var drag = e.target;
            //Set some styles here
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').addClass('dragging-resource');
            drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
            drag.get('dragNode').setStyles({
                opacity: '.5',
                borderColor: drag.get('node').getStyle('borderColor'),
                backgroundColor: drag.get('node').getStyle('backgroundColor')
            });
        });

        Y.DD.DDM.on('drag:end', function(e) {
            var drag = e.target;
            //Put our styles back
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });
            M.block_assessment_information.save(Y);
        });

        Y.DD.DDM.on('drag:drag', function(e) {
            //Get the last y point
            var y = e.target.lastXY[1];
            //is it greater than the lastY var?
            if (y < lastY) {
                //We are going up
                goingUp = true;
            } else {
                //We are going down.
                goingUp = false;
            }
            //Cache for next check
            lastY = y;
        });

        Y.DD.DDM.on('drop:over', function(e) {
            //Get a reference to our drag and drop nodes
            var drag = e.drag.get('node'),
                drop = e.drop.get('node');

            //Are we dropping on a li node?
            if (drop.hasClass('resource')) {
                //Are we not going up?
                if (!goingUp) {
                    drop = drop.get('nextSibling');
                }
                //Add the node to this list
                e.drop.get('node').get('parentNode').insertBefore(drag, drop);
                //Resize this nodes shim, so we can drop on it later.
                e.drop.sizeShim();
            }
        });

        Y.DD.DDM.on('drag:drophit', function(e) {
            var drop = e.drop.get('node'),
                drag = e.drag.get('node');

            //if we are not on an li, we must have been dropped on a ul
            if (drop.hasClass('resource-list')) {
                if (!drop.contains(drag)) {
                    drop.appendChild(drag);
                }
            }
        });
    });
}
M.block_assessment_information.save = function() {
    var Y = M.block_assessment_information.Y;
    var sortorder = [];
    var resourselists = Y.Node.all(SELECTORS.DROPBOX);
    var i = 0;
    resourselists.each(function(v, k) {
        resources = v.all('.resource li');
        resources.each(function(v1,k1){
            sortorder[i] = {
                'id' : v1.get('id'),
                'section' : v.get('id'),
                'weight' : k1
            };
            i++;
        });
    });
    Y.io(M.cfg.wwwroot+'/blocks/assessment_information/save.php', {
        method: 'POST',
        data: Y.JSON.stringify(sortorder),
        headers: {
            'Content-Type': 'application/json'
        },
        context: this,
        on: {
            success: function (id, response) {
                console.log(response);
            }
        }
    });
}
M.block_assessment_information.changelinkstate = function(e){
    var Y = M.block_assessment_information.Y;
    id = e.target.getAttribute('data-id');
    state = e.target.getAttribute('data-state');
    state = M.block_assessment_information.toggle(state,0,1);
    var params = {
        'id' : id,
        'state' : state
    }
    Y.io(M.cfg.wwwroot+'/blocks/assessment_information/changelinkstate.php', {
        method: 'POST',
        data: build_querystring(params),
        context: this,
        on: {
            success: function (id, response) {
                    e.target.get('parentNode').toggleClass('show-link');
                    e.target.get('parentNode').toggleClass('hide-link');
                    e.target.setAttribute('data-state',M.block_assessment_information.toggle(
                        e.target.getAttribute('data-state'),
                        0,
                        1
                    ));
                    e.target.setAttribute('title',M.block_assessment_information.toggle(
                        e.target.getAttribute('title'),
                        'Hide Link',
                        'Show Link'
                    ));
                    e.target.set('text',M.block_assessment_information.toggle(
                        e.target.get('text'),
                        'Hide',
                        'Show'
                    ));
            }
        }
    });
}
M.block_assessment_information.toggle = function(current,a,b){
    if(current == a){
        return b;
    }
    return a;
}

YUI.add('moodle-topiczero-modchooser', function (Y, NAME) {

    var TOPICZEROMODCHOOSERNMAE = 'topiczero-modchooser';

    var TOPICZEROMODCHOOSER = function() {
        TOPICZEROMODCHOOSER.superclass.constructor.apply(this, arguments);
    };

    Y.extend(TOPICZEROMODCHOOSER, M.core.chooserdialogue, {
        sectionid : null,

        initializer : function() {
            var dialogue = Y.one('.chooserdialoguebody');
            var header = Y.one('.choosertitle');
            var params = {};
            this.setup_chooser_dialogue(dialogue, header, params);
            var modchoserlink = Y.one(SELECTORS.ACTIVITYCHOOSER);
            this.sectionid = 52;
            console.log(this);
            modchoserlink.on('click', this.display_chooser, this)
        },
        option_selected : function(thisoption) {
        // Add the sectionid to the URL.
            this.hiddenRadioValue.setAttrs({
                name: 'jump',
                value: thisoption.get('value') + '&section=' + this.sectionid
            });
        }
    },
    {
        NAME : TOPICZEROMODCHOOSERNMAE,
        ATTRS : {
            maxheight : {
                value : 800
            }
        }
    });
    M.block_assessment_information = M.block_assessment_information || {}
    M.block_assessment_information.init_chooser = function(config) {
        return new TOPICZEROMODCHOOSER(config);
    };

}, '@VERSION@', {"requires": ["moodle-core-chooserdialogue", "moodle-course-coursebase"]});