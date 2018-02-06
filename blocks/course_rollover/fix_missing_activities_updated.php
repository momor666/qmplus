<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG, $DB; // Globals
require_once $CFG->dirroot . '/course/lib.php';

$modules = $DB->get_records('modules');

foreach($modules as $module){
	$course_modules = $DB->get_records_sql("
		SELECT cm.id, a.id as aid
		FROM {course_modules} as cm
		LEFT JOIN {".$module->name."} as a
		ON cm.instance = a.id and cm.course = a.course
		WHERE a.id IS NULL AND cm.module = ?
	",array($module->id));
	$i = 0;
	foreach ($course_modules as $course_module) {
		$DB->delete_records('course_modules', array('id'=>$course_module->id));
		$i++;
	}
}
echo "Missing $i Records Deleted";