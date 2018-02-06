<?php

/****************************************************************

File:     /block/course_rollover/forms/activity_reset_form.php

Purpose:  Dummy form for activity resets form 

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
 * Form to create rollover a course.
 *
 * @package      blocks
 * @subpackage   course_rollover
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// No direct script access.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
class activity_reset_form extends moodleform
{
	public function definition()
    {
    	global $CFG, $DB;
        $mform = & $this->_form;

        $modname = $this->_customdata['modname'];
        $mod_reset_course_form_definition = $modname . '_reset_course_form_definition';
        $mod_reset_course_form_definition($mform);
    }

    public function get_elements(){
    	$form_elements = $this->_form->_elements;
    	foreach ($this->_form->_elements as $key => $element) {
    		if(in_array($element->_type, array('header','hidden'))){
    			unset($this->_form->_elements[$key]);
    		}
    	}
    	return $this->_form->_elements;
    }
}