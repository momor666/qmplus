<?php

/****************************************************************

File:      local/landingpages/index_form.php

Purpose:   Global settings form

****************************************************************/

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/lib/formslib.php');


class local_landingpages_form extends moodleform {

    // Define the form
    public function definition () {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'enabled', null, get_string('enabled', 'local_landingpages'));
        $mform->setDefault('enabled', 1);

		$mform->addElement('checkbox', 'showredirecterror', null, get_string('showredirecterror', 'local_landingpages'));
	    $mform->setDefault('showredirecterror', 1);
		$mform->addHelpButton('showredirecterror', 'showredirecterror', 'local_landingpages');

		$mform->addElement('text', 'userlogindelay', get_string('userlogindelay', 'local_landingpages'));
		$mform->addHelpButton('userlogindelay', 'userlogindelay', 'local_landingpages');
		$mform->setType('userlogindelay', PARAM_INT);
        $mform->addRule('userlogindelay', null, 'required');
		$mform->setDefault('userlogindelay', 5);
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $COURSE, $DB, $CFG;

        $errors = parent::validation($data, $files);

        return $errors;
    }

}
