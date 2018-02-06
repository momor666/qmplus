<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 26/06/2017
 * Time: 13:23
 * QM+ Activities reporting plugin
 */

/** @noinspection UntrustedInclusionInspection */
require_once  '../../config.php';
defined('MOODLE_INTERNAL') || die;
require_once(__DIR__. '/locallib.php');
$urlparams  = array();
$PAGE->set_url('/local/qm_activities/course_students.php', $urlparams);
$PAGE->set_context(context_system::instance());
// $PAGE->requires->jquery();
// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);
$error = null;
$mode   = optional_param('mode','', PARAM_ALPHA);
$id     = optional_param('id',0, PARAM_INT);
$from   = optional_param('from', 0,PARAM_INT);
$to     = optional_param('to', 0, PARAM_INT);
if(local_qm_activities_is_an_admin($USER->id) && in_array($mode,array('new','school','category','course','teacher','student'))) {
    $student_ids = local_qm_activities_get_courses_students( array( $id ) );
    if(count($student_ids) > 0){
        $students = $DB->get_records_sql('SELECT id, firstname, middlename, lastname, username FROM {user} u WHERE u.id IN ('.implode(',',$student_ids).') ORDER BY 2,4');
        unset($student_ids);
        $data_array = array();
        foreach($students as $sid => $student){
            $data_array[ (int)$student->id ] = $student->firstname . ' '. $student->middlename. ' '. $student->lastname .' ('.$student->username.')';
        }
        echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'student' , $no_choice = $string_select_student , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
        unset($data_array);
        unset($students);
    } else {
        echo $string_no_students_found.'<br /><br />';
    }

}