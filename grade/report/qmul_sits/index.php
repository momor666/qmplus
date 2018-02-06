<?php
/**
 * The gradebook qmul_sits report
 *
 * @package   gradereport_qmul_sits
 */

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/qmul_sits/lib.php';

$courseid = required_param('id', PARAM_INT);        // course id
$course   = $DB->get_record('course', array('id' => $courseid));
$download = optional_param('download', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/grade/report/qmul_sits/index.php', array('id'=>$courseid, 'download'=>$download)));

require_login($courseid);
$context = context_course::instance($courseid);

require_capability('gradereport/qmul_sits:view', $context);

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
    $mform = new gradereport_qmul_sits_form();
    $data = $mform->get_data();

    // Generate CSV file
    grade_regrade_final_grades($courseid);

    $filename = '"SITS Marks '.$data->sitsmodule.' ('.$course->idnumber.').csv"';

    //DH changed for garbled unicode bug
    header("Content-Encoding: UTF-8");
    header("Content-Type: text/csv;charset=UTF-8;");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: public");

    $users = array();
    foreach ($data->users as $userid => $status) {
        if ($status == 1) {
            $users[] = $userid;
        }
    }
    $gradeitems = array();
    foreach ($data->gradeitems as $gradeitemid => $status) {
        if ($status == 1) {
            $gradeitems[] = $gradeitemid;
        }
    }

    echo gradereport_qmul_sits_csvdata($course->idnumber, $data->sitsmodule, $users, $gradeitems);
    die;

} else if ($download) {
    $zip = gradereport_qmul_sits_bulk_reports(array($courseid));
    $date = date("r");
    $filename = '"SITS Marks Archive ('.$date.').zip"';
    header("Content-Type: application/zip; filename=$filename");
    header("Content-Disposition: attachment; filename=$filename");
    readfile($zip);
    unlink($zip);
    die;
} else {
    $PAGE->requires->yui_module('moodle-gradereport_qmul_sits-index', 'M.gradereport_qmul_sits.index.init');

    //first make sure we have proper final grades - this must be done before constructing of the grade tree
    print_grade_page_head($courseid, 'report', 'qmul_sits', get_string('pluginname', 'gradereport_qmul_sits'));

    $sitsmodules = local_qmul_sync_plugin::sits_modules($course->idnumber);
    echo get_string('transfergradesfor', 'gradereport_qmul_sits');
    echo html_writer::select($sitsmodules, 'sitsmodule', reset($sitsmodules));
    echo get_string('or', 'gradereport_qmul_sits').' ';
    echo html_writer::link('?download=1&id='.$courseid, get_string('transferall', 'gradereport_qmul_sits'));

    foreach ($sitsmodules as $sitsmodule) {
        echo html_writer::start_tag('div', array('class' => "sitsmodule sitsmodule-$sitsmodule"));
        echo html_writer::tag('h1', $sitsmodule);
        $mform = new gradereport_qmul_sits_form(null, null, 'post', '', array('class' => 'sits'));
        $mform->set_data(array('id' => $courseid, 'sitsmodule' => $sitsmodule));
        $mform->display();
        echo html_writer::end_tag('div');
    }
    echo $OUTPUT->footer();
}
