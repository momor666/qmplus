<?php

/****************************************************************

File:     local/landingpages/index.php

Purpose:  Global settings for the plugin

****************************************************************/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/local/landingpages/index_form.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$pageparams = array();
admin_externalpage_setup('local_landingpages', '', $pageparams);

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');

$PAGE->set_heading($SITE->fullname);
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'local_landingpages'));
$returnurl = new moodle_url('/local/landingpages');

$mform = new local_landingpages_form();

$mform->set_data(get_config('local_landingpages'));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $mform->get_data()) {
    if (isset($data->enabled)) {
        set_config('enabled' , 1, 'local_landingpages');
    } else {
        set_config('enabled' , 0, 'local_landingpages');
    }

	if (isset($data->showredirecterror)) {
        set_config('showredirecterror' , 1, 'local_landingpages');
    } else {
        set_config('showredirecterror' , 0, 'local_landingpages');
    }

    set_config('userlogindelay' , $data->userlogindelay, 'local_landingpages');


}

echo $OUTPUT->header();
echo $OUTPUT->box_start();
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
