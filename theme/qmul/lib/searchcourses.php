<?php

define('AJAX_SCRIPT', true);

require_once('../../../config.php');

$search = required_param('search', PARAM_RAW);
$userid = optional_param('userid', 0, PARAM_INT);
$return = array();

if ($userid) {
	$cache = cache::make('theme_qmul', 'searchcourses');
	$modules = $cache->get('mymodules_'.$userid);
} else {
	$cache = cache::make('theme_qmul', 'allsearchcourses');
	$modules = $cache->get('allmodules');
}

foreach ($modules as $module) {
	if (stripos($module->fullname, $search) !== false) {
		$url = new moodle_url('/course/view.php', array('id'=>$module->id));
		$module->url = $url->out();
		$return[] = $module;
	}
}

print json_encode($return);