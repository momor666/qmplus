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

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_qmul
 * @copyright  2016 Andrew Davidson, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/qmframework/lib.php');
require_once($CFG->dirroot.'/mod/quiz/renderer.php');

class theme_qmul_mod_quiz_renderer extends mod_quiz_renderer {

    /**
     * Override renderer to create the summary page to remove the summary
     * table and change the header on the Skills Audit quiz. Other quizzes
     * use the standard renderer.
     *
     * @param quiz_attempt $attemptobj
     * @param mod_quiz_display_options $displayoptions
     */
    public function summary_page($attemptobj, $displayoptions) {
        global $COURSE, $PAGE;

        $cid = $COURSE->id;
        $qid = $PAGE->cm->instance;
        $settings = \local_qmframework_course_and_quiz_settings();

        if ($cid == $settings['course'] && $qid == $settings['quizid']) {
            $output = '';
            $output .= $this->header();
            $output .= $this->heading(format_string($attemptobj->get_quiz_name()));
            $output .= $this->heading(get_string('submitskillsreview', 'local_qmframework'), 3);
            $output .= \html_writer::tag('p', get_string('submitskillsreviewtext', 'local_qmframework'));
            $output .= $this->summary_page_controls($attemptobj);
            $output .= $this->footer();
            return $output;
        } else {
            return parent::summary_page($attemptobj, $displayoptions);
        }
    }

    /**
     * Override renderer to create the review page to remove the summary
     * table and add a link to the dashbaord. Other quizzes
     * use the standard renderer.
     *
     * @param quiz_attempt $attemptobj an instance of quiz_attempt.
     * @param array $slots an array of intgers relating to questions.
     * @param int $page the current page number
     * @param bool $showall whether to show entire attempt on one page.
     * @param bool $lastpage if true the current page is the last page.
     * @param mod_quiz_display_options $displayoptions instance of mod_quiz_display_options.
     * @param array $summarydata contains all table data
     * @return $output containing html data.
     */
    public function review_page(\quiz_attempt $attemptobj, $slots, $page, $showall,
                                $lastpage, \mod_quiz_display_options $displayoptions,
                                $summarydata) {
        global $COURSE, $PAGE;

        $cid = $COURSE->id;
        $qid = $PAGE->cm->instance;
        $settings = \local_qmframework_course_and_quiz_settings();

        if ($cid == $settings['course'] && $qid == $settings['quizid']) {
            $link = \local_qmframework\qmframework::get_dashboardlink();
            $output = '';
            $output .= $this->header();
            if ($link) {
                $output .= \html_writer::link($link, get_string('viewdashboard', 'local_qmframework'));
            }
            $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
            $output .= $this->footer();
            return $output;
        } else {
            return parent::review_page($attemptobj, $slots, $page, $showall, $lastpage, $displayoptions, $summarydata);
        }
    }

