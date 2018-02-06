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
 * Settings for Course Template block.
 *
 * @package      blocks
 * @subpackage   course_rollover
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// No direct script access.
defined('MOODLE_INTERNAL') || die();

/*File:       block/course_rollover/settings.php
 
Purpose:    Global configuration page for the block
 
****************************************************************/

// Get all calendar days
$format = get_string('strftimedateshort', 'langconfig');
for ($i = 1; $i <= 12; $i++) {
    for ($j = 1; $j <= date('t', mktime(0, 0, 0, $i, 1, date('Y'))); $j++) { // Use no leap year to calculate days in month to avoid providing 29th february as an option
        // Create an intermediate timestamp with each day-month-combination and format it according to local date format for displaying purpose
        $daystring = userdate(gmmktime(12, 0, 0, $i, $j, date('Y')), $format);

        // Add the day as an option
        $days[mktime(12, 0, 0, $i, $j, date('Y'))] = $daystring;
    }
}

if ($hassiteconfig) { // needs this condition or there is error on login page
    $ADMIN->add('reports', new admin_externalpage('block_course_rollover_report',
                    get_string('course_rollover_report', 'block_course_rollover'),
                    new moodle_url('/blocks/course_rollover/schedule_report.php', array('show' => '0'))));
}

/*
 * General reset
 */
$settings->add(new admin_setting_heading('headergeneral', get_string('header_general', 'block_course_rollover'), get_string('header_general_desc', 'block_course_rollover')));
$settings->add(new admin_setting_configselect('course_rollover/schedule_day', get_string('reset_date', 'block_course_rollover'),
                get_string('reset_date_desc', 'block_course_rollover'), $days[mktime(12, 0, 0, 6, 1, date('Y'))], $days));
$settings->add(new admin_setting_configselect('course_rollover/cutoff_day', get_string('reset_cutoff_day', 'block_course_rollover'),
                get_string('reset_cutoff_day_desc', 'block_course_rollover'), $days[mktime(12, 0, 0, 9, 1, date('Y'))], $days));
/* 
* this are not currently being used by the plugin
*
* $settings->add(new admin_setting_configcheckbox('course_rollover/course_data', get_string('course_data', 'block_course_rollover'),
*                get_string('course_data_desc', 'block_course_rollover'), 0));
* $settings->add(new admin_setting_configcheckbox('course_rollover/allow_backup', get_string('allow_backup', 'block_course_rollover'),
*                get_string('allow_backup_desc', 'block_course_rollover'), 0));
*/
$settings->add(new admin_setting_configmulticheckbox(
                'course_rollover/general_resets',
                'General Reset Items',
                'General Reset Items',
                null,
                array(
                    'reset_events' => get_string('reset_events', 'block_course_rollover'),
                    'reset_logs' => get_string('reset_logs', 'block_course_rollover'),
                    'reset_notes' => get_string('reset_notes', 'block_course_rollover'),
                    'reset_comments' => get_string('reset_comments', 'block_course_rollover'),
                    'reset_course_completion' => get_string('reset_course_completion', 'block_course_rollover'),
                    'delete_blog_associations' => get_string('delete_blog_associations', 'block_course_rollover'),
                    'reset_gradebook_items' => get_string('reset_gradebook_items', 'block_course_rollover'),
                    'reset_gradebook_grades' => get_string('reset_gradebook_grades', 'block_course_rollover'),
                    'reset_activity_grades' => get_string('reset_activity_grades', 'block_course_rollover'),
                    'reset_groupings_remove' => get_string('reset_groupings_remove', 'block_course_rollover'),
                    'reset_groupings_members' => get_string('reset_groupings_members', 'block_course_rollover'),
                    'reset_groups_remove' => get_string('reset_groups_remove', 'block_course_rollover'),
                    'reset_assignments_remove' => get_string('reset_assignments_remove', 'block_course_rollover')
                )
        )
);

/*
 * Roles reset
 */
$settings->add(new admin_setting_heading('headerroles', get_string('header_roles', 'block_course_rollover'), get_string('header_roles_desc', 'block_course_rollover')));

$settings->add(new admin_setting_configcheckbox('course_rollover/reset_roles_local', get_string('reset_roles_local', 'block_course_rollover'),
                get_string('reset_roles_local_desc', 'block_course_rollover'), 1));
