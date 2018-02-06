<?php

class gradereport_qmul_sits_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sitsmodule');
        $mform->setType('sitsmodule', PARAM_ALPHANUMEXT);

        $mform->addElement('html', html_writer::tag('div', '<ul></ul>', array('class' => 'errors')));
        $mform->addElement('html', html_writer::tag('div', 'Summary', array('class' => 'summary')));
        $this->add_action_buttons(false, get_string('downloadreport', 'gradereport_qmul_sits'));

        // Prepare the select all / none snippet
        $selectallnone  = html_writer::link('#', 'Select all', array('class' => 'select-all'));
        $selectallnone .= ' ';
        $selectallnone .= html_writer::link('#', 'Select none', array('class' => 'select-none'));

        $mform->addElement('header', 'userssection', get_string('students'));
        $mform->setExpanded('userssection', false);
        $mform->addElement('static', 'usersselectallnone', '', $selectallnone);
        $mform->addElement('group', 'users', '', array());

        $mform->addElement('header', 'gradeitemssection', get_string('gradeitems', 'grades'));
        $mform->setExpanded('gradeitemssection', false);
        $mform->addElement('static', 'editgradeitems', '', '');
        $mform->addElement('static', 'gradeitemsselectallnone', '', $selectallnone);
        $mform->addElement('group', 'gradeitems', '', array());
    }

    public function definition_after_data() {
        global $DB;

        $mform =& $this->_form;

        $courseid = $mform->getElement('id')->getValue();
        $course = $DB->get_record('course', array('id' => $courseid));
        $context = context_course::instance($courseid);

        $sitsmodule = $mform->getElement('sitsmodule')->getValue();

        // Show the potential students
        $enrolments = local_qmul_sync_plugin::sits_module_enrolments($sitsmodule, $course->idnumber);
        $elements = array();
        $bad = array();
        foreach ($enrolments as $enrolment) {
            $user = $DB->get_record('user', array('idnumber' => $enrolment->user_id));
            $desc = fullname($user).' ('.$enrolment->user_id.')';
            if (array_key_exists($enrolment->user_id, $elements)) {
                $desc .= get_string('user_has_duplicate', 'gradereport_qmul_sits');
                $element = $mform->createElement('advcheckbox', $user->id, '', $desc, array('disabled' => 'disabled', 'class' => 'error user'));
            } else if ($enrolment->stuprog_code == 'COMPLETED') {
                $desc .= get_string('user_completed', 'gradereport_qmul_sits');
                $element = $mform->createElement('advcheckbox', $user->id, '', $desc, array('disabled' => 'disabled', 'class' => 'error user'));
            } else {
                $desc = get_string('user_transferable', 'gradereport_qmul_sits'). $desc;
                $element = $mform->createElement('advcheckbox', $user->id, '', $desc, array('class' => 'user'));
                $element->setChecked(true);
            }
            $elements[$enrolment->user_id] = $element;
        }
        $enrolments->close();

        $mform->getElement('users')->setElements(array_values($elements));

        // Create a link to edit the QMplus => SITS mappings
        if (has_capability('gradereport/qmul_sits:edit', $context)) {
            $editurl = new moodle_url('edit_mappings.php', array('id' => $courseid, 'module' => $sitsmodule));
            $mform->getElement('editgradeitems')->setValue(html_writer::link($editurl, get_string('edit_mappings', 'gradereport_qmul_sits')));
        }

        // Show the potential grade items
        $sitsassessments = local_qmul_sync_plugin::sits_assessments($sitsmodule);

        $gradetree = new grade_tree($course->id);
        $elements = array();
        foreach ($gradetree->items as $gradeitem) {
            if ($gradeitem->itemtype == 'mod' || $gradeitem->itemtype == 'manual') {
                if ($invalid = gradereport_qmul_sits_has_invalid_assessment($gradeitem)) {
                    $extra = $invalid;
                    $element = $mform->createElement('advcheckbox', $gradeitem->id, '', $gradeitem->itemname.$extra, array('disabled' => 'disabled', 'class' => 'gradeitem'));
                } else {
                    $mab_code = gradereport_qmul_sits_get_assessment($gradeitem);
                    $extra = get_string('e_assessment_assigned', 'gradereport_qmul_sits') . $mab_code;
                    $element = $mform->createElement('advcheckbox', $gradeitem->id, '', $gradeitem->itemname.$extra, array('class' => 'gradeitem'));
                    $element->setChecked(true);
                }
                $elements[] = $element;
            }
        }
        $mform->getElement('gradeitems')->setElements($elements);
    }
}
