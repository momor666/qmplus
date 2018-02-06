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
 * Local qmframework sync new created group adhoc task
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
namespace local_qmframework\task;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/qmframework/lib.php');

class sync_personal_tutor_group extends \core\task\adhoc_task {

    /**
     * Return the component
     *
     * @return string
     */
    public function get_component() {
        return 'local_qmframework';
    }

    /**
     * Execute the sync_personal_tutor_group task.
     *
     * The group data of the task must still match the settings for:
     *  - course ID
     *  - grouping ID
     *
     *  And still have:
     *  - a group idnumber configured
     */
    public function execute() {
        // Sync group.
        $data = $this->get_custom_data();
        $settings = local_qmframework_course_and_group_settings();
        $qmcourse = $settings['course'];
        $qmgrouping = $settings['grouping'];
        if ((int) $data->courseid === (int) $qmcourse) {
            $groupingid = $data->objectid;
            if ((int) $groupingid === (int) $qmgrouping) {
                $groupid = $data->other->groupid;
                $groupingok = local_qmframework_group_in_tutor_grouping($groupid, $groupingid);
                if ($groupingok) {
                    $group = local_qmframework_group_details($groupid);
                    $webservice = new \local_qmframework\web_service();
                    $webservice->connect();
                    $webservice->check_group_admin();
                    $webservice->sync_group($group);
                    $members = local_qmframework_group_valid_members($groupid);
                    if (!empty($members)) {
                        foreach ($members as $memberid => $user) {
                            $role = $user->role;
                            unset($user->memberid);
                            unset($user->role);
                            $webservice->sync_member($user, $group, $role, $memberid);
                        }
                    }
                }
            }
        }
    }
}
