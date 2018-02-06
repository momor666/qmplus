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
 * Extra DB upgrade steps
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_activitytodo_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017051000) {

        // Define field lockduedate to be added to local_activitytodo.
        $table = new xmldb_table('local_activitytodo');
        $field = new xmldb_field('lockduedate', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'duedate');

        // Conditionally launch add field lockduedate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Activitytodo savepoint reached.
        upgrade_plugin_savepoint(true, 2017051000, 'local', 'activitytodo');
    }

    return true;
}