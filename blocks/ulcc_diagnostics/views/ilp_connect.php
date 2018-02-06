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

echo $OUTPUT->heading('ILP Database');
echo $OUTPUT->box_start('generalbox dbdiag');
echo $OUTPUT->heading(get_string('settings','block_ulcc_diagnostics'));

echo get_string('dbtype','block_ulcc_diagnostics');

if (!get_config('block_ilp','dbconnectiontype')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=blocksettingilp" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('block_ilp','dbtype').'</span>';
}

echo '<br />';

echo get_string('dbhost','block_ulcc_diagnostics'); 
if (!get_config('block_ilp','dbhost')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=blocksettingilp" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('block_ilp','dbhost').'</span>';
}

echo '<br />';

echo get_string('dbuser','block_ulcc_diagnostics'); 
if (!get_config('block_ilp','dbuser')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=blocksettingilp" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('block_ilp','dbuser').'</span>';
}

echo '<br />';

echo get_string('dbname','block_ulcc_diagnostics'); 
if (!get_config('block_ilp','dbname')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=blocksettingilp" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.get_config('block_ilp','dbname').'</span>';
}

echo '<br />';
				
$extdb = ADONewConnection(get_config('block_ilp','dbconnectiontype'));

if($extdb->Connect(get_config('block_ilp','dbhost'), get_config('block_ilp','dbuser'), get_config('block_ilp','dbpass'), get_config('block_ilp','dbname'), true)) {
	$extdb->debug = true;
	$extdb->SetFetchMode(ADODB_FETCH_ASSOC);
	$extdb->connectSID = true;
	
	echo '<span style="color:#006600">'.get_string('connection','block_ulcc_diagnostics').'</span>'; 
}else{
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=blocksettingilp" style="color:#ff0000">'.get_string('noconnection','block_ulcc_diagnostics').'</a>'; 			
	echo  '<p>'.$extdb->ErrorMsg().'</p>';
}

echo '<br />';

$plugins = array('mis_plugin_simple_studenttable','mis_misc_timetable_table','mis_learner_contact_table');

foreach($plugins as $plugin) {
   //$plugin_name = substr($plugin->name,4);
   echo $OUTPUT->heading($plugin); 
   if(get_config('block_ilp',$plugin)) {
        if($result = $extdb->SelectLimit("SELECT * FROM ".get_config('block_ilp',$plugin),10,0)) {
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
                        //echo '<td>'.$result->fields[$key].'['.gettype($result->fields[$key]).']</td>' ;
        		 		 echo '<td>'.$result->fields[$key].'</td>' ;
                    }
                    $result->MoveNext(); 
                    echo '</tr>' ;
                }
            echo '</table></span>';
        }else{
        	echo '<span style="color:#ff0000">'.get_string('noresults','block_ulcc_diagnostics').'</span>';
        }
   }else{
       echo '<span style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</span>';
   }
    
    echo '<br />'; 
}
				
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>