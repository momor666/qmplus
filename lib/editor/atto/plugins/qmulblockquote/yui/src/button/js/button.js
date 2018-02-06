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
 * @package    atto_qmulblockquote
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_qmulblockquote-button
 */

/**
 * Atto text editor title plugin.
 *
 * @namespace M.atto_qmulblockquote
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */
Y.namespace('M.atto_qmulblockquote').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    initializer: function() {
        this.addButton({
            icon: 'e/toggle_blockquote',
            callback: this._BlockQuote
        });
    },


    _blockExist: function(element){

        var parentEl = element.commonAncestorContainer;

        if(typeof parentEl !== "undefined"){
            parentEl = parentEl.parentNode;
        }else{
            parentEl = element.parentElement;
        }

        if(parentEl.tagName == 'BLOCKQUOTE'){

            parentEl.outerHTML = parentEl.outerHTML + parentEl.innerHTML;
            document.getElementById(parentEl.id).remove();

            return true;

        }else if(!this._hasClass(parentEl,'editor_atto_content_wrap')){

            return this._blockExist(parentEl);
        }

        return false;

    },

    /**
     * Add blockquote
     *
     * @param {EventFacade} e
     * @private
     */
    _BlockQuote: function(e) {

        var _id = 'uniqueBlockId' + Math.floor(Math.random() * 15); // We need the id for removing the block

        var _selection = document.getSelection().getRangeAt(0),
            _text = '<blockquote id="'+_id+'">'+ _selection.toString()+'</blockquote><br><p><br></p>';

        var blockExist = this._blockExist(_selection);

        if( blockExist || _selection.startContainer.tagName === 'BLOCKQUOTE'
            ||  _selection.endContainer.tagName === 'BLOCKQUOTE'
        ){
            _text = _selection.toString();
        }

        document.execCommand("insertHTML", false, _text);

        // Mark as updated
        this.markUpdated();
    },

    _hasClass:function(element, className) {
        return element.className && new RegExp("(^|\\s)" + className + "(\\s|$)").test(element.className);
    }

});