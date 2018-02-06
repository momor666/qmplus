<?php
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

/**
 * Renderer outputting the quiz editing UI.
 *
 * @package mod_quiz
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_qmul\output\mod_quiz;
defined('MOODLE_INTERNAL') || die();

use \mod_quiz\structure;
use \html_writer;
use renderable;

/**
 * Renderer outputting the quiz editing UI.
 *
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.7
 */
class edit_renderer extends \mod_quiz\output\edit_renderer {

    public function page_row(structure $structure, $slot, $contexts, $pagevars, $pageurl) {
        $output = '';

        $pagenumber = $structure->get_page_number_for_slot($slot);

        // Put page in a heading for accessibility and styling.
        $page = $this->heading(get_string('page') . ' ' . $pagenumber, 4, array('class'=>'m-0 .h5'));

        if ($structure->is_first_slot_on_page($slot)) {
            // Add the add-menu at the page level.
            $addmenu = html_writer::tag('span', $this->add_menu_actions($structure,
                    $pagenumber, $pageurl, $contexts, $pagevars),
                    array('class' => 'add-menu-outer'));

            $addquestionform = $this->add_question_form($structure,
                    $pagenumber, $pageurl, $pagevars);

            $output .= html_writer::tag('li', $page . $addmenu . $addquestionform,
                    array('class' => 'pagenumber activity yui3-dd-drop page', 'id' => 'page-' . $pagenumber));
        }

        return $output;
    }

    public function question(structure $structure, $slot, \moodle_url $pageurl) {
        $output = '';
        $output .= html_writer::start_tag('div');

        if ($structure->can_be_edited()) {
            $output .= $this->question_move_icon($structure, $slot);
        }

        $output .= html_writer::start_div('mod-indent-outer w-100 d-flex flex-wrap');
        $output .= html_writer::start_div('col-2 col-sm-1');
        $output .= $this->question_number($structure->get_displayed_number_for_slot($slot));
        $output .= html_writer::end_tag('div'); // .activityinstance.

        if ($structure->get_question_type_for_slot($slot) == 'random') {
            $questionname = $this->random_question($structure, $slot, $pageurl);
        } else {
            $questionname = $this->question_name($structure, $slot, $pageurl);
        }

        $output .= html_writer::start_div('col-10 col-sm-8');
        $output .= html_writer::start_div('activityinstance');
        $output .= $questionname;

        // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
        $output .= html_writer::end_tag('div'); // .activityinstance.
        $output .= html_writer::end_tag('div');

        // Action icons.
        $questionicons = '';
        $questionicons .= $this->question_preview_icon($structure->get_quiz(), $structure->get_question_in_slot($slot));
        if ($structure->can_be_edited()) {
            $questionicons .= $this->question_remove_icon($structure, $slot, $pageurl);
        }
        $questionicons .= $this->marked_out_of_field($structure, $slot);

        $output .= html_writer::start_div('col-12 col-sm-3 text-right');
        $output .= html_writer::span($questionicons, 'actions'); // Required to add js spinner icon.
        if ($structure->can_be_edited()) {
            $output .= $this->question_dependency_icon($structure, $slot);
        }

        // End of indentation div.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }
    public function question_number($number) {
        if (is_numeric($number)) {
            $number = html_writer::span(get_string('question'), 'accesshide') . ' ' . $number;
        }
        return html_writer::tag('span', $number, array('class' => 'slotnumber m-0'));
    }


    public function marked_out_of_field(structure $structure, $slot) {
        if (!$structure->is_real_question($slot)) {
            $output = html_writer::span('',
                    'instancemaxmark decimalplaces_' . $structure->get_decimal_places_for_question_marks());

            $output .= html_writer::span(
                    $this->pix_icon('spacer', '', 'moodle', array('class' => 'editicon visibleifjs', 'title' => '')),
                    'editing_maxmark');
            return html_writer::span($output, 'instancemaxmarkcontainer infoitem');
        }

        $output = html_writer::span($structure->formatted_question_grade($slot),
                'instancemaxmark decimalplaces_' . $structure->get_decimal_places_for_question_marks(),
                array('title' => get_string('maxmark', 'quiz')));

        $output .= html_writer::span(
            html_writer::link(
                new \moodle_url('#'),
                $this->pix_icon('t/editstring', '', 'moodle', array('class' => 'editicon visibleifjs', 'title' => '')),
                array(
                    'class' => 'editing_maxmark',
                    'data-action' => 'editmaxmark',
                    'title' => get_string('editmaxmark', 'quiz'),
                )
            )
        );
        return html_writer::span($output, 'instancemaxmarkcontainer d-inline-block m-0');
    }

}
