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
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

$confirmed = optional_param('confirm',0,PARAM_INT);

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$PAGE->set_url('/blocks/ulcc_diagnostics/actions/course_upload.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_course($SITE);
$PAGE->set_pagetype('course-upload');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Section Name Fix');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

$nullsections = $DB->count_records_select('course_sections','name IS NULL');
$namedsections = $DB->count_records_select('course_sections','name IS NOT NULL');

if($confirmed == 1) {
  $sections = $DB->get_records('course_sections');
  foreach ($sections as $section) {
      echo $OUTPUT->box_start();
      echo '<h1>Upgrading Section '.$section->section.' in Course <a href="'.$CFG->wwwroot.'/course/view.php?id='.$section->course.'">'.$section->course.'</a></h2>';
      if($section->summary == '' || is_null($section->summary)) {
          echo 'Summary is empty -- skipped<br />';
      }else{
          echo 'Current summary:<br />'.format_text($section->summary,FORMAT_MOODLE).'<br />';
          $section->name = substr(strip_tags(format_text(preg_replace('/[^(\x20-\x7F)]*/','',$section->summary,FORMAT_MOODLE))),0,50);
          $section->summary = str_replace($section->name,'',$section->summary);
          if($updatesection = $DB->update_record('course_sections',$section)) {
              echo 'Name updated to: '.$section->name.'<br />';
              echo 'New summary: '.format_text($section->summary,FORMAT_MOODLE).'<br />';
          }
      }
      echo '<hr />';
      echo $OUTPUT->box_end();
  }
}else{
  echo '<p>This is a one-time script that will replace the default summary name with a truncated version of the summary and is intended to support upgrade from Moodle 1.9.</p>';
  echo '<p>Your database currently has '.$nullsections.' empty sections names to be updated.</p>';
  echo '<p>Your database currently has '.$namedsections.' empty sections names to be <strong>replaced.</strong></p>';
  echo '<p>This script cannot be undone - make sure backup is taken: <code>
mysql> CREATE TABLE mdl_course_sections_backup (id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY) AS SELECT * FROM mdl_course_sections;</code></p>';
  echo '<p>To continue <a href="?confirm=1">click here</a></p>';
}

echo $OUTPUT->footer();
?>