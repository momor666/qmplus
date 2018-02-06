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
 * Manage the activity todolist
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitytodo;

use cm_info;

defined('MOODLE_INTERNAL') || die();

class todolist {
    private $user;
    private $courseitems = [];
    private static $excludedmods = ['label'];

    public function __construct($user) {
        if (!is_object($user)) {
            throw new \coding_exception('Must pass user object');
        }
        $this->user = $user;
    }

    /**
     * Can the user manage their todolist (viewing, adding/removing items, etc.)
     * @return bool
     */
    public function can_manage_list() {
        return has_capability('local/activitytodo:manage', \context_system::instance(), $this->user->id);
    }

    private function require_manage_list() {
        require_capability('local/activitytodo:manage', \context_system::instance(), $this->user->id);
    }

    public function add($cmid) {
        $this->require_manage_list();
        /** @var cm_info $cm */
        list($course, $cm) = get_course_and_cm_from_cmid($cmid, '', 0, $this->user->id);
        $context = \context_course::instance($course->id);
        if (!$cm->uservisible || !(has_capability('local/activitytodo:add', $context, $this->user->id))) {
            // Can only add bookmarks for visible activities on courses you are enrolled in.
            throw new \moodle_exception('invalidcmid');
        }
        if (in_array($cm->modname, self::$excludedmods)) {
            return;
        }
        $item = new todo_item(['userid' => $this->user->id, 'cmid' => $cmid]);
        if (!$item->id) {
            $item->courseid = $course->id;
            $item->duedate = $this->calculate_due_date($cm);
            if ($item->duedate) {
                $item->lockduedate = 1;
            }
            $item->insert();
            $this->reset_course_item_cache($course->id);
        }
    }

    public function remove($cmid) {
        $this->require_manage_list();
        $item = todo_item::fetch(['userid' => $this->user->id, 'cmid' => $cmid]);
        if ($item) {
            $this->reset_course_item_cache($item->courseid);
            $item->delete();
        }
    }

    private function calculate_due_date(cm_info $cm) {
        global $DB;
        switch ($cm->modname) {
            // If this is changed to include other activity types, make sure the function course_module_updated()
            // is also changed.
            case 'assign':
                $duedate = $DB->get_field('assign', 'duedate', ['id' => $cm->instance]);
                return $duedate ?: null;
            default:
                return null;
        }
    }

    private function reset_course_item_cache($courseid) {
        unset($this->courseitems[$courseid]);
    }

    /**
     * Get all the user's todolist items, sorted by sortorder.
     * @return todo_item[]
     */
    public function get_items() {
        $this->require_manage_list();
        $items = todo_item::fetch_all(['userid' => $this->user->id]);
        return $items;
    }

    /**
     * Get a single item from the todolist.
     * @param int $cmid
     * @return todo_item|false
     */
    public function get_item($cmid) {
        $this->require_manage_list();
        return todo_item::fetch(['userid' => $this->user->id, 'cmid' => $cmid]);
    }

    /**
     * Is the user allowed to add to their todolist from this course?
     * @param int $courseid
     * @return bool
     */
    private function can_add_items($courseid) {
        $context = \context_course::instance($courseid);
        $caps = ['local/activitytodo:add', 'local/activitytodo:manage'];
        return has_all_capabilities($caps, $context, $this->user);
    }

    public function add_javascript_to_course($courseid) {
        global $PAGE, $OUTPUT;
        if ($this->can_add_items($courseid)) {
            if (empty($this->user->editing)) {
                $opts = [
                    'todolistCmids' => $this->get_cmids_on_todolist($courseid),
                    'icons' => [
                        'loading' => $OUTPUT->pix_icon('i/ajaxloader', ''),
                    ],
                    'excludedmods' => self::$excludedmods,
                ];
                $PAGE->requires->js_call_amd('local_activitytodo/addactivities', 'init', [$opts]);
            }
        }
    }

    private function get_cmids_on_todolist($courseid) {
        if (!array_key_exists($courseid, $this->courseitems)) {
            $this->courseitems[$courseid] = [];
            $items = todo_item::fetch_all(['userid' => $this->user->id, 'courseid' => $courseid]);
            foreach ($items as $item) {
                $this->courseitems[$courseid][] = $item->cmid;
            }
        }
        return $this->courseitems[$courseid];
    }

    /**
     * Find out if the given item is on the todolist already
     * (caches all items from the same course internally in the todolist instance)
     * @param cm_info $cm
     * @return bool
     */
    public function is_on_todolist(cm_info $cm) {
        $this->require_manage_list();
        return in_array($cm->id, $this->get_cmids_on_todolist($cm->course));
    }

    /**
     * Update the todolist to match the given order.
     * @param int[] $cmids ordered list of cmids
     */
    public function update_sortorder($cmids) {
        global $DB;
        $items = $this->get_items();
        // Highest sortorder is at the top of the list, so new items appear there.
        $cmids = array_reverse($cmids);
        /** @var todo_item[] $items */
        $items = array_reverse($items); // So any leftover items will retain their original order.

        $transaction = $DB->start_delegated_transaction();
        $sortorder = 1;
        foreach (array_values($cmids) as $cmid) {
            // Find each cmid in order in the list, then update the sortorder for that item, if needed.
            foreach ($items as $idx => $item) {
                if ($item->cmid == $cmid) {
                    if ($item->sortorder != $sortorder) {
                        $item->sortorder = $sortorder;
                        $item->update();
                    }
                    unset($items[$idx]);
                    $sortorder++;
                    break;
                }
            }
        }

        // Loop through any items not sorted by the cm list and add them to the top of the list.
        foreach ($items as $item) {
            if ($item->sortorder != $sortorder) {
                $item->sortorder = $sortorder;
                $item->update();
            }
            $sortorder++;
        }

        $transaction->allow_commit();
    }

    public function update_note($cmid, $note) {
        $item = $this->get_item($cmid);
        if ($item) {
            if ($item->note != $note) {
                $item->note = $note;
                $item->update();
            }
        }
    }

    public function update_duedate($cmid, $duedate) {
        $item = $this->get_item($cmid);
        if ($item && !$item->lockduedate) {
            if ($item->duedate != $duedate) {
                $item->duedate = $duedate;
                $item->update();
            }
        }
    }

    /**
     * Check for assignment due dates being changed, then update all relevant todolist items
     *
     * @param \core\event\course_module_updated $event
     */
    public static function course_module_updated(\core\event\course_module_updated $event) {
        global $DB;
        if ($event->other['modulename'] != 'assign') {
            return;
        }
        $duedate = $DB->get_field('assign', 'duedate', ['id' => $event->other['instanceid']]);
        $duedate = $duedate ?: null;
        todo_item::update_due_dates($event->objectid, $duedate);
    }
}