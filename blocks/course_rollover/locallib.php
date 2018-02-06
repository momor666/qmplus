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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    locallib
 * @category   '$PWD'
 * @copyright  2013 Queen Mary University Gerry Hall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * this is still being developed
 */

 function rollover_courses_cron(){
   global $CFG, $DB, $EXTDB; // Globals
   require_once dirname(__FILE__) . '/classlib.php';
   $config = get_config('course_rollover');
   $EXTDB = course_rollover::db_init($config);
   //get list of courses to rollover
   mtrace(" Getting List of Courses \n");
   $courses_to_rollover = $DB->get_records_sql("
     SELECT
       *
     FROM
       mdl_block_course_rollover
     WHERE
       FROM_UNIXTIME(scheduletime , '%m-%y')
         BETWEEN
           FROM_UNIXTIME(? , '%m-%y')
         AND
           FROM_UNIXTIME(? , '%m-%y')
     AND
     (status > ? AND status < ? )
     AND
     scheduletime < UNIX_TIMESTAMP(NOW())", array($config->schedule_day, $config->cutoff_day,'99', '400'), 0, 5);

   if ($courses_to_rollover) {

       foreach ($courses_to_rollover as $c) {
           //rollover course
           if (course_rollover::rollover($c)) {
               mtrace('Course ID('.$c->courseid . ') '.$c->shortname .' New code '. $c->modcode . ' Old code '. $c->idnumber . ' - Rollover successful');
               error_log('Course ID('.$c->courseid . ') ' .  $c->modcode . ' - Rollover successful' . "\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
           } else {
               mtrace('Course ID('.$c->courseid . ') '.$c->modcode . '- Rollover failed');
               error_log('Course ID('.$c->courseid . ') ' .  $c->modcode . ' - Rollover failed' . "\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
           }
       }
   } else {
       mtrace("No Courses to rollover \n");
   }
   return true;
 }
