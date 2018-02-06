<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

global $DB, $CFG, $EXTDB;
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

require_once $CFG->libdir . '/adodb/adodb.inc.php';
require_once $CFG->libdir . '/blocklib.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once dirname(__FILE__) . '/classlib.php';

	  global $CFG, $DB, $EXTDB; // Globals
		$config = get_config('course_rollover');
     $EXTDB = course_rollover::db_init($config);
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
			print_r($c);
             $eventdata = new stdclass();
	            $eventdata->submitttedtime = userdate(time(), '%A, %d %B %Y');
	            $eventdata->schedule = $c;
	            $eventdata->action = 'rollover';
				if($recipient = $DB->get_record('user', array('id'=> $course_to_rollover->userid))){
					$eventdata->recipient = $recipient;
				} else{
					$eventdata->recipient = get_admin();
				}
	            echo course_rollover::notification($eventdata);
				exit;
         }
     } else {
         mtrace("No Courses to rollover \n");
     }

?>
