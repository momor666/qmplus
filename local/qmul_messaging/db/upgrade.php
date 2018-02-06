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
 * Upgrade script for qmul_messaging
 * Landing Pages needs a user profile field called qmul_dashboard which is used to setup where a user is redirected to
 * i.e
 * @package      local
 * @subpackage   qmul_messaging
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function xmldb_local_qmul_messaging_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();


    if ($oldversion < 20161108001) {

        // Define table qmul_dashboard_settings to be created.
        $table = new xmldb_table('local_qmul_messaging_mark');

        // Adding fields to table qmul_dashboard_settings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('message', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeread', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table qmul_dashboard_settings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for qmul_dashboard_settings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 20161108001, 'local', 'qmul_messaging');

    }

    return true;
}
