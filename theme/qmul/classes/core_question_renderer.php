<?php
// This file is part of The Bootstrap Moodle theme
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

defined('MOODLE_INTERNAL') || die();

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_qmul
 * @copyright 2016 Andrew Davidson, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . "/question/engine/renderer.php");

class theme_qmul_core_question_renderer extends core_question_renderer {

    public function question_preview_link($questionid, context $context, $showlabel) {
        if ($showlabel) {
            $alt = '';
            $label = ' ' . get_string('preview');
            $attributes = array();
        } else {
            $alt = get_string('preview');
            $label = '';
            $attributes = array('title' => $alt);
        }
        $attributes['class'] = 'btn btn-success questionpreview';

        $image = $this->pix_icon('t/preview', $alt, '', array('class' => 'iconsmall'));
        $link = question_preview_url($questionid, null, null, null, null, $context);
        $action = new popup_action('click', $link, 'questionpreview',
                question_preview_popup_params());

        return $this->action_link($link, $image . $label, $action, $attributes);
    }

    /**
     * Render the question flag, assuming $flagsoption allows it.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param int $flagsoption the option that says whether flags should be displayed.
     */
    protected function question_flag(question_attempt $qa, $flagsoption) {
        global $CFG;

        $divattributes = array('class' => 'questionflag label');

        switch ($flagsoption) {
            case question_display_options::VISIBLE:
                $flagcontent = $this->get_flag_html($qa->is_flagged());
                break;

            case question_display_options::EDITABLE:
                $id = $qa->get_flag_field_name();
                // The checkbox id must be different from any element name, because
                // of a stupid IE bug:
                // http://www.456bereastreet.com/archive/200802/beware_of_id_and_name_attribute_mixups_when_using_getelementbyid_in_internet_explorer/
                $checkboxattributes = array(
                    'type' => 'checkbox',
                    'id' => $id . 'checkbox',
                    'name' => $id,
                    'value' => 1,
                );
                if ($qa->is_flagged()) {
                    $checkboxattributes['checked'] = 'checked';
                }
                $postdata = question_flags::get_postdata($qa);

                $flagcontent = html_writer::empty_tag('input',
                                array('type' => 'hidden', 'name' => $id, 'value' => 0)) .
                        html_writer::empty_tag('input', $checkboxattributes) .
                        html_writer::empty_tag('input',
                                array('type' => 'hidden', 'value' => $postdata, 'class' => 'questionflagpostdata')) .
                        html_writer::tag('label', $this->get_flag_html($qa->is_flagged(), $id . 'img'),
                                array('id' => $id . 'label', 'for' => $id . 'checkbox')) . "\n";

                if ($qa->is_flagged()) {
                    $label = 'danger';
                } else {
                    $label = 'warning';
                }

                $divattributes = array(
                    'class' => 'questionflag editable label label-'.$label,
                    'aria-atomic' => 'true',
                    'aria-relevant' => 'text',
                    'aria-live' => 'assertive',
                );

                break;

            default:
                $flagcontent = '';
        }

        return html_writer::nonempty_tag('div', $flagcontent, $divattributes);
    }

    /**
     * Work out the actual img tag needed for the flag
     *
     * @param bool $flagged whether the question is currently flagged.
     * @param string $id an id to be added as an attribute to the img (optional).
     * @return string the img tag.
     */
    protected function get_flag_html($flagged, $id = '') {

        $original = parent::get_flag_html($flagged, $id);
        $original = html_writer::tag('div', $original, array('class'=>'hidden'));

        if ($flagged) {
            $text = get_string('flagged', 'question');
            $class = 'flagged';
        } else {
            $text = get_string('notflagged', 'question');
            $class = 'notflagged';
        }

        return $original.'<i class="glyphicon glyphicon-flag"></i>'.$text;
    }

}