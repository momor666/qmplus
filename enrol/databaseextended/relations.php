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
 * Database extended enrolment plugin.
 *
 * This is the interface for altering the relations manually, adding more, etc.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012 University of London Computer Centre {@link http://ulcc.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Standard setup stuff.
require_once(dirname(__FILE__)."/../../config.php");
global $PAGE, $OUTPUT, $CFG;
require_once($CFG->dirroot."/enrol/databaseextended/lib.php");
require_once($CFG->dirroot."/enrol/databaseextended/relations_mform.php");

require_login(1);






// Page variables setup etc.
$url = new moodle_url('/enrol/databaseextended/relations.php');
$PAGE->set_url($url);
$title = 'Databaseextended relations';
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');
// Make it look like the page is part of the admin tree. Can't really put it there properly as
// the settings file is only included when on the actual settings page, so admin tree items won't
// appear normally.
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('plugins', 'admin'));
$PAGE->navbar->add(get_string('enrolments', 'enrol'));
$PAGE->navbar->add(get_string('pluginname', 'enrol_databaseextended'),
                   new moodle_url('/admin/settings.php',
                                  array('section' => 'enrolsettingsdatabaseextended')));
$PAGE->navbar->add($title);

// Capabilities check.
$context = context_system::instance();
if (!has_capability('enrol/databaseextended:managerelations', $context)) {
    die('You do not have permission to manage relations for the databaseextended enrol plugin');
}

// New form.
$enrolplugin = new enrol_databaseextended_plugin();
$enrolplugin->initialise_all_tables();
$form = new enrol_databaseextended_relations_mform($enrolplugin);

// Process it.
if ($form->is_cancelled()) {
    redirect($CFG->wwwroot, 'Operation cancelled');
}
$data = $form->get_data();
if ($data) {
    $form->save_data($data);
    // Without this, the added fields won't show up because the definition has already been done.
    redirect($PAGE->url, get_string('changessaved'));
}
// Adds all the defaults from existing relations.
$form->load_existing_data();

// Display it.
echo $OUTPUT->header();
if ($form->data_was_saved()) { // TODO not used any more.
    echo $OUTPUT->heading(get_string('changessaved'), 2, 'notifysuccess');
}
$form->display();

// Footer.
echo $OUTPUT->footer();



