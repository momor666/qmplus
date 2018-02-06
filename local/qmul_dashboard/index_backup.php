<?php
require_once('../../config.php');
require_once $CFG->libdir . '/gradelib.php';
require_once(__DIR__.'/studentsView.php');


// require valid moodle login.  Will redirect to login page if not logged in.
if (!isloggedin()) {
    //redirect to moodle login page
    echo 'Not Logged In';
    redirect(new moodle_url('/login/index.php'));
} else {
    require_once(__DIR__ . '/lib.php');

    $PAGE->set_pagelayout('frontpage');
    $PAGE->set_url('/local/qmul_dashboard/');
    $PAGE->set_title(get_string('navtitle', 'local_qmul_dashboard'));

    $PAGE->requires->jquery_plugin('qmul_dashboard-bootstrap', 'local_qmul_dashboard'); //boostrap
    $PAGE->requires->jquery_plugin('qmul_dashboard-highcharts', 'local_qmul_dashboard'); ///Highcharts

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('navtitle', 'local_qmul_dashboard'));

    //echo $OUTPUT->heading(get_string('navtitle', 'local_qmul_dashboard'));

    echo html_writer::div(
        get_string('welcometext', 'local_qmul_dashboard'),
        array('class' => 'accordion', 'id' => 'coursesaccordion')
    );

    $userCourses = local_qmul_dashboard_getUserCourses($USER->id);

    // TODO: build mahara notifications, this has to be moved and contiune work at block notifications
    // $mahara = get_mahara_content();

    echo html_writer::start_tag('div', array('class' => 'accordion', 'id' => 'coursesaccordion')); // accordion

    foreach ($userCourses as $key => $course) {
        // check for view permissions
        $permissionSettings = local_qmul_dashboard_checkCourseModulePermissions($course->category);
        if(!empty($permissionSettings)){
            $viewPermissions = local_qmul_dashboard_buildViewPermissions($permissionSettings);
        }

        //first make sure we have proper final grades - this must be done before constructing of the grade tree
        //grade_regrade_final_grades($course->courseid);  // It is removed as it was very slow ... Added as task


        $courseGrades = local_qmul_dashboard_getUserCourseGrades($course);

        // Check if course progress config exists
        $courseContext = local_qmul_dashboard_getCourseContext($course->courseid);

        $progress = local_qmul_dashboard_getProgressConfig($courseContext);

        $progressbar = false;
        $moduleDefined = false;
        $activitiesHeader = false;

        if ($progress) {
            $progressConfig = unserialize(base64_decode($progress->configdata));
            //$modules = block_progress_modules_in_use($course->courseid);

            $modules = block_progress_modules_in_use($course->courseid);
            $events = block_progress_event_information($progressConfig, $modules, $course->courseid);

            $thisCourse = new stdClass();
            $thisCourse->id = $course->courseid;

            $userevents = block_progress_filter_visibility($events, $USER->id, $courseContext, $thisCourse);


            if (count($userevents)!==0) {
                $usersattempts = array();

                //load average progress json json
                $json_file = file_get_contents(
                    $CFG->dataroot . "/qmplus_dashboard/averageActivityProgress-$course->courseid.json"
                );

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

            //echo $progressbar;

        }



        //if ($courseGrades  && !isset($viewPermissions['gradepanel'])) {
        if ($courseGrades) {
            // print_r($courseGrades);
            $totalGrades = count($courseGrades);
            $gradeCounter = 0;


            foreach ($courseGrades as $gradeKey => $grade) {
                $gradeCounter++;


                $graphTitle = $grade->itemname;

                // user array values to reset array keys
                $courseAllGrades = array_values(local_qmul_dashboard_getAllCourseGrades($course, $grade->itemid));
                // using
                $courseAverage = local_qmul_dashboard_getCourseAverageGrade($courseAllGrades);

                //TODO merge tables and sort by timemodified
                $merged = array_merge($courseAllGrades, array($grade->id => $grade));

                $merged = local_qmul_dashboard_sortGrades($merged);
                //usort($merged,'local_qmul_dashboard_cmpArray');


                if (!$moduleDefined) { // The module Start Wrapper
                    //$graphTitle = 'COURSE '.$course->fullname;


                    /*
                    *  Courses Accordion
                    */
                    echo local_qmul_dashboard_renderModuleHeader($course);
                    $moduleDefined = true;


                    /*
                    ####################### COMMENTED COURSE GRADE TO PREVENT IT  FROM DISPLAYING ######################

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

                    ####################################################################################################
                    */

                }
                if ($grade->itemmodule !== 'checklist' && $grade->itemtype !== 'course'
                    && !isset($viewPermissions['gradepanel'])) {
                    /*
                     *  Activities Panel
                     */

                    if (!$activitiesHeader) {
                        echo html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities panel
                        echo html_writer::div(
                            get_string('panelactivitiestitle', 'local_qmul_dashboard'),
                            'panel-heading'
                        );
                        echo html_writer::start_tag('div', array('class' => 'panel-body')); // Activities content
                        echo html_writer::start_tag(
                            'div',
                            array('class' => 'accordion', 'id' => 'accordion' . $course->courseid)
                        ); // accordion


                        $tableoptions['class'] = 'table table-bordered noNow';
                        echo html_writer::start_tag('table', $tableoptions);

                        // Table headers
                        echo html_writer::start_tag('thead');
                        echo html_writer::start_tag('tr');
                        echo html_writer::tag(
                            'th',
                            get_string('activityColumnName', 'local_qmul_dashboard'),
                            array('class' => 'span4')
                        );
                        echo html_writer::tag(
                            'th',
                            get_string('activityColumnGrade', 'local_qmul_dashboard'),
                            array('class' => 'span3')
                        );
                        echo html_writer::tag(
                            'th',
                            get_string('activityColumnFeedback', 'local_qmul_dashboard'),
                            array('class' => 'span2')
                        );

                        if (!isset($viewPermissions['histograms'])) {
                            echo html_writer::tag(
                                'th',
                                get_string('activityColumnHistogram', 'local_qmul_dashboard'),
                                array('class' => 'span3')
                            );
                        }

                        echo html_writer::end_tag('tr');
                        echo html_writer::end_tag('thead');
                        echo html_writer::start_tag('tbody');

                        $activitiesHeader = true;
                    }

                    echo HTML_WRITER::start_tag('tr');

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

                    echo HTML_WRITER::tag('td', $activityName);
                    echo HTML_WRITER::tag('td', round($grade->finalgrade, 2));


                    echo html_writer::start_tag('td');
                    if (null!==$grade->feedback) { // Show feedback button
                        echo HTML_WRITER::tag(
                            'button',
                            get_string('activityViewFeedback', 'local_qmul_dashboard'),
                            array(
                                'class' => 'btn btn-info',
                                'type' => 'button',
                                'data-toggle' => 'modal',
                                'data-target' => '#feedback' . $gradeKey
                            )
                        );

                        echo html_writer::start_tag(
                            'div',
                            array(
                                'id' => 'feedback' . $gradeKey,
                                'class' => 'modal hide fade',
                                'tabindex' => '-1',
                                'role' => 'dialog',
                                'aria-labelledby' => 'modalLabel' . $gradeKey,
                                'aria-hidden' => 'true'
                            )
                        ); // feedback modal

                        echo html_writer::start_tag('div', array('class' => 'modal-header')); // Modal header
                        echo html_writer::tag(
                            'button',
                            'x',
                            array(
                                'class' => 'close',
                                'type' => 'button',
                                'data-dismiss' => 'modal',
                                'aria-hidden' => 'true'
                            )
                        );
                        echo html_writer::tag('h3', $graphTitle, array('id' => 'modalLabel' . $gradeKey));
                        echo html_writer::end_tag('div'); // close Modal header

                        echo html_writer::start_tag('div', array('class' => 'modal-body')); // Modal Body

                        /* echo html_writer::start_tag('div', array('class' => 'span6'));

                         echo html_writer::end_tag('div');*/
                        echo html_writer::div($grade->feedback);


                        echo html_writer::end_tag('div'); // close Modal Body

                        echo html_writer::start_tag('div', array('class' => 'modal-footer')); // Modal Footer
                        echo html_writer::tag(
                            'button',
                            'Close',
                            array(
                                'class' => 'close',
                                'type' => 'button',
                                'data-dismiss' => 'modal',
                                'aria-hidden' => 'true'
                            )
                        );
                        echo html_writer::end_tag('div'); // close Modal Footer

                    }
                    echo html_writer::end_tag('td');
                    // End of feedback

                    echo html_writer::start_tag('td');
                    if ($displayHistogram && !isset($viewPermissions['histograms'])) {


                        echo HTML_WRITER::tag(
                            'button',
                            get_string('activityViewHistogram', 'local_qmul_dashboard'),
                            array(
                                'class' => 'btn btn-info',
                                'type' => 'button',
                                'data-toggle' => 'modal',
                                'data-target' => '#grades' . $gradeKey
                            )
                        );

                        echo html_writer::start_tag(
                            'div',
                            array(
                                'id' => 'grades' . $gradeKey,
                                'class' => 'modal hide fade',
                                'tabindex' => '-1',
                                'role' => 'dialog',
                                'aria-labelledby' => 'modalLabel' . $gradeKey,
                                'aria-hidden' => 'true'
                            )
                        ); // Histogram modal

                        echo html_writer::start_tag('div', array('class' => 'modal-header')); // Modal header
                        echo html_writer::tag(
                            'button',
                            'x',
                            array(
                                'class' => 'close',
                                'type' => 'button',
                                'data-dismiss' => 'modal',
                                'aria-hidden' => 'true'
                            )
                        );
                        echo html_writer::tag('h3', $graphTitle, array('id' => 'modalLabel' . $gradeKey));
                        echo html_writer::end_tag('div'); // close Modal header

                        echo html_writer::start_tag('div', array('class' => 'modal-body')); // Modal Body

                        echo html_writer::start_tag('div', array('class' => 'span6'));
                        echo html_writer::div(
                            '',
                            null,
                            array('id' => 'chart' . $gradeKey, 'class' => 'chartcontainer')
                        );
                        echo html_writer::end_tag('div');

                        echo html_writer::start_tag('div', array('class' => 'span6'));
                        echo html_writer::div(
                            '',
                            null,
                            array('id' => 'chartAgainst' . $gradeKey, 'class' => 'chartcontainer')
                        );
                        echo html_writer::end_tag('div');

                        echo html_writer::div('', null, array('class' => 'clearfix'));

                        echo html_writer::end_tag('div'); // close Modal Body

                        echo html_writer::start_tag('div', array('class' => 'modal-footer')); // Modal Footer
                        echo html_writer::tag(
                            'button',
                            'Close',
                            array(
                                'class' => 'close',
                                'type' => 'button',
                                'data-dismiss' => 'modal',
                                'aria-hidden' => 'true'
                            )
                        );
                        echo html_writer::end_tag('div'); // close Modal Footer

                    }

                    echo html_writer::end_tag('td');

                }

                if ($totalGrades === $gradeCounter && $activitiesHeader) {
                    echo html_writer::end_tag('tbody');
                    echo html_writer::end_tag('table');

                    echo html_writer::end_tag('div'); // close main panel
                    echo html_writer::end_tag('div'); // course accordion-heading
                    echo html_writer::end_tag('div'); // course accordion-group

                }


                if (isset($displayHistogram)  && !isset($viewPermissions['histograms'])) {
                    ?>
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

                            $('#grades<?php echo $gradeKey; ?>').on('shown', function () {
                                $('#chart<?php echo $gradeKey; ?>').highcharts({
                                    chart: {
                                        type: 'bar'
                                    },
                                    title: {
                                        text: '<?php echo get_string('avgGradeGraphTitle', 'local_qmul_dashboard') ?>'
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
                                    }],
                                    exporting: {
                                        buttons: {
                                            /*contextButton: {
                                             symbol: 'url(http://geodev.grid.unep.ch/images/button_download.png)'
                                             },*/
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
                                                menuItems: Highcharts.getOptions().exporting.buttons.contextButton.menuItems.splice(2)
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

                                <?php

                                   $colors = '';
                                   $categories = '';
                                   $data = '';
                                   $c = 0;

                                $groupStep = $grade->rawgrademax/10;
                                $groupRange = array(0=>array());

                                // Check if equals work
                                // for($i=$groupStep; $i<=$grade->rawgrademax; $i+=$groupStep){
                                // TODO Check if work correctly
                                // Check if equals work
                                for ($i = $groupStep; $i < $grade->rawgrademax; $i+=$groupStep) {
                                    $groupRange[$i] = array();
                                }

                                foreach ($merged as $userid => $finalgrade) {
                                    $comma = '';

                                    if ($c) {
                                        $comma=',';
                                    }

                                    $tata=1;

                                    $c++;
                                    $belong = false;

                                    if ((int)$grade->userid === $userid) {
                                        $colors.=$comma."'#7cb5ec'";
                                        $categories.=$comma."'You'";
                                        $belong = true;
                                    } else {
                                        $colors.=$comma."'#ccc'";
                                        $categories.=$comma."'$c'";
                                    }

                                    $groupKey = floor($finalgrade/$groupStep)*$groupStep;

                                    $groupRange[$groupKey]['grades'][] = $finalgrade;

                                    if($belong){
                                        $groupRange[$groupKey]['belong'] = $belong;
                                    }

                                    $data.=$comma.round($finalgrade, 2);

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
                                foreach ($groupRange as $groupkey => $group) {
                                    if ($categories!=='') {
                                        $comma = ',';
                                    }

                                    // groupkey is 0
                                    $toRange = (double)$groupkey-0.1;
                                    if($groupkey===$rangeFrom){
                                        $categories.= "'$rangeFrom - ";
                                    }
                                    else if ($lastGroupKey === $groupkey){
                                        $categories.= "$toRange'".$comma."'$groupkey'";
                                    }
                                    else {
                                        $categories.= "$toRange'".$comma."'$groupkey - ";
                                    }


                                    if ($group['belong'] === true) {
                                        $colors.=$comma."'#7cb5ec'";
                                    } else {
                                        $colors.=$comma."'#ccc'";
                                    }

                                    $totalGroupGrades = count($group['grades']);

                                    $groupRange[$groupkey]['percentage'] = round(
                                        ($totalGroupGrades/$totalStudentGrades)*100,2
                                    );

                                    if($groupRange[$groupkey]['percentage']>=$maxPercentage){
                                        $maxPercentage = $groupRange[$groupkey]['percentage'];
                                    }

                                    $data .= $comma.$groupRange[$groupkey]['percentage'];


                                }


                                ?>


//                                $('#chartAgainst<?php //echo $gradeKey; ?>//').highcharts({
//                                    chart: {
//                                        type: 'column'
//                                    },
//                                    plotOptions: {
//                                        column: {
//                                            colorByPoint: true
//                                        }
//                                    },
//                                    colors: [<?php //echo $colors?>//],
//                                    title: {
//                                        text: "<?php //echo get_string('stdGradeGraphTitle', 'local_qmul_dashboard'); ?>//"
//                                    },
//                                    xAxis: {
//                                        categories: [<?php //echo $categories; ?>//],
//                                        title: {
//                                            text: null
//                                        }
//                                    },
//                                    yAxis: {
//                                        min: 1,
//                                        max: <?php //echo $grade->rawgrademax;?>//,
//                                        title: {
//                                            text: 'Grade'
//                                        }
//                                    },
//                                    series: [{
//                                        name: 'Students (<?php //echo $c;?>//)',
//                                        data: [<?php //echo $data; ?>//]
//                                    }],
//                                    exporting: {
//                                        buttons: {
//                                            /*contextButton: {
//                                             symbol: 'url(http://geodev.grid.unep.ch/images/button_download.png)'
//                                             },*/
//                                            contextButton: {
//                                                enabled: false
//                                            },
//                                            exportButton: {
//                                                text: 'Download',
//                                                symbolStroke: "white",
//                                                theme: {
//                                                    fill:"white",
//                                                    states: {
//                                                        hover: {
//                                                            stroke: '#222',
//                                                            fill: '#000',
//                                                            style: {
//                                                                color: '#fff'
//                                                            }
//                                                        },
//                                                        select: {
//                                                            stroke: '#222',
//                                                            fill: '#000',
//                                                            style: {
//                                                                color: '#fff'
//                                                            }
//                                                        }
//                                                    }
//                                                },
//
//                                                // Use only the download related menu items from the default context button
//                                                menuItems: Highcharts.getOptions().exporting.buttons.contextButton.menuItems.splice(2)
//                                            },
//                                            printButton: {
//                                                text: 'Print',
//                                                symbolStroke: "white",
//                                                theme: {
//                                                    fill:"white",
//                                                    states: {
//                                                        hover: {
//                                                            stroke: '#222',
//                                                            fill: '#000',
//                                                            style: {
//                                                                color: '#fff'
//                                                            }
//                                                        },
//                                                        select: {
//                                                            stroke: '#222',
//                                                            fill: '#000',
//                                                            style: {
//                                                                color: '#fff'
//                                                            }
//                                                        }
//                                                    }
//                                                },
//                                                onclick: function () {
//                                                    this.print();
//                                                }
//                                            }
//                                        }
//                                    }
//                                });


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
                                        text: "<?php get_string('stdGradeGraphTitle', 'local_qmul_dashboard'); ?>"
                                    },
                                    xAxis: {
                                        categories: [<?php echo $categories; ?>],
                                        title: {
                                            text: ''
                                        }
                                    },
                                    yAxis: {
                                        min: 1,
                                        max: <?php echo $maxPercentage;?>,
                                        title: {
                                            text: 'Proportion of Students (% of course)'
                                        }
                                    },
                                    series: [{
                                        name: 'Coursework mark range (%)',
                                        data: [<?php echo $data; ?>]
                                    }],
                                    exporting: {
                                        buttons: {
                                            /*contextButton: {
                                             symbol: 'url(http://geodev.grid.unep.ch/images/button_download.png)'
                                             },*/
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
                                                menuItems: Highcharts.getOptions().exporting.buttons.contextButton.menuItems.splice(2)
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
                            });


                        });
                    </script>
                    <?php
                }
            }
        }

        if ($progressbar) {
            if (!$moduleDefined) {
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
            if (!$moduleDefined) {
                echo local_qmul_dashboard_renderModuleHeader($course);
                $moduleDefined = true;

            }

            $cklist = local_qmul_dashboard_get_user_ChecklistProgress($checklist);
            $percent = $cklist->checked * 100 / $cklist->totalitems;


            echo html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities Progress panel
            echo html_writer::div($cklist->name . ' ' . get_string('progress', 'local_qmul_dashboard'), 'panel-heading');
            echo html_writer::start_tag('div', array('class' => 'panel-body')); // Activities Progress content


            $viewurl = new moodle_url('/mod/checklist/view.php', array('id' => $cklist->coursemodule));

            echo html_writer::start_tag('a', array('href' => $viewurl));
            echo html_writer::start_tag('div', array('class' => 'progress progress-striped active'));
            echo html_writer::div('', 'bar', array('style' => 'width:' . $percent . '%;'));
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('a');


//            $output = '<div class="checklist_progress_outer" style="width: '.$width.';" >';
//            $output .= '<div class="checklist_progress_inner" style="width:'.$percent.
//                          '%; background-image: url('.$OUTPUT->pix_url('progress','checklist').');" >&nbsp;</div>';
//            $output .= '</div>';
//            $output .= '<br style="clear:both;" />';


            echo html_writer::end_tag('div'); // close Activities Progress content
            echo html_writer::end_tag('div'); // close Activities Progress panel

        }


        if ($moduleDefined) {
            echo local_qmul_dashboard_renderModuleFooter();
        }
    }

    echo html_writer::end_tag('div');
    echo $OUTPUT->footer();

}
?>