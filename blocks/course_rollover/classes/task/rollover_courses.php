<?php

/****************************************************************

File:     /block/course_rollover/db/install.php

Purpose:  Upgrade script for course rollover

****************************************************************/

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
 * @package    blocks
 * @subpackage course_rollover
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace block_course_rollover\task;
global $CFG;

class rollover_courses extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('rollover_courses', 'block_course_rollover');
    }

    public function execute() {
      global $CFG;
      require_once($CFG->dirroot. '/blocks/course_rollover/locallib.php');
      rollover_courses_cron();
    }
}
