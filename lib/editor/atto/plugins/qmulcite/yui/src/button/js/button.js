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
 * @package    atto_qmulcite
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_qmulcite-button
 */

/**
 * Atto text editor title plugin.
 *
 * @namespace M.atto_qmulcite
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */
Y.namespace('M.atto_qmulcite').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    initializer: function() {
        this.addButton({
            icon: 'e/cite',
            callback: this._Cite
        });
    },


    _CiteExist: function(element){

        var parentEl = element.commonAncestorContainer;

        if(typeof parentEl !== "undefined"){
            parentEl = parentEl.parentNode;
        }else{
            parentEl = element.parentElement;
        }

        if(parentEl.tagName == 'CITE'){

            parentEl.outerHTML = parentEl.outerHTML + parentEl.innerHTML;
            document.getElementById(parentEl.id).remove();

            return true;

        }else if(!this._hasClass(parentEl,'editor_atto_content_wrap')){

            return this._CiteExist(parentEl);
        }

        return false;

    },

    /**
     * Add cite
     *
     * @param {EventFacade} e
     * @private
     */
    _Cite: function(e) {

        var _id = 'uniqueCiteId' + Math.floor(Math.random() * 15); // We need the id for removing the cite

        var _selection = document.getSelection().getRangeAt(0),
            _text = '<cite id="'+_id+'">'+ _selection.toString()+'</cite>&nbsp;';

        var CiteExist = this._CiteExist(_selection);

        if( CiteExist || _selection.startContainer.tagName === 'CITE'
            ||  _selection.endContainer.tagName === 'CITE'
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