<?php
define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once($CFG->libdir.'/coursecatlib.php');

$courseid = $_REQUEST['courseid'];
$state = $_REQUEST['state'];
$userid = $_REQUEST['userid'];

$PAGE->set_context(context_system::instance());
$preference = "theme_qmul_pincourse_{$courseid}";

$cache = cache::make('theme_qmul', 'backpackcourses');
$courses = $cache->get($userid);

if ($state) {
	if (isset($courses->pinnedmodules[$courseid])) {
		unset($courses->pinnedmodules[$courseid]);
	}
	unset($courses->allmodules[$courseid]->pinned);
    unset_user_preference($preference, $userid);
} else {
	if (!isset($courses->pinnedmodules[$courseid])) {
		$course = get_course($courseid);
		$category = coursecat::get($course->category,null,true);
		$course->categoryname = $category->name;
		$context = context_course::instance($course->id);
        $hidden = empty($course->visible);
        $editable = has_capability('moodle/course:update', $context, $userid);
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
		$course->pinned = true;
		$course->userid = $USER->id;
		$courseurl = new moodle_url('/course/view.php', array('id'=>$course->id));
		$course->url = $courseurl;
		if (!$course->visible) {
            $course->invisible = true;
        }
		if ($hidden) {
            if ($editable) {
                $course->warning = $OUTPUT->pix_icon('i/warning', '', '', array('class'=>'iconlarge'));;
                $course->warning .= get_string('hidden_course_teacher', 'theme_qmul');
            } else {
                $course->warning = $OUTPUT->pix_icon('i/info', '', '', array('class'=>'iconlarge'));;
                $course->warning .= get_string('hidden_course_student', 'theme_qmul');
            }
        }
		if (isset($courses->overviews[$course->id])) {
		    $course->overview = true;
		    $overview = $courses->overviews[$course->id];
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
		$courses->pinnedmodules[$courseid] = $course;
	}
	$courses->allmodules[$courseid]->pinned = true;
    set_user_preference($preference, 1, $userid);
}

if ($courses === false) {
    $courses = null;
}
$cache->set($userid, $courses);

$data = array('success'=>1, 'newpreference'=>get_user_preferences($preference, 0, $userid));

echo json_encode($data);