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
 * @package    theme
 * @subpackage bootstrap
 * @copyright  &copy; 2014-onwards G J Barnard.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (file_exists($CFG->dirroot . "/course/format/topcoll/renderer.php")) {
    require_once($CFG->dirroot . "/course/format/topcoll/renderer.php");
    class theme_qmul_format_topcoll_renderer extends format_topcoll_renderer {

        private $tccolumnwidth = 100; // Default width in percent of the column(s).
        private $tccolumnpadding = 0; // Default padding in pixels of the column(s).
        private $mobiletheme = false; // As not using a mobile theme we can react to the number of columns setting.
        private $tablettheme = false; // As not using a tablet theme we can react to the number of columns setting.
        private $courseformat = null; // Our course format object as defined in lib.php;
        private $tcsettings; // Settings for the format - array.
        private $userpreference; // User toggle state preference - string.
        private $defaultuserpreference; // Default user preference when none set - bool - true all open, false all closed.
        private $togglelib;
        private $isoldtogglepreference = false;
        private $userisediting = false;
        private $tctoggleiconsize;
        private $formatresponsive;
        private $rtl = false;

        /**
         * Constructor method, calls the parent constructor - MDL-21097.
         *
         * @param moodle_page $page.
         * @param string $target one of rendering target constants.
         */
        public function __construct(moodle_page $page, $target) {
            parent::__construct($page, $target);
            $this->togglelib = new topcoll_togglelib;
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
        }

        /**
         * Displays the toggle all functionality.
         * @return string HTML to output.
         */
        protected function toggle_all() {
            $o = html_writer::start_tag('li', array('class' => 'tcsection main clearfix', 'id' => 'toggle-all'));

            if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
                $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'left side'));
                $o .= html_writer::tag('div', $this->output->spacer(), array('class' => 'right side'));
            }

            $o .= html_writer::start_tag('div', array('class' => 'content'));
            $iconsetclass = ' toggle-' . $this->tcsettings['toggleiconset'];
            if ($this->tcsettings['toggleallhover'] == 2) {
                $iconsetclass .= '-hover' . $iconsetclass;
            }
            $o .= html_writer::start_tag('div', array('class' => 'sectionbody' . $iconsetclass));
            $o .= html_writer::start_tag('h4', null);
            $o .= html_writer::tag('span', get_string('topcollopened', 'format_topcoll'),
                array('class' => 'on ' . $this->tctoggleiconsize, 'id' => 'toggles-all-opened',
                'role' => 'button')
            );
            $o .= html_writer::tag('span', get_string('topcollclosed', 'format_topcoll'),
                array('class' => 'off ' . $this->tctoggleiconsize, 'id' => 'toggles-all-closed',
                'role' => 'button')
            );
            $o .= html_writer::end_tag('h4');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('div');
            $o .= html_writer::end_tag('li');

            return $o;
        }

        protected function section_right_content($section, $course, $onsectionpage) {
            $o = '';

            if ($section->section != 0) {
                $controls = $this->section_edit_control_items($course, $section, $onsectionpage);
                if (!empty($controls)) {
                    $o .= $this->section_edit_control_menu($controls, $course, $section);
                } else {
                    if (empty($this->tcsettings)) {
                        $this->tcsettings = $this->courseformat->get_settings();
                    }
                    switch ($this->tcsettings['layoutelement']) { // Toggle section x.
                        case 1:
                        case 3:
                        case 5:
                        case 8:
                            // Get the specific words from the language files.
                            $topictext = null;
                            if (($this->tcsettings['layoutstructure'] == 1) || ($this->tcsettings['layoutstructure'] == 4)) {
                                $topictext = get_string('setlayoutstructuretopic', 'format_topcoll');
                            } else if (($this->tcsettings['layoutstructure'] == 2) || ($this->tcsettings['layoutstructure'] == 3)) {
                                $topictext = get_string('setlayoutstructureweek', 'format_topcoll');
                            } else {
                                $topictext = get_string('setlayoutstructureday', 'format_topcoll');
                            }
                            break;
                    }
                }
            }

            return $o;
        }

        protected function section_left_content($section, $course, $onsectionpage) {
            $o = '';

            if ($section->section != 0) {
                // Only in the non-general sections.
                if ($this->courseformat->is_section_current($section)) {
                    $o .= get_accesshide(get_string('currentsection', 'format_' . $course->format));
                }
                if (empty($this->tcsettings)) {
                    $this->tcsettings = $this->courseformat->get_settings();
                }
                if (!isset($this->tcsettings['layoutelement'])) {
                    $this->tcsettings['layoutelement'] = 1;
                }
                switch ($this->tcsettings['layoutelement']) {
                    case 1:
                    case 2:
                    case 5:
                    case 6:
                        break;
                }
            }
            return $o;
        }

        protected function section_edit_control_items($course, $section, $onsectionpage = false) {

            if (!$this->userisediting) {
                return array();
            }

            $coursecontext = context_course::instance($course->id);

            if ($onsectionpage) {
                $url = course_get_url($course, $section->section);
            } else {
                $url = course_get_url($course);
            }
            $url->param('sesskey', sesskey());

            if (empty($this->tcsettings)) {
                $this->tcsettings = $this->courseformat->get_settings();
            }

            if (!isset($this->tcsettings['layoutstructure'])) {
                    $this->tcsettings['layoutstructure'] = 1;
            }

            $isstealth = $section->section > $course->numsections;
            $controls = array();
            if ((($this->tcsettings['layoutstructure'] == 1) || ($this->tcsettings['layoutstructure'] == 4)) &&
                    !$isstealth && $section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
                if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                    $url->param('marker', 0);
                    $markedthissection = get_string('markedthissection', 'format_topcoll');
                    $highlightoff = get_string('highlightoff');
                    $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                                   'name' => $highlightoff,
                                                   'pixattr' => array('class' => '', 'alt' => $markedthissection),
                                                   'attr' => array('class' => 'editing_highlight', 'title' => $markedthissection));
                } else {
                    $url->param('marker', $section->section);
                    $markthissection = get_string('markthissection', 'format_topcoll');
                    $highlight = get_string('highlight');
                    $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                                   'name' => $highlight,
                                                   'pixattr' => array('class' => '', 'alt' => $markthissection),
                                                   'attr' => array('class' => 'editing_highlight', 'title' => $markthissection));
                }
            }

            $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

            // If the edit key exists, we are going to insert our controls after it.
            if (array_key_exists("edit", $parentcontrols)) {
                unset($parentcontrols['edit']);
            }
            return array_merge($controls, $parentcontrols);
        }

        protected function section_header($section, $course, $onsectionpage, $sectionreturn = null) {
            $o = '';

            if (!isset($this->tcsettings['showsectionsummary']) || empty($this->tcsettings['showsectionsummary'])) {
                $this->tcsettings['showsectionsummary'] = 2;
            }
            $this->tcsettings['showsectionsummary'] = 1;

            $sectionstyle = '';
            $rightcurrent = '';
            $extraclass = '';
            $context = context_course::instance($course->id);

            if ($section->section != 0) {
                // Only in the non-general sections.
                if (!$section->visible) {
                    $sectionstyle = ' hidden';
                    $extraclass = ' hidden';
                } else if ($this->courseformat->is_section_current($section)) {
                    $section->toggle = true; // Open current section regardless of toggle state.
                    $sectionstyle = ' current';
                    $extraclass = ' current';
                    $rightcurrent = ' left';
                }
            }

            if (!isset($this->tcsettings['layoutcolumnorientation'])) {
                $this->tcsettings['layoutcolumnorientation'] = 1;
            }

            if ((!$this->formatresponsive) && ($section->section != 0) &&
                ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
                $sectionstyle .= ' ' . $this->get_column_class($this->tcsettings['layoutcolumns']);
            }
            $liattributes = array(
                'id' => 'section-' . $section->section,
                'class' => 'section main row'.$extraclass,
                'role' => 'region',
                'aria-label' => $this->courseformat->get_topcoll_section_name($course, $section, false)
            );
            if (($this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
                $liattributes['style'] = 'width: ' . $this->tccolumnwidth . '%;';
            }
            $o .= html_writer::start_tag('li', $liattributes);

            $class = 'col-md-12';
            if ($this->userisediting) {
                $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
                $rightcontent = '';
                if (($section->section != 0) && $this->userisediting && has_capability('moodle/course:update', $context)) {
                    $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));

                    $rightcontent .= html_writer::link($url,
                        html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url('t/edit'),
                            'class' => 'icon edit tceditsection', 'alt' => get_string('edit'))),
                            array('title' => get_string('editsection', 'format_topcoll'), 'class' => 'tceditsection'));
                }
                $rightcontent .= $this->section_right_content($section, $course, $onsectionpage);
                if ($section->section > 0) {
                    $o.= html_writer::tag('div', $leftcontent.$rightcontent, array('class' => 'left right side p-1 p-md-0 col-12 col-md-1 push-md-11 m-0'));
                    $class = 'col-md-11 pull-md-1';
                }
            }
            $o .= html_writer::start_tag('div', array('class' => 'content '.$class));

            if (!isset($this->tcsettings['toggleiconset'])) {
                $this->tcsettings['toggleiconset'] = '';
            }

            if (($onsectionpage == false) && ($section->section != 0)) {
                $o .= html_writer::start_tag('div',
                    array('class' => 'sectionhead toggle toggle-'.$this->tcsettings['toggleiconset'],
                    'id' => 'toggle-'.$section->section)
                );

                if ((!($section->toggle === null)) && ($section->toggle == true)) {
                    $toggleclass = 'toggle_open';
                    $ariapressed = 'true';
                    $sectionclass = ' sectionopen';
                } else {
                    $toggleclass = 'toggle_closed';
                    $ariapressed = 'false';
                    $sectionclass = '';
                }
                $toggleclass .= ' the_toggle ' . $this->tctoggleiconsize;
                $o .= html_writer::start_tag('span',
                    array('class' => $toggleclass, 'role' => 'button', 'aria-pressed' => $ariapressed)
                );

                if (empty($this->tcsettings)) {
                    $this->tcsettings = $this->courseformat->get_settings();
                }

                if ($this->userisediting) {
                    $title = $this->section_title($section, $course);
                } else {
                    $title = $this->courseformat->get_topcoll_section_name($course, $section, true);
                }
                if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
                    $o .= $this->output->heading($title, 3, 'sectionname');
                } else {
                    $o .= html_writer::tag('h3', $title); // Moodle H3's look bad on mobile / tablet with CT so use plain.
                }

                $o .= html_writer::end_tag('span');
                $o .= html_writer::end_tag('div');

                if ($this->tcsettings['showsectionsummary'] == 2) {
                    $o .= $this->section_summary_container($section);
                }

                $o .= html_writer::start_tag('div',
                    array('class' => 'sectionbody toggledsection' . $sectionclass,
                    'id' => 'toggledsection-' . $section->section)
                );

                if ($this->tcsettings['showsectionsummary'] == 1) {
                    $o .= $this->section_summary_container($section);
                }

                $o .= $this->section_availability_message($section,
                    has_capability('moodle/course:viewhiddensections', $context));
            } else {
                // When on a section page, we only display the general section title, if title is not the default one.
                $hasnamesecpg = ($section->section == 0 && (string) $section->name !== '');

                if ($hasnamesecpg) {
                    $o .= $this->output->heading($this->section_title($section, $course), 3, 'section-title');
                }
                $o .= html_writer::start_tag('div', array('class' => 'summary'));
                $o .= $this->format_summary_text($section);

                if ($this->userisediting && has_capability('moodle/course:update', $context)) {
                    $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                    $o .= html_writer::link($url,
                        html_writer::empty_tag('img',
                            array('src' => $this->output->pix_url('t/edit'),
                            'class' => 'iconsmall edit', 'alt' => get_string('edit'))),
                            array('title' => get_string('editsection', 'format_topcoll'))
                    );
                }
                $o .= html_writer::end_tag('div');

                $o .= $this->section_availability_message($section,
                    has_capability('moodle/course:viewhiddensections', $context));
            }
            return $o;
        }

        public function public_section_header($section, $course, $onsectionpage, $sectionreturn=null) {
            return $this->section_header($section, $course, $onsectionpage, $sectionreturn=null);
        }

        public function public_section_footer() {
            return $this->section_footer();
        }

        public function public_course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()) {
            return $this->courserenderer->course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array());
        }

        public function public_course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
            return $this->courserenderer->course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array());
        }
    }
}
