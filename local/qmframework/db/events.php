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
 *
 * Local qmframework plugin event handler definition.
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die();

$observers = [

    [
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\local_qmframework\observer::user_enrolled',
    ],

    [
        'eventname'   => '\core\event\grouping_group_assigned',
        'callback'    => '\local_qmframework\observer::group_assigned',
    ],

    [
        'eventname'   => '\core\event\group_member_added',
        'callback'    => '\local_qmframework\observer::member_added',
    ],

    [
        'eventname'   => '\mod_quiz\event\attempt_submitted',
        'callback'    => '\local_qmframework\observer::quiz_attempt_submitted',
    ],

];
