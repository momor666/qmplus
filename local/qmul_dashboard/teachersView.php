<?php

function renderTeacher($course, $courseContext){

    global $USER,$CFG, $OUTPUT;

    $RENDER ='';

        // GET all enrolledusers
//        $enrolledUsers = get_enrolled_users($courseContext, '', 0, 'u.*', 'u.firstname ASC, u.lastname ASC');

        // check for view permissions
        $permissionSettings = local_qmul_dashboard_checkCourseModulePermissions($course->category);
        if(!empty($permissionSettings)){
            $viewPermissions = local_qmul_dashboard_buildViewPermissions($permissionSettings);
        }

        //first make sure we have proper final grades - this must be done before constructing of the grade tree
        //grade_regrade_final_grades($course->courseid);  // It is removed as it was very slow ... Added as task

        $courseGradedActivities = local_qmul_dashboard_getAllCourseGradedActivities($course);

        // Check if course progress config exists
        $courseContext = local_qmul_dashboard_getCourseContext($course->courseid);

        $moduleDefined  = false;
        if ($courseGradedActivities  && !isset($viewPermissions['gradepanel'])) {
        //if ($courseGradedActivities) {
            // print_r($courseGradedActivities);
            $totalGradedActivities = count($courseGradedActivities);
            $gradeCounter = 0;


            $totalGrades = count($courseGradedActivities);
            $gradeCounter = 0;
            $moduleDefined  = false;
            $RENDERGrades  = false;
            foreach ($courseGradedActivities->getCategoriesTree() as $categoryId => $category) {

                $gradeCounter++;

                $RENDERGrades = local_qmul_dashboard_renderCategory($category, 0, false, 'Teachers',$course);

                if($RENDERGrades && !$moduleDefined){
                    // The module Start Wrapper
                    //$graphTitle = 'COURSE '.$course->fullname;
                    /*
                    *  Courses Accordion
                    */
                    $RENDER .=  local_qmul_dashboard_renderModuleHeader($course,'teachers');
                    $RENDER .= html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities panel
                    $RENDER .= html_writer::div(
                        get_string('panelactivitiestitle', 'local_qmul_dashboard'),
                        'panel-heading'
                    );
                    $RENDER .= html_writer::start_tag('div', array('class' => 'panel-body')); // Activities content
                    $RENDER .= html_writer::start_tag(
                        'div',
                        array('class' => 'accordion', 'id' => 'accordion' . $course->courseid)
                    ); // accordion

                    $tableoptions['class'] = 'table table-bordered noNow';
                    $RENDER .= html_writer::start_tag('table', $tableoptions);


                    // if($gradeView==='Students') {
                    // Table headers
                    $RENDER .= html_writer::start_tag('thead');
                    $RENDER .= html_writer::start_tag('tr');
                    $RENDER .= html_writer::tag(
                        'th',
                        get_string('activityColumnName', 'local_qmul_dashboard'),
                        array('class' => 'span9')
                    );
                   /* $RENDER .= html_writer::tag(
                        'th',
                        get_string('activityColumnGrade', 'local_qmul_dashboard'),
                        array('class' => 'span3')
                    );
                    $RENDER .= html_writer::tag(
                        'th',
                        get_string('activityColumnFeedback', 'local_qmul_dashboard'),
                        array('class' => 'span2')
                    );*/

                    if (!isset($viewPermissions['histograms'])) {
                        $RENDER .= html_writer::tag(
                            'th',
                            get_string('activityColumnHistogram', 'local_qmul_dashboard'),
                            array('class' => 'span3')
                        );
                    }

                    /*}else{
                        // Table headers
                        $RENDER .= html_writer::start_tag('thead');
                        $RENDER .= html_writer::start_tag('tr');
                        $RENDER .= html_writer::tag(
                            'th',
                            get_string('activityColumnName', 'local_qmul_dashboard'),
                            array('class' => 'span7')
                        );
                        if (!isset($viewPermissions['histograms'])) {
                            $RENDER .= html_writer::tag(
                                'th',
                                'Students List',//get_string('activityColumnHistogram', 'local_qmul_dashboard'),
                                array('class' => 'span5')
                            );
                        }
                    }*/

                    $RENDER .= html_writer::end_tag('tr');
                    $RENDER .= html_writer::end_tag('thead');
                    $RENDER .= html_writer::start_tag('tbody');



                    $moduleDefined = true;
                    /*
                    ####################### COMMENTED COURSE GRADE TO PREVENT IT  FROM DISPLAYING ######################
                     $RENDER .=  html_writer::start_tag('div', array('class' => 'panel panel-default')); // main panel
                     $RENDER .=  html_writer::div(get_string('panelgrade', 'local_qmul_dashboard'), 'panel-heading');
                     $RENDER .=  html_writer::start_tag('div', array('class' => 'panel-body')); // main content
                     $RENDER .=  html_writer::start_tag('div', array('class' => 'span6'));
                     $RENDER .=  html_writer::div('', null ,array('id'=>'chart'.$gradeKey));
                     $RENDER .=  html_writer::end_tag('div');
                     $RENDER .=  html_writer::start_tag('div', array('class' => 'span6'));
                     $RENDER .=  html_writer::div('', null ,array('id'=>'chartAgainst'.$gradeKey));
                     $RENDER .=  html_writer::end_tag('div');
                     $RENDER .=  html_writer::div('', null ,array('class'=>'clearfix'));
                     $RENDER .=  html_writer::end_tag('div');
                     $RENDER .=  html_writer::end_tag('div');
                    ####################################################################################################
                    */
                }

                $RENDER .= $RENDERGrades;

            }

            if($RENDERGrades){
                $RENDER .=  html_writer::end_tag('tbody');
                $RENDER .=  html_writer::end_tag('table');

                $RENDER .=  html_writer::end_tag('div'); // close main panel
                $RENDER .=  html_writer::end_tag('div'); // course accordion-heading
                $RENDER .=  html_writer::end_tag('div'); // course accordion-group
            }

            $activitiesHeader = false;
            foreach ($courseGradedActivities as $gradeKey => $grade) {
                $gradeCounter++;

                $graphTitle = $grade->itemname;


                if ($grade->itemmodule !== 'checklist' && $grade->itemtype !== 'course'
                    && !isset($viewPermissions['gradepanel'])) {
                    /*
                     *  Activities Panel
                     */

                    if (!$activitiesHeader) {
                        $RENDER .= html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities panel
                        $RENDER .= html_writer::div(
                            get_string('panelactivitiestitle', 'local_qmul_dashboard'),
                            'panel-heading'
                        );
                        $RENDER .= html_writer::start_tag('div', array('class' => 'panel-body')); // Activities content
                        $RENDER .= html_writer::start_tag(
                            'div',
                            array('class' => 'accordion', 'id' => 'accordion' . $course->courseid)
                        ); // accordion


                        $tableoptions['class'] = 'table table-bordered noNow';
                        $RENDER .= html_writer::start_tag('table', $tableoptions);

                        // Table headers
                        $RENDER .= html_writer::start_tag('thead');
                        $RENDER .= html_writer::start_tag('tr');
                        $RENDER .= html_writer::tag(
                            'th',
                            get_string('activityColumnName', 'local_qmul_dashboard'),
                            array('class' => 'span4')
                        );
                        /*$RENDER .= html_writer::tag(
                            'th',
                            get_string('activityColumnGrade', 'local_qmul_dashboard'),
                            array('class' => 'span3')
                        );
                        $RENDER .= html_writer::tag(
                            'th',
                            get_string('activityColumnFeedback', 'local_qmul_dashboard'),
                            array('class' => 'span2')
                        );*/

                        if (!isset($viewPermissions['histograms'])) {
                            $RENDER .= html_writer::tag(
                                'th',
                                'Students List',//get_string('activityColumnHistogram', 'local_qmul_dashboard'),
                                array('class' => 'span3')
                            );
                        }

                        $RENDER .= html_writer::end_tag('tr');
                        $RENDER .= html_writer::end_tag('thead');
                        $RENDER .= html_writer::start_tag('tbody');

                        $activitiesHeader = true;
                    }

                    $RENDER .= HTML_WRITER::start_tag('tr');

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

                    $RENDER .= HTML_WRITER::tag('td', $activityName);

                    $RENDER .= html_writer::start_tag('td');
                    if ($displayHistogram && !isset($viewPermissions['histograms'])) {


                        $RENDER .= HTML_WRITER::tag(
                            'button',
                            'View',//get_string('activityViewHistogram', 'local_qmul_dashboard'),
                            array(
                                'class' => 'btn btn-info viewStudents',
                                'type' => 'button',
//                                'data-toggle' => 'modal',
                                'data-target' => '#grades' . $gradeKey,
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
                        $RENDER .= html_writer::tag('h3', $graphTitle, array('id' => 'modalLabel' . $gradeKey));
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

                        $RENDER .= html_writer::start_tag('div', array('class' => 'accordion studentlist', 'id' => 'stlist' . $gradeKey)); // Students Accordion


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

                    $RENDER .= html_writer::end_tag('td');

                }

                if ($totalGradedActivities === $gradeCounter && $activitiesHeader) {
                    $RENDER .= html_writer::end_tag('tbody');
                    $RENDER .= html_writer::end_tag('table');

                    $RENDER .= html_writer::end_tag('div'); // close main panel
                    $RENDER .= html_writer::end_tag('div'); // course accordion-heading
                    $RENDER .= html_writer::end_tag('div'); // course accordion-group

                }

            }
        }

        if ($moduleDefined) {
            $RENDER .= local_qmul_dashboard_renderModuleFooter();
        }

    return $RENDER;
}
?>