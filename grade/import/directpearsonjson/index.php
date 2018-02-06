<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once('../../../config.php');
require_once('lib.php');
require_once('grade_import_form.php');
require_once('lang/en/gradeimport_directpearsonjson.php');
require_once('../../../lib/oauthlib.php');

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url(new moodle_url('/grade/import/directpearsonjson/index.php', array('id'=>$id)));
$PAGE->set_pagelayout('admin');



if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);
require_capability('moodle/grade:import', $context);
require_capability('gradeimport/directpearsonjson:view', $context);

// print header
$strgrades = get_string('grades', 'grades');
$actionstr = get_string('pluginname', 'gradeimport_directpearsonjson');

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeimport/directpearsonjson:publish', $context);
}



if ((!isset($CFG->pearson_grade_sync_url) || trim($CFG->pearson_grade_sync_url)==='')) {
    //Show error that Pearson Grade Sync URL is not set
    print_grade_page_head($COURSE->id, 'import', 'directpearsonjson', get_string('pagetitle', 'gradeimport_directpearsonjson'));
    echo 'We are unable to sync grades due to a configuration issue.  Please contact your Moodle administrator to configure the Pearson Custom block correctly. <a href="http://www.pearsonhighered.com/mlm/lms-help-for-educators/" target="_blank">More information</a>';
    echo $OUTPUT->footer();
}

$mform = new grade_import_form();

if ($data = $mform->get_data()) {
	print_grade_page_head($COURSE->id, 'import', 'directpearsonjson', get_string('pagetitle', 'gradeimport_directpearsonjson'));

	$gradeURL = $CFG->pearson_grade_sync_url . '/items/' . $COURSE->id;
	
	// The data to send to the API
	$postData = array(
		'nothing' => 'at this time',    
		'important' => 'at this time'
        );
        
    $oauthData = array(
		'oauth_consumer_key' => $CFG->pearson_key,    
		'oauth_nonce' => '5141207439707735494',
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_timestamp' => '1345817820',
		'oauth_token' => 'tpi',
		'oauth_version' => '1.0'
    );
    
    
    
	$sig_array = array(
		'POST',
		preg_replace('/%7E/', '~', rawurlencode($gradeURL)),            
		rawurlencode(oauth_helper::get_signable_parameters($oauthData)),
	);

	$base_string = implode('&', $sig_array);

	
	$secret = $CFG->pearson_secret . '&';

	$sig = base64_encode(hash_hmac('sha1', $base_string, $secret, true));        
    
// 	echo '<br/>base string: ' . $base_string;
//	echo '<br/>Secret: ' . $secret;
//	echo '<br/>';

    
    
    // Setup cURL	
	$authHeader = 'Authorization: ' .
	              'OAuth realm="http%3A%2F%2Ftpidev.pearsoncmg.com%2Fapi%2Ftools%2Fprofiles%2Fsearch", ' .	              
	              'oauth_consumer_key="'. $CFG->pearson_key .'", ' .
	              'oauth_nonce="5141207439707735494", '.
	              
	              //oauth signature will have to be generated
	              'oauth_signature="' . rawurlencode($sig) . '", '. 
	              'oauth_signature_method="HMAC-SHA1", ' .
	              'oauth_timestamp="1345817820", ' .
	              'oauth_token="tpi", ' .
	              'oauth_version="1.0"';
	              
	$contentTypeHeader = 'Content-Type: application/json';

	$ch = new curl(array('cache'=>false));
	$ch->setHeader(array($authHeader,$contentTypeHeader));

	// Send the request
	$pearsonResponse = $ch->post($gradeURL, json_encode($postData));
	
	
			
	if (empty($pearsonResponse)) {
	    // some kind of an error happened		    
		echo($ch->error);
	} else {
            //grab the info from the request
		$info = $ch->info;
	    
	    if ($info['http_code'] == '200') {
                $items = json_decode($pearsonResponse);
		
				$error = '';
				$resultStats = import_pearsonjson_grades($items, $course, $error);
				echo 'Congratulations! You successfully synced your Pearson grades. Click your Grader Report to see your Pearson assignments and student scores.';
				echo '<ul>';
				echo '<li>Status of sync: Success!</li>';						
				echo '<li>Number of grades created: '. $resultStats->numGradesCreated . '</li>';	
				echo '<li>Number of grades updated: '. $resultStats->numGradesUpdated . '</li>';	
				echo '<li>Number of items created: '. $resultStats->numItemsCreated . '</li>';
				echo '<li>Number of items updated: '. $resultStats->numItemsUpdated . '</li>';
				echo '<li>Number of locked grades: '. $resultStats->numLockedGrades . '</li>';
				echo '</ul>';
        }
		else if ($info['http_code'] == '404') {
			echo 'We could not find any Pearson MyLab & Mastering assignments to sync with your course.<br/><br/>';
			echo 'Please check your Pearson course help to make sure you have set up your Pearson course settings to sync grades.<br/><br/>';
			echo 'Your Pearson course may not support syncing grades with Moodle, click <a href="http://247pearsoned.custhelp.com/app/answers/detail/a_id/12108"  target="_blank">here</a> for more information.<br/><br/>';
			echo 'Please contact Pearson’s 24/7 <a href="http://247pearsoned.custhelp.com/"  target="_blank">Help Desk</a> if you continue to receive this error.';
		}
            //if something OTHER then success happens
	    else {			    
		    if (empty($info['http_code'])) {
			    die("No HTTP code was returned"); 
		    } else {
			// load the HTTP codes
			$http_codes = parse_ini_file("httpcodes.ini");
		
			// echo results
			echo "The server responded: <br />";
			echo $info['http_code'] . " " . $http_codes[$info['http_code']];
			echo "<br/><br/>";
			echo "We are unable to sync grades due to a configuration issue.  Please contact your Moodle administrator to have them verify the Pearson block is configured correctly.";
		    }
	    }
	    

	    //handle the success case
            
	}	
	
	echo $OUTPUT->footer();

	die;
}

print_grade_page_head($COURSE->id, 'import', 'directpearsonjson', get_string('pagetitle', 'gradeimport_directpearsonjson'));

echo "Click the 'Sync Grades from Pearson Grades' button to add assignments and grades from your Pearson gradebook to your Moodle grades.";

$mform->display();

echo $OUTPUT->footer();


