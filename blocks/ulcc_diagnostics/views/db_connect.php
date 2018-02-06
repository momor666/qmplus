<?php

/**
 * Moodle ULCC Admin page.
 *
 * @package    block_ulcc_diagnostic
 * @copyright  2011 onwards ULCC (http://www.ulcc.ac.uk)
 * 
 */
 
require('../../../config.php');
global $CFG,$USER,$DB;
require_once($CFG->dirroot.'/lib/adodb/adodb.inc.php');

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/blocks/ulcc_diagnostics/views/db_connect.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_course($SITE);
$PAGE->set_pagetype('category-upload');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('report');
$PAGE->navbar->add('Database Connection');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading('Enrolment Database');
echo $OUTPUT->box_start('generalbox dbdiag');
echo $OUTPUT->heading(get_string('settings','block_ulcc_diagnostics'));


$dbtype = get_config('enrol_database','dbtype');
$dbhost = get_config('enrol_database','dbhost');
$dbuser = get_config('enrol_database','dbuser');
$dbname = get_config('enrol_database','dbname');
$dbpass = get_config('enrol_database','dbpass');
$dbtype = get_config('enrol_database','dbtype');

echo get_string('dbtype','block_ulcc_diagnostics');

if (!$dbtype) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.$dbtype.'</span>';
}

echo '<br />';

echo get_string('dbhost','block_ulcc_diagnostics'); 
if (!$dbhost) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.$dbhost.'</span>';
}

echo '<br />';

echo get_string('dbuser','block_ulcc_diagnostics'); 
if (!$dbuser) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.$dbuser.'</span>';
}

echo '<br />';

echo get_string('dbname','block_ulcc_diagnostics'); 
if (!$dbname) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.$dbname.'</span>';
}

echo '<br />';
				
$extdb = ADONewConnection($dbtype);

if ($dbhost && $dbuser && $dbpass && $dbname) {

    if ($extdb->Connect($dbhost, $dbuser, $dbpass, $dbname, true)) {
        $extdb->debug = true;
        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        $extdb->connectSID = true;

        echo '<span style="color:#006600">' . get_string('connection', 'block_ulcc_diagnostics') . '</span>';
    } else {
        echo '<a href="' . $CFG->wwwroot . '/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">' . get_string('noconnection', 'block_ulcc_diagnostics') . '</a>';
        echo '<p>' . $extdb->ErrorMsg() . '</p>';
    }

    echo '<br />';

    echo $OUTPUT->heading(get_string('courses', 'block_ulcc_diagnostics'));

    if ($result = $extdb->SelectLimit("SELECT * FROM " . get_config('enrol_database', 'newcoursetable'), 10, 0)) {
        echo '<span style="color:#006600">';
        echo '<table>';
        echo '<tr>';
        foreach (array_keys($result->fields) as $key) {
            echo "<th>$key</th>";
        }
        echo '</tr>';
        while (!$result->EOF) {
            echo '<tr>';
            foreach (array_keys($result->fields) as $key) {
                echo '<td>' . $result->fields[$key] . '</td>';
            }
            $result->MoveNext();
            echo '</tr>';
        }
        echo '</table></span>';
    } else {
        echo '<span style="color:#ff0000">' . get_string('noresults', 'block_ulcc_diagnostics') . '</span>';
    }

    echo '<br />';

    if ($result = $extdb->SelectLimit("SELECT * FROM " . get_config('enrol_database', 'remoteenroltable'), 10, 0)) {
        echo '<span style="color:#006600">';
        echo '<table>';
        echo '<tr>';
        foreach (array_keys($result->fields) as $key) {
            echo "<th>$key</th>";
        }
        echo '</tr>';
        while (!$result->EOF) {
            echo '<tr>';
            foreach (array_keys($result->fields) as $key) {
                echo '<td>' . $result->fields[$key] . '</td>';
            }
            $result->MoveNext();
            echo '</tr>';
        }
        echo '</table></span>';
    } else {
        echo '<span style="color:#ff0000">' . get_string('noresults', 'block_ulcc_diagnostics') . '</span>';
    }
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>