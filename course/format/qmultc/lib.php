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
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.md' file.
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
require_once($CFG->dirroot . '/course/format/lib.php'); // For format_base.
require_once($CFG->dirroot . '/course/format/topcoll/lib.php'); // For format_qmultc.

class format_qmultc extends format_topcoll {
    private $settings;

    /**
     * Creates a new instance of class
     *
     * Please use {@link course_get_format($courseorid)} to get an instance of the format class
     *
     * @param string $format
     * @param int $courseid
     * @return format_qmultc
     */
    protected function __construct($format, $courseid) {
        if ($courseid === 0) {
            global $COURSE;
            $courseid = $COURSE->id;  // Save lots of global $COURSE as we will never be the site course.
        }
        parent::__construct($format, $courseid);
    }

    /**
     * Gets the name for the provided course, section and state if need to add addional text.
     *
     * @param stdClass $course The course entry from DB
     * @param int|stdClass $section Section object from database or just field section.section
     * @param boolean $additional State to add additional text yes = true or no = false.
     * @return string The section name.
     */
    public function get_qmultc_section_name($course, $section, $additional) {
        $thesection = $this->get_section($section);
        if (is_null($thesection)) {
            $thesection = new stdClass;
            $thesection->name = '';
            if (is_object($section)) {
                $thesection->section = $section->section;
            } else {
                $thesection->section = $section;
            }
        }
        $o = '';
        $tcsettings = $this->get_settings();
        $tcsectionsettings = $this->get_format_options($thesection->section);
        // Use supplied course as could be a different course to us due to a navigation block call.
        $context = context_course::instance($course->id);

        // We can't add a node without any text.
        if ((string) $thesection->name !== '') {
            $o .= format_string($thesection->name, true, array('context' => $context));
            if (($thesection->section != 0) && (($tcsettings['layoutstructure'] == 2) ||
                ($tcsettings['layoutstructure'] == 3) || ($tcsettings['layoutstructure'] == 5))) {
                $o .= ' ';
                if (empty($tcsectionsettings['donotshowdate'])) {
                    if ($additional == true) { // Break 'br' tags break backups!
                        $o .= html_writer::empty_tag('br');
                    }
                    $o .= $this->get_section_dates($section, $course, $tcsettings);
                }
            }
        } else if ($thesection->section == 0) {
            $o = get_string('section0name', 'format_qmultc');
        } else {
            if (($tcsettings['layoutstructure'] == 1) || ($tcsettings['layoutstructure'] == 4)) {
                $o = get_string('sectionname', 'format_qmultc') . ' ' . $thesection->section;
            } else {
                $o .= $this->get_section_dates($section, $course, $tcsettings);
            }
        }

        /*
         * Now done here so that the drag and drop titles will be the correct strings as swapped in format.js.
         * But only if we are using toggles which will be if all sections are on one page or we are editing the main page
         * when in one section per page which is coded in 'renderer.php/print_multiple_section_page()' when it calls
         * 'section_header()' as that gets called from 'format.php' when there is no entry for '$displaysetting' - confused?
         * I was, took ages to figure.
         */
        if (($additional == true) && ($thesection->section != 0)) {
            switch ($tcsettings['layoutelement']) {
                case 1:
                case 2:
                case 3:
                case 4:
                    // The word 'Toggle'.
                    $o .= '<div class="cttoggle"> - ' .get_string('qmultctoggle', 'format_qmultc') . '</div>';
                    break;
            }
        }

        return $o;
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
        global $CFG;

        MoodleQuickForm::registerElementType('tccolourpopup', "$CFG->dirroot/course/format/topcoll/js/tc_colourpopup.php",
                                             'MoodleQuickForm_tccolourpopup');

        $elements = parent::create_edit_form_elements($mform, $forsection);
        if ($forsection == false) {
            // Assessment Information
            $elements[] = $mform->addElement('header', 'assessmentinformation', get_string('assessmentinformation', 'format_qmultc'));
            $mform->addHelpButton('assessmentinformation', 'assessmentinformation', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_assessmentinformation', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_assessmentinformation', get_string('assessmentinformation', 'format_qmultc'));

            // Extra Tab 1
            $elements[] = $mform->addElement('header', 'extratab1', get_string('extratab', 'format_qmultc', 1));
            $mform->addHelpButton('extratab1', 'extratab', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_extratab1', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('text', 'title_extratab1', get_string('tabtitle', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_extratab1', get_string('tabcontent', 'format_qmultc'));

            // Extra Tab 2
            $elements[] = $mform->addElement('header', 'extratab2', get_string('extratab', 'format_qmultc', 2));
            $mform->addHelpButton('extratab2', 'extratab', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_extratab2', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('text', 'title_extratab2', get_string('tabtitle', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_extratab2', get_string('tabcontent', 'format_qmultc'));

            // Extra Tab 3
            $elements[] = $mform->addElement('header', 'extratab3', get_string('extratab', 'format_qmultc', 3));
            $mform->addHelpButton('extratab3', 'extratab', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_extratab3', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('text', 'title_extratab3', get_string('tabtitle', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_extratab3', get_string('tabcontent', 'format_qmultc'));

        }

        return $elements;
    }

