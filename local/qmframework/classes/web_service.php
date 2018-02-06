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
 * Local qmframework web service class
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */

namespace local_qmframework;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/webservice/xmlrpc/lib.php');
require_once($CFG->dirroot . '/lib/setuplib.php');

class web_service {

    public $client; /* Dashboard XML-RPC client */

    private $institution; /* Dashboard institution */

    private $authclient; /* Authentication XML-RPC client */

    private $authinstitution; /* Authentication institution */

    private $admin; /* Mahara group admin */

    /**
     * Create the xmlrpc client to connect to Mahara.
     */
    public function connect() {
        if (!$this->client) {
            $settings = local_qmframework_get_connection_settings();
            $qmhost = $settings['host'];
            $qmtoken = $settings['token'];
            $authtoken = $settings['authtoken'];
            $this->institution = $settings['institution'];
            $this->authinstitution = $settings['authinstitution'];
            $host = local_qmframework_mahara_mnet_host($qmhost);
            $serverurl = $host->wwwroot.'/webservice/xmlrpc/server.php';
            $this->client = new \webservice_xmlrpc_client($serverurl, $qmtoken);
            $this->authclient = new \webservice_xmlrpc_client($serverurl, $authtoken);
        }
        $this->check_institution();
        $this->check_auth_institution();
    }

