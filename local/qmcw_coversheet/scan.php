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
 *
 * @package    local
 * @subpackage qmcw_coversheet
 * @copyright  2017 Queen Mary University of London
 * @author     Damian Hippisley <d.j.hippisley@qmul.ac.uk>
 * @author     Vasileos Sotiras <v.sotiras@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 *   QM+CW-G-0000036044-0000629761 - GROUP
 *   QM+CW-S-0000000136-0000629761 - SINGLE
 *
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__ . '/locallib.php');


require_login();

$context = context_system::instance();
$PAGE->set_context($context);


if(!local_qmcw_coversheet_is_admin_or_courseadmin($USER->id)){
    //return error
    throw new moodle_exception('You do not have the correct roles to access this page.');
}


$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/qmcw_coversheet/scan.php', array('context' => $context->id));

$pagetitle = get_string('scanformpagetitle', 'local_qmcw_coversheet');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$PAGE->requires->js_call_amd('local_qmcw_coversheet/scan', 'init');


//set barcode object - validation occurs within
if(isset($_REQUEST['cancel'])){
    $barcodeobj = new Barcode();
}
else if(isset($_REQUEST['barcode']) || isset($_REQUEST['placeholderbarcode'])){
    $barcodeobj = new Barcode($_REQUEST['barcode'], $_REQUEST['placeholderbarcode']);
}
else{
    //new scan case
    $barcodeobj = new Barcode();
}

$scanform = new local_qmcw_coversheet_scan_form(null, array('barcodeobj' => $barcodeobj));


$renderer = $PAGE->get_renderer('local_qmcw_coversheet');

echo $OUTPUT->header();
echo html_writer::div('&nbsp;');

/*$debug = true;
print_debug('form state: '.$formstate);
print_debug('is canceled:'.  $scanform->is_cancelled());
print_debug('is validated:'.  $scanform->is_validated());
print_debug('is submitted:'.  $scanform->is_submitted());
print_debug('QM+CW-G-0000036044-0000629761 Single users: QM+CW-S-0000000136-0000629761');*/

if ($scanform->is_cancelled()) {

    redirect($CFG->wwwroot .'/local/qmcw_coversheet/scan.php','',-1);

}
else if($data = $scanform->get_data()){

    if(!is_null($barcodeobj)){
        $data->barcode = $barcodeobj->barcode;
        $data->barcodeobj = $barcodeobj;
    }



    if ($scanform->state == 'newscan'){

        local_qmcw_coversheet_prepare_scan($data);

        $data->scanid = local_qmcw_coversheet_insert_scan($data->scan);

        //insert assignment submission
        $submission = local_qmcw_coversheet_save_submission($data->scan->cmid, $data->scan->userid, $data->scanid, $data->barcodeobj);
        if($submission){
            //update timemodified date otherwise users will see late notices
            local_qmcw_coversheet_set_assignsubmission_submission_time($data->scan->timesubmitted, $data->barcodeobj, $data->scan->userid);
        }

        $scanform->display();

        $data->scandisplay = $renderer->get_scan_display_data($data);

        $message = get_string('scanresultsdescription', 'local_qmcw_coversheet');
        $messagetype = \core\output\notification::NOTIFY_SUCCESS;
        $element = "scanform";

        $renderer->display_update_notification($message, $messagetype, $element);

        echo $renderer->display_scan_results($data);
    }
    else if ($scanform->state == 'rescan'){


        $data->scan = local_qmcw_coversheet_get_scan_record($data->barcodeobj);

        $scanform->display();

        $message = get_string('existingscanresultsdescription', 'local_qmcw_coversheet');
        $messagetype = \core\output\notification::NOTIFY_SUCCESS;
        $element = "scanform";

        $renderer->display_update_notification($message, $messagetype, $element);

        $data->scandisplay = $renderer->get_scan_display_data($data);
        echo $renderer->display_scan_results($data);
    }
    else if ($scanform->state == 'updategrade'){

        $message = get_string('updateassigngradedefault', 'local_qmcw_coversheet');
        $messagetype = \core\output\notification::NOTIFY_ERROR;
        $element = 'gradeform';

        $updategrade = (isset($data->updategrade) ? (float) $data->updategrade : null);

        if((!is_null($updategrade)) && local_qmcw_coversheet_is_grade_update($updategrade, $data->barcodeobj->assigngradevalue)){
            //update the assign grade
            if(local_qmcw_coversheet_save_grade($data)){
                $data->barcodeobj->setAssignGradeValue($updategrade);
                $scanform->set_gradeupdatestatus($updategrade);
                $message = get_string('updateassignmentgrade', 'local_qmcw_coversheet') . $updategrade;
                $messagetype = \core\output\notification::NOTIFY_SUCCESS;
            }
        }

        $scanform->display();

        $renderer->display_update_notification($message, $messagetype, $element);


    }
    else if ($scanform->state == 'update'){

        //update database and display update submission results
        $data->currentscan = local_qmcw_coversheet_get_scan_record($data->barcodeobj);

        local_qmcw_coversheet_prepare_update_log_entry($data);
        local_qmcw_coversheet_prepare_scan_update_record($data);

        //insert update into update table
        if($DB->insert_record('local_qmcw_update_log', $data->logentry)){
            //update scan table with latest update time
            $result = $DB->update_record('local_qmcw_scan', $data->scanupdaterecord);
            if($result && isset($data->updatesubmittime)){
                //if the admin has update the scan submission time then update the assign submission time too!
                local_qmcw_coversheet_set_assignsubmission_submission_time($data->updatesubmittime, $data->barcodeobj, $data->scanupdaterecord->userid);
            }
        }

        $scanform->display();

        $data->scandisplay = $renderer->get_updated_scan_display_data($data);
        echo $renderer->display_scan_results($data, null, true);

        $message = get_string('updatescanresultsdescription', 'local_qmcw_coversheet');
        $messagetype = \core\output\notification::NOTIFY_SUCCESS;
        $element = 'updateform';
        $renderer->display_update_notification($message, $messagetype, $element);

        //update history
        $update_history = local_qmcw_coversheet_get_scan_update_history($data->currentscan->id);
        echo $renderer->display_scan_update_history($update_history);

    }
    else{

        $errorstr = get_string('err_invalidformstate', 'local_qmcw_coversheet');
        echo $OUTPUT->notification($errorstr, \core\output\notification::NOTIFY_ERROR);

        $scanform->display();

    }

//    print_debug_dump($data);
//    print_debug_dump($_REQUEST);
}
else{
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    if($scanform->is_submitted() and !($scanform->is_validated())){
       $errors = $scanform->get_validation_errors();
       echo $renderer->render_validation_errors($errors);
    }

    $scanform->display();

}


echo $OUTPUT->footer();



