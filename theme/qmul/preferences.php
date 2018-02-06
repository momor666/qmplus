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
 * Form page for blog preferences
 *
 * @package    moodlecore
 * @subpackage blog
 * @copyright  2009 Nicolas Connault
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('preferences_form.php');

$url = new moodle_url('/theme/qmul/preferences.php');

$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$sitecontext = context_system::instance();
$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

if (isguestuser()) {
    print_error('noguest');
}

require_capability('moodle/user:editownprofile', $usercontext);

// If data submitted, then process and store.
$userpreference = get_user_preferences('theme_qmul_logindashboard');

if ($userpreference == null) {
	$userpreference = 1;
}

$mform = new theme_qmul_preferences_form('preferences.php');
$mform->set_data(
    array(
        'logindashboard' => $userpreference
    )
);

if (!$mform->is_cancelled() && $data = $mform->get_data()) {
	if (!isset($data->logindashboard)) {
    	$logindashboard = 0;
	} else {
    	$logindashboard = $data->logindashboard;
	}

    set_user_preference('theme_qmul_logindashboard', $logindashboard);
}

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/user/preferences.php');
}

$site = get_site();

$prefstr = get_string('displaypreferences', 'theme_qmul');

$title = "$site->shortname: $prefstr";
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));

echo $OUTPUT->header();

echo $OUTPUT->heading("$prefstr", 2);

$mform->display();

echo $OUTPUT->footer();