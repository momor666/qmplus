<?php
/**
 * Scheduled task to generate  average progress data json files to use in dashboard
 * for improving performance
 *
 * reference https://docs.moodle.org/dev/Task_API
 *
 * @package    local
 * @subpackage qmul_facebook
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_qmul_dashboard\task;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

defined('MOODLE_INTERNAL') || die();

class activityAverageProgress extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('activityAverageProgressTask', 'local_qmul_dashboard');
    }

    public function execute() {

        ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

        //Get Message
        mtrace('Running...');

        global $CFG;

        $courses = get_courses();

        // Create dir to store temp  report files.
        $myDir = $CFG->dataroot . '/qmplus_dashboard';
        if (!is_dir($myDir)) {
            mkdir($myDir, 0777, true); // true for recursive create
        }

        foreach ($courses as $course) {

            ini_set('max_execution_time', 0);

            $courseContext = local_qmul_dashboard_getCourseContext($course->id);

            $progress = local_qmul_dashboard_getProgressConfig($courseContext);

            if($progress){

                $progressConfig = unserialize(base64_decode($progress->configdata));
                $modules = block_progress_modules_in_use($course->id);
                $events = block_progress_event_information($progressConfig, $modules, $course->id);

                if (!empty($events)) {
                    $couresAttempts = array();

                    $courseUsers = local_qmul_dashboard_getCourseUsers($course->id); // Needed for progress bar average!

                    foreach ($courseUsers as $user) {

                        ini_set('max_execution_time', 0);

                        $userEvent = block_progress_filter_visibility($events, $user->id, $courseContext, $course);
                        $couresAttempts[] = block_progress_attempts($modules, $progressConfig, $userEvent, $user->id, $course->id);
                    }

                    $jsonFile = $myDir."/averageActivityProgress-$course->id.json";
                    $fp = fopen($jsonFile, 'w');
                    fwrite($fp, json_encode($couresAttempts));
                    fclose($fp);
                    mtrace("File averageActivityProgress-$course->id.json stored.");

                }

            }

        }

        mtrace('Completed!');
        return true;
    }

}