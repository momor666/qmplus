<?php
/**
 * The gradebook qmul_sits mappings editor
 *
 * @package   gradereport_qmul_sits
 */

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/qmul_sits/lib.php';

$courseid      = required_param('id', PARAM_INT);        // course id

$PAGE->set_url(new moodle_url('/grade/report/qmul_sits/edit_mappings.php', array('id'=>$courseid)));

require_login($courseid);
$context = context_course::instance($courseid);

require_capability('gradereport/qmul_sits:edit', $context);

/// last selected report session tracking
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$courseid] = 'qmul_sits';

/**
 * This rather odd pattern is required because creating the form for display before
 * print_grade_page_head will garble page layout.
 */
if (data_submitted()) {
    $mform = new gradereport_qmul_sits_mappings_form();
    if ($data = $mform->get_data()) {
        gradereport_qmul_sits_save_mappings($data->gradeitems);
    }
    $reporturl = new moodle_url('index.php', array('id' => $courseid));
    redirect($reporturl);
}

//first make sure we have proper final grades - this must be done before constructing of the grade tree
print_grade_page_head($courseid, 'report', 'qmul_sits', get_string('gradeitems_mappings', 'gradereport_qmul_sits'));
$mform = new gradereport_qmul_sits_mappings_form();
$mform->set_data(array('id' => $courseid));
$mform->display();
echo $OUTPUT->footer();
