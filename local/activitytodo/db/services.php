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
 * Web service definitions
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_activitytodo_add' => [
        'classname' => '\local_activitytodo\service',
        'methodname' => 'add',
        'type' => 'write',
        'capabilities' => 'local/activitytodo:manage',
        'ajax' => true,
    ],

    'local_activitytodo_remove' => [
        'classname' => '\local_activitytodo\service',
        'methodname' => 'remove',
        'type' => 'write',
        'capabilities' => 'local/activitytodo:manage',
        'ajax' => true,
    ],

    'local_activitytodo_sort' => [
        'classname' => '\local_activitytodo\service',
        'methodname' => 'sort',
        'type' => 'write',
        'capabilities' => 'local/activitytodo:manage',
        'ajax' => true,
    ],

    'local_activitytodo_update_note' => [
        'classname' => '\local_activitytodo\service',
        'methodname' => 'update_note',
        'type' => 'write',
        'capabilities' => 'local/activitytodo:manage',
        'ajax' => true,
    ],

    'local_activitytodo_update_duedate' => [
        'classname' => '\local_activitytodo\service',
        'methodname' => 'update_duedate',
        'type' => 'write',
        'capabilities' => 'local/activitytodo:manage',
        'ajax' => true,
    ],
];
