<?php
/**
 * Bulk transfer SITS marks
 *
 * @package   gradereport_qmul_sits
 */

require_once '../../../config.php';
require_once $CFG->libdir.'/coursecatlib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/qmul_sits/lib.php';

$context = context_system::instance();
require_capability('gradereport/qmul_sits:bulk', $context);

$PAGE->set_url(new moodle_url('/grade/report/qmul_sits/bulk.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

$coursecat = coursecat::get(0);

// Horrible hitting the DB like this, but needed for performance reasons.
$courseids = $DB->get_fieldset_sql("select distinct gi.courseid from {grade_items} gi where (gi.iteminfo rlike '^#[0-9]{1,3}' or gi.iteminfo rlike '^[0-9]{1,3}') and gi.id in (select distinct itemid from {grade_grades} where finalgrade is not null)");

foreach ($courseids as $i => $courseid) {
    // Get rid of any courseids the current user can't transfer marks for.
    $context = context_course::instance($courseid);
    if (!has_capability('gradereport/qmul_sits:view', $context)) {
        unset($courseids[$i]);
        continue;
    }
}

$renderer = $PAGE->get_renderer('gradereport_qmul_sits');
$treeoptions = array('link', 'check', 'noroot');

$PAGE->requires->yui_module('moodle-gradereport_qmul_sits-tree', 'M.gradereport_qmul_sits.tree.init');

if (data_submitted() && $courseids = optional_param('course', array(), PARAM_RAW)) {
    foreach ($courseids as $i => $courseid) {
        // Get rid of any courseids the current user can't transfer marks for.
        $context = context_course::instance($courseid);
        if (!has_capability('gradereport/qmul_sits:view', $context)) {
            unset($courseids[$i]);
            continue;
        }
    }
    $zip = gradereport_qmul_sits_bulk_reports(array_keys($courseids));
    $date = date("r");
    $filename = '"SITS Marks Archive ('.$date.').zip"';
    header("Content-Type: application/zip; filename=$filename");
    header("Content-Disposition: attachment; filename=$filename");
    readfile($zip);
    unlink($zip);
} else {
    echo $OUTPUT->header();
    echo html_writer::start_tag('form', array('method' => 'post'));
    echo $renderer->course_tree($coursecat, $treeoptions, $courseids);
    echo html_writer::tag('input', '', array('type' => 'submit', 'value' => 'submit'));
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
}

