YUI.add('moodle-atto_qmulcolumns-button', function (Y, NAME) {

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    atto_qmulcolumns
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk> and Matthias Opitz <m.opitz@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_qmulcolumns-button
 */

/**
 * Atto text editor title plugin.
 *
 * @namespace M.atto_qmulcolumns
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */
var COMPONENTNAME = 'atto_qmulcolumns',
    CSS = {
        COLUMN: 'atto_qmulcolumns_column',
        MAP: 'atto_qmulcolumns_map'
    },
    SELECTORS = {
        COLUMN: '.atto_qmulcolumns_column'
    },
    TEMPLATE = '' +
        '<div class="{{CSS.MAP}}">' +
        '{{#each styles}}' +
        '<div>' +
        '<a href="#" class="{{../CSS.COLUMN}}" data-tagname="{{tagname}}" data-attr="{{attr}}" data-attrValue="{{attrValue}}">' +
        '{{get_string stylename "atto_qmulcolumns"}}' +
        '</a>' +
        '</div>' +
        '{{/each}}' +
        '</div>';


//var component = 'atto_qmulcolumns';

Y.namespace('M.atto_qmulcolumns').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {


    _currentSelection: null,

    initializer: function() {
        this.addButton({
            icon: 'e/columns',
            iconComponent: 'atto_qmulcolumns',
            callback: this._displayDialogue
        });
    },

    /**
     * Display the Emotcolumn chooser.
     *
     * @method _displayDialogue
     * @param params
     * @private
     */
    _displayDialogue: function() {
        // Store the current selection.

        this._currentSelection = this.get('host').getSelection();

        if (this._currentSelection === false) {
            return;
        }

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('insertcolumn', COMPONENTNAME),
            focusAfterHide: true
        }, true);

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent()).show();

    },

    /**
     * Insert the emotcolumn.
     *
     * @method  _insertCol
     * @param {EventFacade} e
     * @private
     */
    _insertCol: function(e) {

        var target = e.target.ancestor(SELECTORS. COLUMN, true),
            host = this.get('host'),
            _attr = target.getData('attr'),
            _attrValue = target.getData('attrValue'),
            _class = '';

        e.preventDefault();


        // Hide the dialogue.
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        if(_attr =='class'){
            _attrValue += ' atto-editor-columnborder'
        }else{
            _class = 'class="atto-editor-columnborder"';
        }

        // Split values, detect if it two columns or one
        var columnAttrValue = _attrValue.split(',');

        // Build the columns.
        if(columnAttrValue.length>0){
            var colwidth = (100 - ((columnAttrValue.length - 1)*2))/columnAttrValue.length;
            
            if(columnAttrValue.length === 1){
                var html='<table class="onecolumntable"><tr>';
            }else{
                var html='<table><tr>';
            }
            var count = 0;
            columnAttrValue.forEach(function(){
                if(count++ > 0){
                    html = html + '<td class="qmulcolumn-separator"></td>';
                }
                html = html + '<td class="qmulcolumn" style="width:'+colwidth+'%;">Column '+count+'</td>';
            });
            html=html+'</tr></table>';

        }

        // ########### Custom Append ########################
        var range, node;
        if (window.getSelection && window.getSelection().getRangeAt) {
            range = window.getSelection().getRangeAt(0);
            node = range.createContextualFragment(html);
            range.insertNode(node);
        } else if (document.selection && document.selection.createRange) {
            document.selection.createRange().pasteHTML(html);
        }
        // ./end  ########### Custom Append ########################

        // Mark the content as updated.
        this.markUpdated();

    },

    _insertCol0: function(e) {

        var target = e.target.ancestor(SELECTORS. COLUMN, true),
            host = this.get('host'),
            _attr = target.getData('attr'),
            _attrValue = target.getData('attrValue'),
            _class = '';

        e.preventDefault();


        // Hide the dialogue.
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        if(_attr =='class'){
            _attrValue += ' atto-editor-columnborder'
        }else{
            _class = 'class="atto-editor-columnborder"';
        }

        // Split values, detect if it two columns or one
        var columnAttrValue = _attrValue.split(',');

        // Build the columns.
        if(columnAttrValue.length>1){

            //is two columns

            var html = '<div><'+target.getData('tagname')+' ' + _class + ' '
                + _attr + '="' + columnAttrValue[0] + '" >Column 1'
                + '</' + target.getData('tagname') + '>' +
                '<'+target.getData('tagname')+' ' + _class + ' '
                + _attr + '="' + columnAttrValue[1] + '" >Column 2'
                + '</' + target.getData('tagname') + '><div style="clear:both"></div></div></div><br><p><br></p>';

        }else{

            //is one column

            var html = '<'+target.getData('tagname')+' ' + _class + ' '
                + _attr + '="' + columnAttrValue[0] + '" >Column'
                + '</' + target.getData('tagname') + '><div style="clear:both"></div></div><br><p><br></p>';

        }

        /*
         Make custom append afer selection
         //this.get('host').insertContentAtFocusPoint(html); // default append
         */

        // ########### Custom Append ########################
        var range, node;
        if (window.getSelection && window.getSelection().getRangeAt) {
            range = window.getSelection().getRangeAt(0);
            node = range.createContextualFragment(html);
            range.insertNode(node);
        } else if (document.selection && document.selection.createRange) {
            document.selection.createRange().pasteHTML(html);
        }
        // ./end  ########### Custom Append ########################

        // Mark the content as updated.
        this.markUpdated();

    },

    /**
     * Generates the content of the dialogue, attaching event listeners to
     * the content.
     *
     * @method _getDialogueContent
     * @return {Node} Node containing the dialogue content
     * @private
     */
    _getDialogueContent: function() {

        var template = Y.HanCSS = {
            COLUMN: 'atto_qmulcolumns_column',
            MAP: 'atto_qmulcolumns_map'
        }

        var template = Y.Handlebars.compile(TEMPLATE),
            content = Y.Node.create(template({
                styles: this.get('styles'),
                CSS: CSS
            }));

        content.delegate('click', this. _insertCol, SELECTORS.COLUMN, this);
        content.delegate('key', this. _insertCol, '32', SELECTORS.COLUMN, this);

        return content;

    }

}, {
    ATTRS: {
        /**
         * The list of emotcolumns to display.
         *
         * @attribute emotcolumns
         * @type array
         * @default {}
         */
        styles: {
            value: {}
        }
    }
});

}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
