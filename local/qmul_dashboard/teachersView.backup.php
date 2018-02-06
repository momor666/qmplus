<?php

function renderTeacher($course, $courseContext){

    global $USER,$CFG, $OUTPUT;

    $RENDER ='';

        // GET all enrolledusers
        $enrolledUsers = get_enrolled_users($courseContext, '', 0, 'u.*', 'u.firstname ASC, u.lastname ASC');

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

            //$RENDER .= $progressbar;

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
                $courseAllGrades = array_values(local_qmul_dashboard_getRestCourseGrades($course, $grade->itemid));
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
                    $RENDER .= local_qmul_dashboard_renderModuleHeader($course,'teachers');
                    $moduleDefined = true;

                    ####################### COMMENTED COURSE GRADE TO PREVENT IT  FROM DISPLAYING ######################

                     $RENDER .= html_writer::start_tag('div', array('class' => 'panel panel-default')); // main panel
                     $RENDER .= html_writer::div(get_string('panelgrade', 'local_qmul_dashboard'), 'panel-heading');

                     $RENDER .= html_writer::start_tag('div', array('class' => 'panel-body')); // main content

                     $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                     $RENDER .= html_writer::div('', null ,array('id'=>'chart'.$gradeKey));
                     $RENDER .= html_writer::end_tag('div');

                     $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                     $RENDER .= html_writer::div('', null ,array('id'=>'coursechartagainst'.$course->courseid));
                     $RENDER .= html_writer::end_tag('div');

                     $RENDER .= html_writer::div('', null ,array('class'=>'clearfix'));


                     $RENDER .= html_writer::end_tag('div');
                     $RENDER .= html_writer::end_tag('div');

                    ####################################################################################################


                }
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
                    $RENDER .= HTML_WRITER::tag('td', round($grade->finalgrade, 2));


                    $RENDER .= html_writer::start_tag('td');
                    if (null!==$grade->feedback) { // Show feedback button
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
                                'class' => 'modal hide fade',
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

                       /* $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));

                        $RENDER .= html_writer::end_tag('div');*/
                        $RENDER .= html_writer::div($grade->feedback);


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
                    // End of feedback

                    $RENDER .= html_writer::start_tag('td');
                    if ($displayHistogram && !isset($viewPermissions['histograms'])) {


                        $RENDER .= HTML_WRITER::tag(
                            'button',
                            get_string('activityViewHistogram', 'local_qmul_dashboard'),
                            array(
                                'class' => 'btn btn-info',
                                'type' => 'button',
                                'data-toggle' => 'modal',
                                'data-target' => '#grades' . $gradeKey
                            )
                        );

                        $RENDER .= html_writer::start_tag(
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

                if ($totalGrades === $gradeCounter && $activitiesHeader) {
                    $RENDER .= html_writer::end_tag('tbody');
                    $RENDER .= html_writer::end_tag('table');

                    $RENDER .= html_writer::end_tag('div'); // close main panel
                    $RENDER .= html_writer::end_tag('div'); // course accordion-heading
                    $RENDER .= html_writer::end_tag('div'); // course accordion-group

                }


                if (isset($displayHistogram)  && !isset($viewPermissions['histograms'])) {

//                    TODO REPLACE SCRIPT WITH HEREDOC syntax and return all $RENDER .= code


                   // For courses chart
                   $colors1 = '';
                   $categories1 = '';
                   $data1 = '';
                   $c1 = 0;

                    foreach ($merged as  $userid => $finalgrade){

                        $comma = '';

                        if($c1){ $comma=',';}
                        $c1++;

                        if($grade->userid==$userid) {
                            $colors1.=$comma."'#7cb5ec'";
                            $categories1.=$comma."'You'";
                        }else{
                            $colors1.=$comma."'#ccc'";
                            $categories1.=$comma."''";
                        }

                        $data1.=$comma.round( $finalgrade , 2 );
                    }
                    // /.For courses chart



                    $colors2 = '';
                    $categories2 = '';
                    $data2 = '';
                    $c2 = 0;

                    $groupStep = $grade->rawgrademax/10;
                    $groupRange = array(0=>array());

                    for ($i = $groupStep; $i <= $grade->rawgrademax; $i+=$groupStep) {
                        $groupRange[$i] = array();

                        // fix to eternal loop
                        if($grade->rawgrademax==0) {
                            break;
                        }
                    }

                    foreach ($merged as $userid => $finalgrade) {
                        $comma = '';

                        if ($c2) {
                            $comma=',';
                        }

                        $c2++;
                        $belong = false;

                        if ((int)$grade->userid === $userid) {
                            $colors2.=$comma."'#7cb5ec'";
                            $categories2.=$comma."'You'";
                            $belong = true;
                        } else {
                            $colors2.=$comma."'#ccc'";
                            $categories2.=$comma."'$c2'";
                        }

                        $groupKey = floor($finalgrade/$groupStep)*$groupStep;

                        $groupRange[$groupKey]['grades'][] = $finalgrade;

                        if($belong){
                            $groupRange[$groupKey]['belong'] = $belong;
                        }

                        $data2.=$comma.round($finalgrade, 2);

                    }

                    $totalStudentGrades = count($merged);
                    $categories3 = '';
                    $comma = '';
                    $data3 = '';
                    $colors3 = '';
                    $maxPercentage = 0;
                    $rangeFrom = 0;
                    end($groupRange);
                    $lastGroupKey = key($groupRange);
                    reset($groupRange);

                    $toGroup = 0.1;
                    if($grade->rawgrademax>10){
                        $toGroup = 1;
                    }
                    foreach ($groupRange as $groupkey => $group) {
                        if ($categories3!=='') {
                            $comma = ',';
                        }

                        // groupkey is 0
                        $toRange = (double)$groupkey-$toGroup;
                        if($groupkey===$rangeFrom){
                            $categories3.= "'$rangeFrom - ";
                        }
                        else if ($lastGroupKey === $groupkey){
                            $categories3.= "$toRange'".$comma."'$groupkey'";
                        }
                        else {
                            $categories3.= "$toRange'".$comma."'$groupkey - ";
                        }


                        if ($group['belong'] === true) {
                            $colors3.=$comma."'#7cb5ec'";
                        } else {
                            $colors3.=$comma."'#ccc'";
                        }

                        $totalGroupGrades = count($group['grades']);

                        $groupRange[$groupkey]['percentage'] = round(
                            ($totalGroupGrades/$totalStudentGrades)*100,2
                        );

                        if($groupRange[$groupkey]['percentage']>=$maxPercentage){
                            $maxPercentage = $groupRange[$groupkey]['percentage'];
                        }

                        $data3 .= $comma.$groupRange[$groupkey]['percentage'];


                    }

                $avgGradeGraphTitle = get_string('avgGradeGraphTitle', 'local_qmul_dashboard');
                $stdGradeGraphTitle = get_string('stdGradeGraphTitle', 'local_qmul_dashboard');
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

// Course
                            $('#coursechartagainst$course->courseid').highcharts({
                                chart: {
                                    type: 'column'
                                },
                                plotOptions: {
                                    column: {
                                        colorByPoint: true
                                    }
                                },
                                colors: [$colors1],
                                title: {
                                    text: "$stdGradeGraphTitle"
                                },
                                xAxis: {
                                    categories: [$categories1],
                                    title: {
                                        text: null
                                    }
                                },
                                yAxis: {
                                    min: 1,
                                    max: $grade->rawgrademax,
                                    title: {
                                        text: 'Grade'
                                    }
                                },
                                series: [{
                                    name: 'Students ($c1)',
                                    data: [$data1]
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
// /.Course
                            $('#grades$gradeKey').on('shown', function () {

                                $('#chart$gradeKey').highcharts({
                                    chart: {
                                        type: 'bar'
                                    },
                                    title: {
                                        text: '$avgGradeGraphTitle'
                                    },
                                    xAxis: {
                                        categories: ['Grade']
                                    },
                                    yAxis: {
                                        title: {
                                            text: 'Range'
                                        },
                                        tickInterval: $grade->rawgrademax/20,
                                        max:  $grade->rawgrademax
                                    },
                                    series: [{
                                        name: 'You',
                                        data: [$grade->finalgrade]
                                    }, {
                                        name: 'Students Average Grade',
                                        data: [$courseAverage]
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

                                $('#chartAgainst$gradeKey').highcharts({
                                    chart: {
                                        type: 'column'
                                    },
                                    plotOptions: {
                                        column: {
                                            colorByPoint: true
                                        }
                                    },
                                    colors: [$colors3],
                                    title: {
                                        text: "$stdGradeGraphTitle"
                                    },
                                    xAxis: {
                                        categories: [$categories3],
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
                                        data: [$data3]
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
EOT;
                }
            }
        }

        if ($progressbar) {
            if (!$moduleDefined) {
                $RENDER .= local_qmul_dashboard_renderModuleHeader($course,'teachers');
                $moduleDefined = true;

            }

            $RENDER .= html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities Progress panel
            $RENDER .= html_writer::div(get_string('panelactivitiesprogress', 'local_qmul_dashboard'), 'panel-heading');
            $RENDER .= html_writer::start_tag('div', array('class' => 'panel-body')); // Activities Progress content

            $RENDER .= $progressbar;

            $RENDER .= html_writer::end_tag('div'); // close Activities Progress content
            $RENDER .= html_writer::end_tag('div'); // close Activities Progress panel

        }

        // Checklists
        $checklists = local_qmul_dashboard_getCourseChecklists($course);

        foreach ($checklists as $checklist) {
            if (!$moduleDefined) {
                $RENDER .= local_qmul_dashboard_renderModuleHeader($course,'teachers');
                $moduleDefined = true;

            }

            $cklist = local_qmul_dashboard_get_user_ChecklistProgress($checklist);
            $percent = $cklist->checked * 100 / $cklist->totalitems;


            $RENDER .= html_writer::start_tag('div', array('class' => 'panel panel-default')); // Activities Progress panel
            $RENDER .= html_writer::div($cklist->name . ' ' . get_string('progress', 'local_qmul_dashboard'), 'panel-heading');
            $RENDER .= html_writer::start_tag('div', array('class' => 'panel-body')); // Activities Progress content


            $viewurl = new moodle_url('/mod/checklist/view.php', array('id' => $cklist->coursemodule));

            $RENDER .= html_writer::start_tag('a', array('href' => $viewurl));
            $RENDER .= html_writer::start_tag('div', array('class' => 'progress progress-striped active'));
            $RENDER .= html_writer::div('', 'bar', array('style' => 'width:' . $percent . '%;'));
            $RENDER .= html_writer::end_tag('div');
            $RENDER .= html_writer::end_tag('a');


//            $output = '<div class="checklist_progress_outer" style="width: '.$width.';" >';
//            $output .= '<div class="checklist_progress_inner" style="width:'.$percent.
//                          '%; background-image: url('.$OUTPUT->pix_url('progress','checklist').');" >&nbsp;</div>';
//            $output .= '</div>';
//            $output .= '<br style="clear:both;" />';


            $RENDER .= html_writer::end_tag('div'); // close Activities Progress content
            $RENDER .= html_writer::end_tag('div'); // close Activities Progress panel

        }


        if ($moduleDefined) {
            $RENDER .= local_qmul_dashboard_renderModuleFooter();
        }


    return $RENDER;
}
?>