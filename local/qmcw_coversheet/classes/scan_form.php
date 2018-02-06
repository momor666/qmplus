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
require_once($CFG->libdir.'/formslib.php');
require_once(__DIR__ . '/../locallib.php');


class local_qmcw_coversheet_scan_form extends moodleform {

    private static $_defaultplaceholder = 'QM+CW-S-0000000010-0000000880';

    public $state = null;

    public function definition(){
        global $DB;

        $mform = $this->_form;

        //this adds the text box and the regular scan buttons
        $this->add_action_buttons(true, null);

        //additional form data takes place in definition_after_data()

    }


    /**
     * @param bool $cancel
     * @param null $submitlabel
     *
     */
    public function add_action_buttons($cancel = true, $submitlabel = null) {

        $mform = $this->_form;

        $barcodeobj = $this->_customdata['barcodeobj'];


        $placeholder = ((is_null($barcodeobj->givenbarcode)) ? self::$_defaultplaceholder : $barcodeobj->givenbarcode);

        $textinputoptions = array('size' => '32',
            'placeholder' => $placeholder,
            'autofocus' => 'autofocus',
        );

        //update grade form
        $header = html_writer::tag('h2', get_string('adminscanform', 'local_qmcw_coversheet'), array('class' => 'header scanform'));
        $mform->addElement('html', $header);

        //keep barcode in state even
        //even when user scans place holder
        //submission will act on this state of the barcode
        $mform->addElement('hidden', 'placeholderbarcode', $placeholder);
        $mform->setType('placeholderbarcode', PARAM_RAW);

        $scanobjs = array();
        $scanobjs[] = $mform->createElement('text', 'barcode', null, $textinputoptions);
        $scanobjs[] = $mform->createElement('submit', 'submitbutton', get_string('scanaction', 'local_qmcw_coversheet'));
        $scanobjs[] = $mform->createElement('cancel', 'cancel', get_string('cancel'));


        $mform->addElement('group', 'actionsgrp', get_string('barcode', 'local_qmcw_coversheet'), $scanobjs, ' ', false);
        $mform->setType('barcode', PARAM_RAW);
    }

    /**
     * Definition after data is what is says it is
     * The data has been provided, now add further defintion
     * This is useful if submitted data provides conditional logic to the form
     *
     */
    function definition_after_data(){
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_qmcw_coversheet');

        $mform = $this->_form;

        $customdata = $this->_customdata;
        $barcodeobj = $customdata['barcodeobj'];

        //get scan form state here
        $scanformstate = $this->get_state();
        if($scanformstate){
            $this->set_state($scanformstate);
        }

        $updateformstates = array("rescan", "update", "updategrade");
        //conditional form elements to enable submission update
        if(in_array($this->state, $updateformstates)) {

            //update grade form
            $header = html_writer::tag('h2', get_string('updategradeformheader', 'local_qmcw_coversheet'), array('class' => 'header gradeform'));
            $mform->addElement('html', $header);



            $gradeformoelements = array();
            $gradeformoelements[] = $mform->createElement('text', 'updategrade', get_string('updategrade', 'local_qmcw_coversheet'), array('size' => 5));
            $gradeformoelements[] = $mform->createElement('submit', 'updategradebutton', get_string("updategradebuttonlabel", 'local_qmcw_coversheet'));
            $mform->addElement('group', 'gradegroup', get_string('updategrade', 'local_qmcw_coversheet'), $gradeformoelements, ' ', false);



            //update submission form
            $header = html_writer::tag('h2', get_string('updateformheader', 'local_qmcw_coversheet'), array('class' => 'header updateform'));
            $mform->addElement('html', $header);

            $mform->addElement('date_time_selector', 'updatesubmittime', get_string('updatesubmittime', 'local_qmcw_coversheet'), array('optional' => true));

            $mform->addElement('text', 'updatesubmissionnote', get_string('updatesubmissionnote', 'local_qmcw_coversheet'), array('size'=>'100', 'maxlenght' => '144'));
            $mform->setType('updatesubmissionnote', PARAM_TEXT);
            if($customdata == 'update_scan'){
                $mform->addRule('updatesubmissionnote', null, 'required', null );
            }

            $pwdattributes = array('size' => '17', 'autocomplete' => 'off');
//            $mform->addElement('password', 'overridepassword', get_string('updatepassword', 'local_qmcw_coversheet'), $pwdattributes);
            $mform->addElement('submit', 'updatebutton', get_string('updateaction', 'local_qmcw_coversheet'));

        }
        else {
            $mform->addElement('date_time_selector', 'scandate', get_string('scandate', 'local_qmcw_coversheet'), array('optional' => true));
        }



        if(isset($barcodeobj)){
            $placeholder = $barcodeobj->givenbarcode;
            $hiddenfield =  $mform->getElement('placeholderbarcode');
            $hiddenfield->setValue($placeholder);
        }
    }



