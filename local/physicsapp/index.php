<?php

/****************************************************************

File:       local/physicsapp/index.php

Purpose:    Test page

****************************************************************/

require_login();

$PAGE->set_url('/local/physicsapp/index.php');
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);
$PAGE->set_title(get_string('testpage_title', 'local_physicsapp'));

$output = $OUTPUT->header();
$output .= $OUTPUT->heading(get_string('testpage_title', 'local_physicsapp'));

$hassiteconfig = has_capability('moodle/site:config', $context);

if($hassiteconfig) {
    // Link to settings page...
    $output .= html_writer::start_tag('div', array('class'=>'in-page-controls'));
    $output .= html_writer::start_tag('p', array('class='=>'settings'));
    $output .= html_writer::start_tag('a', array('href'=>$CFG->wwwroot.'/admin/settings.php?section=local_physicsapp'));
    $output .= get_string('general_settings', 'local_physicsapp');
    $output .= html_writer::start_tag('span');
    $output .= html_writer::start_tag('i');
    $output .= html_writer::end_tag('i');
    $output .= html_writer::end_tag('span');
    $output .= html_writer::end_tag('a');
    $output .= html_writer::end_tag('p');
    $output .= html_writer::end_tag('div');
}

$output .= $OUTPUT->footer();

echo $output;
