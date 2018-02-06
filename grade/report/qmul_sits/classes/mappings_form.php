<?php

class gradereport_qmul_sits_mappings_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'module');
        $mform->setType('module', PARAM_ALPHANUMEXT);
        $this->add_action_buttons(true, get_string('save', 'gradereport_qmul_sits'));
    }

    public function definition_after_data() {
        global $DB;

        $mform =& $this->_form;

        $courseid = $mform->getElement('id')->getValue();
        $course = $DB->get_record('course', array('id' => $courseid));

        $modules = local_qmul_sync_plugin::sits_modules($course->idnumber);
        foreach ($modules as $module) {
            $table = html_writer::start_tag('table', array('class' => 'sits-module-mabcodes'));
            $table .= html_writer::start_tag('thead');
            $table .= html_writer::start_tag('tr');
            $table .= html_writer::tag('th', $module, array('colspan' => 2));
            $table .= html_writer::end_tag('tr');
            $table .= html_writer::end_tag('thead');
            $table .= html_writer::start_tag('tbody');
            foreach (local_qmul_sync_plugin::sits_assessments($module) as $mab_code => $description) {
                $table .= html_writer::start_tag('tr');
                $table .= html_writer::tag('td', $mab_code);
                $table .= html_writer::tag('td', $description);
                $table .= html_writer::end_tag('tr');
            }
            $table .= html_writer::end_tag('tbody');
            $table .= html_writer::end_tag('table');
            $element = &$mform->createElement('html', $table);
            $mform->insertElementBefore($element, 'buttonar');
        }
        $gradetree = new grade_tree($courseid);
        foreach ($gradetree->items as $gradeitem) {
            if ($gradeitem->itemtype == 'mod' || $gradeitem->itemtype == 'manual') {
                $mab_code = gradereport_qmul_sits_get_assessment($gradeitem);
                $element = &$mform->createElement('text', "gradeitems[{$gradeitem->id}]", $gradeitem->itemname);
                $element->setValue($mab_code);
                $mform->insertElementBefore($element, 'buttonar');
            }
        }
    }
}
