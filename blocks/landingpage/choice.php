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
 * Choose initial landing page
 *
 * @package   block_landingpage
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $PAGE, $USER, $OUTPUT;

$PAGE->set_url(new moodle_url('/blocks/landingpage/choice.php'));
require_login();
$PAGE->set_context(context_system::instance());

if ($redir = \block_landingpage\landingpage::instance()->get_landing_page($USER, true, true)) {
    redirect($redir); // Shouldn't be on this page in the first place.
}

$custom = [
    'userschools' => \block_landingpage\landingpage::instance()->get_user_schools($USER->id),
    'useradio' => true,
];
$form = new \block_landingpage\updatelandingpage_form(null, $custom);

if ($data = $form->get_data()) {
    \block_landingpage\landingpage::instance()->set_saved_school($USER, $data->school);
    redirect(\block_landingpage\landingpage::instance()->get_landing_page($USER, false, true));
}

$PAGE->set_heading(get_string('chooseschool', 'block_landingpage'));
$PAGE->set_title(get_string('chooseschool', 'block_landingpage'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('chooseschool', 'block_landingpage'), 1);
echo html_writer::div(get_string('intro', 'block_landingpage'));
$form->display();
echo $OUTPUT->footer();
