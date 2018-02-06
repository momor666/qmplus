<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    blocks
 * @subpackage assessment_information
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once 'forms/category_settings_form.php';
require_once($CFG->dirroot.'/lib/coursecatlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir . '/formslib.php');

global $DB, $CFG, $USER, $OUTPUT, $PAGE, $EXTDB;

$categoryid = required_param('id', PARAM_INT);
if (!$course_cat = $DB->get_record('course_categories', array('id' => $categoryid))) {
    print_error("unknowncategory");
}
$category = coursecat::get($categoryid);
if($category->parent){
	print_error('notatoplevelcategory','block_assessment_information');
}
$context = context_coursecat::instance($category->id);

require_login();
require_capability('block/assessment_information:managesettings', $context);
$PAGE->set_context($context);
$PAGE->set_url('/blocks/assessment_information/category_settings.php', array('id' => $categoryid));
$PAGE->set_pagelayout('admin');
//add and list activities
$premixcourseconfig = 'pre_mix_course_'.$category->theme;
$default_course_id = get_config('block_assessment_information', $premixcourseconfig);

$settings_form = new category_settings_form(null, array(
	'id' => $categoryid,
	'default_course_id' => $default_course_id,
	'premixcourseconfig' => $premixcourseconfig,
));
if ($settings_form->is_cancelled()) {
	redirect(new moodle_url('/course/management.php', array('categoryid' => $categoryid)));
} else if($formdata = $settings_form->get_data()) {
	set_config($formdata->premixcourseconfig,$formdata->courseid,'block_assessment_information');
	redirect(new moodle_url('/blocks/assessment_information/category_settings.php', array('id' => $categoryid)));
} else {
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('heading_category_settings','block_assessment_information',$category->theme));
	$settings_form->display();
	$assessmentrenderer = $PAGE->get_renderer('block_assessment_information');
    echo $assessmentrenderer->premix_resources_settings( $default_course_id );
    echo $OUTPUT->footer();
}