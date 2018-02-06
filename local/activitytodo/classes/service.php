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
 * Webservice function implementations
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitytodo;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

class service extends external_api {

    // -------------------------------
    // Add todolist item
    // -------------------------------

    public static function add_parameters() {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT),
            ]
        );
    }

    public static function add($cmid) {
        global $USER;

        $todolist = new todolist($USER);
        $todolist->add($cmid);
        return ['status' => true];
    }

    public static function add_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
            ]
        );
    }

    // -------------------------------
    // Remove todolist item
    // -------------------------------

    public static function remove_parameters() {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT),
            ]
        );
    }

    public static function remove($cmid) {
        global $USER;

        $todolist = new todolist($USER);
        $todolist->remove($cmid);
        return ['status' => true];
    }

    public static function remove_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
            ]
        );
    }

    // -------------------------------
    // Sort todolist items
    // -------------------------------

    public static function sort_parameters() {
        return new external_function_parameters(
            [
                'cmids' => new \external_multiple_structure(
                    new external_value(PARAM_INT)
                )
            ]
        );
    }

    public static function sort($cmids) {
        global $USER;

        $todolist = new todolist($USER);
        $todolist->update_sortorder($cmids);
        return ['status' => true];
    }

    public static function sort_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
            ]
        );
    }

    // -------------------------------
    // Update a note on a todolist item
    // -------------------------------

    public static function update_note_parameters() {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT),
                'note' => new external_value(PARAM_RAW),
            ]
        );
    }

    public static function update_note($cmid, $note) {
        global $USER;

        $todolist = new todolist($USER);
        $todolist->update_note($cmid, $note);
        return ['status' => true];
    }

    public static function update_note_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
            ]
        );
    }

    // -------------------------------
    // Update the duedate on a todolist item
    // -------------------------------

    public static function update_duedate_parameters() {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT),
                'duedate' => new external_value(PARAM_RAW),
            ]
        );
    }

    public static function update_duedate($cmid, $duedate) {
        global $USER;

        $duedate = report_todo_item::parse_submitted_date($duedate);

        $todolist = new todolist($USER);
        $todolist->update_duedate($cmid, $duedate);

        return [
            'status' => true,
            'duedate' => report_todo_item::format_date($duedate),
            'overdue' => ($duedate && ($duedate < time()))
        ];
    }

    public static function update_duedate_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
                'duedate' => new external_value(PARAM_RAW),
                'overdue' => new external_value(PARAM_BOOL),
            ]
        );
    }
}