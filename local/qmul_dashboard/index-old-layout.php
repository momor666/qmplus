<?php
require_once('../../config.php');
require_once $CFG->libdir.'/gradelib.php';
require_once(dirname(__FILE__).'/lib.php');

// require valid moodle login.  Will redirect to login page if not logged in.
if (!isloggedin()) {
    //redirect to moodle login page
    echo 'Not Logged In';
    redirect(new moodle_url('/login/index.php'));
} else {

    require_once(dirname(__FILE__).'/lib.php');

    $PAGE->set_pagelayout('frontpage');
    $PAGE->set_url('/local/qmul_dashboard/');
    $PAGE->set_title(get_string('navtitle', 'local_qmul_dashboard'));
    $PAGE->set_heading($course->fullname);

    $PAGE->requires->jquery_plugin('qmul_dashboard-bootstrap', 'local_qmul_dashboard'); //boostrap
    $PAGE->requires->jquery_plugin('qmul_dashboard-highcharts', 'local_qmul_dashboard'); ///Highcharts

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('navtitle', 'local_qmul_dashboard'));

    //echo $OUTPUT->heading(get_string('navtitle', 'local_qmul_dashboard'));

    echo html_writer::div(get_string('welcometext', 'local_qmul_dashboard'), array('class' => 'accordion', 'id'=>'coursesaccordion'));

    $userCourses = local_qmul_dashboard_getUserCourses($USER->id);

    echo html_writer::start_tag('div', array('class' => 'accordion', 'id'=>'coursesaccordion')); // accordion

    foreach ($userCourses as $key => $course) {

        //first make sure we have proper final grades - this must be done before constructing of the grade tree
        grade_regrade_final_grades($course->courseid);

        $courseGrades = local_qmul_dashboard_getUserCourseGrades($course);

        // Check if course progress config exists
        $courseContext = local_qmul_dashboard_getCourseContext($course->courseid);

        $progress = local_qmul_dashboard_getProgressConfig($courseContext);

        $progressbar = false;
        $moduleDefined = false;
        $activitiesHeader = false;

        if($progress){

            $progressConfig = unserialize(base64_decode($progress->configdata));
            //$modules = block_progress_modules_in_use($course->courseid);

            $modules = block_progress_modules_in_use($course->courseid);
            $events = block_progress_event_information($progressConfig, $modules, $course->courseid);

            $thisCourse = new stdClass();
            $thisCourse->id = $course->courseid;

            $userevents = block_progress_filter_visibility($events, $USER->id, $courseContext, $thisCourse);


            if (!empty($userevents)) {

                $usersattempts = array();

                //load average progress json json
                $json_file = file_get_contents($CFG->dataroot . "/qmplus_dashboard/averageActivityProgress-$course->courseid.json");
                if($json_file){
                    // convert the string to an array
                    $usersattempts = json_decode($json_file, true);
                }

                $attempts = block_progress_attempts($modules, $progressConfig, $userevents, $USER->id, $course->courseid);
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
                $progressvalue = block_progress_percentage($userevents, $attempts, true);
            }

            //echo $progressbar;

        }

        if($courseGrades) {

            // print_r($courseGrades);
            $totalGrades = count($courseGrades);
            $gradeCounter=0;


            foreach ($courseGrades as $gradeKey => $grade) {
                $gradeCounter++;


                $graphTitle = $grade->itemname;

                // user array values to reset array keys
                $courseAllGrades = array_values(local_qmul_dashboard_getAllCourseGrades($course,$grade->itemid));
                // using
                $courseAverage = local_qmul_dashboard_getCourseAverageGrade($courseAllGrades);

                //TODO merge tables and sort by timemodified
                $merged = array_merge($courseAllGrades, array($grade->id=>$grade));

                $merged = local_qmul_dashboard_sortGrades($merged);
                //usort($merged,'local_qmul_dashboard_cmpArray');



                if(is_null($graphTitle)){ // The module Start Wrapper
                    //$graphTitle = 'COURSE '.$course->fullname;


                    /*
                    *  Courses Accordion
                    */

                    /*$courselink = html_writer::link('#courseBody'.$course->courseid, $course->fullname, array('class' => 'accordion-toggle',
                            'data-toggle' => 'collapse',
                            'data-parent' => '#coursesaccordion')
                    );

                    echo html_writer::start_tag('div', array('class' => 'accordion-group'));
                    echo html_writer::div('<h4>'.$courselink.'</h4>', null ,array('class'=>'accordion-heading'));

                    echo html_writer::start_tag('div', array('id'=>'courseBody'.$course->courseid,'class' => 'accordion-body collapse'));
                    echo html_writer::start_tag('div', array('class' => 'accordion-inner'));


                    echo html_writer::start_tag('div', array('class' => 'panel panel-default')); // main panel*/

                    echo local_qmul_dashboard_renderModuleHeader($course);
                    $moduleDefined = true;

                    echo html_writer::start_tag('div', array('class' => 'panel panel-default')); // main panel
                    echo html_writer::div(get_string('panelgrade', 'local_qmul_dashboard'), 'panel-heading');

                    echo html_writer::start_tag('div', array('class' => 'panel-body')); // main content

                    echo html_writer::start_tag('div', array('class' => 'span6'));
                    echo html_writer::div('', null ,array('id'=>'chart'.$gradeKey));
                    echo html_writer::end_tag('div');

                    echo html_writer::start_tag('div', array('class' => 'span6'));
                    echo html_writer::div('', null ,array('id'=>'chartAgainst'.$gradeKey));
                    echo html_writer::end_tag('div');

                    echo html_writer::div('', null ,array('class'=>'clearfix'));


                    echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');
                }
                else if($grade->itemmodule != 'checklist') {

                    /*
                     *  Activities
                     */

                    if(!$activitiesHeader) {
                        echo html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities panel
                        echo html_writer::div(get_string('panelactivitiesgrade', 'local_qmul_dashboard'), 'panel-heading');
                        echo html_writer::start_tag('div', array('class' => 'panel-body')); // Activities content
                        echo html_writer::start_tag('div', array('class' => 'accordion', 'id'=>'accordion'.$course->courseid)); // accordion

                        $activitiesHeader = true;
                    }

                    /*
                     *  Activities
                     */
                    $link = html_writer::link('#chartBody'.$gradeKey, $graphTitle, array('class' => 'accordion-toggle',
                            'data-toggle' => 'collapse',
                            'data-parent' => '#accordion'.$course->courseid)
                    );

                    echo html_writer::start_tag('div', array('class' => 'accordion-group panel-group'));
                    echo html_writer::div($link, null ,array('class'=>'accordion-heading'));

                    echo html_writer::start_tag('div', array('id'=>'chartBody'.$gradeKey,'class' => 'accordion-body collapse'));
                    echo html_writer::start_tag('div', array('class' => 'accordion-inner'));


                    echo html_writer::start_tag('div', array('class' => 'span6'));
                    echo html_writer::div('', null ,array('id'=>'chart'.$gradeKey));
                    echo html_writer::end_tag('div');


                    echo html_writer::start_tag('div', array('class' => 'span6'));
                    echo html_writer::div('', null ,array('id'=>'chartAgainst'.$gradeKey));
                    echo html_writer::end_tag('div');

                    echo html_writer::div('', null ,array('class'=>'clearfix'));
//                    echo html_writer::div('', null ,array('id'=>'chart'.$gradeKey));
//                    echo html_writer::div('', null ,array('id'=>'chartAgainst'.$gradeKey));


                    echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');

                }

                if($totalGrades == $gradeCounter &&  $activitiesHeader){


                    echo html_writer::end_tag('div'); // close main panel
                    echo html_writer::end_tag('div'); // course accordion-heading
                    echo html_writer::end_tag('div'); // course accordion-group
//                    echo html_writer::end_tag('div'); // course accordion

                }

        ?>

                <script>
                    $(function () {
                        $('#chart<?php echo $gradeKey; ?>').highcharts({
                            chart: {
                                type: 'bar'
                            },
                            title: {
                                text: '<?php echo $graphTitle; ?>'
                            },
                            xAxis: {
                                categories: ['Grade']
                            },
                            yAxis: {
                                title: {
                                    text: 'Range'
                                },
                                tickInterval: <?php echo $grade->rawgrademax/20 ?>,
                                max: <?php echo $grade->rawgrademax; ?>
                            },
                            series: [{
                                name: 'You',
                                data: [<?php echo $grade->finalgrade ?>]
                            }, {
                                name: 'Students Average Grade',
                                data: [<?php echo $courseAverage; ?>]
                            }]
                        });

                        <?php

                           $colors = '';
                           $categories = '';
                           $data = '';
                           $c = 0;
                        foreach ($merged as  $userid => $finalgrade){

                               $comma = '';

                               if($c){ $comma=',';}
                               $c++;

                               if($grade->userid==$userid) {
                                    $colors.=$comma."'#7cb5ec'";
                                    $categories.=$comma."'You'";
                                }else{
                                    $colors.=$comma."'#ccc'";
                                    $categories.=$comma."''";
                                }

                                $data.=$comma.round( $finalgrade , 2 );
                        }
                        ?>


                        $('#chartAgainst<?php echo $gradeKey; ?>').highcharts({
                            chart: {
                                type: 'column'
                            },
                            plotOptions: {
                                column: {
                                    colorByPoint: true
                                }
                            },
                            colors: [<?php echo $colors?>],
                            title: {
                                text: '<?php echo $graphTitle; ?>'
                            },
                            xAxis: {
                                categories: [<?php echo $categories; ?>],
                                title: {
                                    text: null
                                }
                            },
                            yAxis: {
                                min: 1,
                                max: <?php echo $grade->rawgrademax;?>,
                                title: {
                                    text: 'Grade'
                                }
                            },
                            series: [ {
                                name: 'Students',
                                data: [<?php echo $data; ?>]
                            }]
                        });
                    });
                </script>
        <?php
            }
        }

        if($progressbar){

            if(!$moduleDefined){
                echo local_qmul_dashboard_renderModuleHeader($course);
                $moduleDefined = true;

            }

            echo html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities Progress panel
            echo html_writer::div(get_string('panelactivitiesprogress', 'local_qmul_dashboard'), 'panel-heading');
            echo html_writer::start_tag('div', array('class' => 'panel-body')); // Activities Progress content

            echo $progressbar;

            echo html_writer::end_tag('div'); // close Activities Progress content
            echo html_writer::end_tag('div'); // close Activities Progress panel

        }

        // Checklists
        $checklists = local_qmul_dashboard_getCourseChecklists($course);

        foreach ($checklists as $checklist) {

            if(!$moduleDefined){
                echo local_qmul_dashboard_renderModuleHeader($course);
                $moduleDefined = true;

            }

            $cklist = local_qmul_dashboard_get_user_ChecklistProgress($checklist);
            $percent = $cklist->checked * 100 / $cklist->totalitems;


            echo html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities Progress panel
            echo html_writer::div($cklist->name.' '.get_string('progress', 'local_qmul_dashboard'), 'panel-heading');
            echo html_writer::start_tag('div', array('class' => 'panel-body')); // Activities Progress content


            $viewurl = new moodle_url('/mod/checklist/view.php', array('id'=>$cklist->coursemodule));

            echo html_writer::start_tag('a', array('href' => $viewurl));
            echo html_writer::start_tag('div', array('class' => 'progress progress-striped active'));
            echo html_writer::div('', 'bar', array('style' => 'width:'.$percent.'%;'));
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('a');


//            $output = '<div class="checklist_progress_outer" style="width: '.$width.';" >';
//            $output .= '<div class="checklist_progress_inner" style="width:'.$percent.'%; background-image: url('.$OUTPUT->pix_url('progress','checklist').');" >&nbsp;</div>';
//            $output .= '</div>';
//            $output .= '<br style="clear:both;" />';


            echo html_writer::end_tag('div'); // close Activities Progress content
            echo html_writer::end_tag('div'); // close Activities Progress panel

        }


        if($moduleDefined){
            echo local_qmul_dashboard_renderModuleFooter();
        }
    }

    echo html_writer::end_tag('div');
    echo $OUTPUT->footer();

}
?>