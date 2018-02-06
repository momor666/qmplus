<?php

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once($CFG->libdir.'/coursecatlib.php');

require_login();

$userid = required_param('userid', PARAM_INT);
$currentloaded = required_param('currentloaded', PARAM_INT);

$PAGE->set_context(context_user::instance($userid));
$preferences = get_user_preferences(null,null, $userid);
$cache = cache::make('theme_qmul', 'backpackcourses');
$cachedcourses = $cache->get($USER->id);

$output = '';
if ($cachedcourses !== false) {
	$moremodules = array_slice($cachedcourses->allmodules, $currentloaded, 10);

	$overviews = array();
    if ($mods = get_plugin_list_with_function('mod', 'print_overview')) {
        if (defined('MAX_MODINFO_CACHE_SIZE') && MAX_MODINFO_CACHE_SIZE > 0 && count($moremodules) > MAX_MODINFO_CACHE_SIZE) {
            $batches = array_chunk($moremodules, MAX_MODINFO_CACHE_SIZE, true);
        } else {
            $batches = array($moremodules);
        }
        foreach ($batches as $courses) {
            foreach ($mods as $fname) {
                $fname($courses, $overviews);
            }
        }
    }

    foreach ($moremodules as $key => $course) {
        $context = context_course::instance($course->id);
        $hidden = empty($course->visible);
        $editable = has_capability('moodle/course:update', $context);
        $pref = "theme_qmul_pincourse_{$course->id}";
        $listcourse = new course_in_list($course);
        $url = '';
        try {
            foreach ($listcourse->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                        '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                        $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            }
        } catch (Exception $e) {
            $url = '';
        }
        if (!empty($url)) {
            $course->overviewfile = $url;
        }
        if (!$course->visible) {
            $course->invisible = true;
        }
        $courseurl = new moodle_url('/course/view.php', array('id'=>$course->id));
        $course->url = $courseurl;
        if ($hidden) {
            if ($editable) {
                $course->warning = $OUTPUT->pix_icon('i/warning', '', '', array('class'=>'iconlarge'));
                $course->warning .= get_string('hidden_course_teacher', 'theme_qmul');
            } else {
                $course->warning = $OUTPUT->pix_icon('i/info', '', '', array('class'=>'iconlarge'));
                $course->warning .= get_string('hidden_course_student', 'theme_qmul');
            }
        }
        $course->userid = $USER->id;
        if (isset($overviews[$course->id])) {
            $course->overview = true;
            $overview = $overviews[$course->id];
            $modoverviews = array();
            foreach (array_keys($overview) as $module) {
                $modinfo = new stdClass();
                $url = new moodle_url("/mod/$module/index.php", array('id' => $course->id));
                $modulename = get_string('modulename', $module);
                $modinfo->icontext = html_writer::link($url, $OUTPUT->pix_icon('icon', $modulename, 'mod_'.$module, array('class'=>'iconlarge')));
                if (get_string_manager()->string_exists("activityoverview", $module)) {
                    $modinfo->icontext .= get_string("activityoverview", $module);
                } else {
                    $modinfo->icontext .= get_string("activityoverview", 'block_course_overview', $modulename);
                }
                $modoverviews[] = $modinfo;
            }
            $course->overviews = $modoverviews;
        }
        $output .= $OUTPUT->render_from_template('theme_qmul/module', $course);
    }
}

$return = new stdClass();
$return->html = $output;
$return->loadmore = (count($cachedcourses->allmodules) > ($currentloaded + 10));

print json_encode($return);