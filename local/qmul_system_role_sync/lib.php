<?php

// This file is part of Moodle - http://moodle.org/

/**
 * Lib functions (for cron) to automatically JP Students
 * if it was not done all at once during the main upgrade.
 *
 * @package    local
 * @subpackage qmul_system_role_sync
 * @copyright  Greg Pasciak/QMUL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function does the cron process within the time range according to settings.
 * please
 * @return
 */
function local_qmul_system_role_sync_process() {

    global $DB;

    $line_number = 0;
    $users_assigned = 0;
    $users_notassigned = 0;
    $config = get_config('qmul_system_role_sync');

    if (empty($config)) {
		 mtrace('qmul_system_role_sync: Config not set...');
	        return;
	}

    $role = $DB->get_record('role', array('name'=>$config->rolename));  // name of the role to assign to system context
    $context = get_context_instance( CONTEXT_SYSTEM);


//get users from the file - file syntax: one column of usernames separated by line termination (\r\n),
//first row contains "username" string - will be skipped in processing

    $arrLines = file($config->filepath.'/'.$config->filename);

    if (empty($arrLines)) {
        mtrace('qmul_system_role_sync: empty file, finishing...');
        return;
    }

    mtrace('qmul_system_role_sync: processing ...');

//process assignments
    foreach ($arrLines as $line) {

        $line = rtrim($line, "\r\n");

        if ($line_number!= 0){                      //condition: "USERNAME" is the first column in the table
            $username = array('username'=>$line);
            $dbUser = $DB->get_record('user', array('username'=>$line));

            if (!empty($dbUser)){
                $role_assignment_id = role_assign($role->id, $dbUser->id, $context->id);
                if (!empty($role_assignment_id))
                    $users_assigned++;
                else
                    $users_notassigned++;
            } else {
                echo('Error assigning user, username: '. $line.',  ' );
                $users_notassigned++;
            }
        }
        $line_number++;
    }

    mtrace( ' qmul_system_role_sync: Done. JP Students role assignment finished,  Users processed: '.($line_number-1).
            ',   Users assigned: '.$users_assigned.
            ',   '. 'Users not assigned: '.$users_notassigned);

    return;
}


/**
 * Standard cron function for processing JP students role assignments
 * - needs to uncomment line containing "$plugin->cron = .." in vaersion.php to run cron function periodically
 */

function local_qmul_system_role_sync_cron() {
    $settings = get_config('local_qmul_system_role_sync');
    if (empty($settings->cronenabled)) {
        return;
    }

    mtrace('qmul_system_role_sync: local_qmul_system_role_sync_cron() started at '. date('H:i:s'));
    try {
        local_qmul_system_role_sync_process($settings);
    } catch (Exception $e) {
        mtrace('qmul_system_role_sync: local_qmul_system_role_sync_cron() failed with an exception:');
        mtrace($e->getMessage());
    }
    mtrace('qmul_system_role_sync: local_qmul_system_role_sync_cron() finished at ' . date('H:i:s'));
}
