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
 * Language strings for the local_qmframework plugin
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */

$string['pluginname'] = 'QMUL Model';

$string['synchronisation'] = 'Synchronisation';
$string['qmmodeldashboard'] = 'Skills Review Progress';
$string['qmmodelsynchronisation'] = 'QMUL Model Synchronisation';
$string['enableplugin'] = 'Enabled';
$string['enableplugin_help'] = 'The QMUL Model plugin synchronisation will not function if this is unchecked.';
$string['dashboardhost'] = 'Skills Review Progress Page host';
$string['dashboardhost_help'] = 'The Mahara MNET host that the Skills Review Progress Page is hosted on.';
$string['institutionshortname'] = 'QMUL Model Institution shortname';
$string['institutionshortname_help'] = 'The Mahara Institution shortname which holds the Skills Review Progress Page template.';
$string['authinstitutionshortname'] = 'Mahara Authentication Institution shortname';
$string['authinstitutionshortname_help'] = 'The Mahara institution used to authenticate from this Moodle into the Mahara Dashboard.';
$string['authwstoken'] = 'Mahara Authentication Institution Webservice Token';
$string['authwstoken_help'] = 'The synchronisation webservice access token of the designated user for the authentication institution.';
$string['wstoken'] = 'Dashboard Webservice Token';
$string['wstoken_help'] = 'The synchronisation webservice access token of the designated user.';
$string['syncuserevent'] = 'Sync User To Mahara Event';
$string['syncgroupevent'] = 'Sync Group To Mahara Event';
$string['syncmemberevent'] = 'Sync Member To Mahara Event';
$string['syncuserdashboardevent'] = 'Sync User\'s Mahara Dashboard Event';
$string['exportattemptevent'] = 'Export the user\'s quiz attempt to its Mahara Dashboard Event';
$string['missing_pluginsettings'] = 'Missing plugin settings';
$string['missing_hostsetting'] = 'Missing Mahara MNET host ID';
$string['missing_institutionsetting'] = 'Missing Mahara institution ID';
$string['missing_authinstitutionsetting'] = 'Missing Mahara authentication institution shortname';
$string['missing_tokensetting'] = 'Missing access token';
$string['missing_authtokensetting'] = 'Missing authentication webservice token';
$string['missing_coursesetting'] = 'Missing QMplus Course ID';
$string['missing_studentsetting'] = 'Missing student role ID';
$string['missing_tutorsetting'] = 'Missing tutor role ID';
$string['missing_enabledsetting'] = 'Missing plugin\'s enabled setting';
$string['missing_groupingsetting'] = 'Missing grouping ID';
$string['missing_adminsetting'] = 'Missing group admin ID';
$string['missing_quizidsetting'] = 'Missing main quiz ID';
$string['missing_maincategorysetting'] = 'Missing main questions category';
$string['missing_theme1setting'] = 'Missing theme one setting';
$string['missing_theme1stage1setting'] = 'Missing theme one stage one setting';
$string['missing_theme1stage2setting'] = 'Missing theme one stage two setting';
$string['missing_theme1stage3setting'] = 'Missing theme one stage three setting';
$string['missing_theme1stage4setting'] = 'Missing theme one stage four setting';
$string['missing_theme2setting'] = 'Missing theme two setting';
$string['missing_theme2stage1setting'] = 'Missing theme two stage one setting';
$string['missing_theme2stage2setting'] = 'Missing theme two stage two setting';
$string['missing_theme2stage3setting'] = 'Missing theme two stage three setting';
$string['missing_theme2stage4setting'] = 'Missing theme two stage four setting';
$string['missing_theme3setting'] = 'Missing theme three setting';
$string['missing_theme3stage1setting'] = 'Missing theme three stage one setting';
$string['missing_theme3stage2setting'] = 'Missing theme three stage two setting';
$string['missing_theme3stage3setting'] = 'Missing theme three stage three setting';
$string['missing_theme3stage4setting'] = 'Missing theme three stage four setting';
$string['missing_theme4setting'] = 'Missing theme four setting';
$string['missing_theme4stage1setting'] = 'Missing theme four stage one setting';
$string['missing_theme4stage2setting'] = 'Missing theme four stage two setting';
$string['missing_theme4stage3setting'] = 'Missing theme four stage three setting';
$string['missing_theme4stage4setting'] = 'Missing theme four stage four setting';
$string['student'] = 'Student';
$string['teacher'] = 'Teacher';
$string['courseid'] = 'QMUL Model Course ID';
$string['courseid_help'] = 'The Moodle course user enrolments will be synced from within to the Mahara host and institution.';
$string['studentrole'] = 'Student role';
$string['studentrole_help'] = 'The Moodle course role to be used for syncing students to Mahara. <br/> These users will be given a copy of the Skills Review Progress Page.';
$string['tutorrole'] = 'Tutor role';
$string['tutorrole_help'] = 'The Moodle course role to be used for syncing students to Mahara. <br/>These users will have access to the Personal Tutor group and the ability to view the Skills Review Progress pages of the students within their group.';
$string['groupingid'] = 'QMUL Model Grouping ID';
$string['groupingid_help'] = 'The QMUL Model Course grouping ID which will contain all Personal Tutor groups.';
$string['dashboardgroupadmin'] = 'QMUL Model Mahara Group Admin';
$string['dashboardgroupadmin_help'] = 'The Mahara user to be set as the default group admin for all QMUL Model Personal Tutor groups (Mahara requirement).';
$string['maincategory'] = 'Main category';
$string['selectaquiz'] = 'Select a quiz';
$string['selectasubcategory'] = 'Select a subcategory';
$string['skillsauditsettings'] = 'Skills audit settings';
$string['skillsaudit'] = 'Skills Review';
$string['skillsaudit_help'] = 'The QMplus quiz to use as the Skills Review.';
$string['stage1'] = 'Stage one';
$string['stage2'] = 'Stage two';
$string['stage3'] = 'Stage three';
$string['stage4'] = 'Stage four';
$string['submitskillsreview'] = 'Submit Skills Review';
$string['theme1'] = 'Theme one';
$string['theme2'] = 'Theme two';
$string['theme3'] = 'Theme three';
$string['theme4'] = 'Theme four';
$string['submitskillsreviewtext'] = 'You have come to the end of the skills review. See a summary of the questions you answered on the right';
$string['thememaincategory'] = 'Theme main category';
$string['thememaincategory_help'] = 'The category that represents a QMUL Model Theme from within the Skills Review quiz.';
$string['thememaincategory_note'] = '<br><br>NOTE: if the theme one stages don\'t show up below, please save the theme category first and then continue setting the  stages.';
$string['viewdashboard'] = 'View Results on your Skills Review Progress Page';
$string['syncgroupmembershiptask'] = 'Sync group membership task';
