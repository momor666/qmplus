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
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage qmultc
 * @version    See the value of '$plugin->version' in below.
 * @copyright  &copy; 2009-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/format/qmultc/togglelib.php');

// Horrible backwards compatible parameter aliasing....
if ($ctopic = optional_param('ctopics', 0, PARAM_INT)) { // Collapsed Topics old section parameter.
    $url = $PAGE->url;
    $url->param('section', $ctopic);
    debugging('Outdated collapsed topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
if ($topic = optional_param('topic', 0, PARAM_INT)) { // Topics and Grid old section parameter.
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic / grid param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
if ($week = optional_param('week', 0, PARAM_INT)) { // Weeks old section parameter.
    $url = $PAGE->url;
    $url->param('section', $week);
    debugging('Outdated week param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing....

$context = context_course::instance($course->id);

if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    course_set_marker($course->id, $marker);
}

// Make sure all sections are created.
$courseformat = course_get_format($course);
$course = $courseformat->get_course();
course_create_sections_if_missing($course, range(0, $course->numsections));

$renderer = $PAGE->get_renderer('format_qmultc');

$devicetype = core_useragent::get_device_type(); // In /lib/classes/useragent.php.
if ($devicetype == "mobile") {
    $portable = 1;
} else if ($devicetype == "tablet") {
    $portable = 2;
} else {
    $portable = 0;
}
$renderer->set_portable($portable);

if ((!empty($displaysection)) && ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE)) {
    $renderer->print_single_section_page($course, null, null, null, null, $displaysection);
} else {
    $defaulttogglepersistence = clean_param(get_config('format_qmultc', 'defaulttogglepersistence'), PARAM_INT);

    if ($defaulttogglepersistence == 1) {
        user_preference_allow_ajax_update('qmultc_toggle_' . $course->id, PARAM_RAW);
        $userpreference = get_user_preferences('qmultc_toggle_' . $course->id);
    } else {
        $userpreference = null;
    }
    $renderer->set_user_preference($userpreference);

    $defaultuserpreference = clean_param(get_config('format_qmultc', 'defaultuserpreference'), PARAM_INT);
    $renderer->set_default_user_preference($defaultuserpreference);

    $PAGE->requires->js_init_call('M.format_qmultc.init', array(
        $course->id,
        $userpreference,
        $course->numsections,
        $defaulttogglepersistence,
        $defaultuserpreference,
        $PAGE->user_is_editing()));

    $tcsettings = $courseformat->get_settings();
    $renderer->print_multiple_section_page($course, null, null, null, null);
}

// Include course format js module.
$PAGE->requires->js('/course/format/qmultc/format.js');