    /**
     * Add Mahara user to QM institution.
     * @param \stdClass $user user object to check
     * @param boolean $return | default false
     */
    public function add_user_to_institution($user) {
        $done = false;
        try {
            $userdata = [];
            $userdata['username'] = $user->username;
            $this->client->call(
                'mahara_institution_add_members',
                ['institution' => $this->institution, 'users' => [$userdata]]
            );
            $done = true;
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, 'Duplicate entry') !== false) {
                $done = 'already in institution';
            } else {
                throw new \invalid_parameter_exception('Add user to Mahara institution error: '.$e->getMessage());
            }
        }
        return $done;
    }

    /**
     * Check if Mahara auth institution exists.
     */
    public function check_auth_institution() {
        $error = null;
        // Check token institution.
        try {
            $context = $this->authclient->call('mahara_user_get_context');
            if ($context === null) {
                $error = 'Connection failed or token expired.';
            } else {
                if ($context !== $this->authinstitution) {
                    $error = 'Configuration issue - Provided Authentication token is not for the Authentication institution';
                    $error .= ' "'.$this->authinstitution.'", it belongs to "'.$context.'" institution.';
                } else {
                    // Check institution access.
                    try {
                        $this->authclient->call('mahara_institution_get_requests', [$this->authinstitution]);
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        if ($error !== null) {
            throw new \invalid_parameter_exception('Mahara Authentication institution error: '.$error);
        }
    }

    /**
     * Check if Mahara institution exists.
     */
    public function check_institution() {

        $error = null;
        // Check token institution.
        try {
            $context = $this->client->call('mahara_user_get_context');
            if ($context === null) {
                $error = 'Connection failed or token expired.';
            } else {
                if ($context !== $this->institution) {
                    $error = 'Configuration issue - Provided Dashboard token is not for the Dashboard institution';
                    $error .= ' "'.$this->institution.'", it belongs to "'.$context.'" institution.';
                } else {
                    // Check institution access.
                    try {
                        $this->client->call('mahara_institution_get_requests', [$this->institution]);
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        if ($error !== null) {
            throw new \invalid_parameter_exception('Mahara Dashboard institution error: '.$error);
        }
    }

    /**
     * Check if the Mahara group admin user exists remotely.
     */
    public function check_group_admin() {
        // Get group admin id from settings.
        if (!$this->admin) {
            $settings = local_qmframework_course_and_group_settings();
            $this->admin = $settings['groupadmin'];
        }
        // Check group admin user exists.
        try {
            $user  = ['id' => $this->admin];
            $this->authclient->call('mahara_user_get_users_by_id', ['users' => [$user]]);
        } catch (\Exception $e) {
            throw new \invalid_parameter_exception('Mahara group admin error: '.$e->getMessage());
        }
    }

    /**
     * Check if corresponding Mahara user exists.
     * @param \stdClass $user user object to check
     * @param boolean $return | default false
     */
    public function mahara_user_exists($user, $return = false) {
        $exists = false;
        try {
            $userdata = [];
            $userdata['username'] = $user->username;
            $userdata['email'] = $user->email;
            $maharauser = (array) $this->authclient->call('mahara_user_get_users_by_id', ['users' => [$userdata]]);
            if ($return && !empty($maharauser)) {
                return $maharauser;
            }
            if (!empty($maharauser)) {
                $exists = true;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $matches = [];
            $notauthorised = preg_match(
                '/.*Not authorised for access to user id .* for institution "'.$this->institution.'"/',
                $message,
                $matches
            );
            if (strpos($message, 'Invalid username "'.$userdata['username'].'"') !== false) {
                $exists = false;
            } else if ($notauthorised !== false) {
                $exists = 'not in institution';
            } else {
                throw new \invalid_parameter_exception('Get Mahara users error: '.$e->getMessage());
            }
        }
        return $exists;
    }

    /**
     * Check if corresponding Mahara group exists.
     * @param \stdClass $group group object to check
     * @param boolean $return | default false
     */
    public function mahara_group_exists($group, $return = false) {
        $exists = false;
        $groupdata = [];
        $groupdata['institution'] = $this->institution;
        $groupdata['shortname'] = $group->idnumber;
        try {
            $maharagroup = (array) $this->client->call('mahara_group_get_groups_by_id', ['groups' => [$groupdata]]);
            if ($return && !empty($maharagroup)) {
                return $maharagroup;
            }
            if (!empty($maharagroup)) {
                $exists = true;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, 'Group "'.$groupdata['shortname'].'" does not exist') !== false) {
                $exists = false;
            } else {
                throw new \invalid_parameter_exception('Get Mahara groups error: '.$message);
            }
        }
        return $exists;
    }

    /**
     * Check if group already has this member.
     *
     * @param  array $group Mahara group array
     * @param  array $user user object to check
     * @return boolean
     */
    public function mahara_is_member($group, $user) {
        $ismember = false;
        $members = $group['members'];
        if (!empty($members) && count($members) > 1) {
            foreach ($members as $member) {
                if ($member['id'] === $user['id'] && $member['username'] === $user['username']) {
                    $ismember = true;
                }
            }
        }
        return $ismember;
    }

    /**
     * Sync user's Mahara dashboard.
     *
     * The user must have the student role within the configured QM Framework
     * course for a dashboard to be synced.
     *
     * @param \stdClass $maharauser Mahara user object to create a qmdashboard for.
     * @param \stdClass $userid user id of the Moodle user.
     */
    public function sync_user_dashboard($maharauser, $userid) {
        $error = false;
        try {
            $maharauser = (array) current($maharauser);
            $data = ['institution' => $this->institution, 'users' => [$maharauser]];
            $maharauserdashboard = $this->client->call('module_qmframework_create_qmdashboard_template', $data);
            if (!empty($maharauserdashboard)) {
                $data = current($maharauserdashboard);
                if (is_array($data) && isset($data['link'])) {
                    \local_qmframework\qmframework::add_dashboardlink($userid, $data['link']);
                }
                $event = \local_qmframework\event\user_dashboard_sync_succeeded::success_sync($userid, $maharauserdashboard);
            } else {
                $event = \local_qmframework\event\user_dashboard_sync_abandoned::abandon_sync($userid, $maharauserdashboard);
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $event = \local_qmframework\event\user_dashboard_sync_failed::fail_sync($userid, $error);
        }
        $event->trigger();
        if ($error !== false) {
            throw new \invalid_parameter_exception('Sync User Dashboard failed: '.$error);
        }
    }

    /**
     * Sync user to Mahara.
     *
     * @param stdClass $user user object to sync
     * @param string   $role the role string for syncing
     */
    public function sync_user($user, $role) {
        $error = false;
        $clone  = clone($user);
        $userid = $clone->userid;
        unset($clone->userid);
        $maharauser = $this->mahara_user_exists($clone, true);
        if ($maharauser === false) {
            $clone->institution = $this->authinstitution;
            $clone->auth = 'xmlrpc';
            $clone->password = local_qmframework_random_password();
            try {
                $maharauser = $this->authclient->call('mahara_user_create_users', ['users' => [$clone]]);
                $this->add_user_to_institution((object) current($maharauser));
                $event = \local_qmframework\event\user_sync_succeeded::success_sync($userid, $maharauser);
            } catch (\Exception $e) {
                $error = $e->getMessage().json_encode($clone);;
                $event = \local_qmframework\event\user_sync_failed::fail_sync($userid, $error);
            }
        } else {
            $event = \local_qmframework\event\user_sync_abandoned::abandon_sync($userid, $maharauser);
            $ok = false;
            if (is_array($maharauser)) {
                $maharauser = current($maharauser);
                if (!empty($maharauser) && $maharauser['institution'] == $this->authinstitution) {
                    if ($maharauser['auth'] == 'xmlrpc') {
                        $ok = true;
                    } else {
                        $auths = $maharauser['auths'];
                        foreach ($auths as $auth) {
                            if ($auth['auth'] == 'xmlrpc') {
                                $ok = true;
                            }
                        }
                    }
                }
            }
            if ($ok) {
                $this->add_user_to_institution($clone);
            }
        }
        $event->trigger();
        if ($error !== false) {
            throw new \invalid_parameter_exception('Sync User failed: '.$error);
        }

        // Attempt to create the user's Mahara qmdashboard if they
        // are a member.
        if ($maharauser !== false && $role === 'member') {
            $this->sync_user_dashboard($maharauser, $userid);
        }
        return $maharauser;
    }

    /**
     * Sync personal tutor group to Mahara.
     *
     * @param \stdClass $group group object to sync
     * @return object
     */
    public function sync_group($group) {
        $error = false;
        $maharagroup = $this->mahara_group_exists($group, true);
        if ($maharagroup === false) {

            // New group record.
            $newgroup = new \stdClass;
            $newgroup->institution    = $this->institution;
            $newgroup->grouptype      = 'course';
            $newgroup->name           = $group->name;
            $newgroup->shortname      = $group->idnumber;
            $newgroup->description    = strip_tags($group->description);
            $newgroup->open           = false;
            $newgroup->controlled     = true;
            $newgroup->request        = false;
            $newgroup->editroles      = 'admin';
            $newgroup->submitpages    = false;

            // Default group member.
            $admin = [];
            $admin['id'] = $this->admin;
            $admin['role'] = 'admin';
            $newgroup->members = [$admin];
            try {
                $maharagroup = $this->client->call('mahara_group_create_groups', ['groups' => [$newgroup]]);
                $event = \local_qmframework\event\group_sync_succeeded::success_sync($group->id, $maharagroup);
            } catch (\Exception $e) {
                $error = $e->getMessage() . json_encode($newgroup);
                $event = \local_qmframework\event\group_sync_failed::fail_sync($group->id, $error);
            }
        } else {
            $event = \local_qmframework\event\group_sync_abandoned::abandon_sync($group->id, $maharagroup);
        }
        $event->trigger();
        if ($error !== false) {
            throw new \invalid_parameter_exception('Sync Group failed: '.$error);
        }
        return $maharagroup;
    }

    /**
     * Sync group membership to Mahara.
     *
     * The group instance must exist on the remote Mahara instance and belong to
     * the QM Framework institution. If this does not exist at the time
     * of the membership sync the webservice will attempt to create it.
     *
     * The user account must exist on the remote Mahara instance and be a member
     * of the QM Framework institution. If this does not exist at the time
     * of the membership sync the webservice will attempt to create it.
     *
     * The membership role in the group will be determined by the users role
     * within the QM Framework course.
     *
     * @param \stdClass $user user object to sync
     * @param \stdClass $group group object to sync to
     * @param string $role what role should the member have in Mahara
     * @param integer $memberid groups_members ID
     */
    public function sync_member($user, $group, $role, $memberid) {
        $error = false;
        $maharagroup = $this->mahara_group_exists($group, true);
        if ($maharagroup === false) {
            try {
                $this->sync_group($group);
                $maharagroup = $this->mahara_group_exists($group, true);
            } catch (\Exception $e) {
                if (property_exists($e, 'debuginfo')) {
                    $error = $e->debuginfo;
                } else {
                    $error = $e->getMessage();
                }
            }
        }
        $maharauser = $this->mahara_user_exists($user, true);
        if ($maharauser === false) {
            try {
                $maharauser = $this->sync_user($user, $role);
            } catch (\Exception $e) {
                if (property_exists($e, 'debuginfo')) {
                    $error = $e->debuginfo;
                } else {
                    $error = $e->getMessage();
                }
            }
        }
        if ($maharagroup !== false && $maharauser !== false) {
            $maharagroup = (array) current($maharagroup);
            $maharauser = (array) current($maharauser);
            $ismember = $this->mahara_is_member($maharagroup, $maharauser);
            if (!$ismember) {
                // Prepare group data.
                $updategroup = [];
                $updategroup['id'] = $maharagroup['id'];
                $updategroup['name'] = $maharagroup['name'];
                $updategroup['shortname'] = $maharagroup['shortname'];
                $updategroup['institution'] = $maharagroup['institution'];
                // Prepare user data.
                $member = [];
                $member['id'] = $maharauser['id'];
                $member['username'] = $maharauser['username'];
                $member['role'] = $role;
                $member['action'] = 'add';
                $updategroup['members'] = [$member];
                try {
                    $this->client->call('mahara_group_update_group_members', ['groups' => [$updategroup]]);
                    $event = \local_qmframework\event\member_sync_succeeded::success_sync($memberid);

                    // Save the tutor's advisees page link in the mdl_local_qmframework_links.
                    if ($role == 'tutor') {
                        $settings = local_qmframework_get_connection_settings();
                        $qmhost   = $settings['host'];
                        $host     = local_qmframework_mahara_mnet_host($qmhost);
                        $link     = "{$host->wwwroot}/module/qmframework/advisees.php?id=" . $maharagroup['id'];
                        \local_qmframework\qmframework::add_adviseeslink($user->userid, $group->id, $link);
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage().json_encode($updategroup);
                    $event = \local_qmframework\event\member_sync_failed::fail_sync($memberid, $error);
                }
            } else {
                $event = \local_qmframework\event\member_sync_abandoned::abandon_sync($memberid, $maharagroup);
            }
            $event->trigger();
        }
        if ($error !== false) {
            throw new \invalid_parameter_exception('Sync Member failed: '.$error);
        }
    }

    /**
     * Check if current quiz attempt of Mahara user was previously exported.
     * @param string $user mahara user ID
     * @param string $attemptid current quiz attempt ID
     */
    public function mahara_user_has_attempt($userid, $attemptid) {
        $exists = false;
        try {
            $userdata = [];
            $userdata['id'] = $userid;
            $userdata['attemptid'] = $attemptid;
            $data = ['institution' => $this->institution, 'users' => [$userdata]];
            $maharaattempt = $this->client->call('blocktype_stagevisualisation_get_quiz_attempt_data', $data);
            $maharaattempt = current($maharaattempt);
            if (!empty($maharaattempt)) {
                $exists = $maharaattempt['found'];
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \invalid_parameter_exception('Get Mahara users quiz attempt data error: '.$message);
        }
        return $exists;
    }

    /**
     * Export quiz attempt to Mahara.
     *
     * The user account must exist on the remote Mahara instance and be a member
     * of the QM Framework institution. If this does not exist at the time
     * of the quiz attempt export the webservice will attempt to create it.
     *
     * @param stdClass $user user object to sync
     * @param string   $attemptid the ID of the quiz attempt to export
     * @param string   $role the role string for syncing
     */
    public function export_attempt($user, $attemptid, $role) {
        $error = false;
        $maharauser = $this->mahara_user_exists($user, true);
        if ($maharauser === false) {
            try {
                $maharauser = $this->sync_user($user, $role);
            } catch (\Exception $e) {
                if (property_exists($e, 'debuginfo')) {
                    $error = $e->debuginfo;
                } else {
                    $error = $e->getMessage();
                }
            }
        }
        if ($maharauser !== false) {
            $maharauser = (array) current($maharauser);
            $maharaattempt = $this->mahara_user_has_attempt($maharauser['id'], $attemptid);
            if ($maharaattempt === false) {
                // Prepare data.
                $data = $userdata = [];
                $data['institution'] = $this->institution;
                // Get the current attempt score and details.
                $attemptdata = local_qmframework_attempt_data($user->userid, $attemptid);
                $userdata['id'] = $maharauser['id'];
                $userdata['attemptid'] = $attemptid;
                $userdata['attempt'] = json_encode($attemptdata);
                $data['users'] = [$userdata];
                try {
                    $maharaattempt = $this->client->call('blocktype_stagevisualisation_save_quiz_attempt_data', $data);
                    $event = \local_qmframework\event\export_attempt_succeeded::success_export($attemptid);
                } catch (\Exception $e) {
                    $error = $e->getMessage().json_encode($data);
                    $event = \local_qmframework\event\export_attempt_failed::fail_export($attemptid, $error);
                }
            } else {
                $event = \local_qmframework\event\export_attempt_abandoned::abandon_export($attemptid, $maharaattempt);
            }
            $event->trigger();
        }
        if ($error !== false) {
            throw new \invalid_parameter_exception('Export quiz attempt to Mahara failed: '.$error);
        }
    }

}
