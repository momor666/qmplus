<?php

require_once($CFG->libdir.'/formslib.php');

class local_qmul_messaging_compose_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        $roles = $this->_customdata['roles'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'context');
        $mform->setType('context', PARAM_INT);

        $mform->addElement('hidden', 'sesskey');
        $mform->setType('sesskey', PARAM_RAW);

        $mform->addElement('header', 'messagesection', get_string('messagesection', 'local_qmul_messaging'));


        $attributes='rows="2" cols="75" maxlength="150"';
        $mform->addElement('textarea', 'subject', get_string('subject', 'local_qmul_messaging'), $attributes);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->setType('subject', PARAM_RAW);

        $mform->addElement('editor', 'message_editor', get_string('message', 'message'), null, $this->_customdata['editoroptions']);
        $mform->addRule('message_editor', null, 'required', null, 'client');
        $mform->setType('message_editor', PARAM_RAW);

        $mform->addElement('header', 'advancedsection', get_string('advancedsection', 'local_qmul_messaging'));

        $mform->addElement('date_time_selector', 'validfrom', get_string('validfrom', 'local_qmul_messaging'), array('optional' => true));
        $mform->addElement('duration', 'validfor', get_string('validfor', 'local_qmul_messaging'), array('optional' => true));
        $mform->disabledIf('validfor', 'validfrom[enabled]');

        $hideordelete[] = &$mform->createElement('radio', 'hideordelete', '', get_string('hide'), local_qmul_messaging::HIDE);
        $hideordelete[] = &$mform->createElement('radio', 'hideordelete', '', get_string('delete'), local_qmul_messaging::DELETE);
        $mform->addGroup($hideordelete, 'hideordelete', get_string('onexpiry', 'local_qmul_messaging'), array(' '), false);
        $mform->setDefault('hideordelete', local_qmul_messaging::HIDE);

        $mform->addElement('select', 'roles', get_string('roles'), $roles);
        $mform->addRule('roles', null, 'required', null, 'client');
        $mform->getElement('roles')->setMultiple(true);

        $this->add_action_buttons();
    }

    function add_action_buttons($cancel = true, $submitlabel = NULL) {

        $submitlabel = get_string('save', 'local_qmul_messaging');

        $mform = $this->_form;

        // elements in a row need a group
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'save', $submitlabel);

        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
    }

    function set_data($data) {
        global $DB;

        $data = (array)$data;
        if (!isset($data['roles'])) {
            $data['roles'] = $DB->get_fieldset_select('role', 'id', '');
        } else {
        }

        parent::set_data($data);
    }

    function get_data() {

        $data = parent::get_data();
        if ($data) {
            $data->sendbyemail = optional_param('saveandemail', false, PARAM_BOOL);
        }
        return $data;
    }
}
