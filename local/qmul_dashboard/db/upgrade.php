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
 * Inatall script for qmul_dashboard
 * Landing Pages needs a user profile field called qmul_dashboard which is used to setup where a user is redirected to
 * i.e 
 * @package      local
 * @subpackage   qmul_dashboard
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function xmldb_local_qmul_dashboard_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();


    if ($oldversion < 2015090103) {

        // Define table qmul_dashboard_settings to be created.
        $table = new xmldb_table('qmul_dashboard_settings');

        // Adding fields to table qmul_dashboard_settings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('category', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemtype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table qmul_dashboard_settings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('category', XMLDB_KEY_FOREIGN, array('category'), 'course_categories', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for qmul_dashboard_settings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Qmul_dashboard savepoint reached.
        upgrade_plugin_savepoint(true, 2015090103, 'local', 'qmul_dashboard');

    }

    return true;
}
