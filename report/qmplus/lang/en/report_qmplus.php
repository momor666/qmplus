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
 * Lang strings.
 *
 * Language strings to be used by report/qmpluss
 *
 * @package    report_qmplus
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['eventcomponent'] = 'Component';
$string['eventcontext'] = 'Event context';
$string['eventloggedas'] = '{$a->realusername} as {$a->asusername}';
$string['eventorigin'] = 'Origin';
$string['eventrelatedfullnameuser'] = 'Affected user';
$string['eventreportviewed'] = 'Log report viewed';
$string['eventuserreportviewed'] = 'User log report viewed';
$string['log:view'] = 'View course logs';
$string['log:viewtoday'] = 'View today\'s logs';
$string['page'] = 'Page {$a}';
$string['logsformat'] = 'Logs format';
$string['nologreaderenabled'] = 'No log reader enabled';
$string['page-report-log-x'] = 'Any log report';
$string['page-report-log-index'] = 'Course log report';
$string['page-report-log-user'] = 'User course log report';
$string['selectlogreader'] = 'Select log reader';
$string['notifyadmins'] = 'Send email to notify about new file uploads';
$string['notifyadmins2'] = 'Send email to notify about new blacklisted file uploads';
$string['newsettings'] = 'Send email to notify about new settings';
$string['messageprovider:newfiles'] = 'Notification of new uploaded files';
$string['messageprovider:newsettings'] = 'Notification of change of settings';

$string['filereportstitle'] = 'Uploaded Files Report';
$string['filereports'] = 'Uploaded files';
$string['mimetype'] = 'mime-type';
$string['filereportsdesc'] = 'Please select a mime-type to generate the report for';

$string['blacklistedreportstitle'] = 'Blacklisted files Report';
$string['blacklisted'] = 'Blacklisted Uploaded files';
$string['blacklistedtype'] = 'file extension';
$string['blacklistedeportsdesc'] = 'Please select a file extension to generate the report for';

$string['backuptitle'] = 'Back-up Files Report';
$string['backupreports'] = 'Back-up files';
$string['backupreportsdesc'] = 'Select a date range to generate the report for';

$string['configlogtitle'] = 'Config log Report';
$string['configlogreports'] = 'QM+ Config log';
$string['configlogreportsdesc'] = 'Select a date range to generate the config log report for';

$string['coursefilestitle'] = 'Course files Report';
$string['coursefilesreports'] = 'Course files';
$string['course'] = 'Course';
$string['coursefilesreportsdesc'] = 'Select a course to generate the report for';

$string['settings'] = 'QM+ File Reports Settings';
$string['externalUsers'] = 'External Users';
$string['externalUsersDescr'] = 'Add each external\'s user email per line followed by a comma ( , ) e.g "ausers@email.com,"';

$string['taskFrequency'] = 'Task lookback time';
$string['taskFrequencyDescr'] = 'The time in hours that the task will look back for new uploaded files at the execution time';

$string['blacklistedSettings'] = 'Blacklisted Extensions';
$string['blacklistedSettingsDescr'] = 'Add each file extension per line followed by a comma ( , ) e.g "exe,"';


$string['taskemailsubject'] = 'QM+ New files';
$string['taskemailmessage'] = 'Latest Uploaded files QM+ Report. Please see attachment.';
$string['taskemailmessagehtmlheader'] = '<p><h3>QM+ Report</h3><b>Latest Uploaded files</b><br>';
$string['taskemailmessagehtmltotal'] = '<p>Total new uploaded files:<b>';
$string['taskemailmessagehtmlfooter'] = '</b></p>For details please see attachment or click the link below:';


$string['tasknewsettingsemailsubject'] = 'QM+ New settings';
$string['tasknewsettingsemailmessage'] = 'QM+ Change of settings report. Please see attachment.';
$string['tasknewsettingsemailmessagehtmlheader'] = '<p><h3>QM+ Report</h3><b>Latest settings updates</b><br>';
$string['tasknewsettingsemailmessagehtmltotal'] = '<p>Total setttings updated:<b>';
$string['tasknewsettingsemailmessagehtmlfooter'] = '</b></p>For details please see attachment or click the link below:';

$string['tasknewblacklistedemailsubject'] = 'QM+ New Blacklisted files';
$string['tasknewblacklistedemailmessage'] = 'Latest blacklisted files QM+ Report. Please see attachment.';
$string['tasknewblacklistedemailmessagehtmlheader'] = '<p><h3>QM+ Report</h3><b>Latest blacklisted files</b><br>';
$string['tasknewblacklistedemailmessagehtmltotal'] = '<p>Total blacklisted files uploaded:<b>';
$string['tasknewblacklistedemailmessagehtmlfooter'] = '</b></p>For details please see attachment or click the link below:';

$string['configlog'] = 'Config log';
$string['success'] = 'Report has been generated succesfully!';

$string['getreport'] = 'Download the Report';
$string['qmplus:view'] = 'View QMplus reports';
$string['qmplus:newfiles'] = 'View new file reports';
$string['pluginname'] = 'QM+ Admin Reports';

