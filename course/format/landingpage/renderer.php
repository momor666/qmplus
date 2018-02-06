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
 * Renderer for outputting the sections course format.
 *
 * @package format_landingpage
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');
require_once($CFG->dirroot.'/course/format/topics/renderer.php');

/**
 * Basic renderer for sections format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_landingpage_renderer extends format_topics_renderer {

    private $editing = false;

    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        if ($page->user_is_editing()) {
            $this->editing = true;
        }
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        $o = html_writer::start_tag('div', array('class'=>'row'));
        if ($this->editing) {
            $o .= html_writer::start_tag('ul', array('class'=>'row sections'));
            return $o;
        }
        $o .= html_writer::start_tag('ul', array('class'=>'row sections grid'));
        $o .= html_writer::tag('div', '', array('class' => 'grid-sizer col-12 col-md-6 col-lg-4'));
        return $o;
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        $o = html_writer::end_tag('ul');
        $o .= html_writer::end_tag('div');
        return $o;
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;

        $o = '';
        $currenttext = '';
        $sectionstyle = '';
        $context = context_course::instance($course->id);

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $imageheader = false;
        if ($settings = course_get_format($course)->get_format_options($section)) {
            if ($settings['style']) {
                $sectionstyle .= " {$settings['style']}";
            }
            if ($settings['style'] == 'imageheader') {
                $image = course_get_format($course)->get_section_image($context, $section);
                if ($image) {
                    $imageheader = true;
                    $imageurl = moodle_url::make_pluginfile_url($image->get_contextid(), $image->get_component(), $image->get_filearea(),
                                               $image->get_itemid(), $image->get_filepath(), $image->get_filename());
                }
            }
            if ($settings['showonmobile'] == 0) {
                $sectionstyle .= " hidden-md-down";
            }
        }

        $o .= html_writer::start_tag('div', array('id' => 'section-'.$section->section,
            'class' => 'grid-item section col-12 col-md-6 col-lg-4 mb-2 main clearfix'.$sectionstyle, 'role'=>'region',
            'aria-label'=> get_section_name($course, $section)));
        $o .= html_writer::start_tag('div', array('class'=>'card'));

        $sectionname = false;
        if (!$imageheader || $section->name != '') {
            $sectionname = get_section_name($course, $section);
        }

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => 'hidden sectionname'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        if ($sectionname) {
            $sectionname = html_writer::tag('span', $this->section_title($section, $course));
        }

        $summary = $this->format_summary_text($section);

        if (!$imageheader) {
            $o.= html_writer::start_tag('div', array('class' => 'card-header sectionheader'));
            if ($sectionname) {
                $o .= $this->output->heading($sectionname, 5, 'sectionname card-title' . $classes);
            }
            $o .= html_writer::end_tag('div');

            $o .= html_writer::start_tag('div', array('class' => 'card-block sectioncontent content'));
        } else {
            $o .= html_writer::tag('div', '', array('class'=>'topimage', 'style'=>"background-image: url($imageurl)"));
            $o .= html_writer::start_tag('div', array('class' => 'card-block sectioncontent content'));

            if ($sectionname) {
                $o .= $this->output->heading($sectionname, 5, 'sectionname card-title text-primary' . $classes);
            }
        }

        if ($this->editing || $summary) {
            $o.= html_writer::start_tag('div', array('class' => 'summary'));
            $o.= $summary;;
            $o.= html_writer::end_tag('div');
        }

        $o .= $this->section_availability_message($section,
                has_capability('moodle/course:viewhiddensections', $context));

        $o .= html_writer::end_tag('div');
        //End Card-block

        return $o;
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_right_content($section, $course, $onsectionpage) {

        $controls = $this->section_edit_control_items($course, $section, $onsectionpage);
        $o = $this->section_edit_control_menu($controls, $course, $section);

        return $o;
    }

    /**
     * Generate the content to displayed on the left part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_left_content($section, $course, $onsectionpage) {
        $o = '';
        if ($section->section != 0) {
            // Only in the non-general sections.
            if (course_get_format($course)->is_section_current($section)) {
                $o = get_accesshide(get_string('currentsection', 'format_'.$course->format));
            }
        }

        return $o;
    }

    private function section_controls($section, $course, $onsectionpage, $sectionreturn=null) {

        $content = '';
        $content = $this->courserenderer->course_section_add_cm_control($course, 0, 0);

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);

        $content .= $leftcontent.$rightcontent;

        if (!$content) {
            return '';
        }

        $o = html_writer::start_tag('div', array('class'=>'card-footer left right'));
        $o .= $content;
        $o .= html_writer::end_tag('div');
        return $o;
    }

    /**
     * Generate the display of the footer part of a section
     *
     * @return string HTML to output.
     */
    protected function section_footer() {
        $o = html_writer::end_tag('div');
        $o .= html_writer::end_tag('div');

        return $o;
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        if ($course->hidecoursecontent && !$PAGE->user_is_editing()) {
            echo '<style>#page-content {display: none}</style>';
            return '';
        }

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        // Print fake section for user info
        echo $this->user_section();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    echo $this->section_header($thissection, $course, false, 0);
                    $cmlist = $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    if ($this->editing || $cmlist != '<ul class="section img-text"></ul>') {
                        echo html_writer::start_tag('div', array('class'=>'card-block sectioncontent content'));
                        echo $cmlist;
                        echo html_writer::end_tag('div');
                    }
                    echo $this->section_controls($thissection, $course, false, 0);
                    echo $this->section_footer();
                }
                continue;
            }
            if ($section > $course->numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo));
            if (!$showsection) {
                // If the hiddensections option is set to 'show hidden sections in collapsed
                // form', then display the hidden section message - UNLESS the section is
                // hidden by the availability system, which is set to hide the reason.
                if (!$course->hiddensections && $thissection->available && $PAGE->user_is_editing()) {
                    echo $this->section_hidden($section, $course->id);
                }

                continue;
            }

            echo $this->section_header($thissection, $course, false, 0);
            if ($thissection->uservisible) {
                $cmlist = $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                if ($cmlist != '<ul class="section img-text"></ul>') {
                    echo html_writer::start_tag('div', array('class'=>'card-block sectioncontent content'));
                    echo $cmlist;
                    echo html_writer::end_tag('div');
                }
            }
            echo $this->section_controls($thissection, $course, false, 0);
            echo $this->section_footer();
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                echo $this->stealth_section_header($section);
                $cmlist = $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                if ($cmlist != '<ul class="section img-text"></ul>') {
                    echo html_writer::start_tag('div', array('class'=>'card-block sectioncontent content'));
                    echo $cmlist;
                    echo html_writer::end_tag('div');
                }
                echo $this->section_controls($thissection, $course, false, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php',
                array('courseid' => $course->id,
                      'increase' => true,
                      'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon.get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                          'increase' => false,
                          'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon.get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }

    }

    /**
     * Generate the header html of a stealth section
     *
     * @param int $sectionno The section number in the coruse which is being dsiplayed
     * @return string HTML to output.
     */
    protected function stealth_section_header($sectionno) {
        $o = '';
        $o.= html_writer::start_tag('div', array('id' => 'section-'.$sectionno, 'class' => 'section main clearfix orphaned hidden col-12'));
        $o.= html_writer::start_tag('div', array('class'=>'card'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));
        $o.= $this->output->heading(get_string('orphanedactivitiesinsectionno', '', $sectionno), 3, 'sectionname card-header');
        return $o;
    }

    /**
     * Generate footer html of a stealth section
     *
     * @return string HTML to output.
     */
    protected function stealth_section_footer() {
        $o = html_writer::end_tag('div');
        $o.= html_writer::end_tag('div');
        $o.= html_writer::end_tag('div');
        return $o;
    }

    public function user_section() {
        global $USER;

        $o = html_writer::start_tag('div', array('class'=>'grid-item mb-1 usersection col-12 col-md-6 col-lg-4'));
        $o .= html_writer::start_tag('div', array('class'=>'card'));
        $o .= html_writer::start_tag('div', array('class'=>'card-block d-flex align-items-center justify-content-center'));

        $picture = $this->output->user_picture($USER, array('size' => 50, 'class' => 'avatar rounded-circle d-block'));
        $o .= $picture;

        $o .= html_writer::start_tag('div', array('class'=>'userinfo text-left'));
        $o .= $this->output->heading(get_string('loggedinas', 'moodle', ''), 6, 'loggedinas mb-0');
        $o .= $this->output->heading($USER->firstname.' '.$USER->lastname, 3, 'fullname text-primary font-weight-bold mb-0');
        $o .= html_writer::end_tag('div');

        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('div');
        $o .= html_writer::end_tag('div');
        return $o;
    }
}
