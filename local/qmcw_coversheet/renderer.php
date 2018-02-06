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



class local_qmcw_coversheet_renderer extends core_renderer {



    //TODO: "DamianH" : refactor these funcitons - possible object here
    /**
     * @param $data
     * @return stdClass
     */
    public function get_scan_display_data($data){
        global $USER;

        $scandisplay = new stdClass;

        if(!property_exists($data, 'usertype')){
            $scanelements = explode('-', $data->barcode);
            $data->usertype = $scanelements[1];
        }

        $scandisplay->usertype = '';
        if($data->usertype == 'G') {
            $scandisplay->usertype = get_string('srd-usertypegroup', 'local_qmcw_coversheet');

            $groupmembers = local_qmcw_coversheet_get_barcode_members($data->barcode);

            $array = [];
            foreach($groupmembers as $groupmember){
                $fullname = $groupmember['firstname'] . $groupmember['lastname'];
                array_push($array, $fullname);
            }
            $scandisplay->groupmembers = implode(', ', $array );

            $groupid = (int) $data->scan->groupid;
            $scandisplay->groupname = groups_get_group_name($groupid);

        }
        else{
            $scandisplay->usertype = get_string('srd-usertypesingleuser', 'local_qmcw_coversheet');
            $members = local_qmcw_coversheet_get_barcode_members($data->barcode);
            if(count($members) == 1){
                $scandisplay->user = $members[0];
            }
        }

        $scandisplay->timesubmitted = date("d-M-Y H:i", $data->scan->timesubmitted);
        $scandisplay->timescanned = date("d-M-Y H:i", $data->scan->timescanned);

        $scandisplay->scanuser = fullname($USER);

        return $scandisplay;
    }



    /*
     * Use this to set formatted data to the renderer class to be displayed to the user.
     *
     *
     */
    public function setScanDisplayProperties($data){


    }





    /**
     * @param $data
     * @return stdClass
     */
    public function get_updated_scan_display_data($data){
        global $USER;

        $scandisplay = new stdClass;

        if(!property_exists($data, 'usertype')){
            $scanelements = explode('-', $data->barcode);
            $data->usertype = $scanelements[1];
        }

        //user and group names of the assignment stay the same
        $scandisplay->usertype = '';
        if($data->usertype == 'G') {
            $scandisplay->usertype = get_string('srd-usertypegroup', 'local_qmcw_coversheet');

            $groupmembers = local_qmcw_coversheet_get_barcode_members($data->barcode);

            $array = [];
            foreach($groupmembers as $groupmember){
                $fullname = $groupmember['firstname'] . $groupmember['lastname'];
                array_push($array, $fullname);
            }
            $scandisplay->groupmembers = implode(', ', $array );

            $groupid = (int) $data->currentscan->groupid;
            $scandisplay->groupname = groups_get_group_name($groupid);

        }
        else{
            $scandisplay->usertype = get_string('srd-usertypesingleuser', 'local_qmcw_coversheet');
            $members = local_qmcw_coversheet_get_barcode_members($data->barcode);
            if(count($members) == 1){
                $scandisplay->user = $members[0];
            }
        }

        //time submitted - contrast with updated date - optional form element
        $scandisplay->currenttimesubmitted = $this->display_date($data->currentscan->timesubmitted);
        if(property_exists($data, 'updatesubmittime') and ($data->updatesubmittime > 0) ){
            $scandisplay->updatedtimesubmitted = $this->display_date($data->updatesubmittime);
        }
        else{
            $scandisplay->updatedtimesubmitted = 0;
        }

        //time scanned - contrast with updated time scanned
        $scandisplay->currenttimescanned = $this->display_date($data->currentscan->timescanned);
        $scandisplay->updatedtimescanned = $this->display_date(time());

        //scanning user - contrast with previous scanning user
        $currentscanuser = user_get_users_by_id(array($data->currentscan->scanuserid));
        //TODO: "DamianH" : ( ! ) Strict standards: Only variables should be passed by reference in /Applications/MAMP/htdocs/moodle32/local/qmcw_coversheet/renderer.php on line 133
        $currentscanuser = array_values($currentscanuser);
        if(count($currentscanuser) == 1){
            $scandisplay->currentscanuser = fullname($currentscanuser[0]);
        }

        $scandisplay->updateduser = fullname($USER);


        if(property_exists($data, 'updatesubmissionnote')){
            $scandisplay->updatesubmissionnote = $data->updatesubmissionnote;
        }

        return $scandisplay;

    }

