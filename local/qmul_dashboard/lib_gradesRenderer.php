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



/**
 * Returns HTML ready to rendered
 *
 * @param $grade
 * @return string
 * @throws coding_exception
 */
function local_qmul_dashboard_renderGrades($grade,$depth,$course, $permissions = null)
{
    
    global $OUTPUT, $USER, $DB, $COURSE,$CFG, $PAGE;

//    $course = new stdClass;
//    $course->courseid = $grade->courseid;
//    $course->userid = $USER->id;
    $RENDER = '';

    $display = local_qmul_dashboard_isGradeItemVisible($grade);
    // check for view permissions
    $permissionSettings = local_qmul_dashboard_checkCourseModulePermissions($course->category);
    if(!empty($permissionSettings)){
        $viewPermissions = local_qmul_dashboard_buildViewPermissions($permissionSettings);
    }


    if ($display && $grade->itemtype!=='course'  && $grade->itemtype!=='category') {
        $gradeKey = $grade->itemid;
        $graphTitle = $grade->itemname;
        // user array values to reset array keys
        $courseAllGrades = array_values(local_qmul_dashboard_getRestCourseGrades($course, $grade->itemid));
        // using
        $courseAverage = local_qmul_dashboard_getCourseAverageGrade($courseAllGrades);

        //TODO merge tables and sort by timemodified
        $merged = array_merge($courseAllGrades, array($grade->id => $grade));

        $merged = local_qmul_dashboard_sortGrades($merged);
        //usort($merged,'local_qmul_dashboard_cmpArray');
        $moduleDefined = true;

        // Disable course total
        // if ($grade->itemmodule !== 'checklist' && $grade->itemtype !== 'course' && !isset($viewPermissions['gradepanel'])) {
        // Enable course total
        if ($grade->itemmodule !== 'checklist' && !isset($viewPermissions['gradepanel'])) {
            /*
             *  Activities Panel
             */

            $RENDER .= HTML_WRITER::start_tag('tr');

            // Helper. We dont want to display buttons for each entry
            $displayHistogram = false;

            // Check if category
            if ($grade->itemtype === 'category') {
                $gradeCategory = local_qmul_dashboard_getGradeCategory($grade->iteminstance);

                $gradeCategoryName = $gradeCategory->fullname . ' '
                    . get_string('gradeCategoryTotal', 'local_qmul_dashboard');

                $activityName = $OUTPUT->pix_icon('icon', '', 'folder', array('class' => 'moduleIcon'))
                    . s($gradeCategoryName);

                // enable or disalbe histograms
                $displayHistogram = true;

            } elseif ($grade->itemtype === 'course') {
                $activityName = $OUTPUT->pix_icon(
                        'i/agg_mean',
                        '',
                        'moodle',
                        array('class' => 'moduleIcon')
                    ) . s('Course Total');
                $displayHistogram = true;

            } elseif ($grade->itemtype === 'manual') {
                $activityName = $OUTPUT->pix_icon(
                        'unstealth',
                        '',
                        'subpage',
                        array('class' => 'manualactivity')
                    ) . ' '.s($graphTitle);


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

            $RENDER .= HTML_WRITER::tag('td', $activityName, array('style' => 'padding-left:' . (($depth * 3)+20 + (int)$depth * 10)  . 'px;'));

            $grade_item = grade_item::fetch(array('id' => $grade->itemid));//grade_item::fetch_course_item($grade->gradeid);
            $formattedgrade = grade_format_gradevalue($grade->finalgrade, $grade_item);


            $showTurintinBt = false;
            $turnitin = false;
            $tiiassign = false;
            $turnitinSimilarity = null;
            $feedbackfiles = null;

            if ($grade->itemmodule === 'quiz') {
                $grade->feedback .= local_qmul_dashboard_quizFeedback($grade);
            }else if ($grade->itemmodule === 'assign') {

                $contextmodulecontext = new stdClass();
                $assigngrade = new stdClass();

                try {

                    if(!is_null($grade->coursemoduleid)){
                        $contextmodulecontext = context_module::instance($grade->coursemoduleid);
                        $assigngrade = local_qmul_dashboard_getUserAssignGrade($grade->iteminstance,$contextmodulecontext->id, $USER->id);
                    }

                }
                 catch (Exception $e){
                    echo 'Message: ' .$e->getMessage();

                }

                /*
                 * NOTE: that this currently only supports the files and
                 * not online text assign submissions with TII.
                 */
                $turnitinfeedback = '';
                $tiiassign = local_qmul_dashboard_is_tiiv2_assign($grade->coursemoduleid);

                if(is_array($tiiassign)){
                    //this is a tii assignment
                    $tiifiles = local_qmul_dashboard_get_tii_plagairism_files($grade->coursemoduleid, $USER->id);
                    if(!is_null($tiifiles)){

                        $tiisubmissions = [];
                        foreach($tiifiles as $tiifile){

                            $tiifile->tiidivsim = local_qmul_dashboard_render_tii_assign_simlink($tiifile);
                            $tiifile->tiidivgm = local_qmul_dashboard_render_tii_assign_grademarklink($tiifile);

                            array_push($tiisubmissions, $tiifile);
                        }

                        $showTurintinBt = true;

                        /**
                           *    If more that one file, display multiple similarity reports.
                           *    NB: only display if there is a submission file.
                           *
                           */
                        $acceptedsubmissiontypes = array('file', 'text_content');
                        if((count($tiisubmissions) == 1) && (in_array($tiisubmissions[0]->submissiontype, $acceptedsubmissiontypes))){
                            $turnitinSimilarity = $tiisubmissions[0]->tiidivsim;
                            $turnitinfeedback = $tiisubmissions[0]->tiidivgm;

                        }else if((count($tiisubmissions) > 1) && (in_array($tiisubmissions[0]->submissiontype, $acceptedsubmissiontypes))){
                            //more than one file
                            $link = new moodle_url('/mod/assign/view.php', array('id' => $grade->coursemoduleid));
                            $textsim =  get_string('originalityreport', 'local_qmul_dashboard') . ' ';
                            $textgm =  get_string('grademarkreport', 'local_qmul_dashboard') . ' ';
                            $icon = '<span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>';

                            $turnitinsimlink = html_writer::link($link , $textsim . $icon , array('target' => '_blank'));
                            $turnitingmlink = html_writer::link($link , $textgm . $icon , array('target' => '_blank'));
                            $turnitinSimilarity = html_writer::div($turnitinsimlink);
                            $turnitinfeedback = html_writer::div($turnitingmlink);
                        }

                        //setting for preventing report from showing
                        if($tiiassign['plagiarism_show_student_report'] == "0"){
                            $turnitinSimilarity = '';
                        }


                    }
                }



                if(isset($assigngrade->activemethod)) {

                        if ($assigngrade->activemethod == 'rubric'){

                            $gradinginfo = grade_get_grades($course->courseid, 'mod', 'assign', $assigngrade->assignid, $USER->id);
                            if (isset($gradinginfo->items[0])) {
                                $gradingitem = $gradinginfo->items[0];
                                $gradebookgrade = $gradingitem->grades[$USER->id];
                            }

                            $gradingformcontroller = new gradingform_rubric_controller($contextmodulecontext,
                                $assigngrade->component,
                                $assigngrade->areaname,
                                $assigngrade->areaid);

                            $cangrade = false;



                            $gradefordisplay = $gradingformcontroller->render_grade($PAGE,
                                $assigngrade->assigngradeid,
                                $gradingitem,
                                $gradebookgrade->str_long_grade,
                                $cangrade);


                            $grade->feedback .= $gradefordisplay;
                            $grade->feedback = html_writer::div($grade->feedback, 'advancedgradefeedback');
                        }
                        else if($assigngrade->activemethod == 'guide'){
                            $gradinginfo = grade_get_grades($course->courseid, 'mod', 'assign', $assigngrade->assignid, $USER->id);
                            if (isset($gradinginfo->items[0])) {
                                $gradingitem = $gradinginfo->items[0];
                                $gradebookgrade = $gradingitem->grades[$USER->id];
                            }

                            $gradingformcontroller = new gradingform_guide_controller($contextmodulecontext,
                                $assigngrade->component,
                                $assigngrade->areaname,
                                $assigngrade->areaid);

                            $cangrade = false;

                            //debugging
                            //                    $activeinst =  $gradingformcontroller->get_active_instances($assigngrade->assigngradeid);
                            //                    $options = $gradingformcontroller->get_options();

                            $gradefordisplay = $gradingformcontroller->render_grade($PAGE,
                                $assigngrade->assigngradeid,
                                $gradingitem,
                                $gradebookgrade->str_long_grade,
                                $cangrade);

                            $grade->feedback .= $gradefordisplay;
                            $grade->feedback = html_writer::div($grade->feedback, 'advancedgradefeedback');
                            
                            
                            
                        }else{
                            
                        }
                    

                }



                $assignfilefeedback = local_qmul_dashboard_hasUserAssignFeedbackFiles($USER->id, $grade->iteminstance);

                if (!empty($assignfilefeedback)) {
                    $feedbackfiles = local_qmul_dashboard_getUserAssignFeedbackFiles($contextmodulecontext->id, $assignfilefeedback->grade);
                    $feedbackfiles = local_qmul_dashboard_RenderAssignFeedbackFiles($feedbackfiles);
                    $grade->feedback .= "&nbsp;";
                }
            }


            $RENDER .= HTML_WRITER::tag('td', $formattedgrade . $turnitinSimilarity);

            // feedback
            $RENDER .= html_writer::start_tag('td');

            if (null !== $grade->feedback && !empty($grade->feedback)) { // Show feedback button
                $RENDER .= HTML_WRITER::tag(
                    'button',
                    get_string('activityViewFeedback', 'local_qmul_dashboard'),
                    array(
                        'class' => 'btn btn-info',
                        'type' => 'button',
                        'data-toggle' => 'modal',
                        'data-target' => '#feedback' . $gradeKey
                    )
                );

                $RENDER .= html_writer::start_tag(
                    'div',
                    array(
                        'id' => 'feedback' . $gradeKey,
                        'class' => 'modal hide',
                        'tabindex' => '-1',
                        'role' => 'dialog',
                        'aria-labelledby' => 'modalLabel' . $gradeKey,
                        'aria-hidden' => 'true'
                    )
                ); // feedback modal

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
                $RENDER .= html_writer::tag('h3', $graphTitle, array('id' => 'modalLabel' . $gradeKey));
                $RENDER .= html_writer::end_tag('div'); // close Modal header

                $RENDER .= html_writer::start_tag('div', array('class' => 'modal-body')); // Modal Body

                /* $RENDER .=  html_writer::start_tag('div', array('class' => 'span6'));

                 $RENDER .=  html_writer::end_tag('div');*/
                $RENDER .= html_writer::div($grade->feedback, 'gradefeedback');


                // Feedback files
                if($feedbackfiles && !empty($feedbackfiles)){
                    $RENDER .= html_writer::start_tag('div', array('class' => 'panel panel-default'));
                    $RENDER .= html_writer::tag('div', get_string('feedbackfileattachments','local_qmul_dashboard'), array('class' => 'panel panel-heading'));
                    $RENDER .= html_writer::start_tag('div', array('class' => 'panel-body'));
                    $RENDER .= $feedbackfiles;
                    $RENDER .= html_writer::end_tag('div');
                    $RENDER .= html_writer::end_tag('div');
                }
                //=end feedback files




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
                $RENDER .= html_writer::end_tag('div'); // close Modal

            }

            if ($showTurintinBt && $tiiassign) {

                $RENDER .= $turnitinfeedback;


//                $RENDER .= HTML_WRITER::tag(
//                    'a',
//                    get_string('activityTurnitinFeedback', 'local_qmul_dashboard'),
//                    array(
//                        'href' => $CFG->wwwroot . "/mod/turnitintool/view.php?id=$grade->coursemoduleid"
//                            . "&jumppage=grade&userid=$USER->id"
//                            . "&utp=$turnitin->uutp&objectid=$turnitin->submission_objectid",
//                        'class' => 'btn btn-info top5',
//                        'target' => '_blank',
//                        'type' => 'button'
//                    )
//                );
            }

            $RENDER .= html_writer::end_tag('td');
            // End of feedback

            $RENDER .= html_writer::start_tag('td');
            if ($displayHistogram && !isset($viewPermissions['histograms'])) {


                $RENDER .= HTML_WRITER::tag(
                    'button',
                    get_string('activityViewHistogram', 'local_qmul_dashboard'),
                    array(
                        'class' => 'btn btn-info grades' . $gradeKey,
                        'type' => 'button',
                        'data-toggle' => 'modal',
                        'data-target' => '#grades' . $gradeKey
                    )
                );

                $RENDER .= html_writer::start_tag(
                    'div',
                    array(
                        'id' => 'grades' . $gradeKey,
                        'class' => 'modal hide',
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
// add the course code and course and activity title to the title at the top of the modal overlay.
// Give this more emphasis and centre.
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

                $RENDER .= html_writer::tag(
                    'button',
                    'info',
                    array(
                        'class' => 'btn pull-right',
                        'type' => 'button',
                        'data-toggle' => 'collapse',
                        'data-target' => '#collapseInfo'. $gradeKey,
                        'aria-expanded' => 'false',
                        'aria-controls' => 'collapseInfo'. $gradeKey
                    )
                );

                $RENDER .= html_writer::tag(
                    'span',
                    '',
                    array(
                        'class' => 'clearfix',
                    )
                );

                $RENDER .= html_writer::start_tag('div', array('class' => 'collapse','id'=>'collapseInfo'. $gradeKey)); // Modal Body
                $RENDER .= html_writer::div(
                    get_string('graphDescr', 'local_qmul_dashboard'),
                    null,
                    array('class' => 'well top5')
                );
                $RENDER .= html_writer::end_tag('div');

                $RENDER .= html_writer::start_tag('div', array('class'=>'top5'));
                $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                $RENDER .= html_writer::div(
                    '',
                    null,
                    array('id' => 'chart' . $gradeKey, 'class' => 'chartcontainer')
                );
                $RENDER .= html_writer::end_tag('div');

                $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                $RENDER .= html_writer::div(
                    '',
                    null,
                    array('id' => 'chartAgainst' . $gradeKey, 'class' => 'chartcontainer')
                );

                $RENDER .= html_writer::end_tag('div');
                $RENDER .= html_writer::div('', null, array('class' => 'clearfix'));
                $RENDER .= html_writer::end_tag('div');

                // relative & boxplot
                $RENDER .= html_writer::start_tag('div');
                $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                $RENDER .= html_writer::div(
                    '',
                    null,
                    array('id' => 'relativeChart' . $gradeKey, 'class' => 'chartcontainer')
                );
                $RENDER .= html_writer::end_tag('div');


                $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                $RENDER .= html_writer::div(
                    '',
                    null,
                    array('id' => 'boxplot' . $gradeKey, 'class' => 'chartcontainer')
                );

                $RENDER .= html_writer::end_tag('div');
                $RENDER .= html_writer::div('', null, array('class' => 'clearfix'));
                $RENDER .= html_writer::end_tag('div');

                // against all (all grades) & boxplot
                $RENDER .= html_writer::start_tag('div');
                $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                $RENDER .= html_writer::div(
                    '',
                    null,
                    array('id' => 'againstAllGrades' . $gradeKey, 'class' => 'chartcontainer')
                );
                $RENDER .= html_writer::end_tag('div');


                $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                $RENDER .= html_writer::div(
                    '',
                    null,
                    array('id' => 'againstAllGradesBoxPlot' . $gradeKey, 'class' => 'chartcontainer')
                );

                $RENDER .= html_writer::end_tag('div');
                $RENDER .= html_writer::div('', null, array('class' => 'clearfix'));
                $RENDER .= html_writer::end_tag('div');


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

            $RENDER .= html_writer::end_tag('td');

        }

        if (isset($displayHistogram) && !isset($viewPermissions['histograms'])) {

            $colors = '';
            $categories = '';
            $data = '';
            $c = 0;

            if (($grade->grademax % 10) === 0) {
                $groupStep = $grade->grademax / 10;
            } else if (($grade->grademax % 9) === 0) {
                $groupStep = $grade->grademax / 9;
            } else if (($grade->grademax % 8) === 0) {
                $groupStep = $grade->grademax / 8;
            } else if (($grade->grademax % 7) === 0) {
                $groupStep = $grade->grademax / 7;
            } else if (($grade->grademax % 6) === 0) {
                $groupStep = $grade->grademax / 6;
            } else if (($grade->grademax % 5) === 0) {
                $groupStep = $grade->grademax / 5;
            } else if (($grade->grademax % 4) === 0) {
                $groupStep = $grade->grademax / 4;
            } else if (($grade->grademax % 3) === 0) {
                $groupStep = $grade->grademax / 3;
            } else if (($grade->grademax % 2) === 0) {
                $groupStep = $grade->grademax / 2;
            } else {
                $groupStep = 1;
            }

            $groupRange = array(0 => array());

            for ($i = $groupStep; $i <= $grade->grademax; $i += $groupStep) {
                $groupRange[$i] = array();

                // fix to eternal loop
                if ($grade->grademax == 0) {
                    break;
                }
            }


            // For againstAll Box Plot
            $againstAlLBoxPlotColors = '';
            $againstAlLBoxPlotCategories = '';
            $againstAlLBoxPlotData = '';
            $displayAgainstAlLBoxPlot = false;
            $minGrade = 37000;
            $maxGrade = -37000;
            if (count($merged) >= 5) {
                $displayAgainstAlLBoxPlot = true;
            }
            $totalMerged = count($merged);
            $mergedHalf = round($totalMerged / 2) - 1;
            $q1k = round($mergedHalf / 2);
            $q2k = $q1k + 1;
            $q3k = round(($totalMerged / 100) * 75) - 1;
            $q4k = $q3k + 1;
            $medianKey = false;
            $q1Key = false;
            $q2Key = false;
            $q3Key = false;
            $q4Key = false;
            $c = 0;
            if ($grade->gradeid == 308425) {
                $t = 1;
            }
            foreach ($merged as $userid => $finalgrade) {

                $comma = '';

                if ($c) {
                    $comma = ',';
                }

                if ($c == $q1k && !$q1Key) {
                    $q1Key = $userid;
                } else if ($c == $q2k && !$q2Key) {
                    $q2Key = $userid;
                } else if ($c == $q3k && !$q3Key) {
                    $q3Key = $userid;
                } else if ($c == $q4k && !$q4Key) {
                    $q4Key = $userid;
                }
                if ($c == $mergedHalf && !$medianKey) {
                    $medianKey = $userid;
                }

                $c++;
                $belong = false;

                if ((int)$grade->userid === $userid) {
                    $colors .= $comma . "'#7cb5ec'";
                    $categories .= $comma . "'You'";
                    $belong = true;
                } else {
                    $colors .= $comma . "'#222'";
                    $categories .= $comma . "'$c'";
                }

                $againstAllColors = $colors;
                $againstAllCategories = $categories;

                $groupKey = 0;
                if($groupStep!=0){
                    $groupKey = floor($finalgrade / $groupStep) * $groupStep;
                }

                $groupRange[$groupKey]['grades'][] = $finalgrade;

                if ($belong) {
                    $groupRange[$groupKey]['belong'] = $belong;
                }

                $data .= $comma . round($finalgrade, 2);
                $againstAllData = $data;

//                    if ($displayAgainstAlLBoxPlot) {
                if ($finalgrade <= $minGrade && $finalgrade > 0) {
                    $minGrade = $finalgrade;
                }

                if ($finalgrade >= $maxGrade) {
                    $maxGrade = $finalgrade;
                }
//                    }
            }


            $againstAlLBoxPlotData = '[]';

            if(isset($merged[$q1Key]) && isset($merged[$q2Key])){
                $Q1 = ($merged[$q1Key] + $merged[$q2Key]) / 2;
                $Q2 = $merged[$medianKey];

                if (isset($merged[$q4Key])) {
                    $Q3 = ($merged[$q2Key] + $merged[$q4Key]) / 2;
                } else { // when we do not have enough values to calculate the boxplot
                    $Q3 = $Q2;
                }

                // $Q2 = ($merged[round($mergedHalf/2)]+$merged[round(($mergedHalf/2)+1)]) / 2;

                $againstAlLBoxPlotData = '['
                    . ($minGrade) . ','
                    . ($Q1) . ','
                    . ($Q2) . ','
                    . ($Q3) . ','
                    . ($maxGrade) . ']';
            }


            $totalStudentGrades = count($merged);
            $categories = '';
            $comma = '';
            $data = '';
            $colors = '';
            $maxPercentage = 0;
            $rangeFrom = 0;
            end($groupRange);
            $lastGroupKey = key($groupRange);
            reset($groupRange);

            $relativeData = '';
            $relativeCategories = '';
            $boxPlotData = '';
            $boxPlotCategories = '';

            $toGroup = 0.1;
            if ($grade->grademax > 10) {
                $toGroup = 1;
            }


            $rangeH = round(count($groupRange) / 2);
            $q1h = round($rangeH / 2) - 1;
            $q2h = $q1h + 1;
            $q3h = $rangeH + $q2h - 1;
            $q4h = $q3h + 1;
            $q1hdefined = false;
            $q2hdefined = false;
            $q3hdefined = false;
            $q4hdefined = false;
            $median = false;
            $c = 0;
            foreach ($groupRange as $groupkey => $group) {


                if ($c == 0) {
                    $boxplotMin = $groupkey;
                    $boxplotMax = $groupkey;
                }

                //boxplot calculations
                if ($groupkey <= $boxplotMin) {
                    $boxplotMin = $groupkey;
                }

                if ($groupkey > $boxplotMax) {
                    $boxplotMax = $groupkey;
                }

                if ($c == $q1h && !$q1hdefined) {
                    $q1h = $groupkey;
                    $q1hdefined = true;
                } else if ($c == $q2h && !$q2hdefined) {
                    $q2h = $groupkey;
                    $q2hdefined = true;
                } else if ($c == $q3h && !$q3hdefined) {
                    $q3h = $groupkey;
                    $q3hdefined = true;
                } else if ($c == $q4h && !$q4hdefined) {
                    $q4h = $groupkey;
                    $q3hdefined = true;
                } else if ($c == $rangeH && !$median) {
                    $median = $groupkey;
                }
                $c++;

                if ($categories !== '') {
                    $comma = ',';
                }

                // groupkey is 0
                $toRange = (double)$groupkey - $toGroup;

                if (!$toRange) {
                    $toRange = $groupkey;
                }

                if ($groupkey === $rangeFrom) {
                    $categories .= "'$rangeFrom - ";
                } else if ($lastGroupKey === $groupkey) {
                    $categories .= "$toRange'" . $comma . "'$groupkey'";
                } else {
                    $categories .= "$toRange'" . $comma . "'$groupkey - ";
                }


                if (isset($group['belong']) && $group['belong'] === true) {
                    $colors .= $comma . "'#7cb5ec'";
                } else {
                    $colors .= $comma . "'#222'";
                }


                $totalGroupGrades = 0;
                if(isset($group['grades'])){
                    $totalGroupGrades = count($group['grades']);
                }

                $groupRange[$groupkey]['percentage'] = round(
                    ($totalGroupGrades / $totalStudentGrades) * 100, 2
                );

                if ($groupRange[$groupkey]['percentage'] >= $maxPercentage) {
                    $maxPercentage = $groupRange[$groupkey]['percentage'];
                }

                $data .= $comma . $groupRange[$groupkey]['percentage'];

                $groupRange[$groupkey]['relative'] = number_format($totalGroupGrades / $totalStudentGrades, 2);
                $relativeData .= $comma . $groupRange[$groupkey]['relative'];
                $relativeCategories .= $comma . "'$groupkey'";

            }


            // categories: ['0 - ],
            $stringEnds =  substr($categories, -1);
            if ($stringEnds != "'") {
                $categories =  str_replace("- ","'",$categories);
            }


            $Q1 = ($q1h + $q2h) / 2;
            $Q2 = $median;
            $Q3 = ($q3h + $q4h) / 2;
            // $Q2 = ($merged[round($mergedHalf/2)]+$merged[round(($mergedHalf/2)+1)]) / 2;

            $boxPlotData = '['
                . ($boxplotMin) . ','
                . ($Q1) . ','
                . ($Q2) . ','
                . ($Q3) . ','
                . ($boxplotMax) . ']';



            $avgGradeGraphTitle = "$USER->firstname  $USER->lastname relative to the average"; // get_string('avgGradeGraphTitle', 'local_qmul_dashboard');
            $avgGradeGraphSubTitle = "$course->fullname<br> $graphTitle";
            //$stdGradeGraphTitle = get_string('stdGradeGraphTitle', 'local_qmul_dashboard');
            $stdGradeGraphTitle = "$USER->firstname  $USER->lastname to  ";
            $stdGradeGraphTitle .=  get_string('stdGradeGraphTitle', 'local_qmul_dashboard')." Histogram";

            $finalgrade = grade_format_gradevalue($grade->finalgrade, $grade_item);
            $courseAveragStr = grade_format_gradevalue($courseAverage, $grade_item);
            $rawgrademax = grade_format_gradevalue($grade->grademax, $grade_item);

            $avgGradeGraphTitle = str_replace("'","\'",$avgGradeGraphTitle);
            $avgGradeGraphSubTitle = str_replace("'","\'",$avgGradeGraphSubTitle);
            $stdGradeGraphTitle = str_replace("'","\'",$stdGradeGraphTitle);

            $RENDER .= <<<EOT
                            <script>

                                $(function () {
                                    var buttonTheme = {
                                        symbolStroke: "white",
                                        theme: {
                                            fill:"white",
                                            states: {
                                                hover: {
                                                    stroke: '#222',
                                                    fill: '#000',
                                                    color: '#fff',
                                                },
                                                select: {
                                                    stroke: '#222',
                                                    fill: '#000',
                                                    color: '#fff',
                                                }
                                            }
                                        }
                                    };

                                    //todo make exports button prototype



                                    $('.grades$gradeKey').on('click', function () {

                                                                       /**
         * Sand-Signika theme for Highcharts JS
         * @author Torstein Honsi
         */

        // Load the fonts
        Highcharts.createElement('link', {
           href: '//fonts.googleapis.com/css?family=Signika:400,700',
           rel: 'stylesheet',
           type: 'text/css'
        }, null, document.getElementsByTagName('head')[0]);

        // Add the background image to the container
        Highcharts.wrap(Highcharts.Chart.prototype, 'getContainer', function (proceed) {
           proceed.call(this);
           this.container.style.background = 'url(http://www.highcharts.com/samples/graphics/sand.png)';
           this.container.style.width = 'auto';
        });


        Highcharts.theme = {
           colors: ["#7cb5ec", "#222", "#8d4654", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee",
              "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"],
           chart: {
              backgroundColor: null,
              style: {
                 fontFamily: "Signika, serif"
              }
           },
           title: {
              style: {
                 color: 'black',
                 fontSize: '16px',
                 fontWeight: 'bold'
              }
           },
           subtitle: {
              style: {
                 color: 'black'
              }
           },
           tooltip: {
              borderWidth: 0
           },
           legend: {
              itemStyle: {
                 fontWeight: 'bold',
                 fontSize: '13px'
              }
           },
           xAxis: {
              labels: {
                 style: {
                    color: '#6e6e70'
                 }
              }
           },
           yAxis: {
              labels: {
                 style: {
                    color: '#6e6e70'
                 }
              }
           },
           plotOptions: {
              series: {
                 shadow: true
              },
              candlestick: {
                 lineColor: '#404048'
              },
              map: {
                 shadow: false
              }
           },

           // Highstock specific
           navigator: {
              xAxis: {
                 gridLineColor: '#D0D0D8'
              }
           },
           rangeSelector: {
              buttonTheme: {
                 fill: 'white',
                 stroke: '#C0C0C8',
                 'stroke-width': 1,
                 states: {
                    select: {
                       fill: '#D0D0D8'
                    }
                 }
              }
           },
           scrollbar: {
              trackBorderColor: '#C0C0C8'
           },

           // General
           background2: '#E0E0E8'

        };

        // Apply the theme
        Highcharts.setOptions(Highcharts.theme);

                                        $('#chart$gradeKey').highcharts({
                                            chart: {
                                                type: 'bar'
                                            },
                                            title: {
                                                text: '$avgGradeGraphTitle',
                                                align: "left"
                                            },
                                            subtitle: {
                                                text: '$avgGradeGraphSubTitle',
                                                align: "left"
                                            },
                                            xAxis: {
                                                categories: ['Grade']
                                            },
                                            yAxis: {
                                                title: {
                                                    text: 'Range'
                                                },
                                                tickInterval: $grade->grademax/20,
                                                max:  $grade->grademax
                                            },
                                            series: [{
                                                name: 'You $finalgrade',
                                                data: [$grade->finalgrade]
                                            }, {
                                                name: 'Students Average Grade $courseAveragStr',
                                                data: [$courseAverage]
                                            }],
                                            exporting: {
                                                buttons: {
                                                    /*contextButton: {
                                                     symbol: 'url(http://geodev.grid.unep.ch/images/button_download.png)'
                                                     },*/
                                                    contextButton: {
                                                        text: 'Export',
                                                        symbol: null
                                                    }
                                                }
                                            }
                                        });

                                        $('#chartAgainst$gradeKey').highcharts({
                                            chart: {
                                                type: 'column'
                                            },
                                            plotOptions: {
                                                column: {
                                                    colorByPoint: true
                                                }
                                            },
                                            colors: [$colors],
                                            title: {
                                                text: "$stdGradeGraphTitle",
                                                align: "left"
                                            },
                                             subtitle: {
                                                text: '$avgGradeGraphSubTitle',
                                                align: "left"
                                            },
                                            xAxis: {
                                                categories: [$categories],
                                                title: {
                                                    text: ''
                                                }
                                            },
                                            yAxis: {
                                                min: 1,
                                                max:  $maxPercentage,
                                                title: {
                                                    text: 'Proportion of Students (% of course)'
                                                }
                                            },
                                            series: [{
                                                name: 'Coursework mark range (%)',
                                                data: [$data]
                                            }],
                                            exporting: {
                                                buttons: {
                                                    /*contextButton: {
                                                     symbol: 'url(http://geodev.grid.unep.ch/images/button_download.png)'
                                                     },*/
                                                    contextButton: {
                                                        text: 'Export',
                                                        symbol: null
                                                    }

                                                }
                                            }
                                        });



/*
*
*
*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************
*    HIDE OTHER GRAHPS FOR NOW
*
*





                                        $('#relativeChart$gradeKey').highcharts({
                                            chart: {
                                                type: 'column'
                                            },
                                            plotOptions: {
                                                column: {
                                                    colorByPoint: true
                                                }
                                            },
                                            colors: [$colors],
                                            title: {
                                                text: "$stdGradeGraphTitle"
                                            },
                                            xAxis: {
                                                categories: [$relativeCategories],
                                                title: {
                                                    text: ''
                                                }
                                            },
                                            yAxis: {
                                                title: {
                                                    text: 'Relative Frequency(percentages)'
                                                }
                                            },
                                            series: [{
                                                name: 'Score',
                                                data: [$relativeData]
                                            }],
                                            exporting: {
                                                buttons: {
                                                    // contextButton: {
                                                    // symbol: 'url(http://geodev.grid.unep.ch/images/button_download.png)'
                                                    // },
                                                    contextButton: {
                                                        enabled: false
                                                    },
                                                    exportButton: {
                                                        text: 'Download',
                                                        symbolStroke: "white",
                                                        theme: {
                                                            fill:"white",
                                                            states: {
                                                                hover: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                },
                                                                select: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                }
                                                            }
                                                        },

                                        // Use only the download related menu items from the default context button
                                        menuItems: Highcharts.getOptions().exporting.buttons.contextButton.menuItems.slice(2)
                                                    },
                                                    printButton: {
                                                        text: 'Print',
                                                        symbolStroke: "white",
                                                        theme: {
                                                            fill:"white",
                                                            states: {
                                                                hover: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                },
                                                                select: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                }
                                                            }
                                                        },
                                                        onclick: function () {
                                                            this.print();
                                                        }
                                                    }
                                                }
                                            }
                                        });


                                        $('#boxplot$gradeKey').highcharts({
                                            chart: {
                                                type: 'boxplot'
                                            },

                                            title: {
                                                text: 'Box Plot'
                                            },

                                            legend: {
                                                enabled: false
                                            },

                                            xAxis: {
                                                categories: [$boxPlotCategories],
                                                title: {
                                                    text: 'Experiment No.'
                                                }
                                            },



                                            series: [{
                                                name: 'Grade Distribution',
                                                data: [$boxPlotData],
                                                tooltip: {
                                                    headerFormat: ''
                                                }
                                            }]

                                        });



                                        $('#againstAllGrades$gradeKey').highcharts({
                                            chart: {
                                                type: 'column'
                                            },
                                            plotOptions: {
                                                column: {
                                                    colorByPoint: true
                                                }
                                            },
                                            colors: [$againstAllColors],
                                            title: {
                                                text: "All $stdGradeGraphTitle"
                                            },
                                            xAxis: {
                                                categories: [$againstAllCategories],
                                                title: {
                                                    text: ''
                                                }
                                            },
                                            yAxis: {
                                                title: {
                                                    text: 'Relative Frequency(percentages)'
                                                }
                                            },
                                            series: [{
                                                name: 'Score',
                                                data: [$againstAllData]
                                            }],
                                            exporting: {
                                                buttons: {
                                                    // contextButton: {
                                                    // symbol: 'url(http://geodev.grid.unep.ch/images/button_download.png)'
                                                    // },
                                                    contextButton: {
                                                        enabled: false
                                                    },
                                                    exportButton: {
                                                        text: 'Download',
                                                        symbolStroke: "white",
                                                        theme: {
                                                            fill:"white",
                                                            states: {
                                                                hover: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                },
                                                                select: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                }
                                                            }
                                                        },

                                        // Use only the download related menu items from the default context button
                                        menuItems: Highcharts.getOptions().exporting.buttons.contextButton.menuItems.slice(2)
                                                    },
                                                    printButton: {
                                                        text: 'Print',
                                                        symbolStroke: "white",
                                                        theme: {
                                                            fill:"white",
                                                            states: {
                                                                hover: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                },
                                                                select: {
                                                                    stroke: '#222',
                                                                    fill: '#000',
                                                                    style: {
                                                                        color: '#fff'
                                                                    }
                                                                }
                                                            }
                                                        },
                                                        onclick: function () {
                                                            this.print();
                                                        }
                                                    }
                                                }
                                            }
                                        });


                                        $('#againstAllGradesBoxPlot$gradeKey').highcharts({
                                            chart: {
                                                type: 'boxplot'
                                            },

                                            title: {
                                                text: 'Box Plot'
                                            },

                                            legend: {
                                                enabled: false
                                            },

                                            xAxis: {
                                                categories: [$againstAlLBoxPlotCategories],
                                                title: {
                                                    text: 'Experiment No.'
                                                }
                                            },
                                            series: [{
                                                name: 'Grade Distribution',
                                                data: [$againstAlLBoxPlotData],
                                                tooltip: {
                                                    headerFormat: ''
                                                }
                                            }]

                                        });

*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************
*    ****************************************************************************/


                                    });


                                });
                            </script>
EOT;
        }
    }
    return $RENDER;
}