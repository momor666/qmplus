<?php

/****************************************************************

File:     /block/course_rollover/db/install.php

Purpose:  Form to create rollover a course

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
 * @subpackage   course_template
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// No direct script access.
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/course/lib.php');

class activities_settings_form extends moodleform
{

    public function definition()
    {
        global $CFG, $DB;
        $mform = & $this->_form;
        if ($allmods = $DB->get_records_list('modules', 'name', explode(',', get_config('course_rollover', 'activity_resets')))) {
            foreach ($allmods as $mod) {
                $modname = $mod->name;
                $modfile = $CFG->dirroot . "/mod/$modname/lib.php";
                $mod_reset_course_form_definition = $modname . '_reset_course_form_definition';
                $mod_reset__userdata = $modname . '_reset_userdata';
                if (file_exists($modfile)) {
                    include_once($modfile);
                    if (function_exists($mod_reset_course_form_definition)) {
                        $mod_reset_course_form_definition($mform);
                    }
                } else {
                    debugging('Missing lib.php in ' . $modname . ' module');
                }
            }
        }

        foreach (json_decode($this->_customdata['default']) as $setting => $value) {
            $mform->setDefault($setting, $value);
        }

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save_activities_settings', 'block_course_rollover'));
        $buttonarray[] = &$mform->createElement('submit', 'selectdefault', get_string('selectdefault'));
        $buttonarray[] = &$mform->createElement('submit', 'deselectall', get_string('deselectall'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    function load_defaults()
    {
        global $CFG, $COURSE, $DB;

        $mform = & $this->_form;

        $defaults = array();
        if ($allmods = $DB->get_records_list('modules', 'name', explode(',', get_config('course_rollover', 'activity_resets')))) {
            foreach ($allmods as $mod) {
                $modname = $mod->name;
                $modfile = $CFG->dirroot . "/mod/$modname/lib.php";
                $mod_reset_course_form_defaults = $modname . '_reset_course_form_defaults';
                if (file_exists($modfile)) {
                    @include_once($modfile);
                    if (function_exists($mod_reset_course_form_defaults)) {
                        if ($moddefs = $mod_reset_course_form_defaults($COURSE)) {
                            $defaults = $defaults + $moddefs;
                        }
                    }
                }
            }
        }

        foreach ($defaults as $element => $default) {
            $mform->setDefault($element, $default);
        }
    }

}
