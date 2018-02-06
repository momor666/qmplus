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
 * Quickscan launching page
 *
 * @package    block
 * @subpackage quickscan
 * @copyright  2012, Lancaster University, Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$courseid = required_param('courseid', PARAM_INT); //if no courseid is given
require_login($courseid);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/quickscan/launchtest.php');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('quickscantest', 'block_quickscan'));
$PAGE->navbar->add(get_string('quickscantest', 'block_quickscan'));

$renderer = $PAGE->get_renderer('block_quickscan');

// OUTPUT
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('quickscantest', 'block_quickscan'), 3, 'main');
echo $renderer->get_test_description();
echo $OUTPUT->footer();
