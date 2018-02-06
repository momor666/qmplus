<?php

/**
 * Moodle ULCC Admin page.
 *
 * @package    block_ulcc_diagnostic
 * @copyright  2011 onwards ULCC (http://www.ulcc.ac.uk)
 *
 * refactored 6th July 2011 for readability
 *
 * displays list of statistics from stats directory and allows to download them
 *
 * Conditions: 
 *            /stat directory is in the same dir as $CFG->dirroot and $CFG->dataroot
 * 
 * 
 */


require ('../../../config.php');
global $CFG;
require_once ($CFG->dirroot.'/lib/filelib.php');


require_login();
require_capability('moodle/site:config', context_system::instance());


//real path to stats dir   
$statDir = str_replace('/docroot', "", $CFG->dirroot)."/stats/";
$statDir = str_replace('\htdocs', "", $statDir);  //remove on Linux server
$statDir = str_replace('\docroot', "", $statDir);  //remove on Linux server


// extract filename to download
$relativepath = get_file_argument();
$args = explode('/', ltrim($relativepath, '/'));
$statFilename = array_shift($args);

//copy file to the temp folder, send and delete
if ($statFilename)  {
	copy ($statDir.$statFilename,$CFG->dataroot.'/temp/'.$statFilename);
	$temppath=$CFG->dataroot.'/temp/'.$statFilename;
	send_temp_file($temppath, $statFilename, false);  
}

//if no args - display page with stats files listed from $statDir

$PAGE->set_url('/blocks/ulcc_diagnostics/views/download_statistics.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_course($SITE);
$PAGE->set_pagetype('download_statistics');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('report');
$PAGE->navbar->add('Download Statistics');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('statistics','block_ulcc_diagnostics'));
echo $OUTPUT->box_start('generalbox stats');


if (file_exists($statDir) && $statfiles = array_diff(scandir($statDir), array('.', '..')))
{
	
	echo '<span style="color:#006600"> '. get_string('statfiles','block_ulcc_diagnostics').'</span>';
	echo '<br />';
	echo '<ul>';
	
	foreach($statfiles as $key => $content) {
		
          echo '<li> <a href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/views/download_statistics.php/'.$content.'" style="color:#000000">'.$content.'</a> </li>';
  	}
	echo '</ul>';
}

else 	// if there is no /stats directory or /stats dir not accessible 
	echo '<span style="color:#ff0000">'.get_string('nostatisticsfound','block_ulcc_diagnostics').'</span>';
				echo '<br />';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

?>