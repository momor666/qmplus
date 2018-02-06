<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 27/06/2017
 * Time: 15:25
 * QM+ Activities reporting plugin
 */

function show_students($students){
    global $string_student,$string_state;
    $html = '';
    if(count($students) > 0 ){
       $html = '';
        $html .= $string_state.'&emsp;'.$string_student.'<br/>';
        foreach($students as $student) {
            $html .= ( is_null( $student->state )  ? 'N/A' : $student->state ).'&emsp;'.html_writer::link(
                    new moodle_url('/user/profile.php',array('id' => (int)$student->id))
                    , $student->firstname . ' '.$student->middlename. ' '.$student->lastname.
                    ' ('.$student->username.')<br/>');
        }
    }
    return $html;
}

/** @noinspection UntrustedInclusionInspection */
require_once  '../../config.php';
defined('MOODLE_INTERNAL') || die;
require_once(__DIR__. '/locallib.php');

$error = null;
$module   = optional_param('module','', PARAM_ALPHA);
$id       = optional_param('id',0, PARAM_INT);
$students = null;

$sql = 'SELECT cm.id,course courseid , mo.name, cm.instance FROM {course_modules} cm join {modules} mo ON mo.id = cm.module AND mo.name = :module AND instance = :id';
try {
    $cm = $DB->get_record_sql($sql, array( 'module' => $module, 'id' => (int) $id));
} catch (Error $error){

} catch (Throwable $throwable){

} catch (Exception $exception){

}
if($cm){
    $activity = $DB->get_record($cm->name,array('id' => $cm->instance));
    $course = $DB->get_record('course',array('id'=>$cm->courseid));
}
$urlparams  = array();
$PAGE->set_url('/local/qm_activities/activity_students.php', $urlparams);
// set the page context to the course reporting about so it is restricted to the registered users for the course
if($cm){
    $PAGE->set_context(context_course::instance( (int)$course->id) );
} else {
    $PAGE->set_context(context_system::instance());
}
$PAGE->set_title( $string_report_page_title );

// $PAGE->requires->jquery();
// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);

// records shown for adminitrators and teachers only, not to students
$uid = (int)$USER->id ;
$permission = local_qm_activities_get_report_permission($uid, $id );
// get the records only if permitted
echo $OUTPUT->header();
echo '<strong>'.html_writer::link( ( new moodle_url( $string_menu ) ) ,$string_back_to_menu.'</strong><br /><br />');
if($permission == true){
    $students = local_qm_activities_get_course_module_submissions( $module ,$id );
    if(count($students) > 0
        && ( ( isset($students['undone']) && count($students['undone']) > 0 )
            || ( isset($students['done']) && count($students['done']) > 0 ) ) ){
        echo html_writer::link(new moodle_url('/course/view.php',array('id' => (int)$course->id)),$course->fullname).'<br />';
        echo html_writer::link(new moodle_url('/mod/'.$module.'/view.php',array('id' => (int)$cm->id)),$activity->name).'<br />';

        echo '<br/><table><thead><tr>';
        echo '<th>'.$string_pending.'</th>';
        echo '<th>'.$string_submitted.'</th>';
        echo '</tr></thead><tbody><tr>';
        echo '<td style="text-align: left; vertical-align: text-top;">'.show_students($students['undone']).'</td>';
        echo '<td style="text-align: left; vertical-align: text-top;">'.show_students($students['done']).'</td>';
        echo '</tr></tbody></table>';
    } else {
        echo $string_no_students_found;
    }
} else {
    echo $string_request_not_permitted;
}
echo $OUTPUT->footer();

