<?php  // $Id: download.php, v1.0 2015/04/21  djhipps Exp $

/**
 * This page downloads the submisssions
 * It is similar to the downloade_all function in the
 * /mod/assign/locallib.php plugin file, but it adds the
 * the student id data instead of generating a random number.
 * The menu item points to this view which is a blank download
 * page. This implements the assignment_download class in
 * lib.php
 */



require_once('../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

$urlparams = array('id' => $id);

$url = new moodle_url('/local/qmul_download_submissions/download.php', $urlparams);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/assign:grade', $context);


$assignmentdownloader = new assignment_downloader($context, $cm, $course);

//if out put is returned then
$output = $assignmentdownloader->download_all_submissions_by_id();

if($output){
    echo $output;
}


//TODO: what does this do?
$completion=new completion_info($course);
$completion->set_module_viewed($cm);

// Get the assign class to
// render the page.
//echo $assign->view(optional_param('action', '', PARAM_TEXT));

