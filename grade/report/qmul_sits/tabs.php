<?php
/**
 * Outputs navigation tabs for the grader report
 *
 * @package   gradereport_qmul_sits
 */

    $row = $tabs = array();
    $tabcontext = context_course::instance($COURSE->id);
    $row[] = new tabobject('qmul_sits_report',
                           $CFG->wwwroot.'/grade/report/qmul_sits/index.php?id='.$courseid,
                           get_string('pluginname', 'gradereport_qmul_sits'));
    $tabs[] = $row;
    echo '<div class="gradedisplay">';
    print_tabs($tabs, $currenttab);
    echo '</div>';

