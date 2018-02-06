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
 * Question renderer.
 *
 * @package    theme_qmul
 * @copyright  2017 Catalyst IT Europe LTD (http://www.catalyst-eu.net)
 * @author     Mark Webster (mark.webster@catalyst-eu.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_qmul\output\core;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/qmframework/lib.php');

class question_renderer extends \core_question_renderer {

    /**
     * Override the function to generate the information bit of the question
     * display that contains the metadata like the question number, current
     * state, and mark to remove the mark on the Skills Audit quiz. Mark is still
     * shown on other quizzes.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param qbehaviour_renderer $behaviouroutput the renderer to output the behaviour
     *      specific parts.
     * @param qtype_renderer $qtoutput the renderer to output the question type
     *      specific parts.
     * @param question_display_options $options controls what should and should not be displayed.
     * @param string|null $number The question number to display. 'i' is a special
     *      value that gets displayed as Information. Null means no number is displayed.
     * @return HTML fragment.
     */
    function info(\question_attempt $qa, \qbehaviour_renderer $behaviouroutput,
            \qtype_renderer $qtoutput, \question_display_options $options, $number) {
        global $COURSE;

        $cid = $COURSE->id;
        $qid = $this->get_page()->cm->instance;
        $settings = \local_qmframework_course_and_quiz_settings();

        if ($cid == $settings['course'] && $qid == $settings['quizid']) {
            $output = '';
            $output .= $this->number($number);
            $output .= $this->status($qa, $behaviouroutput, $options);
            $output .= $this->question_flag($qa, $options->flags);
            $output .= $this->edit_question_link($qa, $options);
            return $output;
        } else {
            return parent::info($qa, $behaviouroutput, $qtoutput, $options, $number);
        }
    }
}
