<?php
/****************************************************************

File:       block/module_info/lang/en/block_module_info.php

Purpose:    Language file

 ****************************************************************/

$string['pluginname'] = 'Module Info';
$string['module_info'] = 'Module Info';
$string['module_info:addinstance'] = 'Add a new Module Info block';

//Config Form DB Connection Section
$string['noconnection'] = 'No Connection';
$string['mis_connection'] = 'MIS Connection';
$string['mis_connection_desc'] = 'Specify connection to the database containing course module data.';

$string['db_connection'] = 'DB Connection';
$string['db_name'] = 'DB Name';
$string['set_db_name'] = 'set the name of the DB';
$string['db_prefix'] = 'DB Prefix';
$string['prefix_for_tablenames'] = 'Prefix for tablenames (if any)';
$string['db_host'] = 'DB host';
$string['host_name_or_ip'] = 'DB Hostname or IP';
$string['db_table'] = 'DB table';
$string['db_pass'] = 'DB Password';
$string['db_user'] = 'DB Username';

//Config Data Mapping Fields
$string['data_mapping'] = 'Data Mapping';
$string['data_mapping_desc'] = 'The name of the field in the remote table that maps onto the given variable.';
$string['extcourseid']  = 'Course ID';
$string['extcourseiddesc'] = 'This field should map to course->idnumber in Moodle';
$string['module_code'] = 'Code';
$string['module_level'] = 'Level';
$string['module_credit'] = 'Credit Value';
$string['module_semester'] = 'Semester';
$string['module_convenor'] = 'Module Convenor';

//Block title
$string['block_title'] = 'Block title';
$string['block_title_help'] = 'This is the title that will appear at the top of the block replacing Module Info(default).';
$string['block_title_error'] = 'Block title too long (cannot exceed 30 characters).';

//section globals
$string['hide_section_block'] = 'Hide Section from Block';

// Core info
$string['coreinfo_header'] = 'Core info';
$string['coreinfo_desc'] = '<p>The details displayed below are being pulled from the student information system (SITS) and is based on the course ID number entered in the settings for this module. Updates made in SITS will be reflected here within 24 hours. You can override the fields below, and disconnect from SITS, by ticking the checkbox and typing custom information in the appropriate fields. </p>';
$string['config_coreinfo_title_default']  = 'Core Information';
$string['config_coreinfo_title_help'] = 'This is the title that will appear at the top of this section in the block replacing Core Information(default).';
$string['coreinfo_data_mismatch'] = "<div class='courseinfo-warning'><span><a title='The information in this field does not match the information stored in SITS.'></a></span></div>";
$string['config_module_code'] = 'Custom Module Code';
$string['config_module_code_override'] = 'Custom Code';
$string['module_code_help'] = 'Enable this setting to override the module code. The code is taken
    automatically from information stored in SITS. However, this can be replaced by text entered
    into the text box on the right.';
$string['config_module_level'] = 'Custom Module Level';
$string['config_module_level_override'] = 'Override Level';
$string['module_level_help'] = 'Enable this setting to override the module level. The level is taken
    automatically from information stored in SITS. However, this can be replaced by text entered
    into the text box on the right.';
$string['config_module_credit'] = 'Custom Module Credits';
$string['config_module_credit_override'] = 'Override Credit Value';
$string['module_credit_help'] = 'Enable this setting to override the module credits. The credits are
    taken automatically from information stored in SITS. However, this can be replaced by text
    entered into the text box on the right.';
$string['config_module_semester'] = 'Custom Module Semester';
$string['config_module_semester_override'] = 'Override Semester';
$string['module_semester_help'] = 'Enable this setting to override the semester. The semester is either taken
automatically from information stored in SITS or extracted from the course id number. However, this can be replaced by text entered into
the text box on the right.';
$string['missing_module'] = 'Cannot find module details. Please ensure \'Course ID\' is set correctly for this page to autopopulate from SITS
or edit this block to manually override core info.';

//Teaching
$string['teaching_header'] = 'Teaching';
$string['config_teaching_title_default']  = 'Teaching';
$string['teaching_desc'] = '<p>The information below comes from the student information system (SITS) and is based on the course ID number entered in the settings for this module. You can amend the Module Convenor by entering a QMUL email address in the Module Convenor field. The location and office hours information comes from the QMplus profile. </p>';

