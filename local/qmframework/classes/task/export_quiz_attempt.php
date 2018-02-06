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
 * Local qmframework export quiz attempt adhoc task
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
namespace local_qmframework\task;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/qmframework/lib.php');

class export_quiz_attempt extends \core\task\adhoc_task {

    public function get_component() {
        return 'local_qmframework';
    }

    public function execute() {
        // Export quiz attempt.
        $data = $this->get_custom_data();
        $enabled = local_qmframework_is_plugin_enabled();
        if ($enabled) {
            $settings = local_qmframework_course_and_quiz_settings();
            $qmcourse = $settings['course'];
            $qmquiz = $settings['quizid'];
            $courseid = $data->courseid;
            $otherdata = $data->other;
            $quizid = $otherdata->quizid;
            if ((int) $courseid === (int) $qmcourse && (int) $quizid === (int) $qmquiz) {
                $userid = $data->relateduserid;
                $context = \context_course::instance($courseid);
                $attemptid = $data->objectid;
                $role = local_qmframework_user_is_student_or_tutor($context->id, $userid);
                if ($role === 'member') {
                    $user = local_qmframework_user_details($userid);
                    $webservice = new \local_qmframework\web_service();
                    $webservice->connect();
                    $webservice->export_attempt($user, $attemptid, $role);
                }
            }
        }
    }
}
