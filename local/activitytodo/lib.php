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
 * API functions
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_activitytodo_extend_navigation(global_navigation $nav) {
    global $PAGE, $USER;

    $todolist = new local_activitytodo\todolist($USER);
    if (!$todolist->can_manage_list()) {
        return;
    }

    if ($PAGE->pagelayout === 'course') {
        $todolist->add_javascript_to_course($PAGE->course->id);
    }

    $viewurl = new moodle_url('/local/activitytodo');
    $nav->add(get_string('viewtodo', 'local_activitytodo'), $viewurl);
}

function local_activitytodo_render_navbar_output(\renderer_base $renderer) {
    global $USER, $CFG;

    // Early bail out conditions.
    if (!isloggedin() || isguestuser() || user_not_fully_set_up($USER) ||
        get_user_preferences('auth_forcepasswordchange') ||
        ($CFG->sitepolicy && !$USER->policyagreed && !is_siteadmin())) {
        return '';
    }

    $todolist = new local_activitytodo\todolist($USER);
    if (!$todolist->can_manage_list()) {
        return '';
    }

    $url = new moodle_url('/local/activitytodo');
    $data = (object)[
        'url' => $url->out(),
    ];
    return $renderer->render_from_template('local_activitytodo/navbaricon', $data);
}