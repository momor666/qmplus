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

if($confirmed == 1) {
  $courses = $DB->get_records('course');
  foreach ($courses as $course) {
      echo $OUTPUT->box_start();
      echo '<h1>Upgrading Course <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->id.'</a></h2>';
      if ($DB->record_exists('enrol', array('courseid'=>$course->id, 'enrol'=>'manual'))) {
            // only one instance allowed, sorry
            echo 'Instance already exists';
        }else{
           if ($course->id == SITEID) {
            echo 'Invalid request to add enrol instance to frontpage.';
           }else{

                $instance = new stdClass();
                $instance->enrol          = 'manual';
                $instance->status         = ENROL_INSTANCE_ENABLED;
                $instance->courseid       = $course->id;
                $instance->enrolstartdate = 0;
                $instance->enrolenddate   = 0;
                $instance->timemodified   = time();
                $instance->timecreated    = $instance->timemodified;
                $instance->sortorder      = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid'=>$course->id));
        
                $fields = (array)$fields;
                unset($fields['enrol']);
                unset($fields['courseid']);
                unset($fields['sortorder']);
                foreach($fields as $field=>$value) {
                    $instance->$field = $value;
                }

                if($DB->insert_record('enrol', $instance)) {
                    echo 'Instance created';
                }else{
                    echo 'Instance denied';
                }
           }
      }
      echo '<hr />';
      echo $OUTPUT->box_end();
  }
}else{
  echo '<p>This is a one-time script that will add a manual enrolment instance to any course that does not have one.</p>';
  echo '<p>To continue <a href="?confirm=1">click here</a></p>';
}

echo $OUTPUT->footer();
?>