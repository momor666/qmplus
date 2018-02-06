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
 * This file contains main class for the course format Topic
 *
 * @since     Moodle 2.0
 * @package   format_landingpage
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot. '/course/format/lib.php');
require_once($CFG->dirroot. '/course/format/topics/lib.php');

/**
 * Main class for the sections course format
 *
 * @package    format_landingpage
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_landingpage extends format_topics {

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = array()) {
        global $CFG;
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        $sr = null;
        if (array_key_exists('sr', $options)) {
            $sr = $options['sr'];
        }
        if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ($sectionno !== null) {
            $usercoursedisplay = COURSE_DISPLAY_MULTIPAGE;
            if ($sectionno != 0) {
                $url->param('section', $sectionno);
            }
        }
        return $url;
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * sections format uses the following options:
     * - coursedisplay
     * - numsections
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            if (!isset($courseconfig->hidecoursecontent)) {
                $courseconfig->hidecoursecontent = 0;
            }
            $courseformatoptions = array(
                'numsections' => array(
                    'default' => $courseconfig->numsections,
                    'type' => PARAM_INT,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'hidecoursecontent' => array(
                    'default' => $courseconfig->hidecoursecontent,
                    'type' => PARAM_INT,
                ),
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseconfig = get_config('moodlecourse');
            $max = $courseconfig->maxsections;
            if (!isset($max) || !is_numeric($max)) {
                $max = 52;
            }
            $sectionmenu = array();
            for ($i = 0; $i <= $max; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections' => array(
                    'label' => new lang_string('numberweeks'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'hidecoursecontent' => array(
                    'label' => new lang_string('hidecoursecontent', 'format_landingpage'),
                    'help' => 'hidecoursecontent',
                    'help_component' => 'format_landingpage',
                    'element_type' => 'checkbox',
                )
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    public function section_format_options($foreditform = false) {
        $options = array();

        $options['style'] = array(
            'label' => new lang_string('style', 'format_landingpage'),
            'help' => 'style',
            'help_component' => 'format_landingpage',
            'element_type' => 'select',
            'element_attributes' => array(
                array(
                    'standard' => new lang_string('style_standard', 'format_landingpage'),
                    'backgroundfill' => new lang_string('style_backgroundfill', 'format_landingpage'),
                    'imageheader' => new lang_string('style_imageheader', 'format_landingpage'),
                )
            ),
        );

        $options['sectionimage'] = array(
            'label' => new lang_string('sectionimage', 'format_landingpage'),
            'help' => 'sectionimage',
            'help_component' => 'format_landingpage',
            'element_type' => 'filemanager',
            'element_attributes' => array(
                null,
                array(
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'accepted_types' => array('image'),
                ),
            ),
        );

        $options['showonmobile'] = array(
            'label' => new lang_string('showonmobile', 'format_landingpage'),
            'help' => 'showonmobile',
            'help_component' => 'format_landingpage',
            'default' => 1,
            'element_type' => 'select',
            'element_attributes' => array(
                array(
                    1 => new lang_string('yes'),
                    0 => new lang_string('no'),
                )
            ),
        );


        return $options;
    }

    /**
     * Adds format options elements to the course/section edit form
     *
     * This function is called from {@link course_edit_form::definition_after_data()}
     *
     * @param MoodleQuickForm $mform form the elements are added to
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form
     * @return array array of references to the added form elements
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        $elements = array();
        if ($forsection) {
            $options = $this->section_format_options(true);
        } else {
            $options = $this->course_format_options(true);
        }
        foreach ($options as $optionname => $option) {
            if (!isset($option['element_type'])) {
                $option['element_type'] = 'text';
            }
            $args = array($option['element_type'], $optionname, $option['label']);
            if (!empty($option['element_attributes'])) {
                $args = array_merge($args, $option['element_attributes']);
            }
            $elements[] = call_user_func_array(array($mform, 'addElement'), $args);
            if (isset($option['help'])) {
                $helpcomponent = 'format_'. $this->get_format();
                if (isset($option['help_component'])) {
                    $helpcomponent = $option['help_component'];
                }
                $mform->addHelpButton($optionname, $option['help'], $helpcomponent);
            }
            if (isset($option['type'])) {
                $mform->setType($optionname, $option['type']);
            }
            if (isset($option['default']) && !array_key_exists($optionname, $mform->_defaultValues)) {
                // Set defaults for the elements in the form.
                // Since we call this method after set_data() make sure that we don't override what was already set.
                $mform->setDefault($optionname, $option['default']);
            }
        }

        if (!$forsection && empty($this->courseid)) {
            // At this stage (this is called from definition_after_data) course data is already set as default.
            // We can not overwrite what is in the database.
            $mform->setDefault('enddate', $this->get_default_course_enddate($mform));
        }

        return $elements;
    }

    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;
        $data = (array)$data;

        if (!isset($data['hidecoursecontent'])) {
            $data['hidecoursecontent'] = 0;
        }

        return parent::update_course_format_options($data, $oldcourse);
    }

    public function editsection_form($action, $customdata = array()) {
        global $CFG;
        require_once($CFG->dirroot. '/course/format/landingpage/editsection_form.php');
        $context = context_course::instance($this->courseid);
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }

        $form = new format_landingpage_editsection_form($action, $customdata);

        return $form;
    }

    public function update_section_format_options($data) {
        $data = (array)$data;

        $context = context_course::instance($this->courseid);
        file_save_draft_area_files($data['sectionimage'], $context->id, 'format_landingpage', 'sectionimage',
                   $data['id'], array('subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => array('image')));

        return parent::update_section_format_options($data);
    }

    public function get_section_image($context, $section) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'format_landingpage', 'sectionimage', $section->id);
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            return $file;
        }
        return false;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_landingpage_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'landingpage'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}


function format_landingpage_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    if ($context->contextlevel == CONTEXT_COURSE) {
        $itemid = array_shift($args);
        $filename = array_pop($args); // The last item in the $args array.
        if (!$args) {
            $filepath = '/'; // $args is empty => the path is '/'
        } else {
            $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
        }
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'format_landingpage', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            return send_file_not_found();
        }
        send_stored_file($file, 86400, 0, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}
