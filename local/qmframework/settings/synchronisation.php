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
 * QM Model Framework synchronisation settings
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/local/qmframework/lib.php');

if ($hassiteconfig) {

    // The Synchronisation settings page title.
    $synchronisation = get_string('synchronisation', 'local_qmframework');
    $settings = new admin_settingpage('local_qmframework_synchronisation', $synchronisation);

    // Master switch to enable or disable the plugin.
    $enableplugin = get_string('enableplugin', 'local_qmframework');
    $enablepluginhelp = get_string('enableplugin_help', 'local_qmframework');
    $settings->add(new admin_setting_configcheckbox(
        'local_qmframework/local_qmframework_enabled',
        $enableplugin,
        $enablepluginhelp,
        1
    ));

    // The Mahara MNET Host.
    $hosts = local_qmframework_mahara_mnet_hosts();
    if (!empty($hosts)) {
        $maharas = [];
        foreach ($hosts as $host) {
            $maharas[$host->id] = $host->url;
        }
        $dashboardhost = get_string('dashboardhost', 'local_qmframework');
        $dashboardhosthelp = get_string('dashboardhost_help', 'local_qmframework');
        $settings->add(new admin_setting_configselect(
            'local_qmframework/local_qmframework_host',
            $dashboardhost,
            $dashboardhosthelp,
            null,
            $maharas
        ));
    }

    // The Mahara Institution ID.
    $institutionshortname = get_string('institutionshortname', 'local_qmframework');
    $institutionshortnamehelp = get_string('institutionshortname_help', 'local_qmframework');
    $settings->add(new admin_setting_configtext(
        'local_qmframework/local_qmframework_institution',
        $institutionshortname,
        $institutionshortnamehelp,
        null
    ));

    // The Mahara Auth Institution ID.
    $authinstitutionshortname = get_string('authinstitutionshortname', 'local_qmframework');
    $authinstitutionshortnamehelp = get_string('authinstitutionshortname_help', 'local_qmframework');
    $settings->add(new admin_setting_configtext(
        'local_qmframework/local_qmframework_authinstitution',
        $authinstitutionshortname,
        $authinstitutionshortnamehelp,
        null
    ));

    // The Dashboard Webservice Token.
    $wstoken = get_string('wstoken', 'local_qmframework');
    $settings->add(new admin_setting_configpasswordunmask(
        'local_qmframework/local_qmframework_wstoken',
        $wstoken,
        new lang_string('wstoken_help', 'local_qmframework'),
        null)
    );

    // The Auth Institution Webservice Token.
    $wstoken = get_string('authwstoken', 'local_qmframework');
    $settings->add(new admin_setting_configpasswordunmask(
        'local_qmframework/local_qmframework_authwstoken',
        $wstoken,
        new lang_string('authwstoken_help', 'local_qmframework'),
        null)
    );

    // The course idnumber of the course.
    $courseid = get_string('courseid', 'local_qmframework');
    $courseidhelp = get_string('courseid_help', 'local_qmframework');
    $settings->add(new admin_setting_configtext(
        'local_qmframework/local_qmframework_course',
        $courseid,
        $courseidhelp,
        null
    ));

    // Get list of coures context roles.
    $courseroles = get_roles_for_contextlevels(CONTEXT_COURSE);
    $roleoptions = array_intersect_key(get_all_roles(), array_flip($courseroles));
    $roles = role_fix_names($roleoptions, null, ROLENAME_ORIGINALANDSHORT);
    $userroles = [];
    if (!empty($roles)) {
        foreach ($roles as $role) {
            $rolename = $role->localname;
            $userroles[$role->id] = $rolename;
        }
    }

    if (!empty($userroles)) {

        // Fetching the role defaults.
        $studentdefault = $tutordefault = 1;
        $studentstr = get_string('student', 'local_qmframework');
        $studentmatched = array_filter(
            $userroles,
            function($var) use ($studentstr) {
                return preg_match("/\b$studentstr\b/i", $var);
            }
        );
        if (!empty($studentmatched)) {
            $studentdefault = current(array_flip($studentmatched));
        }
        $teacherstr = get_string('teacher', 'local_qmframework');
        $teachermatched = array_filter(
            $userroles,
            function($var) use ($teacherstr) {
                return preg_match("/\b$teacherstr\b/i", $var);
            }
        );
        if (!empty($teachermatched)) {
            $tutordefault = current(array_flip($teachermatched));
        }

        // Student role used within the course.
        $studentrole = get_string('studentrole', 'local_qmframework');
        $studentrolehelp = get_string('studentrole_help', 'local_qmframework');
        asort($userroles);
        $settings->add(new admin_setting_configselect(
            'local_qmframework/local_qmframework_student',
            $studentrole,
            $studentrolehelp,
            $studentdefault,
            $userroles
        ));

        // Teacher role used within the course.
        $tutorrole = get_string('tutorrole', 'local_qmframework');
        $tutorrolehelp = get_string('tutorrole_help', 'local_qmframework');
        $settings->add(new admin_setting_configselect(
            'local_qmframework/local_qmframework_tutor',
            $tutorrole,
            $tutorrolehelp,
            $tutordefault,
            $userroles
        ));
    }

    // The course grouping id used for the personal tutor groups.
    $groupingid = get_string('groupingid', 'local_qmframework');
    $groupingidhelp = get_string('groupingid_help', 'local_qmframework');
    $settings->add(new admin_setting_configtext(
        'local_qmframework/local_qmframework_grouping',
        $groupingid,
        $groupingidhelp,
        null
    ));

    // The Dashboard Group Admin.
    $dashboardgroupadmin = get_string('dashboardgroupadmin', 'local_qmframework');
    $dashboardgroupadminhelp = get_string('dashboardgroupadmin_help', 'local_qmframework');
    $settings->add(new admin_setting_configtext(
        'local_qmframework/local_qmframework_groupadmin',
        $dashboardgroupadmin,
        $dashboardgroupadminhelp,
        null
    ));


    $ADMIN->add('local_qmframework', $settings);
}
