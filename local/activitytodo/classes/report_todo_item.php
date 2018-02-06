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
 * Wrapper for todo_item, adding details needed for the report
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitytodo;

use cm_info;

defined('MOODLE_INTERNAL') || die();

class report_todo_item implements \templatable {
    /** @var todo_item */
    protected $item;
    /** @var cm_info */
    protected $cm;

    private function __construct(todo_item $item, cm_info $cm) {
        $this->item = $item;
        $this->cm = $cm;
    }

    /**
     * @param todo_item[] $items
     * @return static[]
     */
    public static function wrap_items($items) {
        if (!$items) {
            return [];
        }
        $wrapped = [];
        foreach ($items as $item) {
            try {
                $modinfo = get_fast_modinfo($item->courseid);
            } catch (\dml_missing_record_exception $e) {
                // Course has been deleted - delete the item + process the rest.
                $item->delete();
                continue;
            }
            try {
                $cm = $modinfo->get_cm($item->cmid);
            } catch (\moodle_exception $e) {
                // Activity has been deleted - delete the item + process the rest.
                $item->delete();
                continue;
            }
            $wrapped[] = new static($item, $cm);
        }
        return $wrapped;
    }

    public function get_formatted_activity_link() {
        $name = $this->cm->get_formatted_name();
        $link = $this->cm->url;
        if ($link) {
            $name = \html_writer::link($link, $name);
        }
        return $name;
    }

    public function get_formatted_course_name() {
        $name = format_string($this->cm->get_course()->fullname);
        $parts = explode('-', $name);
        $name = trim($parts[0]);
        return $name;
    }

    public function has_note() {
        return (bool)(trim($this->item->note));
    }

    public function get_note_content() {
        return trim($this->item->note);
    }

    private static function get_date_format($forjavascript = false, $forparse = false) {
        static $format = null;
        if (!$format) {
            $format = get_string('strftimedatefullshort', 'langconfig');
        }
        if ($forjavascript) {
            $retformat = str_replace(['%d', '%m', '%y'], ['dd', 'mm', 'yy'], $format);
        } else if ($forparse) {
            $retformat = str_replace(['%d', '%m', '%y'], ['d', 'm', 'Y'], $format);
        } else {
            $retformat = str_replace(['%y'], ['%Y'], $format);
        }
        return $retformat;
    }

    public static function format_date($timestamp) {
        $format = self::get_date_format();
        if (!$timestamp) {
            return '';
        }
        return userdate($timestamp, $format);
    }

    public function get_formatted_due_date() {
        return self::format_date($this->item->duedate);
    }

    public function is_overdue() {
        return ($this->item->duedate && ($this->item->duedate < time()));
    }

    public function is_locked_due_date() {
        return (bool)$this->item->lockduedate;
    }

    public function get_cmid() {
        return $this->cm->id;
    }

    public function export_for_template(\renderer_base $output) {
        $ret = (object)[
            'activityname' => $this->get_formatted_activity_link(),
            'coursename' => $this->get_formatted_course_name(),
            'hasnote' => $this->has_note(),
            'notecontent' => $this->get_note_content(),
            'duedate' => $this->get_formatted_due_date(),
            'isoverdue' => $this->is_overdue(),
            'lockduedate' => $this->is_locked_due_date(),
            'dateformat' => self::get_date_format(true),
            'editduedate' => false,
            'cmid' => $this->get_cmid(),
        ];
        return $ret;
    }

    public static function parse_submitted_date($datestr) {
        if (!$datestr) {
            return null;
        }
        $format = self::get_date_format(false, true);
        $timezone = get_user_timezone();
        $timezone = new \DateTimeZone($timezone);
        $datetime = date_create_from_format($format, $datestr, $timezone);
        if (!$datetime) {
            return null;
        }
        $timestamp = $datetime->getTimestamp();
        return $timestamp;
    }
}
