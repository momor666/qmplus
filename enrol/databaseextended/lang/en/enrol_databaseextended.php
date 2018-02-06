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
 * Strings for component 'enrol_databaseextended', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   enrol_databaseextended
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['dbencoding']               = 'Database encoding';
$string['dbhost']                   = 'Database host';
$string['dbhost_desc']              = 'Type database server IP address or host name';
$string['dbname']                   = 'Database name';
$string['dbpass']                   = 'Database password';
$string['dbsetupsql']               = 'Database setup command';
$string['dbsetupsql_desc']          =
    'SQL command for special database setup, often used to setup communication encoding - example for MySQL and PostgreSQL: <em>SET NAMES \'utf8\'</em>';
$string['dbsybasequoting']          = 'Use sybase quotes';
$string['dbsybasequoting_desc']     =
    'Sybase style single quote escaping - needed for Oracle, MS SQL and some other databases. Do not use for MySQL!';
$string['dbtype']                   = 'Database driver';
$string['dbtype_desc']              =
    'ADOdb database driver name, type of the external database engine.';
$string['dbuser']                   = 'Database user';
$string['debugdb']                  = 'Debug ADOdb';
$string['debugdb_desc']             =
    'Debug ADOdb connection to external database - use when getting empty page during login. Not suitable for production sites!';
$string['defaultcategory']          = 'Default new course category';
$string['defaultcategory_desc']     =
    'The default category for auto-created courses. Used when no new category id specified or not found.';
$string['defaultrole']              = 'Default role';
$string['defaultrole_desc']         =
    'The role that will be assigned by default if no other role is specified in external table.';
$string['ignorehiddencourses']      = 'Ignore hidden courses';
$string['ignorehiddencourses_desc'] =
    'If enabled users will not be enrolled on courses that are set to be unavailable to students.';
$string['localcoursefield']         = 'Local course field';
$string['localrolefield']           = 'Local role field';
$string['localuserfield']           = 'Local user field';
$string['newcoursetable']           = 'Remote new courses table';
$string['newcoursetable_desc']      =
    'Specify of the name of the table that contains list of courses that should be created automatically. Empty means no courses are created.';
$string['newcoursecategory']        = 'New course category id field';
$string['newcoursefullname']        = 'New course full name field';
$string['newcourseidnumber']        = 'New course ID number field';
$string['newcourseshortname']       = 'New course short name field';
$string['pluginname']               = 'External database extended';
$string['pluginname_desc']          = 'You can use an external database (of nearly any kind) to '.
                                      'control your enrolments. It is assumed your external '.
                                      'database contains at least a field containing a course '.
                                      'ID, and a field containing a user ID. These are compared '.
                                      'against fields that you choose in the local course and user'.
                                      ' tables. This version has been extended to allow categories '.
                                      'and group memberships to be synced too. The assumption is '.
                                      'that your categories table will have a unique id for each'.
                                      ' category and a parentcategory field, with the uniqueid of '.
                                      'another category. Also, your enrolments table will have a '.
                                      'groupname field.';
$string['connectionoptionsheader']  = 'Connection options';
$string['globalsettingsheader']		= 'Global settings';
$string['remotecoursefield']        = 'Remote course field';
$string['remotecoursefield_desc']   =
    'The name of the field in the remote table that we are using to match entries in the course table.';
$string['remoteenroltable']         = 'Remote user enrolment table';
$string['remoteenroltable_desc']    =
    'Specify the name of the table that contains list of user enrolments. Empty means no user enrolment sync.';
$string['remoterolefield']          = 'Remote role field';
$string['remoterolefield_desc']     =
    'The name of the field in the remote table that we are using to match entries in the roles table.';
$string['remoteuserfield']          = 'Remote user field';
$string['settingsheaderdb']         = 'Connection settings';
$string['settingsheaderlocal']      = 'Local field mapping';
$string['settingsheaderremote']     = 'Remote enrolment sync';
$string['settingsheadernewcourses'] = 'Creation of new courses';
$string['remoteuserfield_desc']     =
    'The name of the field in the remote table that we are using to match entries in the user table.';
$string['templatecourse']           = 'New course template';
$string['templatecourse_desc']      =
    'Optional: auto-created courses can copy their settings from a template course. Type here the shortname of the template course.';

$string['remotegroupfield']         = 'Remote group name field';
$string['remotegroupfield_desc']    = 'If present, this will be used to both create groups (all unique group names found will be made in their respective courses) and thewn add the enrolled user as group members. To add a user to two groups, make duplicate rows in your table/view with different group names.';
$string['newcoursesummary']         = 'New course summary field';
$string['remotecoursetablekey']     = 'Remote course table key';
$string['remotecoursetablekey_desc'] = 'Which field of the remote courses table corresponds to the course field of the remote enrolments table?';

$string['settingsheadernewcategories'] = 'Creation of new categories';
$string['remotecategorytable']         = 'Remote category table';
$string['remotecategorytable_desc'] = 'This holds the categories that will be made, with the parents field and the category field from the courses table referencing whatever field si named below as the key';
$string['rootcategory'] = 'Root category';
$string['rootcategory_desc'] = 'This is the value of the site-level category in the remote database in whichever field is specified as the key for the categories table';
$string['remotecategorytablekey'] = 'Remote category table key field';
$string['remotecategoryname'] = 'Remote category name field';
$string['remotecategorydescription'] = 'Remote category description field';
$string['remotecategoryparent'] = 'Remote category parent field';