$settings->add(new admin_setting_pickroles(
                'course_rollover/unenrol_users',
                get_string('unenrol_users', 'block_course_rollover'),
                get_string('roles_desc', 'block_course_rollover'),
                0
        )
);
$settings->add(new admin_setting_heading('headeractivities_settings', get_string('header_activities_settings', 'block_course_rollover'), get_string('header_activities_settings_desc', 'block_course_rollover')));
if ($allmods = $DB->get_records('modules')) {
    $reset_activities = array();
    foreach ($allmods as $mod) {
        $modname = $mod->name;
        $modfile = $CFG->dirroot . "/mod/$modname/lib.php";
        $mod_reset_course_form_definition = $modname . '_reset_course_form_definition';
        $mod_reset__userdata = $modname . '_reset_userdata';
        if (file_exists($modfile)) {
            include_once($modfile);
            if (function_exists($mod_reset_course_form_definition)) {
                $reset_activities[$modname] = 'reset activity ' . $modname;
            }
        } else {
            debugging('Missing lib.php in ' . $modname . ' module');
        }
    }
// link to a exteral page that allow the Administrator to setup the overriding settings for each modual
    $link = '<a href="' . $CFG->wwwroot . '/blocks/course_rollover/activities_settings.php">' .
            get_string('header_activity', 'block_course_rollover') . '</a>';
    $settings->add(new admin_setting_heading('block_course_rollover_activity_default', '', $link));
    $settings->add(new admin_setting_configmulticheckbox(
                    'course_rollover/activity_resets',
                    'Activity Reset Items',
                    'Activity Reset Items',
                    null,
                    $reset_activities
            )
    );
}

/*
 * mis_connection Allows course_rollover to use a external data source to get the new course data and or validate user selection
 */
$settings->add(new admin_setting_heading('external_database', get_string('header_external_database', 'block_course_rollover'),
                get_string('header_external_database_desc', 'block_course_rollover')));

$options = array(
    false => get_string('noconnection', 'block_course_rollover'),
    'mssql' => 'Mssql',
    'mysql' => 'Mysql',
    'mysqli' => 'Mysqli',
    'odbc' => 'Odbc',
    'oci8' => 'Oracle',
    'postgres' => 'Postgres',
    'sybase' => 'Sybase'
);

$settings->add(new admin_setting_configselect('course_rollover/dbconnectiontype',
                get_string('db_connection', 'block_course_rollover'), '', $CFG->mis_dbtype, $options));

$settings->add(new admin_setting_configtext('course_rollover/db_host', get_string('db_host', 'block_course_rollover'),
                get_string('db_host_desc', 'block_course_rollover'), $CFG->mis_host, PARAM_RAW));

$settings->add(new admin_setting_configtext('course_rollover/db_name', get_string('db_name', 'block_course_rollover'),
                get_string('db_name_desc', 'block_course_rollover'), $CFG->mis_dbase, PARAM_RAW));


$settings->add(new admin_setting_configtext('course_rollover/db_prefix', get_string('db_prefix', 'block_course_rollover'),
                get_string('db_prefix_desc', 'block_course_rollover'), '', PARAM_RAW));


$settings->add(new admin_setting_configtext('course_rollover/db_table', get_string('db_table', 'block_course_rollover'),
                get_string('db_table_desc', 'block_course_rollover'), '', PARAM_RAW));

$settings->add(new admin_setting_configtext('course_rollover/db_user', get_string('db_user', 'block_course_rollover'),
                get_string('db_user_desc', 'block_course_rollover'), $CFG->mis_user, PARAM_RAW));

$settings->add(new admin_setting_configpasswordunmask('course_rollover/db_pass', get_string('db_pass', 'block_course_rollover'),
                get_string('db_pass_desc', 'block_course_rollover'), $CFG->mis_pass, PARAM_RAW));

/*
 * course data mapping
 */
$settings->add(new admin_setting_heading('data_mapping', get_string('data_mapping', 'block_course_rollover'), '** PLease note only Alter these setting if you know what you are doing, changing these to the wrong value can prevent the course rollover brlock from working as explected'));

$settings->add(new admin_setting_configtext('course_rollover/data_mapping_fullname', get_string('data_mapping_fullname', 'block_course_rollover'),
                get_string('data_mapping_fullname_desc', 'block_course_rollover'), '', PARAM_RAW));

$settings->add(new admin_setting_configtext('course_rollover/data_mapping_shortname', get_string('data_mapping_shortname', 'block_course_rollover'),
                get_string('data_mapping_shortname_desc', 'block_course_rollover'), '', PARAM_RAW));
$settings->add(new admin_setting_configtext('course_rollover/data_mapping_course_id', get_string('data_mapping_course_id', 'block_course_rollover'),
                get_string('data_mapping_course_id_desc', 'block_course_rollover'), '', PARAM_RAW));

$settings->add(new admin_setting_configtext('course_rollover/data_mapping_module_code', get_string('data_mapping_module_code', 'block_course_rollover'),
                get_string('data_mapping_module_code_desc', 'block_course_rollover'), '', PARAM_RAW));

$settings->add(new admin_setting_configtext('course_rollover/data_mapping_summary', get_string('data_mapping_summary', 'block_course_rollover'),
                get_string('data_mapping_summary_desc', 'block_course_rollover'), '', PARAM_RAW));

$settings->add(new admin_setting_configtext('course_rollover/data_mapping_category', get_string('data_mapping_category', 'block_course_rollover'),
                get_string('data_mapping_category_desc', 'block_course_rollover'), '', PARAM_RAW));





