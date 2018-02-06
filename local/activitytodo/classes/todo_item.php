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
 * Class to hold data about each todolist item
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitytodo;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/completion/data_object.php');

class todo_item extends \data_object {
    const TABLENAME = 'local_activitytodo';
    public $table = self::TABLENAME;

    public $required_fields = ['id', 'userid', 'cmid', 'courseid', 'note', 'duedate', 'lockduedate', 'dateadded', 'sortorder'];

    public $userid;
    public $cmid;
    public $courseid;
    public $note;
    public $duedate;
    public $lockduedate = 0;
    public $dateadded;
    public $sortorder;

    /**
     * @param array $params
     * @return self
     */
    public static function fetch($params) {
        /** @var self $ret */
        $ret = self::fetch_helper(static::TABLENAME, __CLASS__, $params);
        return $ret;
    }

    /**
     * @param $params
     * @return self[]
     */
    public static function fetch_all($params) {
        $ret = self::fetch_all_helper(static::TABLENAME, __CLASS__, $params);
        if (!$ret) {
            return [];
        }
        uasort($ret, function ($a, $b) {
            if ($a->sortorder < $b->sortorder) {
                return 1;
            }
            if ($a->sortorder > $b->sortorder) {
                return -1;
            }
            return 0;
        });
        return $ret;
    }

    public function insert() {
        global $DB;
        $this->dateadded = time();
        $lastsort = $DB->get_field(static::TABLENAME, 'MAX(sortorder)', ['userid' => $this->userid]);
        $this->sortorder = $lastsort ? $lastsort + 1 : 1;
        return parent::insert();
    }

    /**
     * Update all todolist items that relate to the given cmid and set their duedate
     * Will also lock those duedates.
     *
     * @param $cmid
     * @param $duedate
     */
    public static function update_due_dates($cmid, $duedate) {
        global $DB;
        $existing = $DB->get_record(self::TABLENAME, ['cmid' => $cmid], 'id, duedate, lockduedate', IGNORE_MULTIPLE);
        if (!$existing) {
            // No todolist items to update.
            return;
        }
        if ($existing->duedate == $duedate) {
            // Nothing has changed.
            return;
        }

        $DB->set_field(self::TABLENAME, 'duedate', $duedate, ['cmid' => $cmid]);
        if ($existing->lockduedate && !$duedate) {
            // Unlock the due date, if it is no longer set from the assignment due date.
            $DB->set_field(self::TABLENAME, 'lockduedate', 0, ['cmid' => $cmid]);
        } else if (!$existing->lockduedate && $duedate) {
            // Lock the due date, if it is now set from the assignment due date.
            $DB->set_field(self::TABLENAME, 'lockduedate', 1, ['cmid' => $cmid]);
        }
    }
}