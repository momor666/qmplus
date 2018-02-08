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
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage qmultc
 * @version    See the value of '$plugin->version' in below.
 * @copyright  &copy; 2012-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/topcoll/lib.php');
require_once($CFG->dirroot . '/course/format/qmultc/lib.php');
require_once($CFG->dirroot . '/course/format/qmultc/togglelib.php');
require_once($CFG->dirroot . '/theme/qmul/classes/output/format_topcoll_renderer.php');

class format_qmultc_renderer extends theme_qmul_format_topcoll_renderer {
/*
    private $tccolumnwidth = 100; // Default width in percent of the column(s).
    private $tccolumnpadding = 0; // Default padding in pixels of the column(s).
    private $mobiletheme = false; // As not using a mobile theme we can react to the number of columns setting.
    private $tablettheme = false; // As not using a tablet theme we can react to the number of columns setting.
    private $courseformat = null; // Our course format object as defined in lib.php;
    private $tcsettings; // Settings for the format - array.
    private $defaultuserpreference; // Default user preference when none set - bool - true all open, false all closed.
    private $togglelib;
    private $isoldtogglepreference = false;
    private $userisediting = false;
    private $tctoggleiconsize;
    private $formatresponsive;
    private $rtl = false;
    private $bsnewgrid = false;
*/

    protected $tccolumnwidth = 100; // Default width in percent of the column(s).
    protected $tccolumnpadding = 0; // Default padding in pixels of the column(s).
    protected $mobiletheme = false; // As not using a mobile theme we can react to the number of columns setting.
    protected $tablettheme = false; // As not using a tablet theme we can react to the number of columns setting.
    protected $courseformat = null; // Our course format object as defined in lib.php;
    protected $tcsettings; // Settings for the format - array.
    protected $defaultuserpreference; // Default user preference when none set - bool - true all open, false all closed.
    protected $togglelib;
    protected $isoldtogglepreference = false;
    protected $userisediting = false;
    protected $tctoggleiconsize;
    protected $formatresponsive;
    protected $rtl = false;
    protected $bsnewgrid = false;

    protected $userpreference; // User toggle state preference - string.

    /**
     * Constructor method, calls the parent constructor - MDL-21097.
     *
     * @param moodle_page $page.
     * @param string $target one of rendering target constants.
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->togglelib = new qmultc_togglelib;
        $this->courseformat = course_get_format($page->course); // Needed for collapsed topics settings retrieval.

        /* Since format_topcoll_renderer::section_edit_control_items() only displays the 'Set current section' control when editing
          mode is on we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any
          other managing capability. */
        $page->set_other_editing_capability('moodle/course:setcurrentsection');

        global $PAGE;
        $this->userisediting = $PAGE->user_is_editing();
        $this->tctoggleiconsize = clean_param(get_config('format_topcoll', 'defaulttoggleiconsize'), PARAM_TEXT);
        $this->formatresponsive = get_config('format_topcoll', 'formatresponsive');

        $this->rtl = right_to_left();

