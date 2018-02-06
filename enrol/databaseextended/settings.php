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
 * Database enrolment plugin settings and presets.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * @var admin_root $ADMIN
 */
if ($ADMIN->fulltree) {

    //--- Connection settings -----------------------------------------------------------------
	$settings->add(new admin_setting_heading('enrol_databaseextended_settings', '',
        get_string('pluginname_desc', 'enrol_databaseextended')));

    $settings->add(new admin_setting_heading('enrol_databaseextended_relations_link', '',
        get_string('relationspagelink', 'enrol_databaseextended')));

    $settings->add(new admin_setting_heading('enrol_databaseextended_exdbheader',
        get_string('settingsheaderdb', 'enrol_databaseextended'), ''));

    $options = array('',
                     "access",
                     "ado_access",
                     "ado",
                     "ado_mssql",
                     "borland_ibase",
                     "csv",
                     "db2",
                     "fbsql",
                     "firebird",
                     "ibase",
                     "informix72",
                     "informix",
                     "mssql",
                     "mssql_n",
                     "mysql",
                     "mysqli",
                     "mysqlt",
                     "oci805",
                     "oci8",
                     "oci8po",
                     "odbc",
                     "odbc_mssql",
                     "odbc_oracle",
                     "oracle",
                     "postgres64",
                     "postgres7",
                     "postgres",
                     "proxy",
                     "sqlanywhere",
                     "sybase",
                     "vfp");
    
    $options = array_combine($options, $options);
    $setting = new admin_setting_configselect('enrol_databaseextended/dbtype',
        get_string('dbtype',
                   'enrol_databaseextended'), get_string('dbtype_desc',
                                                         'enrol_databaseextended'), '', $options);
    $settings->add($setting);

    $settings->add(new admin_setting_configtext('enrol_databaseextended/dbhost',
        get_string('dbhost',
                   'enrol_databaseextended'), get_string('dbhost_desc',
                                                         'enrol_databaseextended'), 'localhost'));

    $settings->add(new admin_setting_configtext('enrol_databaseextended/dbuser',
        get_string('dbuser',
                   'enrol_databaseextended'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('enrol_databaseextended/dbpass',
        get_string('dbpass',
                   'enrol_databaseextended'), '', ''));

    $settings->add(new admin_setting_configtext('enrol_databaseextended/dbname',
        get_string('dbname',
                   'enrol_databaseextended'), '', ''));

    $settings->add(new admin_setting_configtext('enrol_databaseextended/logfile',
                                                get_string('logfilelocation',
                                                           'enrol_databaseextended'),
                                                get_string('logfilelocation_desc',
                                                           'enrol_databaseextended'),
                                                '/tmp/db_ext_sync.log'));

    $settings->add(new admin_setting_configtext('enrol_databaseextended/syncemail',
                                                get_string('syncemail',
                                                           'enrol_databaseextended'),
                                                get_string('syncemail_desc','enrol_databaseextended'),
                                                ''));


    //--- Connection options ------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_databaseextended_connectionoptions', '',
    		get_string('connectionoptionsheader', 'enrol_databaseextended')));
    
    $settings->add(new admin_setting_configtext('enrol_databaseextended/dbencoding',
        get_string('dbencoding',
                   'enrol_databaseextended'), '', 'utf-8'));

    $settings->add(new admin_setting_configtext('enrol_databaseextended/dbsetupsql',
        get_string('dbsetupsql',
                   'enrol_databaseextended'), get_string('dbsetupsql_desc',
                                                         'enrol_databaseextended'), ''));

    $settings->add(new admin_setting_configcheckbox('enrol_databaseextended/dbsybasequoting',
        get_string('dbsybasequoting',
                   'enrol_databaseextended'), get_string('dbsybasequoting_desc',
                                                         'enrol_databaseextended'), 0));

    $settings->add(new admin_setting_configcheckbox('enrol_databaseextended/debugdb',
        get_string('debugdb',
                   'enrol_databaseextended'), get_string('debugdb_desc',
                                                         'enrol_databaseextended'), 0));
    //--- Global settings ---------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_databaseextended_globalsettings', '',
    		get_string('globalsettingsheader', 'enrol_databaseextended')));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_databaseextended/defaultrole',
            get_string('defaultrole',
                       'enrol_databaseextended'),
            get_string('defaultrole_desc',
                       'enrol_databaseextended'), $student->id, $options));
    }

    // Slightly tricky to implement and we don't need it yet
    //    $settings->add(new admin_setting_configcheckbox('enrol_databaseextended/ignorehiddencourses',
    //        get_string('ignorehiddencourses',
    //                   'enrol_databaseextended'), get_string('ignorehiddencourses_desc',
    //                                                         'enrol_databaseextended'), 0));

    $options = array(ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
                     ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
                     ENROL_EXT_REMOVED_SUSPEND        => get_string('extremovedsuspend', 'enrol'),
                     ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles',
                                                                    'enrol'));
    $settings->add(new admin_setting_configselect('enrol_databaseextended/unenrolaction',
        get_string('extremovedaction',
                   'enrol'), get_string('extremovedaction_help',
                                        'enrol'), ENROL_EXT_REMOVED_UNENROL, $options));





    if (!during_initial_install()) {
        require_once($CFG->dirroot.'/course/lib.php');
        $options    = array();
        $parentlist = array();
        $options = coursecat::make_categories_list();
        $settings->add(new admin_setting_configselect('enrol_databaseextended/defaultcategory',
            get_string('defaultcategory',
                       'enrol_databaseextended'),
            get_string('defaultcategory_desc',
                       'enrol_databaseextended'), 1, $options));
    }

    $settings->add(new admin_setting_configtext('enrol_databaseextended/templatecourse',
        get_string('templatecourse',
                   'enrol_databaseextended'),
        get_string('templatecourse_desc',
                   'enrol_databaseextended'), ''));

    $settings->add(new admin_setting_configtext('enrol_databaseextended/rootcategory',
        get_string('rootcategory',
                   'enrol_databaseextended'),
        get_string('rootcategory_desc',
                   'enrol_databaseextended'), ''));

    $settings->add(new admin_setting_configtext('enrol_databaseextended/memorylimit',
        get_string('memorylimit',
                   'enrol_databaseextended'),
        get_string('memorylimit_desc',
                   'enrol_databaseextended'), ''));
    
    $settings->add(new admin_setting_configcheckbox('enrol_databaseextended/makecoursevisiblewhentouched',
    		get_string('makecoursevisiblewhentouched',
    				'enrol_databaseextended'),
    		get_string('makecoursevisiblewhentouched_desc',
    				'enrol_databaseextended'), ''));
    
    $settings->add(new admin_setting_configcheckbox('enrol_databaseextended/newcourseisvisible',
    		get_string('newcourseisvisible',
    				'enrol_databaseextended'),
    		get_string('newcourseisvisible_desc',
    				'enrol_databaseextended'), '1'));
    
    $settings->add(new admin_setting_configcheckbox('enrol_databaseextended/allowcoursevisibilitymapping',
            get_string('allowcoursevisibilitymapping',
                    'enrol_databaseextended'),
            get_string('allowcoursevisibilitymapping_desc',
                    'enrol_databaseextended'), '0'));

    $settings->add(new admin_setting_configcheckbox('enrol_databaseextended/synconlogin',
                                                    get_string('synconlogin',
                                                               'enrol_databaseextended'),
                                                    get_string('synconlogin_desc',
                                                               'enrol_databaseextended'), ''));

}
