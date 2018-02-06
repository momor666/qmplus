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
 * Created On 6 Sep 2012
 * @package   course_backup.php
 * @copyright 2012 ghxx2574 gerryghall@googlemail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Create a course backup via CLI
 *
 * Notes:
 * @package    local
 * @subpackage course_backup
 * @copyright  2006 Gerry G Hall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if(!defined('QMULPCB')){
	echo "Don't call this file directly please use course_back24hr or course_backup7day";
	exit(1);
}

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');


// now get cli options
list($options, $unrecognized) = cli_get_params(
                                    array(
										'id' => false,
                                        'users' => false,
										'shortname'=>false,
                                        'role_assignments' => false,
                                        'user_files' => false,
                                        'activities' => false,
                                        'blocks' => false,
                                        'filters' => false,
                                        'comments' => false,
                                        'completion_information' => false,
                                        'logs' => false,
                                        'histories' => false,
                                        'help'=>false),
                                        array(
                                            'U'=>'users',
                                            'r'=>'role_assignments',
                                            'u'=>'user_files',
                                            'a'=>'activities',
                                            'b'=>'blocks',
                                            'F'=>'filters',
                                            'c'=>'comments',
                                            'C'=>'completion_information',
                                            'l'=>'logs',
                                            'H'=>'histories',
                                            'h'=>'help'
                                        )
                                    );

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Create A Course Backup you must pass in either the course ID or shortname.
Options:
-h, --help            			Print out this help
--id							The id of the courses you want to backup
--shortname						Get Information about a course by the shortname ha
-U --users						Backup Users or Not Defaults to no
-r --role_assignments			Backup Role assignments or Not Defaults to no
-u --user_files					Backup User files or Not Defaults to no
-a --activities					Backup Activities or Not Defaults to no
-b --blocks						Backup Blocks or Not Defaults to no
-F --filters					Backup Filters or Not Defaults to no
-c --comments					Backup Comments or Not Defaults to no
-C --completion_information		Backup Completion Information or Not Defaults to no
-l --logs						Backup Logs or Not Defaults to no
-H --histories					Backup Histories or Not Defaults to no
Example:
\$sudo -u htqmumot /www/qmumotest/docroot/local/course_backup/cli/course_backup.php --id=3032 -U -r -u -a -b -F -c -C -l -H";


    mtrace($help);
    die;
}
if ($options['shortname'] !== false) {
	$course = $DB->get_record('course', array('shortname' => $options['shortname']));
    if($course === false){
		mtrace('Fullname : ' . $course->fullname . "\n");
		mtrace('Shortname : ' . $course->shortname . "\n");
		mtrace('Course id : ' . $course->id . "\n");
	} else {
		mtrace("No Cousre found by the shortname " . $options['shortname'] . "\n");
	}
    die;
}

// we must have an course id to progress
if (!$options['id']) {
	cli_error(get_string('required_options', 'local_course_backup'));
    mtrace($help);
    die;
}
// let ensure the course exsits and get the data for the course to be able to supply some feed back to the user.
$course = $DB->get_record('course', array('id' => $options['id']));
if($course === false) {
	cli_error(get_string('no_course', 'local_course_backup' , $options['id']));
}

require_once "{$CFG->dirroot}/local/course_backup/locallib.php";

cli_heading("Creating Course backup for " . $course->fullname);
$userto = 'aaw349';
$userfrom = 'aaw349';
$ticket_no = $options['ticket'];

unset($options['ticket']);
unset($options['id']);
unset($options['help']);

if (launch_backup( $course ,time(), 2, $options )) {
	mtrace("backup successfull " . $course->shortname);
} else {
	mtrace("backup failed " . $course->shortname);
}