        if (strcmp($page->theme->name, 'boost') === 0) {
            $this->bsnewgrid = true;
        }
    }

    /**
     * Generate the starting container html for a list of sections.
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'qmulctopics p-0'));
    }

    /**
     * Generate the starting container html for a list of sections when showing a toggle.
     * @return string HTML to output.
     */
    protected function start_toggle_section_list() {
        $classes = 'qmulctopics p-0';
        $attributes = array();
        if (($this->mobiletheme === true) || ($this->tablettheme === true)) {
            $classes .= ' ctportable';
        }
        if ($this->formatresponsive) {
            $style = '';
            if ($this->tcsettings['layoutcolumnorientation'] == 1) { // Vertical columns.
                $style .= 'width:' . $this->tccolumnwidth . '%;';
            } else {
                $style .= 'width: 100%;';  // Horizontal columns.
            }
            if ($this->mobiletheme === false) {
                $classes .= ' ctlayout';
            }
            $style .= ' padding-left: ' . $this->tccolumnpadding . 'px; padding-right: ' . $this->tccolumnpadding . 'px;';
            $attributes['style'] = $style;
        } else {
            if ($this->tcsettings['layoutcolumnorientation'] == 1) { // Vertical columns.
                $classes .= ' ' . $this->get_column_class($this->tcsettings['layoutcolumns']);
            } else {
                $classes .= ' ' . $this->get_row_class();
            }
        }
        $attributes['class'] = $classes;

        return html_writer::start_tag('ul', $attributes);
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
        $modinfo = get_fast_modinfo($course);
        $course = $this->courseformat->get_course();
        if (empty($this->tcsettings)) {
            $this->tcsettings = $this->courseformat->get_settings();
        }

        $this->tcsettings['showsectionsummary'] = 1;

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        if ($this->formatresponsive) {
            $this->tccolumnwidth = 100; // Reset to default.
        }

        $extratabnames = array('extratab1', 'extratab2', 'extratab3');
        $extratabs = array();
        if (isset($this->tcsettings['enable_assessmentinformation']) &&
            $this->tcsettings['enable_assessmentinformation'] == 1) {
            $tab = new stdClass();
            $tab->name = 'assessmentinformation';
            $tab->title = get_string('assessmentinformation', 'format_qmultc');
            $tab->content = qmul_format_get_assessmentinformation($this->tcsettings['content_assessmentinformation']);
            $extratabs[] = $tab;
        }

        foreach ($extratabnames as $extratabname) {
            if (isset($this->tcsettings["enable_{$extratabname}"]) &&
                $this->tcsettings["enable_{$extratabname}"] == 1) {
                $tab = new stdClass();
                $tab->name = $extratabname;
                $tab->title = format_text($this->tcsettings["title_{$extratabname}"]);
                $tab->content = format_text($this->tcsettings["content_{$extratabname}"], FORMAT_HTML, array('trusted'=>true, 'noclean'=>true));
                $extratabs[] = $tab;
            }
        }


        // Add tab navigation
        echo html_writer::start_tag('ul', array('class'=>'qmultabs nav nav-tabs row'));
            echo html_writer::start_tag('li', array('class'=>'qmultabitem nav-item'));
            echo html_writer::tag('a', get_string('modulecontent', 'format_qmultc'), array('data-toggle'=>'tab', 'class'=>'qmultablink nav-link active modulecontentlink', 'href'=>'#modulecontent'));
            echo html_writer::end_tag('li');
            if (function_exists('theme_qmul_add_pin_tab')) {
                theme_qmul_add_pin_tab();
            }
            foreach ($extratabs as $extratab) {
                echo html_writer::start_tag('li', array('class'=>'qmultabitem nav-item'));
                echo html_writer::tag('a', $extratab->title, array('data-toggle'=>'tab', 'class'=>"nav-link qmultablink {$extratab->name}", 'href'=>"#{$extratab->name}"));
                echo html_writer::end_tag('li');
            }
        echo html_writer::end_tag('ul');


        echo html_writer::start_tag('div', array('class'=>'qmultabcontent tab-content row bg-white'));
        echo html_writer::start_tag('div', array('id'=>'modulecontent', 'class'=>'col-12 tab-pane qmultab modulecontent active'));
        echo $this->start_section_list();

        $sections = $modinfo->get_section_info_all();
        // General section if non-empty.
        $thissection = $sections[0];
        unset($sections[0]);
        if ($thissection->summary or ! empty($modinfo->sections[0]) or $this->userisediting) {
            echo $this->section_header($thissection, $course, false, 0);
            // added topic zero block
            echo $this->output->custom_block_region('topiczero');
            echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
            echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0, 0);
            echo $this->section_footer();
        }

        if ($course->numsections > 0) {
            if ($course->numsections > 1) {
                if ($this->userisediting || $course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
                    // Collapsed Topics all toggles.
                    // echo $this->toggle_all();
                    if ($this->tcsettings['displayinstructions'] == 2) {
                        // Collapsed Topics instructions.
                        echo $this->display_instructions();
                    }
                }
            }
            $currentsectionfirst = false;
            if (($this->tcsettings['layoutstructure'] == 4) && (!$this->userisediting)) {
                $currentsectionfirst = true;
            }

            if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                $section = 1;
            } else {
                $timenow = time();
                $weekofseconds = 604800;
                $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);
                $section = $course->numsections;
                $weekdate = $course->enddate;      // This should be 0:00 Monday of that week.
                $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems.
            }

            $numsections = $course->numsections; // Because we want to manipulate this for column breakpoints.
            if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                $loopsection = 1;
                $numsections = 0;
                while ($loopsection <= $course->numsections) {
                    $nextweekdate = $weekdate - ($weekofseconds);
                    if ((($thissection->uservisible ||
                            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo))) &&
                            ($nextweekdate <= $timenow)) == true) {
                        $numsections++; // Section not shown so do not count in columns calculation.
                    }
                    $weekdate = $nextweekdate;
                    $section--;
                    $loopsection++;
                }
                // Reset.
                $section = $course->numsections;
                $weekdate = $course->enddate;      // This should be 0:00 Monday of that week.
                $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems.
            }

            if ($numsections < $this->tcsettings['layoutcolumns']) {
                $this->tcsettings['layoutcolumns'] = $numsections;  // Help to ensure a reasonable display.
            }
            if (($this->tcsettings['layoutcolumns'] > 1) && ($this->mobiletheme === false)) {
                if ($this->tcsettings['layoutcolumns'] > 4) {
                    // Default in config.php (and reset in database) or database has been changed incorrectly.
                    $this->tcsettings['layoutcolumns'] = 4;

                    // Update....
                    $this->courseformat->update_qmultc_columns_setting($this->tcsettings['layoutcolumns']);
                }

                if (($this->tablettheme === true) && ($this->tcsettings['layoutcolumns'] > 2)) {
                    // Use a maximum of 2 for tablets.
                    $this->tcsettings['layoutcolumns'] = 2;
                }

                if ($this->formatresponsive) {
                    $this->tccolumnwidth = 100 / $this->tcsettings['layoutcolumns'];
                    if ($this->tcsettings['layoutcolumnorientation'] == 2) { // Horizontal column layout.
                        $this->tccolumnwidth -= 0.5;
                        $this->tccolumnpadding = 0; // In 'px'.
                    } else {
                        $this->tccolumnwidth -= 0.2;
                        $this->tccolumnpadding = 0; // In 'px'.
                    }
                }
            } else if ($this->tcsettings['layoutcolumns'] < 1) {
                // Distributed default in plugin settings (and reset in database) or database has been changed incorrectly.
                $this->tcsettings['layoutcolumns'] = 1;

                // Update....
                $this->courseformat->update_qmultc_columns_setting($this->tcsettings['layoutcolumns']);
            }

            echo $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::start_tag('div', array('class' => $this->get_row_class()));
            }
            echo $this->start_toggle_section_list();

            $loopsection = 1;
            $breaking = false; // Once the first section is shown we can decide if we break on another column.
            $canbreak = ($this->tcsettings['layoutcolumns'] > 1);
            $columncount = 1;
            $breakpoint = 0;
            $shownsectioncount = 0;

            if ($this->userpreference != null) {
                $this->isoldtogglepreference = $this->togglelib->is_old_preference($this->userpreference);
                if ($this->isoldtogglepreference == true) {
                    $ts1 = base_convert(substr($this->userpreference, 0, 6), 36, 2);
                    $ts2 = base_convert(substr($this->userpreference, 6, 12), 36, 2);
                    $thesparezeros = "00000000000000000000000000";
                    if (strlen($ts1) < 26) {
                        // Need to PAD.
                        $ts1 = substr($thesparezeros, 0, (26 - strlen($ts1))) . $ts1;
                    }
                    if (strlen($ts2) < 27) {
                        // Need to PAD.
                        $ts2 = substr($thesparezeros, 0, (27 - strlen($ts2))) . $ts2;
                    }
                    $tb = $ts1 . $ts2;
                } else {
                    // Check we have enough digits for the number of toggles in case this has increased.
                    $numdigits = $this->togglelib->get_required_digits($course->numsections);
                    if ($numdigits > strlen($this->userpreference)) {
                        if ($this->defaultuserpreference == 0) {
                            $dchar = $this->togglelib->get_min_digit();
                        } else {
                            $dchar = $this->togglelib->get_max_digit();
                        }
                        for ($i = strlen($this->userpreference); $i < $numdigits; $i++) {
                            $this->userpreference .= $dchar;
                        }
                    }
                    $this->togglelib->set_toggles($this->userpreference);
                }
            } else {
                $numdigits = $this->togglelib->get_required_digits($course->numsections);
                if ($this->defaultuserpreference == 0) {
                    $dchar = $this->togglelib->get_min_digit();
                } else {
                    $dchar = $this->togglelib->get_max_digit();
                }
                $this->userpreference = '';
                for ($i = 0; $i < $numdigits; $i++) {
                    $this->userpreference .= $dchar;
                }
                $this->togglelib->set_toggles($this->userpreference);
            }

            while ($loopsection <= $course->numsections) {
                if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                    $nextweekdate = $weekdate - ($weekofseconds);
                }
                $thissection = $modinfo->get_section_info($section);

                /* Show the section if the user is permitted to access it, OR if it's not available
                  but there is some available info text which explains the reason & should display. */
                if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                    $showsection = $thissection->uservisible ||
                            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));
                } else {
                    $showsection = ($thissection->uservisible ||
                        ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo))) &&
                        ($nextweekdate <= $timenow);
                }
                if (($currentsectionfirst == true) && ($showsection == true)) {
                    // Show the section if we were meant to and it is the current section:....
                    $showsection = ($course->marker == $section);
                } else if (($this->tcsettings['layoutstructure'] == 4) &&
                    ($course->marker == $section) && (!$this->userisediting)) {
                    $showsection = false; // Do not reshow current section.
                }
                if (!$showsection) {
                    // Hidden section message is overridden by 'unavailable' control.
                    $testhidden = false;
                    if ($this->tcsettings['layoutstructure'] != 4) {
                        if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                            $testhidden = true;
                        } else if ($nextweekdate <= $timenow) {
                            $testhidden = true;
                        }
                    } else {
                        if (($currentsectionfirst == true) && ($course->marker == $section)) {
                            $testhidden = true;
                        } else if (($currentsectionfirst == false) && ($course->marker != $section)) {
                            $testhidden = true;
                        }
                    }
                    if ($testhidden) {
                        if (!$course->hiddensections && $thissection->available) {
                            $shownsectioncount++;
                            echo $this->section_hidden($thissection);
                        }
                    }
                } else {
                    $shownsectioncount++;
                    if (!$this->userisediting && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                        // Display section summary only.
                        echo $this->section_summary($thissection, $course, null);
                    } else {
                        if ($this->isoldtogglepreference == true) {
                            $togglestate = substr($tb, $section, 1);
                            if ($togglestate == '1') {
                                $thissection->toggle = true;
                            } else {
                                $thissection->toggle = false;
                            }
                        } else {
                            $thissection->toggle = $this->togglelib->get_toggle_state($thissection->section);
                        }
                        echo $this->section_header($thissection, $course, false, 0);
                        if ($thissection->uservisible) {
                            echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                            echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
                        }
                        echo html_writer::end_tag('div');
                        echo $this->section_footer();
                    }
                }

                if ($currentsectionfirst == false) {
                    /* Only need to do this on the iteration when $currentsectionfirst is not true as this iteration will always
                      happen.  Otherwise you get duplicate entries in course_sections in the DB. */
                    unset($sections[$section]);
                }
                if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                    $section++;
                } else {
                    $section--;
                    if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                        $weekdate = $nextweekdate;
                    }
                }

                // Only check for breaking up the structure with rows if more than one column and when we output all of the sections.
                if (($canbreak === true) && ($currentsectionfirst === false)) {
                    // Only break in non-mobile themes or using a responsive theme.
                    if ((!$this->formatresponsive) || ($this->mobiletheme === false)) {
                        if ($this->tcsettings['layoutcolumnorientation'] == 1) {  // Vertical mode.
                            // This is not perfect yet as does not tally the shown sections and divide by columns.
                            if (($breaking == false) && ($showsection == true)) {
                                $breaking = true;
                                // Divide the number of sections by the number of columns.
                                $breakpoint = $numsections / $this->tcsettings['layoutcolumns'];
                            }

                            if (($breaking == true) && ($shownsectioncount >= $breakpoint) &&
                                ($columncount < $this->tcsettings['layoutcolumns'])) {
                                echo $this->end_section_list();
                                echo $this->start_toggle_section_list();
                                $columncount++;
                                // Next breakpoint is...
                                $breakpoint += $numsections / $this->tcsettings['layoutcolumns'];
                            }
                        } else {  // Horizontal mode.
                            if (($breaking == false) && ($showsection == true)) {
                                $breaking = true;
                                // The lowest value here for layoutcolumns is 2 and the maximum for shownsectioncount is 2, so :).
                                $breakpoint = $this->tcsettings['layoutcolumns'];
                            }

                            if (($breaking == true) && ($shownsectioncount >= $breakpoint) && ($loopsection < $course->numsections)) {
                                echo $this->end_section_list();
                                echo $this->start_toggle_section_list();
                                // Next breakpoint is...
                                $breakpoint += $this->tcsettings['layoutcolumns'];
                            }
                        }
                    }
                }

                $loopsection++;
                if (($currentsectionfirst == true) && ($loopsection > $course->numsections)) {
                    // Now show the rest.
                    $currentsectionfirst = false;
                    $loopsection = 1;
                    $section = 1;
                }
                if ($section > $course->numsections) {
                    // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                    break;
                }
            }
        }

        if ($this->userisediting and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection->section, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::end_tag('div');
            }

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php',
                array('courseid' => $course->id,
                'increase' => true,
                'sesskey' => sesskey())
            );
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon . get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                    'increase' => false,
                    'sesskey' => sesskey())
                );
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon . get_accesshide($strremovesection),
                    array('class' => 'reduce-sections')
                );
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::end_tag('div');
            }
        }
        echo html_writer::end_tag('div');

        foreach ($extratabs as $extratab) {
            echo html_writer::start_tag('div', array('id'=>$extratab->name, 'class'=>'tab-pane col-12 '.$extratab->name));
            echo html_writer::tag('div', $extratab->content, array('class'=>'p-1'));
            echo html_writer::end_tag('div');
        }
        echo html_writer::end_tag('div');
    }

    protected function section_summary_container($section) {
        $summarytext = $this->format_summary_text($section);
        if ($summarytext) {
            $classextra = '';
            $o = html_writer::start_tag('div', array('class' => 'summary' . $classextra));
            $o .= $this->format_summary_text($section);
            $o .= html_writer::end_tag('div');
        } else {
            $o = '';
        }
        return $o;
    }

    public function set_user_preference($preference) {
        $this->userpreference = $preference;
    }

    public function set_default_user_preference($defaultpreference) {
        $this->defaultuserpreference = $defaultpreference;
    }

    protected function get_row_class() {
        return '';
    }

    protected function get_column_class($columns) {
        $colclasses = array(
            1 => 'col-12',
            2 => 'col-6',
            3 => 'col-md-4',
            4 => 'col-lg-3');

        return $colclasses[$columns];
    }

}
