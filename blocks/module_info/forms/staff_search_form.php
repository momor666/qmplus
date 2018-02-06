<?php

/**
 * Created by PhpStorm.
 * User: sumatrix
 * Date: 11/15/16
 * Time: 10:55 AM
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class staff_search_form extends moodleform
{
    public function definition()
    {
        global $PAGE;
        $PAGE->requires->js_init_call('M.block_module_info.init_staff_search');
        $mform = & $this->_form;

        $mform->addElement('text', 'terms', get_string('terms', 'block_module_info'), 'maxlength="100" size="30"');
        $mform->setType('terms', PARAM_TEXT);
        $mform->addHelpButton('terms', 'terms', 'block_module_info');

        $mform->addElement('submit', 'submitbutton', 'Search');
    }
}