    public function edit_form_validation($data, $files, $errors) {

        $return = format_base::edit_form_validation($data, $files, $errors);

        if (isset($data['enable_extratab1'])) {
            $data['enabled_extratab1'] = 0;
            if (empty($data['title_extratab1'])) {
                $return['title_extratab1'] = get_string('titlerequiredwhenenabled', 'format_qmultc');
            }
        } else {
            $data['enabled_extratab1'] = 0;
        }
        if (isset($data['enable_extratab2'])) {
            if (empty($data['title_extratab2'])) {
                $return['title_extratab2'] = get_string('titlerequiredwhenenabled', 'format_qmultc');
            }
        } else {
            $data['enabled_extratab2'] = 0;
        }
        if (isset($data['enable_extratab3'])) {
            if (empty($data['title_extratab3'])) {
                $return['title_extratab3'] = get_string('titlerequiredwhenenabled', 'format_qmultc');
            }
        } else {
            $data['enabled_extratab3'] = 0;
        }

        return $return;
    }

    /**
     * Returns the format options stored for this course or course section
     *
     * When overriding please note that this function is called from rebuild_course_cache()
     * and section_info object, therefore using of get_fast_modinfo() and/or any function that
     * accesses it may lead to recursion.
     *
     * @param null|int|stdClass|section_info $section if null the course format options will be returned
     *     otherwise options for specified section will be returned. This can be either
     *     section object or relative section number (field course_sections.section)
     * @return array
     */
    public function get_format_options($section = null) {
        global $DB;

        $options = parent::get_format_options($section);

        if ($section === null) {
            // course format options will be returned
            $sectionid = 0;
        } else if ($this->courseid && isset($section->id)) {
            // course section format options will be returned
            $sectionid = $section->id;
        } else if ($this->courseid && is_int($section) &&
                ($sectionobj = $DB->get_record('course_sections',
                        array('section' => $section, 'course' => $this->courseid), 'id'))) {
            // course section format options will be returned
            $sectionid = $sectionobj->id;
        } else {
            // non-existing (yet) section was passed as an argument
            // default format options for course section will be returned
            $sectionid = -1;
        }

        if ($sectionid == 0) {
            $alloptions = $DB->get_records('course_format_options',
                        array('courseid'=>$this->courseid, 'format'=>'qmultc',
                            'sectionid'=>0));

            foreach ($alloptions as $option) {
                if (!isset($options[$option->name])) {
                    $options[$option->name] = $option->value;
                }
            }

            $this->formatoptions[$sectionid] = $options;
        }

        return $options;
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'Collapsed Topics', we try to copy options
     * 'coursedisplay', 'numsections' and 'hiddensections' from the previous format.
     * If previous course format did not have 'numsections' option, we populate it with the
     * current number of sections.  The layout and colour defaults will come from 'course_format_options'.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;

        $newdata = (array) $data;
        $savedata = array();
        if (isset($newdata['fullname'])) {
            if (isset($newdata['enable_assessmentinformation'])) {
                $savedata['enable_assessmentinformation'] = $newdata['enable_assessmentinformation'];
            } else {
                $savedata['enable_assessmentinformation'] = 0;
            }
            if (isset($newdata['content_assessmentinformation'])) {
                $savedata['content_assessmentinformation'] = $newdata['content_assessmentinformation'];
            }
            if (isset($newdata['enable_extratab1'])) {
                $savedata['enable_extratab1'] = $newdata['enable_extratab1'];
            } else {
                $savedata['enable_extratab1'] = 0;
            }
            if (isset($newdata['title_extratab1'])) {
                $savedata['title_extratab1'] = $newdata['title_extratab1'];
            }
            if (isset($newdata['content_extratab1'])) {
                $savedata['content_extratab1'] = $newdata['content_extratab1'];
            }
            if (isset($newdata['enable_extratab2'])) {
                $savedata['enable_extratab2'] = $newdata['enable_extratab2'];
            } else {
                $savedata['enable_extratab2'] = 0;
            }
            if (isset($newdata['title_extratab2'])) {
                $savedata['title_extratab2'] = $newdata['title_extratab2'];
            }
            if (isset($newdata['content_extratab2'])) {
                $savedata['content_extratab2'] = $newdata['content_extratab2'];
            }
            if (isset($newdata['enable_extratab3'])) {
                $savedata['enable_extratab3'] = $newdata['enable_extratab3'];
            } else {
                $savedata['enable_extratab3'] = 0;
            }
            if (isset($newdata['title_extratab3'])) {
                $savedata['title_extratab3'] = $newdata['title_extratab3'];
            }
            if (isset($newdata['content_extratab3'])) {
                $savedata['content_extratab3'] = $newdata['content_extratab3'];
            }
        }
        $records = $DB->get_records('course_format_options',
                array('courseid' => $this->courseid,
                      'format' => $this->format,
                      'sectionid' => 0
                    ), '', 'name,id,value');

        foreach ($savedata as $key => $value) {
             if (isset($records[$key])) {
                if (array_key_exists($key, $newdata) && $records[$key]->value !== $newdata[$key]) {
                    $DB->set_field('course_format_options', 'value',
                            $value, array('id' => $records[$key]->id));
                    $changed = true;
                } else {
                    $DB->set_field('course_format_options', 'value',
                            $value, array('id' => $records[$key]->id));
                    $changed = true;
                }
            } else {
                $DB->insert_record('course_format_options', array(
                    'courseid' => $this->courseid,
                    'format' => $this->format,
                    'sectionid' => 0,
                    'name' => $key,
                    'value' => $value
                ));
            }
        }

        $changes = parent::update_course_format_options($data, $oldcourse);

        return $changes;
    }

