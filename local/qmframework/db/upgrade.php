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
 * This file keeps track of upgrades to Moodle.
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Maria Sorica <maria.sorica@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main upgrade tasks to be executed on Moodle version bump
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_local_qmframework_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    // Add local_qmframework_links table that stores the QM Framework links to students qmdashboards or to tutors advisees pages.
    if ($oldversion < 2017110203) {

        $table = new xmldb_table('local_qmframework_links');

        // Adding fields to table local_qmframework_links.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('link', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, '');

        // Adding keys to table local_qmframework_links.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, array('groupid'), 'groups', array('id'));

        // Conditionally launch create table for local_qmframework_links.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2017110203, 'local', 'qmframework');
    }

    if ($oldversion < 2017110801) {

        $table = new xmldb_table('local_qmframework_links');
        if ($dbman->table_exists($table)) {
            $key = new xmldb_key('groupid', XMLDB_KEY_FOREIGN, array('groupid'), 'groups', array('id'));
            $dbman->drop_key($table, $key);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2017110801, 'local', 'qmframework');
    }

    if ($oldversion < 2017111403) {

        $table = new xmldb_table('local_qmframework_links');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $dbman->change_field_default($table, $field);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2017111403, 'local', 'qmframework');
    }

    return true;
}
