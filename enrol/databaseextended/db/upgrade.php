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
 * Database enrolment plugin upgrade.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/enrol/databaseextended/lib.php');

/**
 * Move old settings from 1.9 to 2.0.
 */
function migrate_settings() {

    global $CFG, $DB;

    if (isset($CFG->enrol_dbxtype)) {
        set_config('dbtype', $CFG->enrol_dbxtype, 'enrol_databaseextended');
        unset_config('enrol_dbxtype');
    }
    if (isset($CFG->enrol_dbxhost)) {
        set_config('dbhost', $CFG->enrol_dbxhost, 'enrol_databaseextended');
        unset_config('enrol_dbxhost');
    }
    if (isset($CFG->enrol_dbxuser)) {
        set_config('dbuser', $CFG->enrol_dbxuser, 'enrol_databaseextended');
        unset_config('enrol_dbxuser');
    }
    if (isset($CFG->enrol_dbxpass)) {
        set_config('dbpass', $CFG->enrol_dbxpass, 'enrol_databaseextended');
        unset_config('enrol_dbxpass');
    }
    if (isset($CFG->enrol_dbxname)) {
        set_config('dbname', $CFG->enrol_dbxname, 'enrol_databaseextended');
        unset_config('enrol_dbxname');
    }
    if (isset($CFG->enrol_dbxtable)) {
        set_config('remoteenroltable', $CFG->enrol_dbxtable, 'enrol_databaseextended');
        unset_config('enrol_dbxtable');
    }
    if (isset($CFG->enrol_dbxlocalcoursefield)) {
        set_config('localcoursefield', $CFG->enrol_dbxlocalcoursefield, 'enrol_databaseextended');
        unset_config('enrol_dbxlocalcoursefield');
    }
    if (isset($CFG->enrol_dbxlocaluserfield)) {
        set_config('localuserfield', $CFG->enrol_dbxlocaluserfield, 'enrol_databaseextended');
        unset_config('enrol_dbxlocaluserfield');
    }
    if (isset($CFG->enrol_dbx_localrolefield)) {
        set_config('localrolefield', $CFG->enrol_dbx_localrolefield, 'enrol_databaseextended');
        unset_config('enrol_dbx_localrolefield');
    }
    if (isset($CFG->enrol_dbxremotecoursefield)) {
        set_config('remotecoursefield', $CFG->enrol_dbxremotecoursefield, 'enrol_databaseextended');
        unset_config('enrol_dbxremotecoursefield');
    }
    if (isset($CFG->enrol_dbxremoteuserfield)) {
        set_config('remoteuserfield', $CFG->enrol_dbxremoteuserfield, 'enrol_databaseextended');
        unset_config('enrol_dbxremoteuserfield');
    }
    if (isset($CFG->enrol_dbx_remoterolefield)) {
        set_config('remoterolefield', $CFG->enrol_dbx_remoterolefield, 'enrol_databaseextended');
        unset_config('enrol_dbx_remoterolefield');
    }
    if (isset($CFG->enrol_dbx_defaultcourseroleid)) {
        set_config('defaultrole', $CFG->enrol_dbx_defaultcourseroleid, 'enrol_databaseextended');
        unset_config('enrol_dbx_defaultcourseroleid');
    }
    unset_config('enrol_dbx_autocreate'); // Replaced by new coruse temple sync.
    if (isset($CFG->enrol_dbx_category)) {
        set_config('defaultcategory', $CFG->enrol_dbx_category, 'enrol_databaseextended');
        unset_config('enrol_dbx_category');
    }
    if (isset($CFG->enrol_dbx_template)) {
        set_config('templatecourse', $CFG->enrol_dbx_template, 'enrol_databaseextended');
        unset_config('enrol_dbx_template');
    }
    if (isset($CFG->enrol_dbx_ignorehiddencourse)) {
        set_config('ignorehiddencourses', $CFG->enrol_dbx_ignorehiddencourse,
                   'enrol_databaseextended');
        unset_config('enrol_dbx_ignorehiddencourse');
    }
    unset_config('enrol_dbx_disableunenrol');

    // Extra bits.
    if (isset($CFG->enrol_dbxcoursetable)) {
        set_config('newcoursetable', $CFG->enrol_dbxcoursetable,
                   'enrol_databaseextended');
        unset_config('enrol_dbxcoursetable');
    }
    if (isset($CFG->enrol_dbxremotecoursefullnamefield)) {
        set_config('newcoursefullname', $CFG->enrol_dbxremotecoursefullnamefield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecoursefullnamefield');
    }
    if (isset($CFG->enrol_dbxremotecourseshortnamefield)) {
        set_config('newcourseshortname', $CFG->enrol_dbxremotecourseshortnamefield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecourseshortnamefield');
    }
    if (isset($CFG->enrol_dbxremotecourseidnumberfield)) {
        set_config('newcourseidnumber', $CFG->enrol_dbxremotecourseidnumberfield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecourseidnumberfield');
    }
    if (isset($CFG->enrol_dbxremotecoursecategoryfield)) {
        set_config('newcoursecategory', $CFG->enrol_dbxremotecoursecategoryfield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecoursecategoryfield');
    }
    // Done.
    if (isset($CFG->enrol_dbxremotecoursedescriptionfield)) {
        set_config('newcoursesummary', $CFG->enrol_dbxremotecoursedescriptionfield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecoursedescriptionfield');
    }
    unset_config('enrol_dbxcategoryseparator');
    // Done.
    if (isset($CFG->enrol_dbxcoursetablekey)) {
        set_config('remotecoursetablekey', $CFG->enrol_dbxcoursetablekey,
                   'enrol_databaseextended');
        unset_config('enrol_dbxcoursetablekey');
    }
    if (isset($CFG->enrol_dbxrootcategory)) {
        set_config('rootcategory', $CFG->enrol_dbxrootcategory,
                   'enrol_databaseextended');
        unset_config('enrol_dbxrootcategory');
    }
    if (isset($CFG->enrol_dbxcategorytable)) {
        set_config('remotecategorytable', $CFG->enrol_dbxcategorytable,
                   'enrol_databaseextended');
        unset_config('enrol_dbxcategorytable');
    }
    if (isset($CFG->enrol_dbxcategorytablekey)) {
        set_config('remotecategorytablekey', $CFG->enrol_dbxcategorytablekey,
                   'enrol_databaseextended');
        unset_config('enrol_dbxcategorytablekey');
    }
    if (isset($CFG->enrol_dbxremotecategorynamefield)) {
        set_config('remotecategoryname', $CFG->enrol_dbxremotecategorynamefield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecategorynamefield');
    }
    if (isset($CFG->enrol_dbxremotecategorydescriptionfield)) {
        set_config('remotecategorydescription', $CFG->enrol_dbxremotecategorydescriptionfield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecategorydescriptionfield');
    }
    if (isset($CFG->enrol_dbxremotecategoryparentfield)) {
        set_config('remotecategoryparent', $CFG->enrol_dbxremotecategoryparentfield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotecategoryparentfield');
    }
    // Done.
    if (isset($CFG->enrol_dbxremotegroupfield)) {
        set_config('remotegroupfield', $CFG->enrol_dbxremotegroupfield,
                   'enrol_databaseextended');
        unset_config('enrol_dbxremotegroupfield');
    }

    // Just make sure there are no leftovers after disabled plugin.
    if (!$DB->record_exists('enrol', array('enrol' => 'databaseextended'))) {
        role_unassign_all(array('component' => 'enrol_databaseextended'));
    }
}

/**
 * Upgrade functions for the extended database enrolmentplugin
 *
 * @param $oldversion
 * @return bool
 */
function xmldb_enrol_databaseextended_upgrade($oldversion) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012020900) {
        // Run install thing that hoovers up old variables.
        migrate_settings($CFG, $DB);

        upgrade_plugin_savepoint(true, 2012020900, 'enrol', 'databaseextended');
    }

    if ($oldversion < 2012040300) {

        // Define field externalname to be added to databaseextended_flags.
        $table = new xmldb_table('databaseextended_flags');
        $field = new xmldb_field('externalname', XMLDB_TYPE_CHAR, '255', null, null,
                null, null, 'tablename');

        // Conditionally launch add field externalname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field internal to be added to databaseextended_relations.
        $table = new xmldb_table('databaseextended_relations');
        $field = new xmldb_field('internal', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, '0', 'relationtype');

        // Conditionally launch add field internal.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field unique to be added to databaseextended_relations.
        $field = new xmldb_field('uniquefield', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
                XMLDB_NOTNULL, null, '0', 'internal');

        // Conditionally launch add field unique.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update new fields from old one.
        require_once($CFG->dirroot . '/enrol/databaseextended/lib.php');

        $relations = $DB->get_records('databaseextended_relations');
        foreach ($relations as $relation) {
            if ($relation->relationtype == ENROL_DATABASEEXTENDED_INTERNAL_RELATION ||
                $relation->relationtype == ENROL_DATABASEEXTENDED_INTERNAL_SINGULAR_RELATION) {

                $relation->internal = 1;
            }
            if ($relation->relationtype == ENROL_DATABASEEXTENDED_MAPPING_UNIQUE_RELATION ||
                $relation->relationtype == ENROL_DATABASEEXTENDED_INTERNAL_SINGULAR_RELATION ||
                $relation->relationtype == ENROL_DATABASEEXTENDED_INTERNAL_RELATION
            ) {

                $relation->uniquefield = 1;
            }
            $DB->update_record('databaseextended_relations', $relation);
        }

        $field = new xmldb_field('relationtype');

        // Conditionally launch drop field relationtype.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Databaseextended savepoint reached.
        upgrade_plugin_savepoint(true, 2012040300, 'enrol', 'databaseextended');
    }

    if ($oldversion < 2012041200) {

        $conditions = array('maintable' => 'user');
        $userrelation = $DB->get_record('databaseextended_relations', $conditions);
        $userrelation->internal = ENROL_DATABASEEXTENDED_INTERNAL;
        $DB->update_record('databaseextended_relations', $userrelation);

        upgrade_plugin_savepoint(true, 2012041200, 'enrol', 'databaseextended');
    }

    return true;
}
