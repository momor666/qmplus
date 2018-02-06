<?php

/****************************************************************

File:     /local/course_creation_wizard/forms/course_create_form.php

Purpose:  Form for course creation

 ****************************************************************/

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
 * Form to create a course.
 * @package local
 * @subpackage course_creation_wizard
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// No direct script access.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
class course_create_form extends moodleform
{
    const TEMPELATE_CAT_ID = 1;
    public function definition()
    {
        global $DB;
        $categoryid = optional_param('category','0', PARAM_INT);
        $mform = & $this->_form;
        $mform->addElement('header', 'course_create_header', get_string('course_creation_wizard_legend', 'local_course_creation_wizard'));
        $radioarray=array();
        $attributes = array();
        $radioarray[] = $mform->createElement('radio', 'createchoice', '', get_string('create_option_c','local_course_creation_wizard'), 2, $attributes);
        $radioarray[] = $mform->createElement('radio', 'createchoice', '', get_string('create_option_a','local_course_creation_wizard'), 0, $attributes);
        $radioarray[] = $mform->createElement('radio', 'createchoice', '', get_string('create_option_b','local_course_creation_wizard'), 1, $attributes);

        $mform->addGroup($radioarray, 'radioar',get_string('create_option_label','local_course_creation_wizard'), array(' '), false);
        $mform->setDefault('createchoice', 2);
        $catcourses = $DB->get_records_select('course',"category='".$categoryid."'",[],'fullname ASC','id, fullname');
        foreach ($catcourses as $id => &$catcourse){
            $catcourse = $catcourse->fullname;
        }
        $mform->addElement('select', 'course_prev', get_string('prev_course_options', 'local_course_creation_wizard'), $catcourses, array('class'=>'create_prev'));

        $tempcourses = $DB->get_records_select('course',"category='".self::TEMPELATE_CAT_ID."'",[],'fullname ASC','id, fullname');
        foreach ($tempcourses as $id => &$tempcourse){
            $tempcourse = $tempcourse->fullname;
        }
//        $mform->addElement('select', 'course_template', get_string('template_options', 'local_course_creation_wizard'), $tempcourses, array('class'=>'create_template'));
        $dummyArray = ['L & S Templates'=>[1641=>'MT300 Module Template Learning & Support - Generic',4698 => 'MT301 Module Template - Researcher Development'],'SMD Templates'=>[1573=>'MT400 Module Template - Dentistry',1576=>'MT401 Module Template - BCI & PGMed'],'HSS Templates'=>[6670=>'MT200 Module Template HSS - Generic',5903=>'CCLS Module Template',2722=>'MT202 Module Template - Paris LLM',1592=>'MT201 Module Template - Law Terminology'],'S & E Templates'=>[2080=>'MT500 Module Template S & E - EECS',6139=>'MT501 Module Template - S & E - Nanchang Joint Programme',6143=>'MT502 Module Template - S & E - SBCS', 3007=>'MT503 Module Template - S & E - SEFP',2489=>'MT504 Module Template - S & E - SEMS', 2969 =>'MT505 Module Template - S & E - SMS',2477=>'MT506 Module Template - S & E - SPA', 4999=>'MT507 Module Template - S & E - BUPT Joint Programme copy 1' ]];
        $mform->addElement('selectgroups', 'course_template', get_string('template_options', 'local_course_creation_wizard'), $dummyArray, array('class'=>'create_template'));
        $this->add_action_buttons(true,'Create Course');

    }
}