<?php
/**
 * reference https://docs.moodle.org/dev/Task_API
 *
 * @package    report_qmplus
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_qmplus\task;

require_once(dirname(__FILE__).'/../../lib.php');

defined('MOODLE_INTERNAL') || die();

class newFiles extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens
        return get_string('notifyadmins', 'report_qmplus');
    }

    public function execute() {


        $date = new \DateTime();
        $frequency = get_config('report_qmplus', 'taskFrequency');  // Get from settings file
        $date->sub(new \DateInterval('PT'.$frequency.'H'));

        // Create/ Overwrite files
        $newFiles = report_qmplus_getNewFiles($date->getTimestamp());

        if($newFiles->valid()){

            $users = report_qmplus_getSiteAdmins(); // get users to send email to
            $file = report_qmplus_createEmailReportFile($newFiles);
            $sender = null;

            foreach ($users as $user) {

                if (is_null($sender)) {
                    $sender = $user; // We use the first user as a sender
                }

                $send[]=report_qmplus_sendEmail($sender, $user, $file);

            }


            $externalUsers = get_config('report_qmplus', 'externalUsers'); // get from settings file
            if($externalUsers!=''){  // if external users exist, send them an email
                $externalUsers = preg_replace('/\s+/', '', $externalUsers);   //remove empty space
                $externalUsers = explode(',',$externalUsers);

                foreach ($externalUsers as $user) {

                    $externalUsersSend[]=report_qmplus_sendEmail($sender, $user, $file);

                }

            }


            $users->close();

        }

        $newFiles->close();

        return true;
    }
}