    public function result($attemptobj) {
        $output = '';
        $attempt = $attemptobj->get_attempt();
        $quiz = $attemptobj->get_quiz();
        $output .= html_writer::start_tag('div', array('class'=>'col-sm-4 clearfix'));
        $grade = format_float($attempt->sumgrades * 100 / $quiz->sumgrades, 0);
        $output .= html_writer::tag('div', $grade.'%', array('class'=>'quizgrade'));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Outputs the table containing data from summary data array
     *
     * @param array $summarydata contains row data for table
     * @param int $page contains the current page number
     */
    public function review_summary_table($summarydata, $page) {
        $summarydata = $this->filter_review_summary_table($summarydata, $page);
        if (empty($summarydata)) {
            return '';
        }

        $output = '';
        $output .= html_writer::start_tag('div', array('class'=>'row quizinfo'));
        $output .= html_writer::start_tag('div', array('class'=>'col-sm-8'));
        $output .= html_writer::start_tag('table', array(
                'class' => 'table quiztable'));
        $output .= html_writer::start_tag('tbody');
        foreach ($summarydata as $rowdata) {
            if ($rowdata['title'] instanceof renderable) {
                $title = $this->render($rowdata['title']);
            } else {
                $title = $rowdata['title'];
            }

            $class = strtolower(str_replace(' ', '', $title));

            if ($rowdata['content'] instanceof renderable) {
                $content = $this->render($rowdata['content']);
            } else {
                $content = $rowdata['content'];
            }

            $output .= html_writer::tag('tr',
                html_writer::tag('th', $title, array('class' => 'cell '.$class, 'scope' => 'row')) .
                        html_writer::tag('td', $content, array('class' => 'cell'))
            );
        }

        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Returns either a liink or button
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     */
    public function finish_review_link(quiz_attempt $attemptobj) {
        $url = $attemptobj->view_url();

        if ($attemptobj->get_access_manager(time())->attempt_must_be_in_popup()) {
            $this->page->requires->js_init_call('M.mod_quiz.secure_window.init_close_button',
                    array($url), false, quiz_get_js_module());
            return html_writer::empty_tag('input', array('type' => 'button',
                    'value' => get_string('finishreview', 'quiz'),
                    'id' => 'secureclosebutton',
                    'class' => 'mod_quiz-next-nav btn btn-primary'));

        } else {
            return html_writer::link($url, get_string('finishreview', 'quiz'),
                    array('class' => 'mod_quiz-next-nav btn btn-primary'));
        }
    }

    /**
     * Creates the navigation links/buttons at the bottom of the reivew attempt page.
     *
     * Note, the name of this function is no longer accurate, but when the design
     * changed, it was decided to keep the old name for backwards compatibility.
     *
     * @param quiz_attempt $attemptobj instance of quiz_attempt
     * @param int $page the current page
     * @param bool $lastpage if true current page is the last page
     * @param bool|null $showall if true, the URL will be to review the entire attempt on one page,
     *      and $page will be ignored. If null, a sensible default will be chosen.
     *
     * @return string HTML fragment.
     */
    public function review_next_navigation(quiz_attempt $attemptobj, $page, $lastpage, $showall = null) {
        $nav = '';
        if ($page > 0) {
            $nav .= link_arrow_left(get_string('navigateprevious', 'quiz'),
                    $attemptobj->review_url(null, $page - 1, $showall), false, 'mod_quiz-prev-nav');
        }
        if ($lastpage) {
            $nav .= $this->finish_review_link($attemptobj);
        } else {
            $nav .= link_arrow_right(get_string('navigatenext', 'quiz'),
                    $attemptobj->review_url(null, $page + 1, $showall), false, 'mod_quiz-next-nav');
        }
        return html_writer::tag('div', $nav, array('class' => 'submitbtns float-right'));
    }

    /**
     * Display the prev/next buttons that go at the bottom of each page of the attempt.
     *
     * @param int $page the page number. Starts at 0 for the first page.
     * @param bool $lastpage is this the last page in the quiz?
     * @param string $navmethod Optional quiz attribute, 'free' (default) or 'sequential'
     * @return string HTML fragment.
     */
    protected function attempt_navigation_buttons($page, $lastpage, $navmethod = 'free') {
        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'submitbtns'));
        if ($page > 0 && $navmethod == 'free') {
            $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'previous',
                    'value' => get_string('navigateprevious', 'quiz'), 'class' => 'mod_quiz-prev-nav btn btn-primary'));
        }
        if ($lastpage) {
            $nextlabel = get_string('endtest', 'quiz');
        } else {
            $nextlabel = get_string('navigatenext', 'quiz');
        }
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'next',
                'value' => $nextlabel, 'class' => 'mod_quiz-next-nav btn btn-primary'));
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Outputs the navigation block panel
     *
     * @param quiz_nav_panel_base $panel instance of quiz_nav_panel_base
     */
    public function navigation_panel(quiz_nav_panel_base $panel) {

        $output = '';
        $userpicture = $panel->user_picture();
        if ($userpicture) {
            $fullname = fullname($userpicture->user);
            $class = 'small';
            if ($userpicture->size === true) {
                $class = 'large';
                $fullname = html_writer::div($fullname);
            }
            $output .= html_writer::tag('div', $this->render($userpicture) . $fullname,
                    array('id' => 'user-picture', 'class' => 'clearfix '.$class));
        }
        $output .= $panel->render_before_button_bits($this);

        $bcc = $panel->get_button_container_class();
        $output .= html_writer::start_tag('div', array('class' => "qn_buttons clearfix $bcc"));
        foreach ($panel->get_question_buttons() as $button) {
            $output .= $this->render($button);
        }
        $output .= html_writer::end_tag('div');

        $output .= html_writer::tag('div', $panel->render_end_bits($this),
                array('class' => 'othernav'));

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false,
                quiz_get_js_module());

        return $output;
    }
}