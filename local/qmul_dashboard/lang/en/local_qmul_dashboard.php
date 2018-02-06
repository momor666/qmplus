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

/**
 * Version details.
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['settings'] = 'Gradesplus user Settings';
$string['pluginname'] = 'Gradesplus';
$string['navtitle'] = 'Gradesplus';

$string['welcometext'] = 'Each module featured below contains a list of assessments which have been graded or have received 
feedback within QMplus. These grades are provisional and do NOT represent the final grade for the assessment. Final grades
 are subject to confirmation at exam board at which point they will be available for you to view via 
 <a href="https://mysis.qmul.ac.uk/urd/sits.urd/run/siw_lgn">mySIS</a>. Any queries, 
 please contact your school.';

$string['pilottext'] = 'The Gradesplus area is currently being piloted within the following schools:
the School of Biological and Chemical Sciences,
the School of Mathematical Sciences
and the Blizard Institute.
If you do not take any modules within these schools, you will not see any modules listed below.';

$string['qmul_dashboard:pageerrormessage'] = '
Ooops. There is a problem displaying your gradesplus page. Please can you raise this issue on 
<a href="its-helpdesk@qmul.ac.uk">ITS Helpdesk</a> so that we can investigate. 
In the meantime, you can view your <a href="' . $CFG->wwwroot .'/grade/report/overview/index.php">Course Grades Overview</a> 
page for a summary of your 
current QMplus grades.';



//capabilties
$string['qmul_dashboard:view'] = 'Allow users to view gradesplus';
$string['qmul_dashboard:edit'] = 'Allow users to manage the plugin settings to hide the various panels and histograms by category';




$string['panelgrade'] = 'Module Grade';
$string['panelactivitiesgrade'] = 'Activity Grades';
$string['panelactivitiesgrade'] = 'Activity Grades';
$string['panelactivitiestitle'] = 'Activities Summary';
$string['panelactivitiesprogress'] = 'Module Activities Progress';
$string['progress'] = 'Progress';

$string['progressColumnName'] = 'Name';
$string['progressColumnStatus'] = 'Status';
$string['progressColumnExpexted'] = 'Expected By';
$string['progressColumnAverage'] = 'Average Student\'s Status';

$string['activityColumnName'] = 'Activity';
$string['activityColumnGrade'] = 'Grade';
$string['activityColumnFeedback'] = 'Feedback';
$string['activityColumnHistogram'] = 'Grades Histogram';
$string['activityViewFeedback'] = 'View Feedback';
$string['activityTurnitinFeedback'] = 'Turnitin Feedback';
$string['activityViewHistogram'] = 'View Histogram';

$string['avgGradeGraphTitle'] = 'Grade Graph';
$string['stdGradeGraphTitle'] = "Student's Grades";
$string['gradeCategoryTotal'] = 'category total';

// Actions.
$string['activity_completion'] = 'Activity completion';
$string['answered'] = 'Has been answered';
$string['assessed'] = 'Has been assessed';
$string['attempted'] = 'Has been attempted';
$string['awarded'] = 'Has been awarded';
$string['completed'] = 'Has been completed';
$string['finished'] = 'Has been  finished';
$string['graded'] = 'Has received a grade';
$string['marked'] = 'Has been marked';
$string['passed'] = 'Has been passed';
$string['passedscorm'] = 'Scorm has been passed';
$string['posted_to'] = 'You have posted to';
$string['responded_to'] = 'You have responded to';
$string['submitted'] = 'Has been submitted';
$string['viewed'] = 'Link viewed';

#Settings
$string['settingstitle'] = 'Gradesplus Settings';
$string['settingsdesc'] = 'Select a school to define the visibility per dashboard option';
$string['editbutton'] = 'Add View Settings';
$string['removebutton'] = 'Remove Setting';
$string['school'] = 'School/Category';
$string['hide'] = 'Hide item';
$string['activitiessummary'] = 'Activities Summary';
$string['activitiespanel'] = 'Hide panel';
$string['activitieshistogram'] = 'Hide grade histograms';
$string['facebook'] = 'Hide facebook block';

$string['activitysummarymess'] = ' view settings';
$string['facebookmess'] = '"facebook block" view settings';
$string['existmess'] = ' already exists';
$string['addmess'] = ' is saved';
$string['deletemess'] = 'setting deleted';

$string['settingsColumnCategory'] = 'School';
$string['settingsColumnName'] = 'Module Name';
$string['settingsColumnType'] = 'Module Type';
$string['settingsColumnUser'] = 'Author';

//manage category  
$string['manageSettingsURL'] = 'Gradesplus histogram visibilty';
$string['manageSettingsHeaderTitle'] = 'Gradesplus histogram visibilty';
$string['manageSettingsHeaderDesc'] = 'Hide the histogram display in gradesplus for all users enrolled on courses listed under the following category: ';
$string['manageSettingsHideButton'] = 'Hide';
$string['manageSettingsColumnType'] = 'Hidden item';
$string['manageSettingsColumnValue'] = 'histogram';



//activitiespanel
$string['activityAverageProgressTask'] = 'Gradesplus average activity progress';
$string['regradeFinalGradesTask'] = 'Gradesplus regrade final grades';

$string['facebookhelp_help'] = 'If the student belongs to <b>ALL</b> defined schools, the block will be hidden, otherwise it will be visible';


// Teacher's View
$string['teachersViewNoGrades'] = 'This activity has not received any grades yet!';

//
$string['graphDescr'] = "
<p>Both of these graphs aim to show your performance within this activity against the performance of the other students who have been marked.</p>
<p>The 'You relative to the average’ graph shows the average performance across the activity in the top bar measured against your performance in the bottom bar.</p>
<p>The second Histogram graph also compares your performance in the activity to other students who have been awarded grades.
However, it divides the data across a range of values as determined by the mark scheme for the activity.
The blue bar shows which group you fall into which should give you a good indication if you are in a high, middle, or low performing group.
It should also give you an indication of how well your peers have scored overall in the activity.</p>
";

$string['viewCourseLink'] = 'View Course';
$string['viewGradesLink'] = 'View Gradebook';
$string['originalityreport'] = 'View multiple Turnitin similarity reports';
$string['grademarkreport'] = 'View multiple Turnitin feedback reports';
$string['originalityreportlabel'] = 'Similarity';
$string['grademarkfeedbacklabel'] = 'View Turnitin feedback ';
$string['feedbackfileattachments'] = 'Feedback Files';