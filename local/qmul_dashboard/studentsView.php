<?php

function renderStudent($course, $courseContext){

    global $USER,$CFG, $OUTPUT;

    $RENDER = '';

        // check for view permissions
        $permissionSettings = local_qmul_dashboard_checkCourseModulePermissions($course->category);
        if(!empty($permissionSettings)){
            $viewPermissions = local_qmul_dashboard_buildViewPermissions($permissionSettings);
        }

        //first make sure we have proper final grades - this must be done before constructing of the grade tree
        //grade_regrade_final_grades($course->courseid);  // It is removed as it was very slow ... Added as task

        $gradeCategories = local_qmul_dashboard_getUserCourseGrades($course);



        // Check if course progress config exists
        $courseContext = local_qmul_dashboard_getCourseContext($course->courseid);


        // $progress = local_qmul_dashboard_getProgressConfig($courseContext);
        // Disable progress for relase 1
        $progress = false;
        $progressbar = null;
        $moduleDefined = false;

        if ($progress) {
            $progressConfig = unserialize(base64_decode($progress->configdata));
            //$modules = block_progress_modules_in_use($course->courseid);

            $modules = block_progress_modules_in_use($course->courseid);
            $events = block_progress_event_information($progressConfig, $modules, $course->courseid);

            $thisCourse = new stdClass();
            $thisCourse->id = $course->courseid;

            $userevents = block_progress_filter_visibility($events, $USER->id, $courseContext, $thisCourse);


            if ($userevents && count($userevents)!==0) {
                $usersattempts = array();

                //load average progress json json
                $json_file = false;
                if(file_exists($CFG->dataroot . "/qmplus_dashboard/averageActivityProgress-$course->courseid.json")){
                    $json_file = file_get_contents(
                        $CFG->dataroot . "/qmplus_dashboard/averageActivityProgress-$course->courseid.json"
                    );
                }


                if ($json_file) {
                    // convert the string to an array
                    $usersattempts = json_decode($json_file, true);
                }

                $attempts = block_progress_attempts(
                    $modules,
                    $progressConfig,
                    $userevents,
                    $USER->id,
                    $course->courseid
                );

                $progressbar = local_qmul_dashboard_drawProgressBar(
                    $modules,
                    $progressConfig,
                    $userevents,
                    $USER->id,
                    $progress->id,
                    $attempts,
                    $course->id,
                    true,
                    $usersattempts
                );

                //$progressvalue = block_progress_percentage($userevents, $attempts, true);
                $progressvalue = block_progress_percentage($userevents, $attempts);
            }

            //$RENDER .=  $progressbar;

        }

        if ($gradeCategories  && !isset($viewPermissions['gradepanel'])) {
        //if ($gradeCategories) {
            // print_r($gradeCategories);
            $totalGrades = count($gradeCategories);
            $gradeCounter = 0;

            foreach ($gradeCategories->getCategoriesTree() as $categoryId => $category) {

                $gradeCounter++;

                $RENDERGrades = local_qmul_dashboard_renderCategory($category,0,false,'Students',$course);

                if($RENDERGrades && !$moduleDefined){
                    // The module Start Wrapper
                    //$graphTitle = 'COURSE '.$course->fullname;
                    /*
                    *  Courses Accordion
                    */
                    $RENDER .=  local_qmul_dashboard_renderModuleHeader($course,'students');
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
                            array('class' => 'span4')
                        );
                        $RENDER .= html_writer::tag(
                            'th',
                            get_string('activityColumnGrade', 'local_qmul_dashboard'),
                            array('class' => 'span3')
                        );
                        $RENDER .= html_writer::tag(
                            'th',
                            get_string('activityColumnFeedback', 'local_qmul_dashboard'),
                            array('class' => 'span2')
                        );

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
//                $graphTitle['category']['category']->fullname;
                //create category table

            }


            if($RENDERGrades){
                $RENDER .=  html_writer::end_tag('tbody');
                $RENDER .=  html_writer::end_tag('table');

                $RENDER .=  html_writer::end_tag('div'); // close main panel
                $RENDER .=  html_writer::end_tag('div'); // course accordion-heading


                //helper links
                $RENDER .= html_writer::start_tag('div', array('class' => 'span4 no-margin')); // Activities content
                $courselink = $CFG->wwwroot.'/course/view.php?id=' . $course->courseid;
                $RENDER .=  '<a href="'.$courselink.'" target="_blank" title="'
                    .get_string('viewCourseLink','local_qmul_dashboard').
                    '" class="scoreLink span5">'.get_string('viewCourseLink','local_qmul_dashboard')
                    .' <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a>';

                if ($course->showgrades) {
                    $gradelink = $CFG->wwwroot . '/grade/report/user/index.php?id=' . $course->courseid;
                    $RENDER .= '<a href="' . $gradelink . '" target="_blank" title="'
                        . get_string('viewGradesLink', 'local_qmul_dashboard') .
                        '" class="scoreLink span6">' .
                        get_string('viewGradesLink', 'local_qmul_dashboard') . '
                    <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span></a>';
                }
                $RENDER .= html_writer::tag(
                    'span',
                    '',
                    array(
                        'class' => 'clearfix',
                    )
                );
                $RENDER .=  html_writer::end_tag('div'); // course accordion-heading
                $RENDER .= html_writer::tag(
                    'span',
                    '',
                    array(
                        'class' => 'clearfix',
                    )
                );


                $RENDER .=  html_writer::end_tag('div'); // course accordion-group
            }

        }

        if ($progressbar) {
            if (!$moduleDefined) {
                $RENDER .=  local_qmul_dashboard_renderModuleHeader($course,'students');
                $moduleDefined = true;

            }

            $RENDER .=  html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities Progress panel
            $RENDER .=  html_writer::div(get_string('panelactivitiesprogress', 'local_qmul_dashboard'), 'panel-heading');
            $RENDER .=  html_writer::start_tag('div', array('class' => 'panel-body')); // Activities Progress content

            $RENDER .=  $progressbar;

            $RENDER .=  html_writer::end_tag('div'); // close Activities Progress content
            $RENDER .=  html_writer::end_tag('div'); // close Activities Progress panel

        }




        // Checklists
        // $checklists = local_qmul_dashboard_getCourseChecklists($course);
        // Disable Checklists for relase 1
        $checklists = array();
        foreach ($checklists as $checklist) {
            if (!$moduleDefined) {
                $RENDER .=  local_qmul_dashboard_renderModuleHeader($course,'students');
                $moduleDefined = true;

            }

            $cklist = local_qmul_dashboard_get_user_ChecklistProgress($checklist);
            if($cklist){
                $percent = $cklist->checked * 100 / $cklist->totalitems;

                $RENDER .=  html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities Progress panel
                $RENDER .=  html_writer::div($cklist->name . ' ' . get_string('progress', 'local_qmul_dashboard'), 'panel-heading');
                $RENDER .=  html_writer::start_tag('div', array('class' => 'panel-body')); // Activities Progress content


                $viewurl = new moodle_url('/mod/checklist/view.php', array('id' => $cklist->coursemodule));

                $RENDER .=  html_writer::start_tag('a', array('href' => $viewurl));
                $RENDER .=  html_writer::start_tag('div', array('class' => 'progress progress-striped active'));
                $RENDER .=  html_writer::div('', 'bar', array('style' => 'width:' . $percent . '%;'));
                $RENDER .=  html_writer::end_tag('div');
                $RENDER .=  html_writer::end_tag('a');

    //            $output = '<div class="checklist_progress_outer" style="width: '.$width.';" >';
    //            $output .= '<div class="checklist_progress_inner" style="width:'.$percent.
    //                          '%; background-image: url('.$OUTPUT->pix_url('progress','checklist').');" >&nbsp;</div>';
    //            $output .= '</div>';
    //            $output .= '<br style="clear:both;" />';

                $RENDER .=  html_writer::end_tag('div'); // close Activities Progress content
                $RENDER .=  html_writer::end_tag('div'); // close Activities Progress panel
            }
        }


        if ($moduleDefined) {
            $RENDER .=  local_qmul_dashboard_renderModuleFooter();
        }

    return $RENDER;
}
?>