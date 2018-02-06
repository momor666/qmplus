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
 * Local qmframework observer class
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */

namespace local_qmframework;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/qmframework/lib.php');

class observer {

    /**
     * Handle the user_enrolled event to create an adhoc task for
     * syncing the user account to the QM Framewok Mahara host and
     * institution.
     *
     * The enrolment data of the event must match the settings for:
     *  - course ID
     *  - role ID
     */
    public static function user_enrolled(\core\event\base $event) {
        $eventdata = (object) $event->get_data();
        $enabled = local_qmframework_is_plugin_enabled();
        if ($enabled) {
            $settings = local_qmframework_course_and_roles_settings();
            $qmcourse = $settings['course'];
            if ((int) $eventdata->courseid === (int) $qmcourse) {
                // Get user data.
                $userid = $eventdata->relateduserid;
                $objectid = $eventdata->objectid;
                $ok = local_qmframework_check_user_enrolment($objectid, $userid);
                if ($ok !== false) {
                    // Create adhoc task.
                    $syncuser = new \local_qmframework\task\sync_enrolled_user();
                    $syncuser->set_custom_data($eventdata);
                    \core\task\manager::queue_adhoc_task($syncuser);
                }
            }
        }
    }

    /**
     * Handle the group_assigned event to create an adhoc task for
     * syncing the gorup to the QM Framework Mahara host and
     * institution.
     *
     * The group data of the event must match the settings for:
     *  - course ID
     *  - grouping ID
     *
     *  And additonally have:
     *  - a group idnumber configured
     */
    public static function group_assigned(\core\event\base $event) {
        $eventdata = (object) $event->get_data();
        $enabled = local_qmframework_is_plugin_enabled();
        if ($enabled) {
            $settings = local_qmframework_course_and_group_settings();
            $qmcourse = $settings['course'];
            $qmgrouping = $settings['grouping'];
            if ((int) $eventdata->courseid === (int) $qmcourse) {
                // Get grouping data.
                $groupingid = $eventdata->objectid;
                if ((int) $groupingid === (int) $qmgrouping) {
                    $syncgroup = new \local_qmframework\task\sync_personal_tutor_group();
                    $syncgroup->set_custom_data($eventdata);
                    \core\task\manager::queue_adhoc_task($syncgroup);
                }
            }
        }
    }

    /**
     * Handle the group_member_added event to create an adhoc task for
     * syncing the gorup memberships to the QM Framework Mahara host and
     * institution.
     *
     * The grouping data of the event must match the settings for:
     *  - course ID
     *  - grouping ID
     *  - Tutor or Student role
     *
     *  And additonally have:
     *  - the group idnumber configured
     */
    public static function member_added(\core\event\base $event) {
        $eventdata = (object) $event->get_data();
        $enabled = local_qmframework_is_plugin_enabled();
        if ($enabled) {
            $settings = local_qmframework_course_and_group_settings();
            $qmcourse = $settings['course'];
            $qmgrouping = $settings['grouping'];
            if ((int) $eventdata->courseid === (int) $qmcourse) {
                $userid = $eventdata->relateduserid;
                $contextid = $eventdata->contextid;
                $groupid = $eventdata->objectid;
                $role = local_qmframework_user_is_student_or_tutor($contextid, $userid);
                $groupingok = local_qmframework_group_in_tutor_grouping($groupid, $qmgrouping);
                if ($role !== false && $groupingok) {
                    // Create adhoc task.
                    $syncmember = new \local_qmframework\task\sync_personal_tutor_group_member();
                    $syncmember->set_custom_data($eventdata);
                    \core\task\manager::queue_adhoc_task($syncmember);
                }
            }
        }
    }

    /**
     * Handle the attempt_submitted event to create an adhoc task for
     * quiz attempt to the QM Framework Mahara Dashboard
     *
     * The attempt submitted data of the event must match the settings for:
     *  - course ID
     *  - quiz ID
     *  - student role
     */
    public static function quiz_attempt_submitted(\core\event\base $event) {
        $eventdata = (object) $event->get_data();
        $enabled = local_qmframework_is_plugin_enabled();
        if ($enabled) {
            $settings = local_qmframework_course_and_quiz_settings();
            $qmcourse = $settings['course'];
            $qmquiz = $settings['quizid'];
            $courseid = $eventdata->courseid;
            $otherdata = $eventdata->other;
            $quizid = $otherdata['quizid'];
            if ((int) $courseid === (int) $qmcourse && (int) $quizid === (int) $qmquiz) {
                $userid = $eventdata->relateduserid;
                $context = \context_course::instance($courseid);
                $contextid = $context->id;
                $attemptid = $eventdata->objectid;
                $role = local_qmframework_user_is_student_or_tutor($contextid, $userid);
                if ($role === 'member') {
                    // Create adhoc task.
                    $exportattempt = new \local_qmframework\task\export_quiz_attempt();
                    $exportattempt->set_custom_data($eventdata);
                    \core\task\manager::queue_adhoc_task($exportattempt);
                }
            }
        }
    }
}
