<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/course/editsection_form.php');

/**
 * Default form for editing course section
 *
 * Course format plugins may specify different editing form to use
 */
class format_landingpage_editsection_form extends editsection_form {

    public function definition_after_data() {
        global $CFG, $DB;

        $mform  = $this->_form;
        $course = $this->_customdata['course'];
        $context = context_course::instance($course->id);

        if (!empty($CFG->enableavailability)) {
            $mform->addElement('header', 'availabilityconditions',
                    get_string('restrictaccess', 'availability'));
            $mform->setExpanded('availabilityconditions', false);

            // Availability field. This is just a textarea; the user interface
            // interaction is all implemented in JavaScript. The field is named
            // availabilityconditionsjson for consistency with moodleform_mod.
            $mform->addElement('textarea', 'availabilityconditionsjson',
                    get_string('accessrestrictions', 'availability'));
            \core_availability\frontend::include_all_javascript($course, null,
                    $this->_customdata['cs']);
        }

        $data = $this->_customdata['cs'];

        $sectionid = required_param('id', PARAM_INT);
        $fileoptions = array(
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => array('image'),
        );

        $draftitemid = file_get_submitted_draft_itemid('sectionimage');
        file_prepare_draft_area($draftitemid, $context->id, 'format_landingpage', 'sectionimage', $sectionid, $fileoptions);
        $data->sectionimage = $draftitemid;

        $this->set_data($data);

        $this->add_action_buttons();
    }

}
