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
 * @package    assignsubmission
 * @subpackage qmcw_coversheet
 * @copyright  2017 Queen Mary University of London
 * @author     Damian Hippisley <d.j.hippisley@qmul.ac.uk>
 * @author     Vasileos Sotiras <v.sotiras@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->dirroot . '/local/qmcw_coversheet/locallib.php');


class assign_submission_qmcw_coversheet extends assign_submission_plugin{

    /**
     * @return string
     */
    public function get_name(){
        return get_string('name', 'assignsubmission_qmcw_coversheet');
    }


    /**
     * This form is hooked on the assign submission page
     *
     * @param MoodleQuickForm $mform
     *
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

//        $mform->disabledIf('assignsubmission_onlinetext_enabled', 'assignsubmission_qmcw_coversheet_enabled', 'checked');
//        $mform->disabledIf('assignsubmission_file_enabled', 'assignsubmission_qmcw_coversheet_enabled', 'checked');
//        $mform->disabledIf('submissiondrafts', 'assignsubmission_qmcw_coversheet_enabled', 'checked');

    }

    /**
     * This is hooked when the assignment settings are saved
     * This is used to force settings for the assignment to prevent issues
     *
     * @param stdClass $formdata
     * @return bool
     *
     */
    public function save_settings(stdClass $formdata)
    {

        if($formdata->assignsubmission_qmcw_coversheet_enabled){
            //we don't want students to have to submit after admin have scanned
            if(isset($formdata->submissiondrafts)){
                $formdata->submissiondrafts = 0;
            }

            $instance = $this->assignment->get_instance();
            $instance->submissiondrafts = 0;
            $this->assignment->set_instance($instance);
            if(isset($formdata->assignfeedback_editpdf_enabled)){
                $formdata->assignfeedback_editpdf_enabled = 0;
            }

        }


        return true;
    }