    /**
     * Restores the course settings when restoring a Moodle 2.3 or below (bar 1.9) course and sets the settings when upgrading
     * from a prevous version.  Hence no need for 'coursedisplay' as that is a core rather than CT specific setting and not
     * in the old 'format_qmultc_settings' table.
     * @param int $courseid If not 0, then a specific course to reset.
     * @param int $layoutelement The layout element.
     * @param int $layoutstructure The layout structure.
     * @param int $layoutcolumns The layout columns.
     * @param int $tgfgcolour The foreground colour.
     * @param int $tgbgcolour The background colour.
     * @param int $tgbghvrcolour The background hover colour.
     */
    public function restore_qmultc_setting($courseid, $layoutelement, $layoutstructure, $layoutcolumns, $tgfgcolour,
        $tgbgcolour, $tgbghvrcolour) {
        $currentcourseid = $this->courseid;  // Save for later - stack data model.
        $this->courseid = $courseid;
        // Create data array.
        $data = array(
            'layoutelement' => $layoutelement,
            'layoutstructure' => $layoutstructure,
            'layoutcolumns' => $layoutcolumns,
            'toggleforegroundcolour' => $tgfgcolour,
            'togglebackgroundcolour' => $tgbgcolour,
            'togglebackgroundhovercolour' => $tgbghvrcolour);

        $lco = get_config('format_qmultc', 'defaultlayoutcolumnorientation');
        if (empty($lco)) {
            // Upgrading from M2.3 and the defaults in 'settings.php' have not been processed at this time.
            // Defaults taken from 'settings.php'.
            $data['displayinstructions'] = get_config('format_qmultc', 'defaultdisplayinstructions');
            $data['layoutcolumnorientation'] = get_config('format_qmultc', 'defaultlayoutcolumnorientation');
            $data['showsectionsummary'] = get_config('format_qmultc', 'defaultshowsectionsummary');
            $data['togglealignment'] = get_config('format_qmultc', 'defaulttogglealignment');
            $data['toggleallhover'] = get_config('format_qmultc', 'defaulttoggleallhover');
            $data['toggleiconposition'] = get_config('format_qmultc', 'defaulttoggleiconposition');
            $data['toggleiconset'] = get_config('format_qmultc', 'defaulttoggleiconset');
        }
        $this->update_course_format_options($data);

        $this->courseid = $currentcourseid;
    }