    /**
     * @param $data
     * @param string $message
     * @param bool $update
     * @return string
     */
    public function display_scan_results($data, $message = null, $update = false){
        $output = '';
        $cssclasses = 'scan-result';
        $scandisplay = $data->scandisplay;

        $output .= html_writer::tag('h2', get_string('resultsheader', 'local_qmcw_coversheet'), array('class' => 'header'));

        if(!is_null($message)){
            $output .= $this->display_form_message($message);
        }

        //deflist starts here
        $submissiontypelabel = get_string('srd-submissiontypelabel', 'local_qmcw_coversheet');
        $dl = $this->dl_wrapper($submissiontypelabel, $scandisplay->usertype);

        if($data->usertype == "G"){
            $groupnamelabel = get_string('srd-groupnamelabel', 'local_qmcw_coversheet');
            $dl .= $this->dl_wrapper($groupnamelabel, $scandisplay->groupname);

            $groupmemberslabel = get_string('srd-groupmemberslabel', 'local_qmcw_coversheet');
            $dl .= $this->dl_wrapper($groupmemberslabel, $scandisplay->groupmembers);

        }
        else if ($data->usertype == "S"){
            $singleuser = $scandisplay->user;
            $singleusernamelabel = get_string('srd-singleuserlabel', 'local_qmcw_coversheet');
            $fullname = $singleuser['firstname'] . " " . $singleuser['lastname'];
            $dl .= $this->dl_wrapper($singleusernamelabel, $fullname);
        }

        //submission date
        if($update and (property_exists($data, 'updatesubmittime'))){
            $label = get_string('srd-submitdatelabel', 'local_qmcw_coversheet');

            $span = $this->update_span_wrapper($scandisplay->updatedtimesubmitted, $scandisplay->currenttimesubmitted,
                get_string('srd-previousdatemessage', 'local_qmcw_coversheet'));
            if($data->updatesubmittime == 0){
                $span = $scandisplay->currenttimesubmitted;
            }

            $dl .= $this->dl_wrapper($label, $span);
        }
        else{
            $submitdatelabel = get_string('srd-submitdatelabel', 'local_qmcw_coversheet');
            $submitdate = $scandisplay->timesubmitted;
            $dl .= $this->dl_wrapper($submitdatelabel, $submitdate);
        }

        //scan user
        if($update and (property_exists($data, 'updatebutton'))) {

            $label = get_string('srd-scannedbylabel', 'local_qmcw_coversheet');

            $span = $this->update_span_wrapper($scandisplay->updateduser, $scandisplay->currentscanuser,
                get_string('srd-previousscanbymessage', 'local_qmcw_coversheet'));

            $dl .= $this->dl_wrapper($label, $span);
        }
        else{
            $scannedbylabel = get_string('srd-scannedbylabel', 'local_qmcw_coversheet');
            $dl .= $this->dl_wrapper($scannedbylabel, $scandisplay->scanuser);
        }


        //scan time
        if($update and (property_exists($data, 'updatebutton'))){
            $label = get_string('srd-udpatedscandatelabel', 'local_qmcw_coversheet');

            $span = $this->update_span_wrapper($scandisplay->updatedtimescanned, $scandisplay->currenttimescanned,
                get_string('srd-previousscandatemessage', 'local_qmcw_coversheet'));

            $dl .= $this->dl_wrapper($label, $span);

        }
        else{
            $scandatelabel = get_string('srd-scandatelabel', 'local_qmcw_coversheet');
            $dl .= $this->dl_wrapper($scandatelabel, $scandisplay->timescanned);
        }


        if($update and (property_exists($scandisplay, 'updatesubmissionnote')) and $scandisplay->updatesubmissionnote != ''){
            $scandatelabel = get_string('srd-scanupdatesubmissionnote', 'local_qmcw_coversheet');
            $dl .= $this->dl_wrapper($scandatelabel, $scandisplay->updatesubmissionnote, null , array('class'=>'updatenote'));
        }

        if((property_exists($scandisplay, 'grade'))){
            $scangradelabel = get_string('srd-grade', 'local_qmcw_coversheet');
            $dl .= $this->dl_wrapper($scangradelabel, $scandisplay->grade, null , array('class'=>'updategrade'));
        }



        $dl = html_writer::tag('dl', $dl);
        $output .= html_writer::div($dl, 'scan-results');

        $output = $this->container($output, '', 'scandetails');

        return $output;
    }

    /**
     * @param $message
     * @return mixed
     */
    public function display_form_message($message){

        $notification = new \core\output\notification($message, \core\output\notification::NOTIFY_SUCCESS);
        $output = $this->render($notification);
        return $output;
    }

