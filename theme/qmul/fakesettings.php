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
 * Force access to theme settings, even if user is not site admin.
 *
 * @package   theme_qmul
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_qmul\settings_helper;

require_once(__DIR__.'/../../config.php');
global $PAGE, $OUTPUT, $SITE;

$section = optional_param('section', null, PARAM_ALPHANUMEXT);
$url = new moodle_url('/theme/qmul/fakesettings.php');
if ($section) {
    $url->param('section', $section);
}
$PAGE->set_url($url);
require_login();
$syscontext = context_system::instance();
$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('admin');
require_capability('theme/qmul:configure', $syscontext);

// Generate a fake admin root, which just has the theme settings pages.
$fakeadminroot = settings_helper::get_fake_admin_root();
$adminroot = admin_get_root();

$settingspage = $adminroot->locate($section, true);
if (!$settingspage) {
    // No sub-page selected, so show a list of available pages.
    $PAGE->set_title($SITE->shortname);
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('availablesettings', 'theme_qmul'));
    echo settings_helper::output_pages_list($fakeadminroot);
    echo $OUTPUT->footer();
    die();
}

// Save the settings, if submitted.
if (settings_helper::process_settings($fakeadminroot)) {
    redirect($PAGE->url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Check for error messages.
$errormsg = '';
if ($adminroot->errors) {
    $errormsg = get_string('errorwithsettings', 'admin');
}

$visiblepathtosection = array_reverse($settingspage->visiblepath);
$PAGE->set_title("$SITE->shortname: " . implode(": ",$visiblepathtosection));
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
if ($errormsg !== '') {
    echo $OUTPUT->notification($errormsg);
}

// Output the settings.
$pageparams = $PAGE->url->params();
$context = [
    'actionurl' => $PAGE->url->out(false),
    'params' => array_map(function($param) use ($pageparams) {
        return [
            'name' => $param,
            'value' => $pageparams[$param]
        ];
    }, array_keys($pageparams)),
    'sesskey' => sesskey(),
    'return' => null,
    'title' => $settingspage->visiblename,
    'settings' => $settingspage->output_html(),
    'showsave' => $settingspage->show_save()
];
echo $OUTPUT->render_from_template('core_admin/settings', $context);

$PAGE->requires->yui_module('moodle-core-formchangechecker',
                            'M.core_formchangechecker.init',
                            array(array(
                                      'formid' => 'adminsettings'
                                  ))
);
$PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');

echo $OUTPUT->footer();
