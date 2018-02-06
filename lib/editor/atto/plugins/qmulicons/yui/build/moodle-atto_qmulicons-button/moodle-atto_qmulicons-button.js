YUI.add('moodle-atto_qmulicons-button', function (Y, NAME) {

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
     * @package    atto_qmulicons
     * @copyright  2015 Queen Mary University of London
     * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */

    /**
     * @module moodle-atto_qmulicons-button
     */

    /**
     * Atto text editor title plugin.
     *
     * @namespace M.atto_qmulicons
     * @class button
     * @extends M.editor_atto.EditorPlugin
     */


    var COMPONENTNAME = 'atto_qmulicons',
        CSS = {
            ICON: 'atto_qmulicons_icon',
            MAP: 'atto_qmulicons_map'
        },
        SELECTORS = {
            ICON: '.atto_qmulicons_icon'
        },
        TEMPLATE = '' +
            '<div class="{{CSS.MAP}}">' +
            '{{#each styles}}' +
            '<div>' +
            '<a href="#" class="{{../CSS.ICON}}" data-text="{{get_string text "atto_qmulicons"}}" data-tagname="{{tagname}}" data-attr="{{attr}}" data-attrValue="{{attrValue}}">' +
            '<div>' +
            '<{{tagname}} ' +
            '{{attr}}="{{attrValue}}" ' +
            '>{{get_string text "atto_qmulicons"}}</{{tagname}}>' +
            '</div>' +
            '</a>' +
            '</div>' +
            '{{/each}}' +
            '</div>';


    //var component = 'atto_qmulicons';

    Y.namespace('M.atto_qmulicons').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {


        _currentSelection: null,

        initializer: function() {
            this.addButton({
                icon: 'e/customicon',
                iconComponent: 'atto_qmulicons',
                callback: this._displayDialogue
            });
        },

        /**
         * Display the Emoticon chooser.
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
                headerContent: M.util.get_string('inserticon', COMPONENTNAME),
                focusAfterHide: true
            }, true);

            // Set the dialogue content, and then show the dialogue.
            dialogue.set('bodyContent', this._getDialogueContent()).show();
        },

        /**
         * Insert the emoticon.
         *
         * @method  _insertIcon
         * @param {EventFacade} e
         * @private
         */
        _insertIcon: function(e) {

            var target = e.target.ancestor(SELECTORS. ICON, true),
                host = this.get('host');

            e.preventDefault();

            // Hide the dialogue.
            this.getDialogue({
                focusAfterHide: null
            }).hide();

            // Build the Emoticon text.
            var html = '<ul><'+target.getData('tagname')+' '
                + target.getData('attr') + '="' + target.getData('attrValue') + '" >'
                    //+ target.getData('text')  // we dont text to be displayed
                + '</' + target.getData('tagname') + '></ul>';

            // Focus on the previous selection.
            host.setSelection(this._currentSelection);

            // And add the character.
            host.insertContentAtFocusPoint(html);

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
                ICON: 'atto_qmulicons_icon',
                MAP: 'atto_qmulicons_map'
            }

            var template = Y.Handlebars.compile(TEMPLATE),
                content = Y.Node.create(template({
                    styles: this.get('styles'),
                    CSS: CSS
                }));

            content.delegate('click', this. _insertIcon, SELECTORS.ICON, this);
            content.delegate('key', this. _insertIcon, '32', SELECTORS.ICON, this);

            return content;

        }

    }, {
        ATTRS: {
            /**
             * The list of emoticons to display.
             *
             * @attribute emoticons
             * @type array
             * @default {}
             */
            styles: {
                value: {}
            }
        }
    });



}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});