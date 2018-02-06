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
 * Strings for component 'assignsubmission_qmcw_coversheet', language 'en'
 *
 * @package   assignsubmission_qmcw_coversheet
 * @copyright  2017 Queen Mary University of London
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

$string['pluginname'] = 'Coursework coversheet submission';
$string['name'] = 'Coursework coversheet submission';
$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this submission method will be enabled by default for all new assignments.';
$string['printcoversheet'] = 'Print coversheet';
$string['addprintcoversheet'] = 'Add print coversheet';
$string['add  printcoversheet'] = 'Print coversheet';
$string['enabled'] = 'Coursework coversheet submission';
$string['enabled_help'] = 'If enabled, students are able to print a coversheet from the assignment submission summary page 
in order to attach to a physical assignment.';

$string['addsubmission_status_scanned_submitted'] = 'This students\'s coursework has been submitted for grading.';
$string['addsubmission_action_scanned_submitted'] = 'To reset the scan click on this link.'; //TODO
$string['addsubmission_status_not_scanned_submitted'] = '<b>This user\'s coursework has not been submitted for grading.</b> 
<br>Clicking on Save changes will scan and submit the student\'s coursework.
(This has the same effect as using the QR code reader with a physical coversheet.)
If you are submitting after the due date, the date will default to the assignment due date. To override this e.g. if the student submission was received late, use the date selector to set a different date. 
Cancel to leave this page.';
$string['addsubmission_action_not_scanned_submitted'] = 'Click on the Save button to scan and submit the user\' coursework.<br> 
(Please use this only in exceptional cases, otherwise use the <a href="{$a->url}">Scan page</a> 
to physically scan the coursework. 
Clicking cancel does not update the user\'s submission in any way).';
$string['addsubmission_status_error'] = 'Error - something has gone wrong here. The user\'s coursework has been scanned but not submitted or visa versa';
$string['addsubmission_action_error'] = 'Contact an adminstistrator.'; //TODO: reset link
$string['addsubmission_status'] = 'Submission status';
$string['addsubmission_action'] = 'Action';
$string['syncsubs'] = 'Sync the submissiondate for the coversheet modules';
$string['esubmissiondelay'] = '<b>Note to students</b>
 <br>The due date for this assignment has now passed. If you have already handed your coursework assignment in to your departmental office, 
please be patient until the your coursework is entered into the system by your departmental administrator.';