    public function render_notification(\core\output\notification $notification)
    {
        return parent::render_notification($notification);
    }


    /**
     * @param $label
     * @param $value
     * @param null $dtattributes
     * @param null $ddattributes
     * @return string
     *
     */
    private function dl_wrapper($label, $value, $dtattributes = null, $ddattributes = null){
        $output = '';

        $output .= html_writer::tag('dt', $label, $dtattributes);
        $output .= html_writer::tag('dd', $value, $ddattributes);

        return $output;
    }

    /**
     * @param $scanval
     * @param $prevscanval
     * @param $prevmessage
     * @return string
     */
    private function update_span_wrapper($scanval, $prevscanval, $prevmessage){

        $span = '';
        $span .= $scanval;
        $span .= html_writer::start_span('prevmessage');
        $span .= '(';
        $span .= $prevmessage;
        $span .= $prevscanval;
        $span .= ')';
        $span .= html_writer::end_span();

        return $span;
    }


    /**
     * @param $errors
     * @return string
     */
    public function render_validation_errors($errors){
        $errorstr = '';
        foreach($errors as $error){
            $errorstr.= html_writer::tag('li', $error);
        }

        $errorstr = html_writer::tag('ul', $errorstr);
        $errorstr = html_writer::tag('div', $errorstr, array('class' => 'validation-errors'));

        $notification = new \core\output\notification($errorstr, \core\output\notification::NOTIFY_ERROR);

        return $this->render($notification);
    }

    /**
     * @param $update_history
     * @return string
     */
    public function display_scan_update_history($update_history){

        $uh = html_writer::tag('h2', get_string('updatehistoryheader', 'local_qmcw_coversheet'), array('class' => 'header'));
        $uh .= html_writer::start_div( 'updatehistory-list');

        $counter = 1;
        foreach($update_history as $update){
             //counter
            $uh .= html_writer::start_tag('dl', array('updatehistory updatehistory' . $counter));

            $uh .= html_writer::tag('dt', get_string('srd-counter', 'local_qmcw_coversheet'));
            $uh .= html_writer::tag('dd',$counter);

             if($update->timesubmitted > 0){
                 $uh .= html_writer::tag('dt', get_string('ul-submitdatelabel', 'local_qmcw_coversheet'));
                 $uh .= html_writer::tag('dd', $this->display_date($update->timesubmitted));
             }
            $uh .= html_writer::tag('dt', get_string('ul-scanupdatesubmissionnote', 'local_qmcw_coversheet'));
            $uh .= html_writer::tag('dd',$update->updatenote);

            $user = local_qmcw_coursework_get_user_by_id($update->scanuserid);
            $fullname = fullname($user);
            $uh .= html_writer::tag('dt', get_string('ul-scannedbylabel', 'local_qmcw_coversheet'));
            $uh .= html_writer::tag('dd',$fullname);


            $uh .= html_writer::tag('dt', get_string('ul-scandatelabel', 'local_qmcw_coversheet'));
            $uh .= html_writer::tag('dd',$this->display_date($update->timescanned));

            $uh .= html_writer::end_tag('dl');

            $counter++;
        }

        $uh .= html_writer::end_div();

        return $uh;
    }

    /**
     * @param $timestamp
     * @return string
     *
     */
    public function display_date($timestamp){
        return userdate($timestamp, "%d/%m/%y  %R");
    }


    /**
     * @param \local_qmcw_coversheet\output\update_grade $widget
     * @return bool|string
     */
    public function render_update_grade(local_qmcw_coversheet\output\update_grade $widget){
        //to pass to template
        $export = $widget->export_for_template($this);
        return $this->render_from_template('local_qmcw_coversheet/update_grade', $export);
    }

    /**
     * @param \local_qmcw_coversheet\output\update_grade $widget
     * @return bool|string
     */
    public function render_update_grade_display(local_qmcw_coversheet\output\update_grade_display $widget){
        //to pass to template
        $export = $widget->export_for_template($this);
        return $this->render_from_template('local_qmcw_coversheet/update_grade_display', $export);
    }


    public function display_update_notification($message, $messagetype, $element){

        if(is_null($messagetype)){
            $messagetype = \core\output\notification::NOTIFY_SUCCESS;
        }

        $notification = new \core\output\notification($message, $messagetype);
        $notification = $this->render($notification);
        $params = array('notification' => $notification, 'element' => $element);

        global $PAGE;
        $PAGE->requires->js_call_amd('local_qmcw_coversheet/scan', 'updatemessage', $params);

    }

}


