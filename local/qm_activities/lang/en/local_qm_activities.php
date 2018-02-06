<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 26/06/2017
 * Time: 09:31
 * QM+ Activities reporting plugin
 *
 * User: vasileios
 * Date: 28/10/2016
 * Time: 15:03
 *
 * File:     local/qm_activities/lang/en/local_qm_activities.php
 *
 * Purpose:  Define the locale language strings used in the plugin code
 *
 * Input:    N/A
 *
 * Output:   N/A
 *
 * Notes:    Any language message for any type of output should be defined here
 *          So it will be easy to be translated in other languages
 *
 *
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

$string['pluginname'] = 'Activities Calendar';
$string['profilename'] = 'Activities Calendar (New!)';
// $string['glsubs:addinstance'] = 'Add a Activities Calendar link';
//$string['glsubs:myaddinstance'] = 'Add a new Activities Calendar link to the Course';

$string['form_action'] = "./reporter.php";
$string['form_export_calendar'] = "./calendar_export.php";
$string['form_admin_ajax'] = './course_students.php';
$string['reporter'] = './reporter.php';
$string['menu'] = './index.php';
$string['submissions'] = ' ./activity_students.php';

$string['user'] = 'User ';
$string['state'] = 'State ';
$string['request'] = ' requested a calendar for ';
$string['select_school'] = 'Select a School'; 
$string['select_category'] = 'Select a Category'; 
$string['select_course'] = 'Select a Course'; 
$string['select_teacher'] = 'Select a Teacher'; 
$string['select_student'] = 'Select a Student'; 
$string['page_title'] = 'Activities Calendar Reporting Menu'; 
$string['site_administrator'] = 'Site Administrator'; 
$string['school_administrator'] = 'School Administrator'; 
$string['course_administrator'] = 'Course Administrator'; 
$string['course_teacher'] = 'Course Teacher'; 
$string['course_student'] = 'Course Student'; 
$string['date_from'] = 'From '; 
$string['date_to'] = 'To '; 
$string['date_latest'] = 'Latest '; 
$string['no_students_found'] = 'No students were found'; 
$string['please_wait'] = 'Please wait ...'; 
$string['request_not_permitted'] = 'This request is NOT PERMITTED due to current role assignments';
$string['no_activities_found'] = 'No Activities were found<br/>'; 
$string['school'] = 'School '; 
$string['category'] = 'Category '; 
$string['course'] = 'Course '; 
$string['teacher'] = 'Teacher '; 
$string['student'] = 'Student(s) '; 
$string['activity'] = 'Activity '; 
$string['weight'] = 'Weight '; 
$string['site_admin_options'] = 'Please wait for the Site Admin options ... '; 
$string['preparing_menus'] = 'Preparing the menus ...'; 
$string['report_page_title'] = 'Activities Report'; 
$string['report_error'] = 'Wrong mode'; 
$string['back_to_menu'] = 'Back to the Activities Reporting Menu';
$string['pending'] = 'Students who have not submitted their work for this activity yet ';
$string['submitted'] = 'Students who have submitted their work for this activity';
$string['export_calendar'] = 'Export Calendar';

$string['cohort'] = ' Cohort ';
$string['total'] = ' Total Date Range ';
$string['daily_activities'] = ' Daily Activities ';
$string['daily_courses'] = ' Daily Active Courses ';
$string['daily_load'] = ' Expected Submissions ';
$string['max_daily_submissions'] = ' Maximum Daily Submissions:';
$string['max_daily_activities'] = ' Maximum Daily Activities ' ;
$string['rel_daily_submissions'] = ' Relevant Daily Submissions ';
$string['hide_grid'] = '&nbsp; Hide Grid &nbsp;';
$string['show_grid'] = '&nbsp; Show Grid &nbsp;';

$string['label_css'] = 'display: inline-block; margin:5px; text-align: right; width:200px; position:relative; top: -5px;';
$string['label_empty_css'] = 'display: inline-block; margin:5px; text-align: right; width:20px;  position:relative; top: -5px;';
$string['range_label_css'] = 'display: inline-block; margin:5px; text-align: right; width:60px;  position:relative; top: -5px;';
$string['to_List'] = '&nbsp; Full List &nbsp;';
$string['to_Page'] = '&nbsp; Paged List &nbsp;';
$string['hideList'] = '&nbsp; Hide List &nbsp;';
$string['showList'] = '&nbsp; Show List &nbsp;';

$string['work_submitted'] = 'Submits';
$string['enrolled_cohort'] = 'Cohort ';