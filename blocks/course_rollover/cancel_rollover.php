<?php
/****************************************************************

File:     /block/course_rollover/cancel_rollover.php

Purpose:  To render the form to a page on moodle

 ****************************************************************/

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $DB, $CFG, $PAGE, $OUTPUT;

require_once 'classlib.php';
$courseid = required_param('id', PARAM_INT);
$confirm = required_param('confirm', PARAM_INT);


if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error("invalidcourseid");
}

require_login();
$context = context_course::instance($courseid);
require_capability('block/course_rollover:manage', $context);
$PAGE->set_context($context);
$PAGE->set_url('/blocks/course_rollover/cancel_rollover.php', array('id' => $courseid, 'confirm' => $confirm));
$PAGE->set_pagelayout('course');
$PAGE->set_course($course);

if($confirm){
    $DB->delete_records('block_course_rollover', array('courseid' => $courseid));
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);

} else{
    $confirmurl = new moodle_url('/blocks/course_rollover/cancel_rollover.php', array('id' => $courseid, 'confirm' => 1));
    $cancelurl = new moodle_url('/course/view.php', array('id' => $courseid));
    $yesbutton = new single_button($confirmurl, get_string('yes'));
    $nobutton = new single_button($cancelurl, get_string('no'));
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('block_cancel_rollover_confirm','block_course_rollover'), $yesbutton, $nobutton);
    echo $OUTPUT->footer();
}