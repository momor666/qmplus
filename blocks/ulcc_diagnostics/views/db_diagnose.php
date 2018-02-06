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
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$PAGE->set_url('/blocks/ulcc_diagnostics/views/db_connect.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
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

echo get_string('dbtype','block_ulcc_diagnostics');

if (!get_config('enrol_database','dbtype')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('enrol_database','dbtype').'</span>';
}

echo '<br />';

echo get_string('dbhost','block_ulcc_diagnostics'); 
if (!get_config('enrol_database','dbhost')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('enrol_database','dbhost').'</span>';
}

echo '<br />';

echo get_string('dbuser','block_ulcc_diagnostics'); 
if (!get_config('enrol_database','dbuser')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('enrol_database','dbuser').'</span>';
}

echo '<br />';

echo get_string('dbname','block_ulcc_diagnostics'); 
if (!get_config('enrol_database','dbname')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('enrol_database','dbname').'</span>';
}

echo '<br />';
				
$extdb = ADONewConnection(get_config('enrol_database','dbtype'));

if($extdb->Connect(get_config('enrol_database','dbhost'), get_config('enrol_database','dbuser'), get_config('enrol_database','dbpass'), get_config('enrol_database','dbname'), true)) {
	$extdb->debug = true;
	$extdb->SetFetchMode(ADODB_FETCH_ASSOC);
	$extdb->connectSID = true;
	
	echo '<span style="color:#006600">'.get_string('connection','block_ulcc_diagnostics').'</span>'; 
}else{
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noconnection','block_ulcc_diagnostics').'</a>'; 			
	echo  '<p>'.$extdb->ErrorMsg().'</p>';
}

echo '<br />';

$courses = 0;
$users = 0;

echo $OUTPUT->heading(get_string('courses','block_ulcc_diagnostics')); 
if($result = $extdb->Execute("SELECT * FROM ".get_config('enrol_database','newcoursetable'))) {
    echo '<p>External Courses: '.$result->RecordCount().'</p>';
}

if($result = $extdb->SelectLimit("SELECT * FROM ".get_config('enrol_database','newcoursetable'),10,0)) {
	echo '<span style="color:#006600">'; 
	echo '<table>' ;
        echo '<tr>' ;
        foreach (array_keys($result->fields) as $key) {
            echo "<th>$key</th>";
        }
        echo '</tr>' ;
        while (!$result->EOF) {
            echo '<tr>' ;
            foreach (array_keys($result->fields) as $key) {
                if(!$DB->get_record('course',array(get_config('enrol_database','localcoursefield')=>$result->fields[get_config('enrol_database','remotecoursefield')]))) {
                    $courses++;
                }
		 		echo '<td>'.$result->fields[$key].'</td>' ;
            }
            $result->MoveNext(); 
            echo '</tr>' ;
        }
    echo '</table></span>';
}else{
	echo '<span style="color:#ff0000">'.get_string('noresults','block_ulcc_diagnostics').'</span>';
}

echo '<br />';

echo $OUTPUT->heading(get_string('enrolments','block_ulcc_diagnostics')); 
if($result = $extdb->Execute("SELECT * FROM ".get_config('enrol_database','remoteenroltable'))) {
    $enrolments = $result->RecordCount();
    echo '<p>External Enrolments: '.$enrolments.'</p>';
}
if($result = $extdb->SelectLimit("SELECT * FROM ".get_config('enrol_database','remoteenroltable'),10,0)) {
	echo '<span style="color:#006600">'; 
	echo '<table>' ;
        echo '<tr>' ;
        foreach (array_keys($result->fields) as $key) {
            echo "<th>$key</th>" ;
        }
        echo '</tr>' ;
        while (!$result->EOF) {
            echo '<tr>' ;
            foreach (array_keys($result->fields) as $key) {
                echo '<td>'.$result->fields[$key].'</td>' ;
            }
            $result->MoveNext(); 
            echo '</tr>' ;
        }
    echo '</table></span>'; 
}else{
	echo  '<span style="color:#ff0000">'.get_string('noresults','block_ulcc_diagnostics').'</span>';
}

echo $OUTPUT->heading(get_string('users','block_ulcc_diagnostics')); 
if($result = $extdb->Execute("SELECT DISTINCT(".get_config('enrol_database','remoteuserfield').") FROM ".get_config('enrol_database','remoteenroltable'))) {
    echo '<p>'.get_string('external','block_ulcc_diagnostics').' '.get_string('users','block_ulcc_diagnostics').': '.$result->RecordCount().'</p>';
    //echo '<p>(';
    while (!$result->EOF) {
            foreach ($result as $userid) {
                if(!$DB->get_record('user',array(get_config('enrol_database','localuserfield')=>$userid[get_config('enrol_database','remoteuserfield')]))) {
                    $users++;
                    //echo "'".$userid[get_config('enrol_database','remoteuserfield')]."',";
                }
            }
            $result->MoveNext(); 
        }
    //echo ')</p>';
}

$moodle_enrolments = $DB->count_records('role_assignments',array('component'=>'enrol_database'));

echo $OUTPUT->heading(get_string('summary','block_ulcc_diagnostics'));
echo '<p>Moodle Enrolments: '.$moodle_enrolments.'</p>';
echo '<ul>';
echo '<li>'.get_string('missing','block_ulcc_diagnostics').' '.get_string('users','block_ulcc_diagnostics').': '.$users.'</li>';
echo '<li>'.get_string('missing','block_ulcc_diagnostics').' '.get_string('courses','block_ulcc_diagnostics').': '.$courses.'</li>';
echo '<li>'.get_string('missing','block_ulcc_diagnostics').' '.get_string('enrolments','block_ulcc_diagnostics').': '.($enrolments - $moodle_enrolments).'</li>';
echo '</ul>';


echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>