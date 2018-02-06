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
 * Local library functions for Course Template block.
 *
 * @package      local
 * @subpackage   course_backup
 * @copyright    2012 Gerry G Hall
 * @author       Gerry G Hall<gerryghall.co.uk>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// this is a CLI Lib so lets ensure the a CLI script is calling it.
define('CLI_SCRIPT', true);


function get_user($username){
	 global $DB;
	 return $DB->get_record('user', array('username'=>$username));

}


/**
 * Launches an automated backup routine for the given course
 *
 * @param object $course
 * @param object $course_template
 * @param int $userid
 * @return bool
 */
function launch_backup($course, $starttime = false , $userid = false, array $settings ) {
		global $CFG, $DB;
		try {
			$starttime = ($starttime === false) ? time() : $starttime;
			mtrace('setting starttime : ' . $starttime);
        	$config = get_config('qmul/course_backup');
			if($config === NULL) {
				mtrace("Backup configuration for qmul/course_backup doesn't exsits please ensure that the forced settings are in place and you have installed the plugin correctly");
				exit();
			}
			$bc = new backup_controller(
											backup::TYPE_1COURSE, 
											$course->id, backup::FORMAT_MOODLE, 
											backup::INTERACTIVE_NO, 
											backup::MODE_AUTOMATED, 
											$userid
										);
			
			mtrace("getting backup configuration");
			
            foreach ($settings as $setting => $configsetting) {
                if ($bc->get_plan()->setting_exists($setting)) {
                    $bc->get_plan()->get_setting($setting)->set_value($configsetting);
                }
            }

            // Set the default filename
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            $bc->get_plan()->get_setting('filename')->set_value(backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised));

            $bc->set_status(backup::STATUS_AWAITING);
            $outcome = $bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'];
            $dir = $config->backup_auto_destination;
            $storage = (int)$config->backup_auto_storage;
            if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
                $dir = null;
            }
            if (!empty($dir) && $storage !== 0) {
                if ($file->copy_content_to($dir.'/'. $course->id . '-' . $starttime . '.mbz') && $storage === 1) {
                    $file->delete();
                }
            }
            $outcome = true;
        } catch (backup_exception $e) {
            $bc->log('backup_auto_failed_on_course', backup::LOG_WARNING, $course->idnumber);
            $outcome = false;
        }

        $bc->destroy();
        unset($bc);
	
        return $outcome;
}


