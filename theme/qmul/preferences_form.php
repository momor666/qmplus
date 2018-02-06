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
 * Form for theme preferences
 *
 * @package    theme_qmul
 * @copyright  2017 Andrew Davidson, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

require_once($CFG->libdir.'/formslib.php');

class theme_qmul_preferences_form extends moodleform {
    public function definition() {
        global $USER, $CFG;

        $mform    =& $this->_form;

        $mform->addElement('checkbox', 'logindashboard', get_string('opendashboardonlogin', 'theme_qmul'));
        $mform->setDefault('logindashboard', 1);
        $mform->addHelpButton('logindashboard', 'logindashboard', 'theme_qmul');

        $this->add_action_buttons();
    }
}