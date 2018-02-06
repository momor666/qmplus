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

class newSettings extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens
        return get_string('newsettings', 'report_qmplus');
    }

    public function execute() {


        $date = new \DateTime();
        $frequency = get_config('report_qmplus', 'taskFrequency');  // Get from settings file
        $date->sub(new \DateInterval('PT'.$frequency.'H'));

        // Create/ Overwrite files
        $newSettings = report_qmplus_get_configlog_records($date->getTimestamp());

        if(count($newSettings)>1){

            $users = report_qmplus_getSiteAdmins(); // get users to send email to
            $file = report_qmplus_createEmailtFileForNewSettings($newSettings);
            $sender = null;

            foreach ($users as $user) {

                if (is_null($sender)) {
                    $sender = $user; // We use the first user as a sender
                }

                $send[]=report_qmplus_sendNewSettingsEmail($sender, $user, $file);

            }


            $externalUsers = get_config('report_qmplus', 'externalUsers'); // get from settings file
            if($externalUsers!=''){  // if external users exist, send them an email
                $externalUsers = preg_replace('/\s+/', '', $externalUsers);   //remove empty space
                $externalUsers = explode(',',$externalUsers);

                foreach ($externalUsers as $user) {

                    $externalUsersSend[]=report_qmplus_sendNewSettingsEmail($sender, $user, $file);

                }

            }


            $users->close();

        }

        return true;
    }
}