$string['relationspage'] = 'Extended database relations';
$string['relationspagelink'] = '<a href="'.$CFG->wwwroot.'/enrol/databaseextended/relations.php">Click here to configure the Extended database relations</a>';
$string['memoryuse'] = 'Memory in use: {$a->current}, memory at peak: {$a->max}';
$string['rootcategory'] = 'Root category name';
$string['rootcategory_desc'] = 'What is the name of the root category in the external DB? This only appears as the parent category attribute of top level categories.';
$string['dbconfigmissing'] = 'Config stuff not present - cannot connect to external DB';
$string['dbcommunicationerror'] = 'Error while communicating with external enrolment database';
$string['startingsync'] = 'Starting {$a} sync. ';
$string['tablemissingconfig'] = 'The {$a} table doesn\'t have all the config variables it needs';
$string['tablefailedsanity'] = '{$a} failed sanity check.';
$string['populatecachefail'] = 'Failed to populate {$a} cache.';
$string['endingsync'] = 'Ending sync for {$a} with empty caches.';
$string['syncstarted'] = 'Sync process started at {$a}';
$string['syncrunningfor'] = 'Sync running for {$a}';
$string['syncfinished'] = 'Sync finished at {$a->timenow}. Total time: {$a->humanreadabletime}';
$string['zeroprogress'] = 'Progress: 0%';
$string['crossreferencing'] = 'Cross referencing {$a} items:';
$string['cachelooping'] = 'Looping through cache deleting any that need it...';
$string['nothingtodelete'] = 'Nothing to delete';
$string['skipped'] = 'Skipped {$a->numberofrows} rows on pass {$a->pass} during {$a->table} cross reference';
$string['updated'] = 'Updated {$a->numberofrows} rows on pass {$a->pass} during {$a->table} cross reference';
$string['added'] = 'Added {$a->numberofrows} rows on pass {$a->pass} during {$a->table} cross reference';
$string['cachemissingvalues'] = '{$a} cache was missing the following values: ';
$string['minute'] = 'minute';
$string['minutes'] = 'minutes';
$string['second'] = 'second';
$string['seconds'] = 'seconds';
$string['hour'] = 'hour';
$string['hours'] = 'hours';
$string['unique'] = 'Unique';
$string['flagfield'] = 'Hidden ID field';
$string['configok'] = 'Config is OK sync will run';
$string['configwrong'] = 'Config is missing required fields. Sync will not run';
$string['memorylimit'] = 'Memory limit';
$string['memorylimit_desc'] = 'If you want to limit how much memory the sync script can use, this needs to be set, otherwise it\'ll have no limit and may crash the server. Use the same syntax as php.ini e.g. 500M';
$string['addflags_desc'] = 'Note: only some tables (just courses right now). If you have existing data, then it will be ignored unless it is marked (flagged in the databaseextended_flags table) as having been created by this plugin. This can cause a problem if you then try to sync data that would create a duplicate of what\'s already there. Setting this to true will cause extra checks to be made to see if a duplicate exists and if so, it will flag it so that from now on, it is updated or deleted in line with the sync data. This only needs to be done once at the start.';
$string['disableupdates'] = 'Disable updates';
$string['disableupdates_help'] = 'This will make the sync only create or delete. Choose this option if you want your staff to be able to make changes to e.g. the course titles without them getting overwritten each night. Be warned that altering the field that\'s used as the unique identifier will cause the sync script to be unable to find the thins and it\'ll create a duplicate.';
$string['claimexisting'] = 'Claim existing items';
$string['claimexisting_help'] = 'Anything stuff that matches records in the external database will be flagged so that the sync script will manage them from now on (delete or update if necessary). If you leave this off then there\'s a risk that the sync will create duplicates.';
$string['columnnotthere'] = 'This column does not exist in the Moodle table';
$string['externaltablenotthere'] = 'This table does not exist in the external database';
$string['makecoursevisiblewhentouched'] = 'Make course available to students when it is updated';
$string['makecoursevisiblewhentouched_desc'] = 'If enabled, when a course is updated (for example, moved to another category or if its name is changes) then it is made available to students.';
$string['newcourseisvisible'] = 'Make course visible on creation';
$string['newcourseisvisible_desc'] = 'If enabled, a new course is visible to students.';
$string['allowcoursevisibilitymapping'] = 'Map course visibility to external data';
$string['allowcoursevisibilitymapping_desc'] = 'If enabled then we can map course visibility to external data. Note that if one attempts to map visibility without this setting enabled then such mapping will fail. This is because \'deleted\' courses are, in fact, hidden.';
$string['synconlogin'] = 'Enable per-user sync of enrolments on login';
$string['synconlogin_desc'] = 'If enabled, then when each user logs in, we will have a sync of their user enrolments and role assignments into any existing courses.';
$string['sync_started'] = 'Sync started';
$string['sync_finished'] = 'Sync finished';
$string['databaseextended:managerelations'] = 'Can manage relations';

// status lines
$string['statusline0']='Status: Green';
$string['statusline1']='Status: Amber';
$string['statusline2']='Status: Red';
$string['timetaken']='Time taken: {$a}';

//Sync file & email
$string['logfilelocation']='Name and location of DB sync log file';
$string['logfilelocation_desc']='This should be a full path and file name which the cron user can write to.';

$string['syncemail']='DB sync result email list';
$string['syncemail_desc']='A comma separated list of addresses to send the DB sync log to when not all tables return in the "green" state.';
