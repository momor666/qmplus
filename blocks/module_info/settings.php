<?php
/****************************************************************

File:       block/module_info/settings.php

Purpose:    Global configuration page for the block

****************************************************************/

global $DB, $CFG;

// $settings->add(new admin_setting_heading('block_module_info/mis_connection', get_string('mis_connection', 'block_module_info'), get_string('mis_connection_desc', 'block_module_info')));
/*
$options = array(
    ' '     => get_string('noconnection','block_module_info'),
'mssql' => 'Mssql',
'mysql' => 'Mysql',
'mysqli' => 'Mysqli',
'odbc' => 'Odbc',
'oci8' => 'Oracle',
'postgres' => 'Postgres',
'sybase' => 'Sybase'
);
*/
// $mis_connection	= new admin_setting_configselect('block_module_info/dbconnectiontype',get_string('db_connection','block_module_info'),'', $CFG->mis_dbtype, $options);

// $settings->add( $mis_connection );

//Data Mapping
// $settings->add(new admin_setting_configtext('block_module_info/dbname',get_string( 'db_name', 'block_module_info' ),get_string( 'set_db_name', 'block_module_info' ), $CFG->mis_dbase ,PARAM_RAW));

// $settings->add(new admin_setting_configtext('block_module_info/dbprefix',get_string( 'db_prefix', 'block_module_info' ),get_string( 'prefix_for_tablenames', 'block_module_info' ),'',PARAM_RAW));

// $settings->add(new admin_setting_configtext('block_module_info/dbhost',get_string( 'db_host', 'block_module_info' ), get_string( 'host_name_or_ip', 'block_module_info' ), $CFG->mis_host,PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/dbtable',get_string( 'db_table', 'block_module_info' ), get_string( 'db_table', 'block_module_info' ),'',PARAM_RAW));

// $settings->add(new admin_setting_configtext('block_module_info/dbuser',get_string( 'db_user', 'block_module_info' ), get_string( 'db_user', 'block_module_info' ), $CFG->mis_user,PARAM_RAW));

// $settings->add(new admin_setting_configtext('block_module_info/dbpass',get_string( 'db_pass', 'block_module_info' ), get_string( 'db_pass', 'block_module_info' ), $CFG->mis_pass ,PARAM_RAW));

$settings->add(new admin_setting_heading('block_module_info/data_mapping', get_string('data_mapping', 'block_module_info'), get_string('data_mapping_desc', 'block_module_info')));

$settings->add(new admin_setting_configtext('block_module_info/extcourseid',get_string('extcourseid', 'block_module_info'),get_string('extcourseiddesc', 'block_module_info'),'',PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/module_code',get_string( 'module_code', 'block_module_info' ), get_string( 'module_code', 'block_module_info' ),'',PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/module_level',get_string( 'module_level', 'block_module_info' ), get_string( 'module_level', 'block_module_info' ),'',PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/module_credit',get_string( 'module_credit', 'block_module_info' ), get_string( 'module_credit', 'block_module_info' ),'',PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/module_semester',get_string( 'module_semester', 'block_module_info' ), get_string( 'module_semester', 'block_module_info' ),'',PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/module_convenor',get_string( 'module_convenor', 'block_module_info' ), get_string( 'module_convenor', 'block_module_info' ),'',PARAM_RAW));

//SMART
$settings->add(new admin_setting_heading('block_module_info/smart', get_string('setting_header_smart', 'block_module_info'), get_string('setting_header_smart_desc', 'block_module_info')));

$settings->add(new admin_setting_configtext('block_module_info/baseurl', get_string('setting_baseurl', 'block_module_info'),
    get_string('setting_baseurl_desc', 'block_module_info'), 'http://dev.timetables.qmul.ac.uk/dEVSCI1314SWS/timetable.asp', PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/day', get_string('setting_dayrange', 'block_module_info'),
    get_string('setting_dayrange_desc', 'block_module_info'), '1-5', PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/week', get_string('setting_weekrange', 'block_module_info'),
    get_string('setting_weekrange_desc', 'block_module_info'), '1-52', PARAM_RAW));

$settings->add(new admin_setting_configcheckbox('block_module_info/autoincrementweek', get_string('setting_autoincrementweekrange', 'block_module_info'),
    get_string('setting_autoincrementweekrange_desc', 'block_module_info'), 0));

$settings->add(new admin_setting_configtext('block_module_info/period', get_string('setting_periodrange', 'block_module_info'),
    get_string('setting_periodrange_desc', 'block_module_info'), '1-2', PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/style', get_string('setting_style', 'block_module_info'),
    get_string('setting_style_desc', 'block_module_info'), 'individual', PARAM_RAW));

$settings->add(new admin_setting_configtext('block_module_info/template', get_string('setting_template', 'block_module_info'),
    get_string('setting_template_desc', 'block_module_info'), 'swsnet+object+individual', PARAM_RAW));
