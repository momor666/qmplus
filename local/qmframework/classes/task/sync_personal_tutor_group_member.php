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
 * Local qmframework sync new group member adhoc task
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
namespace local_qmframework\task;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/qmframework/lib.php');

class sync_personal_tutor_group_member extends \core\task\adhoc_task {

    /**
     * Return the component
     *
     * @return string
     */
    public function get_component() {
        return 'local_qmframework';
    }

    /**
     * Execute the sync_personal_tutor_group_member task.
     *
     * The grouping data of the event must match the settings for:
     *  - course ID
     *  - grouping ID
     *  - Tutor or Student role
     *
     *  And additonally have:
     *  - the group idnumber configured
     *
     * @return string
     */
    public function execute() {
        // Sync new group member.
        $data = $this->get_custom_data();
        $settings = local_qmframework_course_and_group_settings();
        $qmcourse = $settings['course'];
        $qmgrouping = $settings['grouping'];
        if ((int) $data->courseid === (int) $qmcourse) {
            $userid = $data->relateduserid;
            $contextid = $data->contextid;
            $groupid = $data->objectid;
            $role = local_qmframework_user_is_student_or_tutor($contextid, $userid);
            $groupingok = local_qmframework_group_in_tutor_grouping($groupid, $qmgrouping);
            if ($role !== false && $groupingok) {
                $user = local_qmframework_user_details($userid);
                $group = local_qmframework_group_details($groupid);
                $memberid = local_qmframework_membership_id($userid, $groupid);
                $webservice = new \local_qmframework\web_service();
                $webservice->connect();
                $webservice->check_group_admin();
                $webservice->sync_member($user, $group, $role, $memberid);
            }
        }
    }
}