$string['config_module_convenor_heading'] = 'Module Convenor heading';
$string['config_module_convenor_heading_help'] = 'Schools and faculties have their own name to describe the module convenor. Select from the predefined options or select \'Custom Name\' to enter your own.';
$string['config_custom_module_convenor_heading'] = 'Custom Heading name';
$string['config_custom_module_convenor_heading_help'] = "Enter custom module convenor name.";

$string['module_organizer'] ='Module Organizer';
$string['module_owner'] = 'Module Owner';
$string['convenor'] = 'Convenor';
$string['custom_name'] = 'Custom Name';

$string['config_module_convenor'] = 'Custom Module Convenor (email)';
$string['config_module_convenor_override'] = 'Override Primary Module Convenor';
$string['module_convenor_help'] = 'Enable this setting to override the primary module convenor. The convenor is taken
automatically from information stored in SITS. However, this can be replaced by text entered into
the text box on the right.';
$string['module_convenor_mismatch'] = "<div class='courseinfo-warning'><span><a title='The email does not match information held in SITS'></a></span></div>";
$string['module_convenor_mismatch_and_missing'] =  "<div class='courseinfo-warning'><span><a title='The primary module convenor is not available in QMplus and also does not exist in SITS'></a></span></div>";
$string['module_convenor_missing'] = "<div class='courseinfo-warning'><span><a title='The primary convenor is not available in QMplus database'></a></span></div>";

$string['module_convenor_display'] = 'Module Convenor Display Fields';
$string['module_convenor_display_help'] = 'Please check the fields that you would like to display for this additional
user. The fields can be updated in the user profile.';

$string['location'] = 'Location';
$string['officehours'] = 'Office Hours';
$string['largeimage'] = 'Large User Picture(100x100)';

$string['config_additional_user'] = 'Additional User';
$string['config_additional_user_heading'] = 'Heading';
$string['config_additional_user_heading_help'] = 'Enter your own name to describe this additional user.';
$string['config_additional_user_staffs'] = 'User';
$string['config_additional_user_staffs_help'] = 'Select the user from the list of enrolled users who are NOT students to display them in this additional user section';
$string['config_additional_user_add_string'] = 'Add 1 Additional User';
$string['additional_user_display'] = 'Display Fields';
$string['additional_user_display_help'] = 'Please check the fields that you would like to display for this additional
user. The fields can be updated in the user profile.';

//Schedule
$string['schedule_header'] = 'Schedule';
$string['schedule_desc'] = '<p>Choose to display links to the Scientia timetabling system, an external
timetable (via a URL) or add teaching sessions for your module manually.</p>';
$string['config_schedule_title_default']  = 'Schedule';
$string['config_schedule_title_help'] = 'This is the title that will appear at the top of this section in the block replacing Schedule(default).';

$string['config_enable_personal_timetable_link'] = 'Display personal timetable link (SMART)';
$string['config_enable_module_timetable_link'] = 'Display module timetable link (SMART)';
$string['login_to_view_timetable'] = 'Log in to view your personal timetable.';

$string['config_custom_timetable_url'] = 'Custom timetable URL';
$string['config_custom_timetable_url_help'] = 'If you know the URL of an alternate timetable for your module then enter it
here. Otherwise you can leave this setting empty. The link you specify here will always open in a new window.';
$string['config_custom_timetable_text'] = 'Custom timetable link text';
$string['config_custom_timetable_text_default'] = 'Custom timetable';
$string['config_custom_timetable_text_help'] = 'This is the text users will click on to access the \'Custom timetable URL\'';

$string['config_additional_session'] = 'Additional teaching session';
$string['config_additional_session_subheading'] = 'Teaching subheading';
$string['config_additional_session_subheading_help'] = 'Teaching subheading';
$string['config_additional_session_day'] = 'Day';
$string['config_additional_session_day_help'] = 'Day';
$string['config_additional_session_time'] = 'Time';
$string['config_additional_session_time_help'] = 'Please enter the time that your teaching session takes place.';
$string['config_additional_session_location'] = 'Location';
$string['config_additional_session_location_help'] = 'Please enter the room number where your teaching session takes place.';
$string['config_additional_session_add_string'] = 'Add 1 Additional Teaching Session';

$string['session_details'] = '{$a->day} {$a->time} {$a->location}';
$string['nosessionsavailable'] = 'No extra sessions configured';

