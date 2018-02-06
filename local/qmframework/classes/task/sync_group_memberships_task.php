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
 * A scheduled task for QM Framework sync group memberships.
 *
 * @package   local_qmframework
 * @category  task
 * @copyright 2017 Catalyst IT Limited <https://catalyst-eu.net>
 * @author    Stacey Walker <stacey@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_qmframework\task;

defined('MOODLE_INTERNAL') || die();

class sync_group_memberships_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncgroupmembershiptask', 'local_qmframework');
    }

    /**
     * Run QM Framework cron.
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/qmframework/lib.php');

        // Fetch webservice and ensure connection before continuing.
        $webservice = new \local_qmframework\web_service();
        $webservice->connect();

        self::sync_group_memberships($webservice);
    }

    /**
     * Sync all current group memberships if these haven't already
     * been resolved by the adhoc tasks
     *
     * @param object $webservice the connetion object
     */
    private static function sync_group_memberships($webservice) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/group/lib.php');

        try {
            $webservice->check_group_admin();
        } catch (\Exception $e) {
            mtrace($e->getMessage());
            return;
        }

        // Fetch configuration.
        $config = local_qmframework_course_and_group_settings();
        $roles  = local_qmframework_course_and_roles_settings();

        // Fetch configured groups and sync all memberships
        // in case users have been removed.
        $groupsql = "SELECT g.*
                       FROM {groups} g
                       JOIN {groupings_groups} gr ON gr.groupid = g.id
                      WHERE gr.groupingid = :grouping";
        if ($groups = $DB->get_records_sql($groupsql, array('grouping' => $config['grouping']))) {
            foreach ($groups as $group) {
                $group = local_qmframework_group_details($group->id);
                $maharagroup = $webservice->sync_group($group);
                if ($maharagroup && is_array($maharagroup)) {
                    $maharagroup = current($maharagroup);
                    $students = [];
                    $tutors   = [];
                    if ($users = groups_get_members_by_role($group->id, $config['course'])) {

                        // Ordered so tutors is the 'higher' role
                        // and supercedes a student role if required.
                        foreach ($users as $rolegroup) {
                            if ($rolegroup->id == $roles['student']) {
                                $students = array_merge($students, $rolegroup->users);
                            } else if ($rolegroup->id == $roles['tutor']) {
                                $tutors = array_merge($tutors, $rolegroup->users);
                            }
                        }
                        $members = [];
                        foreach ($students as $student) {
                            $user = new \stdClass;
                            $user->userid = $student->id;
                            $user->username = $student->username;
                            $user->email    = $student->email;
                            if (($maharauser = $webservice->mahara_user_exists($user, true)) === false) {
                                $error = false;
                                try {
                                    $user->firstname = $student->firstname;
                                    $user->lastname  = $student->lastname;
                                    $maharauser = $webservice->sync_user($user, 'member');
                                } catch (\Exception $e) {
                                    if (property_exists($e, 'debuginfo')) {
                                        $error = $e->debuginfo;
                                    } else {
                                        $error = $e->getMessage();
                                    }
                                }
                                if ($error !== false) {
                                    mtrace('Failed to add user account: ' . $error);
                                }
                            }
                            if ($maharauser !== false) {
                                if ($webservice->add_user_to_institution($user)) {
                                    $webservice->sync_user_dashboard($maharauser, $student->id);
                                    $maharauser = current($maharauser);
                                    $members[$student->username]['id']       = $maharauser['id'];
                                    $members[$student->username]['username'] = $user->username;
                                    $members[$student->username]['role']     = 'member';
                                    $members[$student->username]['action']   = 'add';
                                }
                            }
                        }
                        foreach ($tutors as $tutor) {
                            $user = new \stdClass;
                            $user->userid   = $tutor->id;
                            $user->username = $tutor->username;
                            $user->email = $tutor->email;
                            if (($maharauser = $webservice->mahara_user_exists($user, true)) === false) {
                                $error = false;
                                try {
                                    $user->firstname = $tutor->firstname;
                                    $user->lastname  = $tutor->lastname;
                                    $maharauser = $webservice->sync_user($user, 'tutor');
                                } catch (\Exception $e) {
                                    if (property_exists($e, 'debuginfo')) {
                                        $error = $e->debuginfo;
                                    } else {
                                        $error = $e->getMessage();
                                    }
                                }
                                if ($error !== false) {
                                    mtrace('Failed to add user account: ' . $error);
                                }
                            }
                            if ($maharauser !== false) {
                                if ($webservice->add_user_to_institution($user)) {
                                    $maharauser = current($maharauser);
                                    $members[$tutor->username]['id']       = $maharauser['id'];
                                    $members[$tutor->username]['username'] = $user->username;
                                    $members[$tutor->username]['role']     = 'tutor';
                                    $members[$tutor->username]['action']   = 'add';
                                }
                            }
                        }
                    }

                    // Users in the Mahara group to update or add.
                    $updategroup = [];
                    $updategroup['id']          = $maharagroup['id'];
                    $updategroup['name']        = $maharagroup['name'];
                    $updategroup['shortname']   = $maharagroup['shortname'];
                    $updategroup['institution'] = $maharagroup['institution'];
                    if (isset($members) && !empty($members)) {
                        $updategroup['members']     = $members;
                        try {
                            $webservice->client->call('mahara_group_update_group_members', ['groups' => [$updategroup]]);
                        } catch (\Exception $e) {
                            $error = $e->getMessage() . json_encode($maharagroup);
                            mtrace('Failed group member sync: ' . $error);
                        }
                    }

                    // Users in the Mahara group to remove - only students/tutors.
                    // These users should be:
                    // - returned from the Mahara group check
                    // - have one of the roles we care about
                    // - not in the previous update group members list and
                    // - known about in Moodle already.
                    $removegroup = $updategroup;
                    $removegroup['members'] = [];
                    foreach ($maharagroup['members'] as $existing) {
                        if (in_array($existing['role'], array('tutor', 'member'))) {
                            if (!array_key_exists($existing['username'], $updategroup['members'])) {
                                if ($user = $DB->get_record('user', array('username' => $existing['username']))) {
                                    if (is_enrolled(\context_course::instance($config['course']), $user)) {
                                        if ($maharauser = $webservice->mahara_user_exists($user, true)) {
                                            $maharauser = current($maharauser);
                                            $removegroup['members'][$existing['username']]['id']       = $maharauser['id'];
                                            $removegroup['members'][$existing['username']]['username'] = $existing['username'];
                                            $removegroup['members'][$existing['username']]['action']   = 'remove';
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!empty($removegroup['members'])) {
                        try {
                            $webservice->client->call('mahara_group_update_group_members', ['groups' => [$removegroup]]);
                        } catch (\Exception $e) {
                            $error = $e->getMessage() . json_encode($maharagroup);
                            mtrace('Failed group member sync: ' . $error);
                        }
                    }

                    // Remove advisees links for non-tutors.
                    $tutorids = [];
                    foreach ($tutors as $tutor) {
                        $tutorids[] = $tutor->id;
                    }
                    \local_qmframework\qmframework::remove_adviseeslinks($tutorids, $group->id);

                    // Add advisees links for new tutors.
                    if (!empty($tutorids)) {
                        list($sql, $params) = $DB->get_in_or_equal($tutorids, SQL_PARAMS_NAMED, 'u');
                        $params['groupid'] = $group->id;
                        $existing = $DB->get_records_select('local_qmframework_links', "groupid = :groupid AND userid {$sql}",
                            $params, '', 'DISTINCT userid');
                        $settings = local_qmframework_get_connection_settings();
                        $qmhost   = $settings['host'];
                        $host     = local_qmframework_mahara_mnet_host($qmhost);
                        $link     = "{$host->wwwroot}/module/qmframework/advisees.php?id=" . $maharagroup['id'];
                        foreach ($tutorids as $tutorid) {
                            if (!array_key_exists($tutorid, $existing)) {
                                \local_qmframework\qmframework::add_adviseeslink($tutorid, $group->id, $link);
                            }
                        }
                    }
                }
            }
        }
    }
}