    private function get_context() {
        global $SITE;

        if ($SITE->id == $this->courseid) {
            // Use the context of the page which should be the course category.
            global $PAGE;
            return $PAGE->context;
        } else {
            return context_course::instance($this->courseid);
        }
    }

    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'numsections' => array(
                    'default' => $courseconfig->numsections,
                    'type' => PARAM_INT,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),
                'coursedisplay' => array(
                    'default' => get_config('format_topcoll', 'defaultcoursedisplay'),
                    'type' => PARAM_INT,
                ),
                'displayinstructions' => array(
                    'default' => get_config('format_topcoll', 'defaultdisplayinstructions'),
                    'type' => PARAM_INT,
                ),
                'layoutelement' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutelement'),
                    'type' => PARAM_INT,
                    'label' => '',
                    'element_type' => 'hidden',
                ),
                'layoutstructure' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutstructure'),
                    'type' => PARAM_INT,
                    'label' => '',
                    'element_type' => 'hidden',
                ),
                'layoutcolumns' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutcolumns'),
                    'type' => PARAM_INT,
                    'label' => '',
                    'element_type' => 'hidden',
                ),
                'layoutcolumnorientation' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutcolumnorientation'),
                    'type' => PARAM_INT,
                    'label' => '',
                    'element_type' => 'hidden',
                ),
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $context = $this->get_context();

            $courseconfig = get_config('moodlecourse');
            $sectionmenu = array();
            for ($i = 0; $i <= $courseconfig->maxsections; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections' => array(
                    'label' => new lang_string('numbersections', 'format_topcoll'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(0 => new lang_string('hiddensectionscollapsed'),
                              1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ),
                'displayinstructions' => array(
                    'label' => new lang_string('displayinstructions', 'format_topcoll'),
                    'help' => 'displayinstructions',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('no'),
                              2 => new lang_string('yes'))
                    )
                )
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Updates the number of columns when the renderer detects that they are wrong.
     * @param int $layoutcolumns The layout columns to use, see tcconfig.php.
     */
    public function update_qmultc_columns_setting($layoutcolumns) {
        // Create data array.
        $data = array('layoutcolumns' => $layoutcolumns);

        $this->update_course_format_options($data);
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_qmultc_inplace_editable($itemtype, $itemid, $newvalue) {
    global $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        global $DB;
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'qmultc'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * The string that is used to describe a section of the course.
 *
 * @return string The section description.
 */
function callback_qmultc_definition() {
    return get_string('sectionname', 'format_qmultc');
}

function qmul_format_get_assessmentinformation($content) {
    global $CFG, $DB, $COURSE, $OUTPUT, $USER;

    $output = '';
    $output = html_writer::tag('div', format_text($content), array('class'=>'assessmentinfo col-12 mb-1'));

    $assignments = qmul_format_get_assignments();

    $assignoutput = html_writer::tag('div', get_string('assignmentsdue', 'format_qmultc'), array('class'=>'card-header h5'));
    $assignoutput .= html_writer::start_tag('div', array('class'=>'list-group list-group-flush'));
    $assignsubmittedoutput = html_writer::tag('div', get_string('assignmentssubmitted', 'format_qmultc'), array('class'=>'card-header h5'));
    $assignsubmittedoutput .= html_writer::start_tag('div', array('class'=>'list-group list-group-flush'));

    $modinfo = get_fast_modinfo($COURSE);

    $submitted = 0;
    $due = 0;
    foreach ($assignments as $assignment) {

        $context = context_module::instance($assignment->cmid);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $context);

        $hidden = '';
        if (!$assignment->visible) {
            $hidden = ' notvisible';
        }

        $cminfo = $modinfo->get_cm($assignment->cmid);

        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $info = new \core_availability\info_module($cminfo);
            if (!$info->is_available_for_all()) {
                $information = '';
                if ($info->is_available($information)) {
                    $hidden = ' conditionalhidden';
                    $conditionalhidden = false;
                } else {
                    $hidden = ' notvisible conditionalhidden';
                    $conditionalhidden = true;
                }
            }
        }

        $accessiblebutdim = (!$assignment->visible || $conditionalhidden) && $canviewhidden;

        if ((!$assignment->visible || $conditionalhidden) && !$canviewhidden) {
            continue;
        }

        // Check overrides for new duedate

        $sql = "SELECT
                    module.id,
                    module.allowsubmissionsfromdate AS timeopen,
                    module.duedate AS timeclose";
        $groups = groups_get_user_groups($COURSE->id);
        $groupbysql = '';
        $params = array();
        if ($groups[0]) {
            list ($groupsql, $params) = $DB->get_in_or_equal($groups[0]);
            $sql .= ", CASE WHEN ovrd1.allowsubmissionsfromdate IS NULL THEN MIN(ovrd2.allowsubmissionsfromdate) ELSE ovrd1.allowsubmissionsfromdate END AS timeopenover,
                    CASE WHEN ovrd1.duedate IS NULL THEN MAX(ovrd2.duedate) ELSE ovrd1.duedate END AS timecloseover
                    FROM {assign} module
                    LEFT JOIN {assign_overrides} ovrd1 ON module.id=ovrd1.assignid AND $USER->id=ovrd1.userid
                    LEFT JOIN {assign_overrides} ovrd2 ON module.id=ovrd2.assignid AND ovrd2.groupid $groupsql";
            $groupbysql = " GROUP BY module.id, timeopen, timeclose, ovrd1.allowsubmissionsfromdate, ovrd1.duedate";
        } else {
            $sql .= ", ovrd1.allowsubmissionsfromdate AS timeopenover, ovrd1.duedate AS timecloseover
                     FROM {assign} module
                     LEFT JOIN {assign_overrides} ovrd1
                     ON module.id=ovrd1.assignid AND $USER->id=ovrd1.userid";
        }
        $sql .= " WHERE module.course = ?";
        $sql .= " AND module.id = ?";
        $sql .= $groupbysql;
        $params[] = $COURSE->id;
        $params[] = $assignment->id;
        $overrides = $DB->get_records_sql($sql, $params);
        $overrides = reset($overrides);
        if (!empty($overrides->timecloseover)) {
            $assignment->duedate = $overrides->timecloseover;
            if ($overrides->timeopenover) {
                $assignment->open = $overrides->open;
            }
        }

        $out = '';
        $url = new moodle_url('/mod/assign/view.php', array('id' => $assignment->cmid));
        if ($assignment->status == 'submitted') {
            $duestatus = get_string('submitted', 'widgettype_assignments');
            $statusclass = 'success';
        } else if ($assignment->status == 'draft') {
            $duestatus = get_string('draft', 'widgettype_assignments');
            $statusclass = 'info';
        } else if ($assignment->duedate > 0 && $assignment->duedate < time()) {
            $duestatus = get_string('overdue', 'widgettype_assignments');
            $statusclass = 'danger';
        } else if ($assignment->duedate > 0 && $assignment->duedate < (time() + 14 * DAYSECS)) {
            $duestatus = get_string('duesoon', 'widgettype_assignments');
            $statusclass = 'warning';
        } else {
            $duestatus = '';
            $statusclass = 'default';
        }

        $duedate = date('d/m/Y', $assignment->duedate);

        $out .= html_writer::start_tag('div', array('class'=>'list-group-item assignment'.$hidden));
        $out .= html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('icon', 'assign'), 'class'=>'icon-large'));
        $out .= html_writer::link($url, $assignment->name, array('class'=>'d-inline-block ml-1 name w-50'));

        if ($assignment->status == 'submitted') {
            $grade = get_string('notyetgraded', 'mod_quiz');
            $gradeclass = 'default';
            $showgrade = ($assignment->grade !== null && $assignment->grade != -1);
            if ($assignment->markingworkflow) {
                if ($assignment->workflowstate !== 'released') {
                    $showgrade = false; // Grade not yet released.
                    if ($assignment->workflowstate) {
                        $grade = get_string('markingworkflowstate'.$assignment->workflowstate, 'mod_assign');
                    }
                }
            }
            if ($assignment->gradehidden == 1 || $assignment->gradehidden > time()) {
                $showgrade = false;
            }
            if ($showgrade) {
                $grade = number_format($assignment->grade);
                $gradeclass = 'danger';
                if ($assignment->grade > $assignment->gradepass) {
                    $gradeclass = 'success';
                }
            }
            $out .= html_writer::tag('div', $grade, array('class'=>"d-inline-block grade ml-1 badge badge-$gradeclass"));
        } else if ($assignment->duedate > 0) {
            $out .= html_writer::tag('div', $duedate, array('class'=>"d-inline-block due-date ml-1 badge badge-$statusclass",
            'data-toggle'=>'tooltip', 'data-placement'=>'top', 'title'=>$duestatus));
        }
        if ($assignment->showdescription) {
            $out .= html_writer::tag('div', format_text($assignment->intro), array('class'=>"summary pl-3"));
        }
        $out .= html_writer::end_tag('div');

        if ($assignment->status == 'submitted') {
            $submitted++;
            $assignsubmittedoutput .= $out;
        } else {
            $due++;
            $assignoutput .= $out;
        }
    }
    if ($submitted == 0) {
        $assignsubmittedoutput .= html_writer::tag('div', get_string('noassignmentssubmitted', 'format_qmultc'), array('class'=>'card-block'));
    }
    if ($due == 0) {
        $assignoutput .= html_writer::tag('div', get_string('noassignmentsdue', 'format_qmultc'), array('class'=>'card-block'));
    }
    $assignoutput .= html_writer::end_tag('div');
    $assignsubmittedoutput .= html_writer::end_tag('div');
    $assignoutput = html_writer::tag('div', $assignoutput, array('class'=>'card'));
    $assignsubmittedoutput = html_writer::tag('div', $assignsubmittedoutput, array('class'=>'card'));

    $output .= html_writer::tag('div', $assignoutput, array('class'=>'col-12 col-md-6 mb-1'));
    $output .= html_writer::tag('div', $assignsubmittedoutput, array('class'=>'col-12 col-md-6 mb-1'));

    return html_writer::tag('div', $output, array('class'=>'row'));
}

