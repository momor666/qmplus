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
 * Code for adding a link to category menu
 *

 *
 * @package local
 * @subpackage rollover_plugin
 */


defined('MOODLE_INTERNAL') || die();

/**
 * Hook to insert a link in settings navigation menu block
 *
 * @param settings_navigation $navigation
 * @param course_context      $context
 * @return void
 */
function local_rollover_plugin_extend_settings_navigation(settings_navigation $navigation, $context) {
    global $CFG, $DB;
    // If not in a course category context, then leave
    if ($context == null){
        return;
    }
    if($context->contextlevel != CONTEXT_COURSECAT) {
        return;
    }
    if(!$DB->get_record('block', array('name'=>'course_rollover','visible'=>1))){
        return;
    }
    if(null == ($categorynode = $navigation->get('categorysettings'))){
        return;
    }

    require_once($CFG->dirroot . '/blocks/course_rollover/classlib.php');
    $config = get_config('course_rollover');
    $rangetest = course_rollover::check_in_range($config->schedule_day, $config->cutoff_day, time());
    if(isset($rangetest['error']) && $rangetest['error'] == true){
        return;
    }

    if (has_capability('block/course_rollover:manage', $context)) {
        $url = new moodle_url('/blocks/course_rollover/view_category.php', array('id' => $context->instanceid));
        $categorynode->add('Rollover Courses', $url, navigation_node::TYPE_SETTING, null, 'course_rollover', new pix_icon('i/return', ''));
    }

    if (has_capability('block/course_rollover:viewreports', $context)) {
        $report_url = new moodle_url('/blocks/course_rollover/schedule_report.php', array('show' => '0'));
        $categorynode->add(get_string('course_rollover_report', 'block_course_rollover'), $report_url, navigation_node::TYPE_CUSTOM, null, 'course_rollover', new pix_icon('i/report', ''));
    }
}