    /**
     * This method contains a lot of logic as the one form is really three distinct forms
     *
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $USER;


        $errors = parent::validation($data, $files);
        $errors = array();

        $customdata = $this->_customdata;
        $barcodeobj = $customdata['barcodeobj'];

        //surely better way?
        $submitteddata = $this->get_submitted_data();
        $pwd = (isset($submitteddata->overridepassword) ? $submitteddata->overridepassword : false);

        if($barcodeobj->syntax_valid === false){
            $errors['barcodevalidation'] = get_string('err_barcode', 'local_qmcw_coversheet');
        }

        if($barcodeobj->is_valid == false){
            $errors['notincourse'] = get_string('err_notincourse', 'local_qmcw_coversheet');
        }

        //TODO: "DamianH" : validate other fields based on submitted data
        if(filter_has_var(INPUT_POST, 'updategrade') && ($this->state == 'updategrade') && (isset($this->updategrade))){
            if(!filter_input(INPUT_POST, 'updategrade', FILTER_VALIDATE_FLOAT) && ($this->updategrade === 0)){
                 $errors['updategrade'] = get_string('err_gradeNaN', 'local_qmcw_coversheet');
            }
        }


        if($this->state == 'update'){
            //may have to use password if
            //TODO: "DamianH" : if updatesubmittime and updatesubmissionnote are open for business then we need to invoke password
//            if(!authenticate_user_login( $USER->username, $pwd )){
//                $errors['passwordvalidation'] = get_string('err_password', 'local_qmcw_coversheet');
//            }

        }

        return $errors;
    }


    /**
     * @return array
     */
    public function get_validation_errors(){
        return $this->_form->_errors;
    }

    public function  set_state($state){
         $this->state = $state;
    }


    /**
     * Controls the form state of the scan form.
     * If returns false form is invalid
     *
     * @param $request
     * @return string
     */
    public function get_state(){

        $formstate = 'cancel';
        $customdata = $this->_customdata;
        $barcodeobj = $customdata['barcodeobj'];

        $submitteddata = $this->get_submitted_data();
        if(is_null($submitteddata)){
            return false;
        }

        $updatebutton = ( isset($_REQUEST['updatebutton']) ? $_REQUEST['updatebutton'] : null);
        $submitbutton = ( isset($_REQUEST['submitbutton']) ? $_REQUEST['submitbutton'] : null);
        $updategradebutton = ( isset($_REQUEST['updategradebutton']) ? $_REQUEST['updategradebutton'] : null);

        if($updatebutton == "Update Submission"){
            return 'update';
        }
        else if ($updategradebutton == "Submit grade"){
            return 'updategrade';
        }
        else if(($submitbutton == "Scan") && ($barcodeobj->is_already_scanned == true)){
            return 'rescan';
        }
        else if (($submitbutton == "Scan") && ($barcodeobj->is_already_scanned == false)){
            return 'newscan';
        }
        else{
            return 'invalid';
        }

    }


    public function set_gradeupdatestatus($updatedgrade){
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $barcodeobj = $customdata['barcodeobj'];

        $mform->setDefault('updategradestatus', $updatedgrade);

    }




}