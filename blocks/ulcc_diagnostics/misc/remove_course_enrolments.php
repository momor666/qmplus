<?php

/**
 * Moodle ULCC Admin page.
 *
 * @package    block_ulcc_diagnostic
 * @copyright  2011 onwards ULCC (http://www.ulcc.ac.uk)
 *
 * refactored 6th July 2011 for readability
 *
 */

require('../../../config.php');
global $CFG, $USER, $DB;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/enrollib.php');

$confirmed = optional_param('confirm',0,PARAM_INT);

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$PAGE->set_url('/blocks/ulcc_diagnostics/actions/manual_course_fix.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_course($SITE);
$PAGE->set_pagetype('manual-course-fix');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Manual Course Fix');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

$plugin = enrol_get_plugin('manual');

if($confirmed == 1) {
  $courses = $DB->get_records('course');
  foreach ($courses as $course) {
      echo $OUTPUT->box_start();
      echo '<h1>Removing course enrolments <a href="'.$CFG->wwwroot.'/enrol/users.php?id='.$course->id.'">'.$course->id.'</a></h1>';
      if ($instance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'manual','roleid'=>5))) {
            // only one instance allowed, sorry
            echo '<p>Instance exists:<br />';
            //first unenrol all users
            $participants = $DB->get_recordset('user_enrolments', array('enrolid'=>$instance->id));
            foreach ($participants as $participant) {
                $user = $DB->get_record('user',array('id'=>$participant->userid));
                if(is_numeric($user->username)) {
                    $plugin->unenrol_user($instance, $user->id);
                    echo $user->username.' unenrolled<br />';     
                }else{
                    echo $user->username.' is staff<br />';
                }
            }
        }else{
           echo 'Instance doesn\'t exist.<br />';
      }
      echo '<hr />';
      echo $OUTPUT->box_end();
  }
}else{
  echo '<p>This is a one-time script that will remove enrolments from instances to courses that does not have one.</p>';
  echo '<p>To continue <a href="?confirm=1">click here</a></p>';
}

echo $OUTPUT->footer();
?>