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

/****************************************************************

File:     /block/course_rollover/db/install.php

Purpose:  Message provider

****************************************************************/

/**
 * Message provider
 *
 * @package     course_rollover
 * @subpackage  block
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
$messageproviders = array (
    // Notify user that a course_rolover has been requested this tells site Admin that the task has been requested
    'submission' => array (
		 'capability'  => 'block/course_rollover:emailnotify'
    ),
    // Confirm a course_rolover was done this tell theuser that a shedule has been created and when the task has ran
    // please note that the task may not run on the assigned day but will not run before the assigned day.
    'confirmation' => array (
		'capability'  => 'block/course_rollover:emailconfirm'
    )
);
?>
