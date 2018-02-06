<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG, $DB, $EXTDB; // Globals
$config = get_config('course_rollover');
require_once 'classlib.php';
$EXTDB = course_rollover::db_init($config);
print(" Getting List of Courses \n");
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
        if (course_rollover::rollover($c)) {
            print('Course ID('.$c->courseid . ') '.$c->shortname .' New code '. $c->modcode . ' Old code '. $c->idnumber . ' - Rollover successful');
            error_log('Course ID('.$c->courseid . ') ' .  $c->modcode . ' - Rollover successful' . "\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
        } else {
            print('Course ID('.$c->courseid . ') '.$c->modcode . '- Rollover failed');
			error_log('Course ID('.$c->courseid . ') ' .  $c->modcode . ' - Rollover failed' . "\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
        }
    }
} else {
    print("No Courses to rollover \n");
}