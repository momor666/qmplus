YUI.add('moodle-block_qmul_course_metadata-plugin', function (Y, NAME) {

    /**
     * qmul_course_metadata block JS.
     *
     * This file contains the qmul_course_metadata block JS..
     *
     * @module moodle-block_qmul_course_metadata-plugin
     */

    /**
     * This namespace will contain all of the contents of the qmul_course_metadata blocks
     * global qmul_course_metadata and settings.
     * @class M.block_qmul_course_metadata
     * @static
     */
    M.block_qmul_course_metadata = M.block_qmul_course_metadata || {};

    /**
     * Add new instance of qmul_course_metadata tree to tree collection
     *
     * @method init_add_tree
     * @static
     * @param {Object} properties
     */
    M.block_qmul_course_metadata.plugin = M.block_qmul_course_metadata.plugin || {};

    M.block_qmul_course_metadata.plugin.init = function(params) {

        var instance = this;

        this.sits_data_request = null;
        this.sits_course_id = null;
        this.data = params;
        this.io_complete = Y.on('io:complete', this.load_sits_data_complete, Y, this);

        this.dmp = new diff_match_patch();

        Y.all('a.view-link').on('click', function(e) {

            instance.update();
            instance.dialog.show();
            e.preventDefault();
        });

        this.dialog = new Y.Panel({
            contentBox  : Y.Node.create('<div>'),
            bodyContent : '<div class="message">Click</div>',
            width       : 800,
            zIndex      : 6,
            centered    : true,
            modal       : true,
            visible     : false,
            buttons     : {
                footer: [
                    {
                        name   : 'done',
                        label  : 'Done',
                        action : 'onDone',
                    }
                ]
            }
        });

        this.dialog.onDone = function (e) {
            e.preventDefault();
            this.hide();
        }

        this.dialog.render();

        Y.one('#id_idnumber').on('focus', function(e) {

            instance.last_idnumber = Y.one(e.target).get('value');
        }, this);

        Y.one('#id_idnumber').on('blur', function(e) {

            var idnumber = Y.one(e.target).get('value');

            if (instance.last_idnumber != idnumber) {
                instance.load_sits_data(idnumber);
            }
        }, this);
        
        
        
        //TODO: add other events
        //TODO: refactor but avoid scoping issues
        //TODO: Is there and tinyMce.onLoad() function that could replace setTimeout()
        setTimeout(function () {
            if (typeof tinyMCE !== "undefined"){
                if (typeof tinyMCE.get('id_summary_editor') !== "undefined") {
                    var tinymce = window.parent.tinyMCE.get('id_summary_editor');
                    var update = function (ed, e) {
                        instance.update();
                    };
                    tinymce.onChange.add(update);
                    tinymce.onKeyUp.add(update);

                }
            }
        }, 3000);





        // Capture a slew of events as update triggers, for cross browser compatability.
        var events = [ 'change', 'input', 'focus', 'blur', 'DOMSubtreeModified', 'propertychange' ];
        for (var i = 0; i < events.length; i++) {
            Y.delegate(events[i], function() {
                instance.update();
            }, 'body', '#id_fullname,#id_shortname,#id_summary_editor,#id_summary_editoreditable,#tinymce, #tinymce p');
        }
        this.update();
    };

    M.block_qmul_course_metadata.plugin.load_sits_data = function(idnumber) {

        if (idnumber == '') {
            this.data = null;
            this.update();
        }
        this.sits_course_id = idnumber;
        Y.io('/local/qmul_sync/ajax.php?action=moodle_course&idnumber='+idnumber);
    };

    M.block_qmul_course_metadata.plugin.load_sits_data_complete = function(id, o, args) {

        var regex = /^.*\/\/[^\/]*([^\?]*)\??.*$/
        var result = regex.exec(o.responseURL);

        if (result[1] == '/local/qmul_sync/ajax.php') {
            result = Y.JSON.parse(o.responseText);
            args.data = result;
            args.update();
            if (Y.one('#id_fullname').get('value') == '') {
                Y.one('#id_fullname').set('value', args.data.sits.course_name);
            }
            if (Y.one('#id_shortname').get('value') == '') {
                Y.one('#id_shortname').set('value', args.data.sits.course_short_name);
            }
            if (Y.one('#id_summary_editor').get('text') == '') {
                Y.one('#id_summary_editor').set('value', '<p>'+args.data.sits.module_desc+'</p>');
                Y.one('#id_summary_editor').simulate('change');
            }
            if (Y.one('#id_summary_editoreditable') && Y.one('#id_summary_editoreditable').get('text') == '') {
                Y.one('#id_summary_editoreditable').setHTML('<p>'+args.data.sits.module_desc+'</p>');
                Y.one('#id_summary_editoreditable').simulate('change');
            }
        }
    }

    M.block_qmul_course_metadata.plugin.updateable = function() {

        var result = this.fullname_updateable()
            || this.shortname_updateable()
            || this.summary_updateable();

        return result;
    }

    M.block_qmul_course_metadata.plugin.fullname_updateable = function() {
        if (this.data && this.data.sits) {
            var sits = this.data.sits.course_name;
            var editor = Y.one('#id_fullname').get('value');
            return (sits != editor);
        }
        return false;
    }

    M.block_qmul_course_metadata.plugin.shortname_updateable = function() {
        if (this.data && this.data.sits) {
            var sits = this.data.sits.course_short_name;
            var editor = Y.one('#id_shortname').get('value');
            return (sits != editor);
        }
        return false;
    }

// edit this! says PL
    M.block_qmul_course_metadata.plugin.summary_updateable = function() {
        if (this.data && this.data.sits) {
            var sits = this.data.sits.module_desc;
            var editor;
            var node;

            //use TinyMce object on global scope to
            if (typeof tinyMCE !== "undefined") {
                if (typeof tinyMCE.get('id_summary_editor') !== "undefined") {
                    var tinymce = tinyMCE.get('id_summary_editor');
                    editor = tinymce.getContent();
                }
            }
            else if ((node = Y.one('#id_summary_editoreditable'))) {
                editor = node.get('text');
            } else {
                editor = Y.one('#id_summary_editor').get('value');
            }

            if(editor == sits || editor == '<p>'+sits+'</p>'){
                return false;
            }

            return (editor != sits || editor != '<p>'+sits+'</p>');
        }
        return false;
    }

    M.block_qmul_course_metadata.plugin.update = function() {
        var block = Y.one('.block_qmul_course_metadata .content');
        var msg;
        var visible = false;

        if (this.data && this.data.sits) {
            if (this.updateable()) {
                msg = M.str.block_qmul_course_metadata.sits_diff;
                visible = true;
            } else {
                msg = M.str.block_qmul_course_metadata.sits_same;
            }
        } else {
            msg = M.str.block_qmul_course_metadata.sits_none;
        };
        block.one('.message').setHTML(msg);
        if (visible) {
            block.one('.view-link').set('style', '');
        } else {
            block.one('.view-link').set('style', 'display: none');
        }

        var fullname = Y.one('#id_fullname').get('value');
        var shortname = Y.one('#id_shortname').get('value');
        var summary;
        var node;

        if (node = Y.one('#id_summary_editoreditable')) {
            summary = node.get('text');
        } else if (typeof tinyMCE !== "undefined") {
            if(typeof tinyMCE.get('id_summary_editor') !== "undefined") {
                summary = tinyMCE.get('id_summary_editor').getContent();
            }
        }
        else {
            summary = Y.one('#id_summary_editor').get('text');
        }

        var content = '';
        content += M.str.block_qmul_course_metadata.sits_help;
        content += '<table class="course-vs-sitsdata">';
        content += '<tbody>';
        content += this.add_data_row('Full Name', 'course_name', fullname);
        content += this.add_data_row('Short Name', 'course_short_name', shortname);
        content += this.add_data_row('Description', 'module_desc', summary);
        content += '</tbody>';
        content += '<tfoot>';
        if (this.updateable()) {
            content += '<td/><td/><td><input type="button" value="Copy all changes" class="copy course_name course_short_name module_desc"/></td>';
        }
        content += '</tfoot>';
        content += '</table>';
        content += M.str.block_qmul_course_metadata.sits_wrong;

        if (this.dialog.get('bodyContent') != content) {
            this.dialog.set('bodyContent', content);

            var instance = this;

            this.dialog.get('contentBox').all('.copy.course_name').on('click', function(e) {
                Y.one('#id_fullname').set('value', instance.data.sits.course_name);
                instance.update();
            });
            this.dialog.get('contentBox').all('.copy.course_short_name').on('click', function(e) {
                Y.one('#id_shortname').set('value', instance.data.sits.course_short_name);
                instance.update();
            });
            this.dialog.get('contentBox').all('.copy.module_desc').on('click', function(e) {
                if (Y.one('#id_summary_editoreditable')) {
                    Y.one('#id_summary_editoreditable').setHTML('<p>'+instance.data.sits.module_desc+'</p>');
                    Y.one('#id_summary_editoreditable').simulate('change');
                }
                else if (typeof tinyMCE !== "undefined") {
                    if(tinyMCE.get('id_summary_editor') !== "undefined") {
                        var tinymce = window.parent.tinyMCE.get('id_summary_editor');
                        Y.one('#id_summary_editor').set('value', instance.data.sits.module_desc);
                        tinymce.setContent('<p>' + instance.data.sits.module_desc + '</p>');
                        Y.one('#id_summary_editor').simulate('change');
                    }
                }
                else{
                    console.log('Error: unidentified editor.');
                }

                instance.update();
            });
        }
    }

    M.block_qmul_course_metadata.plugin.add_data_row = function(label, sitsfield, editor) {
        var sits = (this.data && this.data.sits)?this.data.sits[sitsfield]:'';
        var disabled = (sits==editor);

        if (editor == null || sits == null) return;
        var pattern = /^<p[^>]*>|<\/p>$/g;
        editor = editor.replace(pattern, '');
        sits = sits.replace(pattern, '');

        var diffs = this.dmp.diff_main(editor, sits);
        var content = '';

        content += '<tr>';
        content += '<td class="description">'+label+'</td>';
        content += '<td>';
        for (var i = 0; i < diffs.length; i++) {
            switch (diffs[i][0]) {
                case -1:
                    content += '<span class="removed">'+diffs[i][1]+'</span>';
                    break;
                case 0:
                    content += '<span class="unchanged">'+diffs[i][1]+'</span>';
                    break;
                case 1:
                    content += '<span class="added">'+diffs[i][1]+'</span>';
                    break;
            }
        }
        content += '</td>';
        if (disabled) {
            content += '<td class="button-col">Same as editor</td>';
        } else {
            content += '<td class="button-col"><input type="button" value="Copy changes to editor" class="copy '+sitsfield+'"/></td>';
        }
        content += '</tr>';
        return content;
    }





}, '@VERSION@', {
    "requires": [
        "base",
        "io-base",
        "node",
        "json-parse",
        "panel",
        "yui-lang-later",
        "node",
        "event-valuechange"
    ]
});
