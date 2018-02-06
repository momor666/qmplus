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
 * Save the landing page
 *
 * @package   block_landingpage
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_landingpage\landingpage;

require_once(dirname(__FILE__).'/../../config.php');
global $PAGE, $USER;

$showallschools = optional_param('showallschools', 0, PARAM_BOOL);

$PAGE->set_url(new moodle_url('/')); // If all else fails, just go back to the front page.
require_login();
$PAGE->set_context(context_system::instance());

if ($showallschools) {
    $schools = landingpage::instance()->get_all_schools();
} else {
    $schools = landingpage::instance()->get_user_schools($USER->id);
}
$custom = [
    'userschools' => $schools,
];
$form = new \block_landingpage\updatelandingpage_form(null, $custom);

if ($data = $form->get_data()) {
    landingpage::instance()->set_saved_school($USER, $data->school);
}

redirect(landingpage::instance()->get_landing_page($USER, false, true));
