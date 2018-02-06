<?php

require_once($CFG->libdir.'/formslib.php');

class local_qmul_messaging_admin_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('text', 'wstoken', get_string('wstoken', 'local_qmul_messaging'), array('size'=>'30'));
        $mform->setType('wstoken', PARAM_RAW);


        $roles = $DB->get_records_menu('role', array(), 'id,name');

        foreach ($roles as $id => $name) {
            $group = array();
            foreach ($roles as $id2 => $name2) {
                $group[] = &$mform->createElement('advcheckbox', "enable[$id][$id2]", $name, $name2);
            }
            $mform->addGroup($group, "enable[$id]", $name, array(' '), false);
        }
        $this->add_action_buttons();
    }
}
