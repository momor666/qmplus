<?php
/**
 * Scheduled task to regrade final grades for improving performance
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
require_once (__DIR__.'/../../../../lib/gradelib.php');

defined('MOODLE_INTERNAL') || die();

class regradeFinalGrades extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens
        return get_string('regradeFinalGradesTask', 'local_qmul_dashboard');
    }

    public function execute() {

        ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

        //Get Message
        mtrace('Running...');

        $courses = get_courses();

        foreach ($courses as $course) {
            ini_set('max_execution_time', 0);
            grade_regrade_final_grades($course->id);
        }

        mtrace('Completed!');
        return true;
    }

}