    /**
     * 1. Gets barcode data to display and status of scan
     * 2. Associates scanid with assign submission plugin id
     *
     *
     * @param mixed $submission
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     *
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $CFG, $USER, $PAGE, $OUTPUT;

        if (!empty($USER->id)) {
            if(!local_qmcw_coversheet_is_admin_or_courseadmin($USER->id)){
                $cm = $this->assignment->get_course_module();
                $cmid = $cm->id;
                //TODO: "DamianH" : remove this if you want files too!
                redirect($CFG->wwwroot .'/mod/assign/view.php?id= ' . $cmid,'',-1);
                return false;
            }
        }

//        $mform->addElement('static', 'formtext', get_string('srd-grade', 'local_qmcw_coversheet'), $grade);


        $cm = $this->assignment->get_course_module();
        $cmid = $cm->id;
        $userid = $submission->userid;

        //get the scan data so that submitting also creates a dummy scan
        $data->scanid = 0;

        $data->barcodeobj = new Barcode(false, false, $cmid, $userid);
        if($scandata = local_qmcw_coversheet_get_scan_record($data->barcodeobj)){
            $data->scanid = $scandata->id;
        }


        $coversheetflag = false;
        $submittedsubmissionflag = false;

        if($submission){
            if($submission->status == "submitted"){
                $submittedsubmissionflag = true;
            }
            $coversheetsubmission = $this->get_coversheet_submission($submission->id);
            if($coversheetsubmission){
                $data->scanid = $coversheetsubmission->scanid;
                $coversheetflag = true;
            }
        }

        //we have a complete set of records display to user
        if($scandata && $coversheetflag){
            $message = get_string('addsubmission_status_scanned_submitted', 'assignsubmission_qmcw_coversheet');
            $notification = $OUTPUT->notification($message, \core\output\notification::NOTIFY_SUCCESS);
            $mform->addElement('html',$notification);

        }
        else if(($submittedsubmissionflag == false) && ($scandata == false)){
            $message = get_string('addsubmission_status_not_scanned_submitted', 'assignsubmission_qmcw_coversheet');
            $notification = $OUTPUT->notification($message, \core\output\notification::NOTIFY_SUCCESS);
            $mform->addElement('html',$notification);

        }
        else{
            $message = get_string('addsubmission_action_scanned_submitted', 'assignsubmission_qmcw_coversheet');
            $notification = $OUTPUT->notification($message, \core\output\notification::NOTIFY_ERROR);
            $mform->addElement('html',$notification);
        }

        if($data->scanid){
            $mform->addElement('hidden', 'scanid', $data->scanid);
        }

        $mform->addElement('date_time_selector', 'submissiondate', get_string('scandate', 'local_qmcw_coversheet'), array('optional' => true));

        return true;
    }

    /**
     * The "save" function is called to save a user submission.
     * The parameters are the submission object and the data from the submission form.
     * This inserts the scan data and assign sumbission if it doesn't exist.
     * Unlike other assign submissions, it does not handle updating the submission.
     * This is handled using the scan update system.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     *
     */
    public function save(stdClass $submission, stdClass $data){
        global $USER, $DB;

        $coversheetsubmission = $this->get_coversheet_submission($submission->id);

        //set draft status to submitted to by pass assign setting
        // 'Require students click submit button'
        // this extra step, enabled by defaut, will never be desirabled with Physical coursework
        if(isset($submission->status) && ($submission->status == 'draft')){
            $submission->status = 'submitted';
        }

        $scandata = false;
        $cmid = $data->id;
        $userid = $data->userid;
        $data->barcodeobj = new Barcode(false, false, $cmid, $userid);
        if($scandata = local_qmcw_coversheet_get_scan_record($data->barcodeobj)){
            $data->scanid = $scandata->id;
        }

        //submit scan if it dosen't exist
        if(!$scandata){
            //if no scan insert one - whether editing or adding
            local_qmcw_coversheet_prepare_scan($data);
            if(isset($data->scan)){
                $result = local_qmcw_coversheet_insert_scan($data->scan);
                $data->scanid = $result;
            }
        }
        else{
            $data->scan = $scandata;
        }
        $scanid = $data->scanid;

        //set submission date either with the due date or the date provided
        if(isset($data->submissiondate) && $data->submissiondate != 0){
            $submissiondate = $data->submissiondate;
        }
        else if (isset($data->scan->timesubmitted)){
            $submissiondate = $data->scan->timesubmitted;
        }
        else{
            $submissiondate = time();
        }


        //submit assign submission if it doesn't exist
        if($coversheetsubmission === false){
            $coversheetsubmission = new stdClass();
            $coversheetsubmission->submission = $submission->id;
            $coversheetsubmission->assignment = $this->assignment->get_instance()->id;
            $coversheetsubmission->scanid = $data->scanid;
            $coversheetsubmission->submissiondate = $submissiondate;
            $insertstatus = $DB->insert_record('assignsubmission_coversheet', $coversheetsubmission);

            //TODO: "DamianH" : add events trigger here

            return $insertstatus;
        }
        else{
            //TODO: "DamianH" : WTF!!! Revise!
            //check that the scanid matches the inserted scan above
            if($coversheetsubmission->scanid != $data->scanid){
                $params = array('id' => $coversheetsubmission->id,'scanid' => $scanid, 'submissiondate' => $submissiondate);
                $updatestatus = $DB->update_record('assignsubmission_coversheet', $params);
            }

            //TODO: "DamianH" : add events trigger here

            return $updatestatus;

        }
    }



    /**
     * Carry out any extra processing required when a student is given a new attempt
     * (i.e. when the submission is "reopened"
     * @param stdClass $oldsubmission The previous attempt
     * @param stdClass $newsubmission The new attempt
     */
    public function add_attempt(stdClass $oldsubmission, stdClass $newsubmission) {

        //delete scan data
        $userid = $oldsubmission->userid;
        $cm = $this->assignment->get_course_module();
        $cmid = $cm->id;
        return local_qmcw_coversheet_delete_scan_data($userid, $cmid);

    }

    /**
     * Carry out any extra processing required when the work reverted to draft.
     *
     * @param stdClass $submission - assign_submission data
     * @return void
     */
    public function revert_to_draft(stdClass $submission) {

        //delete scan data
        $userid = $submission->userid;
        $cm = $this->assignment->get_course_module();
        $cmid = $cm->id;
        return local_qmcw_coversheet_delete_scan_data($userid, $cmid);

    }




