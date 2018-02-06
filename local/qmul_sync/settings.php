<?php
/**
 * qmul_sync plugin settings and presets.
 *
 * @package    local_qmul_sync
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_qmul_sync', get_string('pluginname', 'local_qmul_sync'));

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('local_qmul_sync_heading', '', get_string('pluginname_desc', 'local_qmul_sync')));

    $settings->add(new admin_setting_configselect('local_qmul_sync/mode', get_string('sync_mode', 'local_qmul_sync'), get_string('sync_mode_desc', 'local_qmul_sync'), 'proxy', array(
//        'file' => get_string('sync_mode_file', 'local_qmul_sync'),
        'proxy' => get_string('sync_mode_proxy', 'local_qmul_sync'),
    )));

    $settings->add(new admin_setting_configselect('local_qmul_sync/remote_type', get_string('remote_type', 'local_qmul_sync'), '', null, array(
        'mysqli' => get_string('nativemysqli', 'install'),
        'mariadb' => get_string('nativemariadb', 'install'),
        'pgsql' => get_string('nativepgsql', 'install'),
        'oci' => get_string('nativeoci', 'install'),
        'sqlsrv' => get_string('nativesqlsrv', 'install'),
        'mssql' => get_string('nativemssql', 'install'),
    )));
    $settings->add(new admin_setting_configtext('local_qmul_sync/remote_hostname', get_string('dbhost', 'install'), '', $CFG->mis_host));
    $settings->add(new admin_setting_configtext('local_qmul_sync/remote_database', get_string('database', 'install'), '', $CFG->mis_dbase));
    $settings->add(new admin_setting_configtext('local_qmul_sync/remote_username', get_string('username'), '', $CFG->mis_user));
    $settings->add(new admin_setting_configpasswordunmask('local_qmul_sync/remote_password', get_string('password'), '', $CFG->mis_pass));

    /* These settings are currently unused
    $settings->add(new admin_setting_configtext('local_qmul_sync/user_source',
        get_string('user_source', 'local_qmul_sync'), '', ''));

    $settings->add(new admin_setting_configtext('local_qmul_sync/enrolment_source',
        get_string('enrolment_source', 'local_qmul_sync'), '', ''));

    $settings->add(new admin_setting_configtext('local_qmul_sync/course_source',
        get_string('course_source', 'local_qmul_sync'), '', ''));

    $settings->add(new admin_setting_configtext('local_qmul_sync/mapping_source',
        get_string('mapping_source', 'local_qmul_sync'), '', ''));

    $settings->add(new admin_setting_configtext('local_qmul_sync/course_map_source',
        get_string('course_map_source', 'local_qmul_sync'), '', ''));

     */
    $ADMIN->add('localplugins', $settings);
}
