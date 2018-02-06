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
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/lib.php';

$confirmed = optional_param('confirm',0,PARAM_INT);

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$PAGE->set_url('/blocks/ulcc_diagnostics/actions/grade_item_insert.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_course($SITE);
$PAGE->set_pagetype('manual-course-fix');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Insert Aspriational Grade Item');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

if($confirmed == 1) {
  $courses = $DB->get_records('course');
  foreach ($courses as $course) {
      echo $OUTPUT->box_start();
      echo '<h1>Upgrading Course <a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.$course->id.'</a></h2>';
      if(!$grade_cat = $DB->get_record_select('grade_categories',"fullname = 'Core Indicators' AND courseid = $course->id")) {
		 	$grade_category = new grade_category();
            $grade_category->courseid = $course->id;
            $grade_category->fullname = 'Core Indicators';
            $grade_category->droplow = 0;
            $grade_category->aggregation = GRADE_AGGREGATE_WEIGHTED_MEAN2;
            $grade_category->aggregateonlygraded = 0;
            if($grade_category->insert()) {
				echo '<p>Category Created</p>';
			}
			$grade_category->load_grade_item(); // force creation of grade_item
			$grade_cat = $DB->get_record_select('grade_categories',"fullname = 'Core Indicators' AND courseid = $course->id");
		}else{
			echo '<p>Category Exists</p>';
		}
		
		if(!$gradeitem = grade_item::fetch(array('itemname'=>'Aspiration', 'courseid'=>$course->id))) {
			$gradeitem = new grade_item(array('courseid'=>$course->id, 'itemtype'=>'manual', 'itemname'=>'Aspiration', 'gradetype'=>1, 'grademax'=>'100.00000','grademin'=>'0.00000','categoryid'=>$grade_cat->id), false);	
			if($gradeitem->insert()) {
				echo '<p>Grade Item Aspiration installed</p>';
			}else{
				echo '<p>Grade Item Aspiration not installed</p>';
			}
		}else{
			$gradeitem->categoryid = $grade_cat->id;
			if($gradeitem->update()) {
				echo '<p>Grade Item Aspiration updated</p>';
			}
		}
		unset($gradeitem);
		
      echo '<hr />';
      echo $OUTPUT->box_end();
  }
}else{
  echo '<p>This is a one-time script that will add a grade item called \'Aspirational Grade\' to any course that does not have one.</p>';
  echo '<p>To continue <a href="?confirm=1">click here</a></p>';
}

echo $OUTPUT->footer();
?>