    /**
     * This function is hooked when a viewer views a submission summary page after having made a submission
     *
     * note that id has_user_summary is set to false
     * this is not called on the grading page.
     *
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return bool
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $CFG, $USER, $PAGE;

        if (!empty($USER->id)) {
            if(!local_qmcw_coversheet_is_admin_or_courseadmin($USER->id)){
                //script to hide submissionactions
                $PAGE->requires->js_call_amd('assignsubmission_qmcw_coversheet/coversheet', 'status_view');
            }
        }
        $showcoversheet = ( ( $cmid = clean_param($_REQUEST['id'], PARAM_INT ) ) > 0);
        $showviewlink = false;
        //add js for switching off add submission, submit submission
        if($showcoversheet){
            $url = $CFG->wwwroot.'/local/qmcw_coversheet/coversheet.php?cmid=' . $cmid . '&userid='. $submission->userid;
            $linktext  = get_string('printcoversheet', 'assignsubmission_qmcw_coversheet');
            $button = html_writer::link($url, $linktext, array('class' => 'btn btn-primary', 'type' => 'button'));
            return $button;

        } else {
            $button = html_writer::link('#','Coversheet N/A', array('class' => 'btn btn-primary disabled', 'type' => 'button'));
            return $button;
        }
    }


    /**
     * Get coversheet submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_coversheet_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_coversheet', array('submission'=>$submissionid));
    }


    /**
     *  It will always be empty as there is no e-submission.
     *  This adds the submit assignment button.
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return false;
    }

    /**
     *
     * @return boolean
     */
    public function allow_submissions() {

        return true;
    }



    /**
     *
     * This function syncs the assign submission timemodified data
     * with the submissionplugin's submissiondate field.
     * It is a work around as moodle does let you handle any data after
     * the assignment submission is updated.
     *
     * @return int
     */
    public static function sync_submissiondates(){
        global $DB;

        $count = 0;

        try {
            $select = 'submissiondate <> 0';
            $results = $DB->get_records_select('assignsubmission_coversheet', $select);
        } catch (Error $error){
        } catch (Throwable $throwable){
        } catch (Exception $exception){
        }

        if($results){
            foreach ($results as $result){
                $updateparams = new stdClass();
                $updateparams->id = $result->submission;
                $updateparams->timemodified = $result->submissiondate;
                $updateresult = $DB->update_record('assign_submission', $updateparams);
                if($updateresult){
                    //reset the submission date in the plugin table to 0 as a flag
                    //the acutual data still exists in the local_coversheet_scan table.
                    $result->submissiondate = 0;
                    $DB->update_record('assignsubmission_coversheet', $result);
                }
                $count ++;
            }
        }
        return $count;
    }


    /**
     * This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main assignment page.
     *
     * @return string
     */
    public function view_header() {
        global $OUTPUT;

        //DH: WORKAROUND
        self::sync_submissiondates();

        $notification = "";
        if($this->is_due_date_passed()){
            //display message in introduction
            $message = get_string('esubmissiondelay', 'assignsubmission_qmcw_coversheet');
            $messagetype = \core\output\notification::NOTIFY_WARNING;
            $notification = new \core\output\notification($message, $messagetype);
            $notification = $OUTPUT->render($notification);
        }

        return $notification;
    }

    /**
     * If this plugin should not include a column in the grading table or a row on the summary page
     * then return false
     *
     * @return bool
     */

    public function has_user_summary() {
        global $PAGE;
        //DH: WORKAROUND
        self::sync_submissiondates();

        if(isset($_REQUEST['action']) && ($_REQUEST['action'] === "grading")){
            return false;
        }

        return true;
    }


    private function is_due_date_passed(){
        global $USER;

        $assign =  $this->assignment;
        $instance = $assign->get_instance();
        $duedate = $instance->duedate;

        //check if assign has been submitted
        $submission = $assign->get_user_submission($USER->id, false);
        if($submission->status === 'submitted'){
            return false;
        }

        $now = $this->get_timestamp_local_now();

        if($duedate < $now){
            return true;
        }
        return false;
    }

    public function get_timestamp_local_now(){

        $tzo = new DateTimeZone(date_default_timezone_get());
        $dto = new DateTime(date(DATE_W3C), $tzo);
        return $now = $dto->getTimestamp();

    }


}