//Documents
$string['documents_header'] = 'Module documents';
$string['documents_desc'] = '<p>Add files that you would like to display in the block here.</p>';
$string['config_documents_title_default']  = 'Module documents';
$string['config_documents_title_help'] = 'This is the title that will appear at the top of this section in the block replacing Module documents(default).';
$string['files_filemanager'] = 'Files';
$string['files_filemanager_help'] = 'Upload module documents here.';

//Other Info
$string['otherinfo_header'] = 'Additional Content';
$string['otherinfo_desc'] = '<p>Any additional content you would like to add to the page e.g. links can be added here</p>';
$string['config_otherinfo_title_default']  = 'Additional Content';
$string['config_otherinfo_title_help'] = 'This is the title that will appear at the top of this section in the block replacing Additional Content(default).';
$string['config_otherinfo_title_error'] = 'Section title too long (cannot exceed 50 characters).';
$string['config_htmlcontent'] = 'Content';
$string['config_htmlcontent_default'] = 'You can add essential links and/or text here, if you do not wish to use please HIDE in the settings.';

// SMART Timetabling
$string['setting_header_smart'] = "SMART Timtable Settings";
$string['setting_header_smart_desc'] = "QMplus settings for timetabling service Scientia Enterprise also know as SMART (Space Management and Room Timetabling). ";

$string['setting_baseurl'] = 'Base Url';
$string['setting_baseurl_desc'] = 'the base Url for the service';

$string['setting_title'] = 'Block Title';
$string['setting_title_desc'] = 'the block title';

$string['setting_weekrange'] = 'Week Range';
$string['setting_weekrange_desc'] = 'The weeks to generate for the timetable. Can be a a single week (e.g. 1), a consecutive week range (e.g. 1-10) or set of non-consecutive weeks (e.g. 2;4;6). If omitted then the current week is displayed.';

$string['setting_dayrange'] = 'Day Range';
$string['setting_dayrange_desc'] = 'The days to include in the timetable where 1 = Monday through to 7 = Sunday. Can be a single day (e.g. 1), consecutive day range (e.g. 1-5) or set of non-consecutive days (e.g. 1,5). If omitted then all days are displayed.';

$string['setting_periodrange'] = 'Period Range';
$string['setting_periodrange_desc'] = 'A consecutive period range. You cannot have a single period or a non-consecutive period range. QMUL periods run from 08:00 – 23:00, so 09:00 would be period 3 and 18:00 would be period 20. If omitted then all periods are displayed.';

$string['setting_template'] = 'Timetabling template';
$string['setting_template_desc'] = 'Timetabling template';

$string['setting_style'] = 'Timetabling Style';
$string['setting_style_desc'] = 'Timetabling Style';

$string['default_personal_smart_link'] = 'Personal timetable';
$string['student_personal_smart_link'] = 'Personal timetable';
$string['staff_personal_smart_link'] = 'Personal timetable';

$string['default_module_smart_link'] = 'Module timetable';
$string['student_module_smart_link'] = 'Module timetable';
$string['staff_module_smart_link'] = 'Module timetable';

//Config Defaults
$string['default'] = 'Defaults';
$string['default_desc'] = 'Block default configuration settings can be specified here.';

$string['convenor_role_name_options'] = 'Module owner names';
$string['convenor_role_name_options_desc'] = 'Possible valid alternative names for the module owner are \'Convenor\' or \'Module Organiser\', for instance. Write each option on a new line.';
$string['convenor_role_name_default'] = 'Module Owner';
$string['additional_teacher_role_name_options'] = 'Additional teacher names';
$string['additional_teacher_role_name_options_desc'] = 'Possible valid alternative names for additional teachers are \'Additional teachers\' or \'Teaching assistants\', for example. Write each option on a new line.';
$string['additional_teacher_role_name_default'] = 'Additional Teachers';

$string['defaulthtml'] = 'Default HTML';

//staff search
$string['staffsearch_header'] = 'Staff Directory';
$string['staffsearch_desc'] = '<p>Add a staff search form to the block here.</p>';
$string['terms'] = 'Search';
$string['terms_help'] = 'Browse the staff directory by entering a staff name, role, extension number or email address';

//increment weekrange
$string['increment_weekrange'] = 'Auto Increment Week Range';
$string['setting_autoincrementweekrange'] = 'Auto Increment Week Range';
$string['setting_autoincrementweekrange_desc'] = 'Auto increment option for week range field, the number is incremented every Monday at 00:00.';