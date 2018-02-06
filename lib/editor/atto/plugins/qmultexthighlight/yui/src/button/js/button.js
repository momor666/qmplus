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
 * @package    atto_qmultexthighlight
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_qmultexthighlight-button
 */

/**
 * Atto text editor title plugin.
 *
 * @namespace M.atto_qmultexthighlight
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

var component = 'atto_qmultexthighlight';

Y.namespace('M.atto_qmultexthighlight').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function(params) {
        var items = [];
        Y.Object.each(params.styles, function(style) {
            var htmlclass = '';

            if (style.class!==''){
                htmlclass=' '+style.attr+'="'+style.attrValue+'" ';
            }


            var text = '';

            if(style.tagname !== '') {
                text = '<'+style.tagname+htmlclass+'>'+M.util.get_string(style.stylename, component)+'</'+style.tagname+'>';
            } else {
                text = '<div class="removehighlight">'+M.util.get_string(style.stylename, component)+'</div>';
            }


            items.push({
                //text: M.util.get_string(style.stylename, component),
                //render style is the dropdown list
                text: text,

                callbackArgs: {
                    tagname: style.tagname,
                    attr: style.attr,
                    attrValue: style.attrValue
                }
            });
        });
        this.addToolbarMenu({
            icon: 'e/text_highlight',
            globalItemConfig: {
                callback: this._changeStyle
            },
            items: items
        });
    },

    /**
     * Change the title to the specified style.
     *
     * @method _changeStyle
     * @param {EventFacade} e
     * @param {Object} The new style
     * @private
     */
    _changeStyle: function(e, style) {

        var htmlclass = '';
        if (style.classname!==''){
            htmlclass=' '+style.attr+'="'+style.attrValue+'" ';
        }

        /*var selection = document.getSelection();

         var str = selection.toString();
         var divs= $('div:contains("'+str+'")');

         $.each(divs, function(){

         console.log($(this).text());
         if ($(this).text() === str && $(this).hasClass('qmhighlighted')){
         $(this).remove();
         }

         });*/

        /**
         * tagname has to change to class and theme styles need to be updated to classess as well.
         *
         */

        /*  if(style.tagname !== '') {
         document.execCommand("insertHTML", false,
         '<'+style.tagname+' '+htmlclass+' class="qmhighlighted">'+ str+'</'+style.tagname+'>');
         } else {
         document.execCommand("insertHTML", false, str);
         }*/


        var selection = window.getSelection();

        var str = "";

        if (window.getSelection
            && window.getSelection().toString())
//            && $(window.getSelection()).attr('type') != "Caret")
        {
            str = window.getSelection();
        }
        else if (document.getSelection
            && document.getSelection().toString())
//            && $(document.getSelection()).attr('type') != "Caret")
        {
            str = document.getSelection();
        }
        else {
            selection = document.selection && document.selection.createRange();

            if (!(typeof selection === "undefined")
                && selection.text
                && selection.text.toString()) {
                str = selection.text;
            }
        }

        if(style.tagname !== '' && str !== '') {
            this._pasteHtmlAtCaret('<'+style.tagname+' '+htmlclass+' class="qmhighlighted">'+ str+'</'+style.tagname+'>');
        } else if(str !== '') {
            this._pasteHtmlAtCaret(str.toString());
        }

        // Mark as updated
        this.markUpdated();


        // Mark as updated
        this.markUpdated();
    },
    _pasteHtmlAtCaret: function (html) {

        var sel, range;
        if (window.getSelection) {
            // IE9 and non-IE
            sel = window.getSelection();
            console.log(sel);
            if (sel.getRangeAt && sel.rangeCount) {
                range = sel.getRangeAt(0);

                if(sel.anchorNode.parentNode) {

                    if(sel.anchorNode.parentNode.id.indexOf('highlight-')>=0){
//                        var parentNode = sel.anchorNode.parentNode;
//                        $(parentNode).remove();
                        var pn = sel.anchorNode.parentNode;
                        pn.parentNode.removeChild(pn);
                    }
                }

                range.deleteContents();

                var el = document.createElement("div");
                el.innerHTML = html;

                var frag = document.createDocumentFragment(), node, lastNode;
                while ( (node = el.firstChild) ) {
                    lastNode = frag.appendChild(node);
                }

                var firstNode = frag.firstChild;
                range.insertNode(frag);

                return true;

            }
        } else if ( (sel = document.selection) && sel.type != "Control") {
            // IE < 9
            var originalRange = sel.createRange();
            originalRange.collapse(true);
            sel.createRange().pasteHTML(html);
            if (selectPastedContent) {
                range = sel.createRange();
                range.setEndPoint("StartToStart", originalRange);
                range.select();
            }
        }
    }
});