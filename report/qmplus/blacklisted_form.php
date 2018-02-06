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
 * Form for generating mime type list
 *
 * @package    report_qmplus
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once("$CFG->libdir/formslib.php");

/**
 * class for displaying blacklilsted type form.
 *
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 */
class report_qmplus_blacklisted_form extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('html', "<h2>".get_string('blacklistedreportstitle', 'report_qmplus')."</h2>");
        $mform->addElement('html', "<p>".get_string('blacklistedeportsdesc', 'report_qmplus')."</p>");
        $mform->addElement('date_time_selector', 'reportfrom', get_string('from'), null, array('class' => 'id_reportfrom'));
        $mform->addElement('date_time_selector', 'reportto', get_string('to'), null, array('class' => 'id_reportto'));
        $mform->addElement('select', 'filetype', get_string('blacklistedtype', 'report_qmplus'), report_qmplus_getBlacklistedTypes_list(),
            array('id'=>'blacklisted'));

        $mform->addElement('submit', 'filetypesubmit', get_string('getreport', 'report_qmplus'), array('id'=>'filetypesubmit'));


    }
}
