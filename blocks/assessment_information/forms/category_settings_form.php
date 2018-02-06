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
 * Version details.
 *
 * @package    blocks
 * @subpackage assessment_information
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// No direct script access.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class category_settings_form extends moodleform
{

    public function definition()
    {
        global $CFG, $DB;
        $mform = & $this->_form;
        $id = $this->_customdata['id'];
        $premixcourseconfig = $this->_customdata['premixcourseconfig'];
        $default_course_id = $this->_customdata['default_course_id'];

        //add course id
        $mform->addElement('text','courseid', get_string('label_courseid','block_assessment_information'));
        $mform->setType('courseid', PARAM_INT);
        $mform->addRule('courseid',null,'required',null,'client');
        if($default_course_id){
            $mform->setDefault('courseid',$default_course_id);
        }

        $mform->addElement('hidden', 'id');
        $mform->setDefault('id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'premixcourseconfig');
        $mform->setType('premixcourseconfig', PARAM_TEXT);
        $mform->setDefault('premixcourseconfig', $premixcourseconfig);

        //add resources list
        //add submit buttons
        $this->add_action_buttons(true, 'Save Changes');
    }

    public function validation($data, $files){
        global $DB;
        $errors = parent::validation($data, $files);
        if (array_key_exists('courseid', $data)) {
            if (!$DB->get_record('course',array('id'=>$data['courseid']))) {
                $errors['courseid'] = get_string('messages_category_courseid_error', 'block_assessment_information');
            }
        }
        return $errors;
    }
}
