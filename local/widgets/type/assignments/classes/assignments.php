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
 * Main class for the widget
 *
 * @package   widgettype_assignments
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace widgettype_assignments;

defined('MOODLE_INTERNAL') || die();

class assignments extends \block_widgets\widgettype_base {

    const MAX_ASSIGNMENTS = 5;

    /**
     * Get the title to display for this widget.
     * @return string
     */
    public function get_title_internal() {
        return get_string('pluginname', 'widgettype_assignments');
    }

    private function get_duestatus($assignment) {
        if ($assignment->status == 'submitted') {
            $duestatus = get_string('submitted', 'widgettype_assignments');
            $statusclass = 'submitted';
        } else if ($assignment->status == 'draft') {
            $duestatus = get_string('draft', 'widgettype_assignments');
            $statusclass = 'draft';
        } else if ($assignment->duedate < time()) {
            $duestatus = get_string('overdue', 'widgettype_assignments');
            $statusclass = 'overdue';
        } else if ($assignment->duedate < (time() + 14 * DAYSECS)) {
            $duestatus = get_string('duesoon', 'widgettype_assignments');
            $statusclass = 'duesoon';
        } else {
            $duestatus = get_string('due', 'widgettype_assignments');
            $statusclass = 'due';
        }
        return [$duestatus, $statusclass];
    }

    public function get_assignments($userid, $limit) {
        global $DB;

        $sql = "
           SELECT cm.id AS cmid, a.name, c.fullname AS coursename, a.duedate, s.status, c.id AS courseid
             FROM {assign} a
             JOIN {course} c ON c.id = a.course
             JOIN {course_modules} cm ON cm.instance = a.id
             JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'

             JOIN (SELECT DISTINCT e.courseid
                              FROM {enrol} e
                              JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = :userid1
                             WHERE e.status = :enabled AND ue.status = :active
                               AND ue.timestart < :now2 AND (ue.timeend = 0 OR ue.timeend > :now3)
                  ) en ON (en.courseid = c.id)

             LEFT JOIN {assign_submission} s ON s.assignment = a.id AND s.userid = :userid2 AND s.latest = 1

            WHERE a.duedate IS NOT NULL AND a.duedate > 0
              AND (a.duedate > :now1 OR s.status IS NULL OR s.status != 'submitted')
              AND cm.visible = 1 AND c.visible = 1
            ORDER BY a.duedate
        ";
        $params = [
            'userid1' => $userid, 'userid2' => $userid,
            'now1' => time(), 'now2' => time(), 'now3' => time(),
            'active' => ENROL_USER_ACTIVE, 'enabled' => ENROL_INSTANCE_ENABLED
        ];
        $assignments = $DB->get_recordset_sql($sql, $params);

        $format = get_string('strftimedatefullshort', 'langconfig');
        $ret = [];
        foreach ($assignments as $a) {
            if (count($ret) >= $limit) {
                break;
            }
            $modinfo = get_fast_modinfo($a->courseid);
            if (!$modinfo->get_cm($a->cmid)->uservisible) {
                continue;
            }
            $coursename = explode('-', format_string($a->coursename));
            $coursename = trim($coursename[0]);
            $viewurl = new \moodle_url('/mod/assign/view.php', ['id' => $a->cmid]);
            list($duestatus, $statusclass) = $this->get_duestatus($a);

            $ret[] = (object)[
                'name' => format_string($a->name),
                'coursename' => $coursename,
                'duedate' => userdate($a->duedate, $format),
                'duestatus' => $duestatus,
                'statusclass' => $statusclass,
                'viewurl' => $viewurl,
            ];
        }
        return $ret;
    }

    /**
     * Return the main content for the widget.
     * @return string[]
     */
    public function get_items() {
        global $OUTPUT, $USER;
        $assignments = $this->get_assignments($USER->id, self::MAX_ASSIGNMENTS);
        if (!$assignments) {
            return [get_string('noassignments', 'widgettype_assignments')];
        }
        $ret = [];
        foreach ($assignments as $assignment) {
            $ret[] = $OUTPUT->render_from_template('widgettype_assignments/assignment', $assignment);
        }
        return $ret;
    }

    public function get_footer() {
        return '';
    }
}
