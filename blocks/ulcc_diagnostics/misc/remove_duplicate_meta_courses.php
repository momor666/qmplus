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

$PAGE->set_url('/blocks/ulcc_diagnostics/actions/remove_duplicate_meta_courses.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_course($SITE);
$PAGE->set_pagetype('manual-course-fix');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Meta Course Fix');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

$plugin = enrol_get_plugin('meta');

if($confirmed > 0) {
  $duplicates = $DB->get_records_sql("SELECT distinct(table".$confirmed.".id) FROM mdl_enrol AS table1, mdl_enrol AS table2 WHERE table1.courseid = table2.customint1 AND table1.enrol = 'meta';");
  foreach ($duplicates as $duplicate) {
      echo $OUTPUT->box_start();
      echo '<h1>Removing meta enrolment duplicates</h1>';
      if ($instance = $DB->get_record('enrol', array('id'=>$duplicate->id,))) {
            // only one instance allowed, sorry
            echo '<p>Instance exists: <a href="'.$CFG->wwwroot.'/enrol/users.php?id='.$instance->courseid.'">'.$instance->courseid.'</a><br />';
            //first unenrol all users
            $plugin->delete_instance($instance);
            echo 'Deleted '.$instance->id.'</p>';
        }else{
           echo 'Instance doesn\'t exist.<br />';
      }
      echo '<hr />';
      echo $OUTPUT->box_end();
  }
}else{
  echo '<p>This is a one-time script that will remove instances of meta course enrolments that appear as duplicates.</p>';
  echo '<p>To delete parents <a href="?confirm=1">click here</a></p>';
  echo '<p>To delete children <a href="?confirm=2">click here</a></p>';
}

echo $OUTPUT->footer();
?>