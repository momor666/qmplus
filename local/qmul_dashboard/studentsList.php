<?php
/**
 * Version details.
 *
 * @package    local
 * @subpackage qmul_dasboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    require_once(__DIR__.'/../../config.php');
    require_once(__DIR__.'/lib.php');

    if(isset($_REQUEST['courseid']))
    {

        global $DB, $CFG;

        // GET course
        $course = $DB->get_record('course', array('id'=> $_REQUEST['courseid']));
        $courseContext = local_qmul_dashboard_getCourseContext($course->id);

        require_once $CFG->libdir . '/gradelib.php';
        $grade_item = grade_item::fetch(array('id'=>$_REQUEST['item']));

        // GET all enrolledusers
        $enrolledUsers = get_enrolled_users($courseContext, '', 0, 'u.*', 'u.firstname ASC, u.lastname ASC');

        $studentGrades = array();
        $activityNumbers = array('totalFinalGrades'=>0, 'averageGrade'=>0);

        $activityGrades = local_qmul_dashboard_getAllCourseActivityGrades($course,$_REQUEST['item']);

        $RENDER = '';
        /*
         ***********************************************************
         ***********************************************************
         * Highcharts theme
         */

        $RENDER .= <<<EOT
        <script>
            $(function () {
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
                            });
                </script>
