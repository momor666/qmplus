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
 * Displays different views of the qmplus reports.
 *
 * @package    report_qmplus
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once(dirname(__FILE__).'/lib.php');
require(dirname(__FILE__).'/media_form.php');

require_login();

if (!has_capability('report/qmplus:view', context_system::instance())) {
    header('HTTP/1.1 403 Forbidden');
    die();
}

// Get URL parameters.
$requestedMimeType = optional_param('mimetype', '', PARAM_SAFEDIR);

// Print the header & check permissions.
admin_externalpage_setup('reportqmplus', '', null, '', array('pagelayout'=>'report'));


$date = new DateTime();
$frequency = get_config('report_qmplus', 'taskFrequency');
$date->sub(new DateInterval('PT'.$frequency.'24H'));

$newFiles = report_qmplus_getNewFiles($date->getTimestamp());

if($newFiles->valid()){

    $users = report_qmplus_getSiteAdmins();
    $file = report_qmplus_createEmailReportFile($newFiles);
    $sender = null;

    foreach ($users as $user) {

        if (is_null($sender)) {
            $sender = $user;
        }

        $send[]=report_qmplus_sendEmail($sender, $user, $file);

    }


    $externalUsers = get_config('report_qmplus', 'externalUsers');
    if($externalUsers!=''){
        $externalUsers = preg_replace('/\s+/', '', $externalUsers);   //remove epmty space
        $externalUsers = explode(',',$externalUsers);

        foreach ($externalUsers as $user) {

            $externalUsersSend[]=report_qmplus_sendEmail($sender, $user, $file);

        }

    }


    $users->close();

}

$newFiles->close();