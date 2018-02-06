<?php

/****************************************************************

File:     /block/course_rollover/db/install.php

Purpose:  Upgrade script for course rollover

****************************************************************/

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
 * This file keeps track of upgrades to the course rollover block
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * Upgrade script for course rollover
 *
 * @package    block
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
function xmldb_block_course_rollover_upgrade($oldversion = 0)
{
    global $CFG, $DB;
	$dbman = $DB->get_manager();
    if ($oldversion < 2013051901) {

        // Define table course_rollover to be created
        $table = new xmldb_table('block_course_rollover');

	    // Adding fields to table block_course_rollover
	    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
	    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
	    $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
	    $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
	    $table->add_field('shortname', XMLDB_TYPE_CHAR, '254', null, null, null, null);
	    $table->add_field('summary', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
	    $table->add_field('scheduletime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
	    $table->add_field('course_reset_data', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
	    $table->add_field('modcode', XMLDB_TYPE_CHAR, '100', null, null, null, null);
	    $table->add_field('status', XMLDB_TYPE_INTEGER, '3', null, null, null, '200');

	    // Adding keys to table block_course_rollover
	    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

	    // Adding indexes to table block_course_rollover
	    $table->add_index('course_rollover_course_ix', XMLDB_INDEX_UNIQUE, array('courseid'));
	    $table->add_index('course_rollover_user_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
	    $table->add_index('course_rollover_idnumb_ix', XMLDB_INDEX_NOTUNIQUE, array('idnumber'));

	    // Conditionally launch create table for block_course_rollover
	    if(!$dbman->table_exists($table)) {
			$dbman->create_table($table);
	    }


        // course_rollover savepoint reached
        upgrade_block_savepoint(true, 2013051901, 'course_rollover');
    } else if ($oldversion < 2015050100) {
    	upgrade_block_savepoint(true, 2015050100, 'course_rollover');
    } else if ($oldversion < 2015050101) {
    	upgrade_block_savepoint(true, 2015050101, 'course_rollover');
    } else if ($oldversion < 2016021700) {
		upgrade_block_savepoint(true, 2016021700, 'course_rollover');
	} else if ($oldversion < 2016021701) {
		upgrade_block_savepoint(true, 2016021701, 'course_rollover');
	} else if ($oldversion < 2016021702) {
		upgrade_block_savepoint(true, 2016021702, 'course_rollover');
	} else if ($oldversion < 2016101800) {
		upgrade_block_savepoint(true, 2016101800, 'course_rollover');
	} else if ($oldversion < 2016102800) {
		upgrade_block_savepoint(true, 2016102800, 'course_rollover');
	}

	return true;
}