EOT;
        /*
         ***********************************************************
         ***********************************************************
         */


        if(!empty($activityGrades)){
            foreach ($activityGrades as $grade) {
                $studentGrades[$grade->userid] = $grade;
                $activityNumbers['totalFinalGrades'] += $grade->finalgrade;
                $activityNumbers['averageGrade'] = $activityNumbers['totalFinalGrades'] / count($activityGrades);
                $activityNumbers['maxGrade'] = $grade->grademax;
            }

            $merged = local_qmul_dashboard_sortGrades($activityGrades);


            foreach ($enrolledUsers as $enrolledUser) {

                $studentGrade = $studentGrades[$enrolledUser->id];

                if($studentGrade!==null) {

                    $studentlink = html_writer::link('#studentActivityBody' . $enrolledUser->id,
                        '<span class="glyphicon glyphicon-collapse-down collapsedown" aria-hidden="true"></span>'
                        . $enrolledUser->firstname . ' ' . $enrolledUser->lastname,
                        array(
                            'class' => 'accordion-toggle',
                            'data-toggle' => 'collapse',
                            'data-parent' => $_REQUEST['key']));
                    
                    $RENDER .= html_writer::start_tag('div', array('class' => 'accordion-group panel panel-default'));
                    $RENDER .= html_writer::start_tag('div', array('class' => ' panel-heading'));

                    $RENDER .= html_writer::div(
                        '<h4>' . $studentlink . '</h4>',
                        null, array('class' => 'accordion-heading panel-title')
                    );
                    $RENDER .= html_writer::end_tag('div');

                    $RENDER .= html_writer::start_tag('div', array(
                            'id' => 'studentActivityBody' . $enrolledUser->id, 'class' => 'accordion-body panel-collapse collapse')
                    );
                    $RENDER .= html_writer::start_tag('div', array('class' => 'accordion-inner panel-body'));

                    // Content
                    $RENDER .= html_writer::start_tag('div');

                    $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                    $RENDER .= html_writer::div(
                        '',
                        null,
                        array('id' => 'chart' . $enrolledUser->id, 'class' => 'chartcontainer')
                    );
                    $RENDER .= html_writer::end_tag('div');

                    $RENDER .= html_writer::start_tag('div', array('class' => 'span6'));
                    $RENDER .= html_writer::div(
                        '',
                        null,
                        array('id' => 'chartAgainst' . $enrolledUser->id, 'class' => 'chartcontainer')
                    );
                    $RENDER .= html_writer::end_tag('div');

                    $RENDER .= html_writer::div('', null, array('class' => 'clearfix'));
                    $RENDER .= html_writer::end_tag('div');
                    // /.Content

                    // JS Higcharts
                    $colors = '';
                    $categories = '';
                    $data = '';
                    $c = 0;

                    // $groupStep = $studentGrade->rawgrademax / 10;
                    if (($studentGrade->rawgrademax%10) === 0) {
                        $groupStep = $studentGrade->rawgrademax/10;
                    }
                    else if(($studentGrade->rawgrademax%9) === 0) {
                        $groupStep = $studentGrade->rawgrademax/9;
                    }
                    else if(($studentGrade->rawgrademax%8) === 0) {
                        $groupStep = $studentGrade->rawgrademax/8;
                    }
                    else if(($studentGrade->rawgrademax%7) === 0) {
                        $groupStep = $studentGrade->rawgrademax/7;
                    }
                    else if(($studentGrade->rawgrademax%6) === 0) {
                        $groupStep = $studentGrade->rawgrademax/6;
                    }
                    else if(($studentGrade->rawgrademax%5) === 0) {
                        $groupStep = $studentGrade->rawgrademax/5;
                    }
                    else if(($studentGrade->rawgrademax%4) === 0) {
                        $groupStep = $studentGrade->rawgrademax/4;
                    }
                    else if(($studentGrade->rawgrademax%3) === 0) {
                        $groupStep = $studentGrade->rawgrademax/3;
                    }
                    else if(($studentGrade->rawgrademax%2) === 0) {
                        $groupStep = $studentGrade->rawgrademax/2;
                    }
                    else {
                        $groupStep = 1;
                    }

                    $groupRange = array(0 => array());

                    for ($i = $groupStep; $i <= $studentGrade->rawgrademax; $i += $groupStep) {
                        $groupRange[$i] = array();

                        // fix to eternal loop
                        if ($studentGrade->rawgrademax == 0) {
                            break;
                        }
                    }

                    foreach ($merged as $userid => $finalgrade) {
                        $comma = '';

                        if ($c) {
                            $comma = ',';
                        }

                        $c++;
                        $belong = false;

                        if ((int)$enrolledUser->id === $userid) {
                            $colors .= $comma . "'#7cb5ec'";
                            $categories .= $comma . "'" . $enrolledUser->lastname . "'";
                            $belong = true;
                        } else {
                            $colors .= $comma . "'#222'";
                            $categories .= $comma . "'$c'";
                        }

                        $groupKey = floor($finalgrade / $groupStep) * $groupStep;

                        $groupRange[$groupKey]['grades'][] = $finalgrade;

                        if ($belong) {
                            $groupRange[$groupKey]['belong'] = $belong;
                        }

                        $data .= $comma . round($finalgrade, 2);

                    }

                    $totalStudentGrades = count($activityGrades);
                    $categories = '';
                    $comma = '';
                    $data = '';
                    $colors = '';
                    $maxPercentage = 0;
                    $rangeFrom = 0;
                    end($groupRange);
                    $lastGroupKey = key($groupRange);
                    reset($groupRange);

                    $toGroup = 0.1;
                    if ($studentGrade->rawgrademax > 10) {
                        $toGroup = 1;
                    }
                    foreach ($groupRange as $groupkey => $group) {
                        if ($categories !== '') {
                            $comma = ',';
                        }

                        // groupkey is 0
                        $toRange = (double)$groupkey - $toGroup;

                        if(!$toRange){
                            $toRange = $groupkey;
                        }

                        if ($groupkey === $rangeFrom) {
                            $categories .= "'$rangeFrom - ";
                        } else if ($lastGroupKey === $groupkey) {
                            $categories .= "$toRange'" . $comma . "'$groupkey'";
                        } else {
                            $categories .= "$toRange'" . $comma . "'$groupkey - ";
                        }


                        if ($group['belong'] === true) {
                            $colors .= $comma . "'#7cb5ec'";
                        } else {
                            $colors .= $comma . "'#222'";
                        }

                        $totalGroupGrades = count($group['grades']);

                        $groupRange[$groupkey]['percentage'] = round(
                            ($totalGroupGrades / $totalStudentGrades) * 100, 2
                        );

                        if ($groupRange[$groupkey]['percentage'] >= $maxPercentage) {
                            $maxPercentage = $groupRange[$groupkey]['percentage'];
                        }

                        $data .= $comma . $groupRange[$groupkey]['percentage'];


                    }

//                    $avgGradeGraphTitle = get_string('avgGradeGraphTitle', 'local_qmul_dashboard');
//                    $stdGradeGraphTitle = get_string('stdGradeGraphTitle', 'local_qmul_dashboard');
                    $courseAverage = round($activityNumbers['averageGrade'], 2);
                    $studentGrade->finalgrade = round($studentGrade->finalgrade, 2);
                    $userName = $enrolledUser->firstname.' '.$enrolledUser->lastname;

                    $userName = str_replace("'","\'",$userName);


                    $avgGradeGraphTitle = "$userName relative to the average";
                    $avgGradeGraphSubTitle = "$course->fullname<br> $graphTitle";
                    //$stdGradeGraphTitle = get_string('stdGradeGraphTitle', 'local_qmul_dashboard');
                    $stdGradeGraphTitle = "$userName to ". get_string('stdGradeGraphTitle', 'local_qmul_dashboard')." Histogram";

                    $finalgrade = grade_format_gradevalue($studentGrade->finalgrade, $grade_item);
                    $rawgrademax = grade_format_gradevalue($studentGrade->rawgrademax, $grade_item);
                    $courseAveragStr = grade_format_gradevalue($courseAverage, $grade_item);

                    $avgGradeGraphTitle = str_replace("'","\'",$avgGradeGraphTitle);
                    $avgGradeGraphSubTitle = str_replace("'","\'",$avgGradeGraphSubTitle);
                    $stdGradeGraphTitle = str_replace("'","\'",$stdGradeGraphTitle);


                    $RENDER .= <<<EOT
                    <script>

                        $(function () {


                            $('#chart$enrolledUser->id').highcharts({
                                chart: {
                                    type: 'bar'
                                },
                                title: {
                                    text: '$avgGradeGraphTitle'
                                },
                                subtitle: {
                                    text: '$avgGradeGraphSubTitle'
                                },
                                xAxis: {
                                    categories: ['Grade']
                                },
                                yAxis: {
                                    title: {
                                        text: 'Range'
                                    },
                                    tickInterval: $studentGrade->rawgrademax/20,
                                    max:  $studentGrade->rawgrademax
                                },
                                series: [{
                                    name: '$userName $finalgrade',
                                    data: [$studentGrade->finalgrade]
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

                            $('#chartAgainst$enrolledUser->id').highcharts({
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
                                subtitle: {
                                    text: '$avgGradeGraphSubTitle'
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
                        });
                    </script>
EOT;

                }


                $RENDER .= html_writer::end_tag('div');
                $RENDER .= html_writer::end_tag('div');
                $RENDER .= html_writer::end_tag('div');
            }

        }else{

            $RENDER .= html_writer::div(
                '<p>'.get_string('teachersViewNoGrades', 'local_qmul_dashboard').'<p>',
                null, array('class' => 'alert alert-warning')
            );
        }

        echo json_encode($RENDER, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }

}