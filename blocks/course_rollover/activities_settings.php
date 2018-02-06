<?php

/****************************************************************

File:     /block/course_rollover/activities_settings.php

Purpose:  To render the form to a page on moodle

-****************************************************************/

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
 * this file is use to render the form to a page on moodle
 * @package     rgu_contact_us
 * @subpackage  block
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once dirname(__FILE__) . '/classlib.php';
require_once dirname(__FILE__) . '/forms/activities_settings_form.php';
global $DB, $CFG, $USER, $OUTPUT, $PAGE, $COURSE;

//FIXME !!!!!
require_login(0, false);

$conx = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($conx);
$PAGE->set_url('/blocks/course_rollover/activities_settings.php');
$PAGE->set_pagelayout('course');
//create Page navigation
$pagenode = $PAGE->settingsnav->add(get_string('header_activity', 'block_course_rollover'));
$pagenode->make_active();
$course_rollover_config = get_config('course_rollover');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('header_activity', 'block_course_rollover'));
echo $OUTPUT->box(get_string('header_activity_desc', 'block_course_rollover'));

$site = get_site();
$mform = new activities_settings_form(null, array('default' => $course_rollover_config->activities_settings));
if ($mform->is_cancelled()) {
    echo 'redirecting...';
    redirect($CFG->wwwroot);
} else if ($fromform = $mform->get_submitted_data()) {
	unset($fromform->submitbutton);
    set_config('activities_settings', json_encode($fromform), 'course_rollover');
    $mform->display();
} else {
    // form didn't validate or this is the first display
    $site = get_site();
    $mform->display();
}

echo $OUTPUT->footer();
?>
