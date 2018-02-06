<?php

class admin_readonly_site_mode_form extends abstract_multiple_form
{
    function definition()
    {
        global $DB, $CFG;
        $this->_form->addElement('header', 'settingsheader',  'Read-Only user info');

        $site_roles = get_roles_for_contextlevels(CONTEXT_SYSTEM);
        if (!empty ($site_roles)){
            foreach ($site_roles as $key=>$value) {
                $roles_results [$value] = $DB->get_record('role', array('id'=>$value),'shortname');
                $choices [$value] = $roles_results [$value]->shortname;
            }
        }
        else {
            error('There is no roles assigned to the context: CONTEXT_SYSTEM');
            die();
        }

        $this->_form->addElement('select', 'readonlyuser', 'Choose Read-Only User shortname:', $choices);
        $this->_form->setDefault('readonlyuser', 'readonlyuser');

        $choices = array('0' => 'Off', '1' => 'On');
        $this->_form->addElement('select', 'assigntype', 'Read-Only Mode switch', $choices);
        $this->_form->setType('assigntype', PARAM_INT);
        $this->_form->addElement('hidden', 'cat_form_action', 'form1');
        $this->_form->setType('cat_form_action', PARAM_RAW);


        $this->add_action_buttons(true, 'Proceed');
        if ($this->is_cancelled()) {
            redirect($CFG->wwwroot);
        }
    }

    /**
     * @param $OUTPUT core_renderer
     * @return void
     */
    function handle(core_renderer $OUTPUT)
    {
        global $CFG;
        $return_url = "$CFG->wwwroot/blocks/ulcc_diagnostics/actions/readonly_site_mode.php";

        $data = $this->get_data();
        if (!$data) {
            echo $OUTPUT->heading('Read-Only Site Mode Management');
            echo '<p>This process allows you to put the site into Read-Only mode by assigning read only permissions for "readonlyuser" user</br>
                1. Please ensure that you have created the Read-Only user in System context (readonlyuser) by setting all permissions to "Prohibit" except of viewing options which should be set where desired to "Not set".</br>
                2. Once the Read-Only User shortname, switch on/switch off  Read-Only Mode has been chosen and "Proceed" button has been clicked, be aware that processing all users may take hours!</br>
                3. When the process is finished, the number of processed users will be displayed, if process will fail for some reasons, then the error message will be displayed.</br>
                4. moodle-support user is the only one user excluded from being read-only user and is keeping full admin privileges</p>';
            $this->display();

        }
        if ($this->is_cancelled()) {
                redirect($CFG->wwwroot);
        }
      $this->next = true;


    }

}

class admin_readonly_site_mode_intermediate extends abstract_multiple_form
{
    function definition()
    {
        global $CFG;

        if ($this->is_submitted()) {
            redirect($CFG->wwwroot);
        }
    }

    /**
     * @param $OUTPUT core_renderer
     * @return void
     */
    function handle(core_renderer $OUTPUT)
    {
        global $STD_FIELDS, $DB, $CFG;

        $data = $this->get_data(false); // no magic quotes here!!!
        if ($data === NULL) {
            return;
        }
        // Print the header
        $headingstr = '';

        $headingstr .= 'All done!';

        echo $OUTPUT->heading($headingstr);

        //$usersCreated = $this->process_Row();

        //echo $OUTPUT->box_start('generalbox uploadresults');
        echo '<p>';
        echo 'Users processed: ' . int($usersCreated) . '</p>';

        //echo $OUTPUT->box_end();

        echo 'This process may take a long time, please be patient.....';
        $this->next = true;

    }

    public function definition_after_data(){

        $usersCreated = $this->process_Row();

        if (!empty($usersCreated)){

            echo 'Finished ... <p>';
            echo 'Users Processed: ' . $usersCreated . '</p>';

            //$this->add_action_buttons(false, 'Continue');
            $this->display();

        }
    }


// process the enrollments
    private function process_Row()
    {
        global $DB;

        if (!empty($_POST['readonlyuser'])) {
            $role_id = $_POST['readonlyuser'];
            $assigntype = $_POST['assigntype'];
        }

        else if (empty($role_id ) && (!isset($assigntype )))
            return;

        // get recordset to deal with memory issue warning, exclude moodle-support user from assigning readonly mode
        $sql = "SELECT * FROM {user} WHERE username <> 'moodle-support' and not deleted";
        $users = $DB->get_recordset_sql($sql);
        $line_number = 0;


        $context = context_system::instance();

        // process users
        if  ( $_POST['assigntype'] == 1)
            foreach ($users as $user) {
                $role_assignment_id = role_assign($role_id, $user->id, $context->id);
                 if (!empty($role_assignment_id)) {
                   $line_number++;
            } else {
                   error('created', 'Error : assigning user: '. $user->id );
            }
        }
        else {
            foreach ($users as $user) {
                role_unassign($role_id, $user->id, $context->id);
                $line_number++;
            }
        }
        return $line_number;
    }

}

class admin_readonly_site_mode_form_final extends abstract_multiple_form
{

    /**
     * @return void
     */
    function definition()
    {
        global $CFG;

        //no editors here - we need proper empty fields
        $CFG->htmleditor = null;

        // hidden fields
        $this->_form->addElement('hidden', 'cat_form_action', 'form3');
        $this->_form->setType('cat_form_action', PARAM_RAW);

        $a = $this->_form->getAttributes();
        $a['method'] = 'get';
        $a['action'] = "$CFG->wwwroot/index.php";
        $this->_form->setAttributes($a);

        $this->add_action_buttons(false, 'Finish');

    }


    public function handle(core_renderer $OUTPUT)
    {
        echo $OUTPUT->heading('All done!');
        echo '<p></p>';
        $this->display();
        $this->displayFooter($OUTPUT);

    }


}


