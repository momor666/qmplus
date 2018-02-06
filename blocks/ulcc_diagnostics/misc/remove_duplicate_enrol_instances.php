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

$PAGE->set_url('/blocks/ulcc_diagnostics/actions/remove_duplicate_enrol_instances.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_course($SITE);
$PAGE->set_pagetype('duplicate-enrol-deletion');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Meta Course Fix');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();



if($confirmed > 0) {
  $originals = $DB->get_records_sql("SELECT id FROM mdl_enrol WHERE enrol IN ('self','guest') GROUP BY enrol,courseid;");
  $instances = $DB->get_records_sql("SELECT id FROM mdl_enrol WHERE enrol IN ('self','guest');");
  foreach ($instances as $instance) {
      echo $OUTPUT->box_start();
      echo '<h1>Checking enrolment instance: '.$instance->id.'</h1>';
      if(!in_array($instance->id,array_keys($originals))) {
          if ($duplicate = $DB->get_record('enrol', array('id'=>$instance->id))) {
                $plugin = enrol_get_plugin($duplicate->enrol);
                // only one instance allowed, sorry
                echo '<h2>Instance exists: <a href="'.$CFG->wwwroot.'/enrol/users.php?id='.$duplicate->courseid.'">'.$duplicate->courseid.'</a></h2>';
                //first unenrol all users
                $plugin->delete_instance($duplicate);
                echo '<p>Deleted '.$duplicate->id.'<br />';
                echo 'It is impossible to imitate Voltaire without being Voltaire.<br />';
            }else{
               echo '<p>Instance doesn\'t exist.</p>';
          }
      }else{
          echo '<h2>Original</h2>';
          echo '<p>Originality is nothing but judicious imitation.</p>';
      }
      echo '<hr />';
      echo $OUTPUT->box_end();
  }
}else{
  echo '<p>This is a one-time script that will remove duplicate instances of the guest and self enrolment created by http://tracker.moodle.org/browse/MDL-29414.</p>';
  echo '<p>This cannot be undone - to continue <a href="?confirm=1">click here</a></p>';
}

echo $OUTPUT->footer();
?>