function qmul_format_get_assignments() {
    global $DB, $COURSE, $USER;
    $sql = "
       SELECT a.id, cm.id AS cmid, cm.visible, cm.showdescription, a.name, a.duedate, s.status, a.intro, g.grade, gi.gradepass,
              gi.hidden As gradehidden, a.markingworkflow, uf.workflowstate
         FROM {assign} a
         JOIN {course_modules} cm ON cm.instance = a.id
         JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
         JOIN (SELECT DISTINCT e.courseid
                          FROM {enrol} e
                          JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = :userid1
                         WHERE e.status = :enabled AND ue.status = :active
                           AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
              ) en ON (en.courseid = a.course)
         LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = :userid2 AND s.latest = 1
         LEFT JOIN {assign_grades} g ON g.assignment = a.id AND g.userid = :userid3
         LEFT JOIN {grade_items} gi ON gi.iteminstance = a.id AND itemmodule = 'assign'
         LEFT JOIN {assign_user_flags} uf ON uf.assignment = a.id AND uf.userid = s.userid
        WHERE a.course = :courseid
        ORDER BY a.duedate
    ";
    $params = [
        'userid1' => $USER->id, 'userid2' => $USER->id, 'userid3' => $USER->id,
        'now1' => time(), 'now2' => time(),
        'active' => ENROL_USER_ACTIVE, 'enabled' => ENROL_INSTANCE_ENABLED,
        'courseid' => $COURSE->id
    ];

    $assignments = $DB->get_records_sql($sql, $params);
    return $assignments;
}