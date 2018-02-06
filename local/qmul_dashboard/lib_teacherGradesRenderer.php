<?php
/**
 * Version details.
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

/**
 * Returns HTML ready to rendered
 *
 * @param $grade
 * @return string
 * @throws coding_exception
 */
function local_qmul_dashboard_renderTeacherGrades($grade,$depth,$course){

    global $OUTPUT,$USER,$DB;

//    $course = new stdClass;
//    $course->courseid = $grade->courseid;
//    $course->userid = $USER->id;

    $RENDER = '';
    $gradeKey = $grade->id;
    $graphTitle = $grade->itemname;
    /*    // user array values to reset array keys
        $courseAllGrades = array_values(local_qmul_dashboard_getRestCourseGrades($course, $grade->itemid));
        // using
        $courseAverage = local_qmul_dashboard_getCourseAverageGrade($courseAllGrades);

        //TODO merge tables and sort by timemodified
        $merged = array_merge($courseAllGrades, array($grade->id => $grade));

        $merged = local_qmul_dashboard_sortGrades($merged);
        //usort($merged,'local_qmul_dashboard_cmpArray');
        $moduleDefined = true;*/


    // check if activity has any grades
    $thisCourse = $DB->get_record('course', array('id'=> $course->courseid));
    $activityGrades = local_qmul_dashboard_getAllCourseActivityGrades($thisCourse,$gradeKey);

    if(!empty($activityGrades)) {

    if ($grade->itemmodule !== 'checklist' && $grade->itemtype !== 'course'
        && !isset($viewPermissions['gradepanel'])) {
        /*
         *  Activities Panel
         */

        $RENDER .=  HTML_WRITER::start_tag('tr');

        // Helper. We dont want to display buttons for each entry
        $displayHistogram = false;

        // Check if category
        if ($grade->itemtype === 'category') {
            $gradeCategory = local_qmul_dashboard_getGradeCategory($grade->iteminstance);

            $gradeCategoryName = $gradeCategory->fullname . ' '
                .get_string('gradeCategoryTotal', 'local_qmul_dashboard');

            $activityName = $OUTPUT->pix_icon('icon', '', 'folder', array('class' => 'moduleIcon'))
                . s($gradeCategoryName);

        } elseif ($grade->itemtype === 'manual') {
            // Check if category
            $activityName = $OUTPUT->pix_icon(
                    'unstealth',
                    '',
                    'subpage',
                    array('class' => 'manualactivity')
                ) . s($graphTitle);
            $displayHistogram = true;

        } else { // activity
            $link = '/' . $grade->itemtype . '/' . $grade->itemmodule
                . '/view.php?id=' . $grade->coursemoduleid;
            $text = $OUTPUT->pix_icon(
                    'icon',
                    '',
                    $grade->itemmodule,
                    array('class' => 'moduleIcon')
                ) . s($graphTitle);

            $activityName = $OUTPUT->action_link($link, $text);
            $displayHistogram = true;
        }

        $RENDER .=  HTML_WRITER::tag('td', $activityName, array('style' => 'padding-left:'.(20+(int)$depth*10).'px;'));

        if(isset($grade->finalgrade)){
            $grade_item = grade_item::fetch(array('id'=>$grade->itemid));//grade_item::fetch_course_item($grade->gradeid);
            $formattedgrade = grade_format_gradevalue($grade->finalgrade, $grade_item);
        }

        $RENDER .=  html_writer::start_tag('td');

        if ($displayHistogram && !isset($viewPermissions['histograms'])) {

            $RENDER .= HTML_WRITER::tag(
                'button',
                'View',//get_string('activityViewHistogram', 'local_qmul_dashboard'),
                array(
                    'class' => 'btn btn-info viewStudents',
                    'type' => 'button',
//                                'data-toggle' => 'modal',
                    'data-target' => '#gradesList' . $gradeKey,
                    'data-key' => '#stlist' . $gradeKey,
                    'data-course' => $course->courseid,
                    'data-backdrop' => "static",
                    'data-keyboard' => "false",
                    'data-itemid' =>  $gradeKey
//$grade
                )
            );

            $RENDER .= html_writer::start_tag(
                'div',
                array(
                    'id' => 'gradesList' . $gradeKey,
                    'class' => 'modal hide fade',
                    'tabindex' => '-1',
                    'role' => 'dialog',
                    'aria-labelledby' => 'modalLabel' . $gradeKey,
                    'aria-hidden' => 'true'
                )
            ); // Histogram modal

            $RENDER .= html_writer::start_tag('div', array('class' => 'modal-header')); // Modal header
            $RENDER .= html_writer::tag(
                'button',
                'x',
                array(
                    'class' => 'close',
                    'type' => 'button',
                    'data-dismiss' => 'modal',
                    'aria-hidden' => 'true'
                )
            );

            $RENDER .= html_writer::tag('h2', $course->fullname, array(
                    'class'=>'text-center'
                )
            );

            $RENDER .= html_writer::tag('h3', $graphTitle, array(
                    'id' => 'modalLabel' . $gradeKey,
                    'class'=>'text-center'
                )
            );

            $RENDER .= html_writer::end_tag('div'); // close Modal header

            $RENDER .= html_writer::start_tag('div', array('class' => 'modal-body')); // Modal Body

            $RENDER .= html_writer::start_tag('div', array('class' => 'progress')); // loading
            $RENDER .= html_writer::start_tag('div', array(
                    'class' => 'progress-bar progress-bar-striped active',
                    'role' => 'progressbar',
                    'aria-valuenow' => '100',
                    'style' => 'width: 100%',
                )
            );
            $RENDER .= html_writer::end_tag('div');
            $RENDER .= html_writer::end_tag('div');

            $RENDER .= html_writer::start_tag('div', array(
                'class' => 'accordion studentlist', 'id' => 'stlist' . $gradeKey)); // Students Accordion

            $RENDER .= html_writer::end_tag('div'); // close Students Accordion

            $RENDER .= html_writer::end_tag('div'); // close Modal Body

            $RENDER .= html_writer::start_tag('div', array('class' => 'modal-footer')); // Modal Footer
            $RENDER .= html_writer::tag(
                'button',
                'Close',
                array(
                    'class' => 'close',
                    'type' => 'button',
                    'data-dismiss' => 'modal',
                    'aria-hidden' => 'true'
                )
            );
            $RENDER .= html_writer::end_tag('div'); // close Modal Footer

        }

        $RENDER .=  html_writer::end_tag('td');

    }
    }
    return $RENDER;

}
