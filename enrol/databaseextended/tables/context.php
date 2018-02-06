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
 * Database extended enrolment plugin.
 *
 * This plugin synchronises enrolment and roles with external database table.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012 University of London Computer Centre {@link http://ulcc.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/enrol/databaseextended/classes/one_to_one_table.class.php');

/**
 * Context is one to one with course (when contextlevel is CONTEXT_COURSE). This will just be a
 * cache.
 */
class enrol_databaseextended_context_table extends enrol_databaseextended_one_to_one_table {

    /**
     * @var bool Contexts are managed by the create_course() function etc.
     */
    protected $syncenabled = false;

    /**
     * The table joins to another with conditions sometimes e.g. only enrol instances of this type,
     * or only a particulkar context level
     *
     * @return string
     */
    protected function get_extra_sql() {
        $contextlevel = CONTEXT_COURSE;
        return "AND this.contextlevel = '{$contextlevel}' ";
    }

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     */
    protected function add_instance($data) {
        // TODO: Implement add_instance() method.
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    protected function delete_instance($thingtodelete) {
        // TODO: Implement delete_instance() method.
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @return array
     */
    public function get_internal_existing_rows() {
        // TODO: Implement get_internal_existing_rows() method.
    }

    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {
        global $DB;
        $relations = array(
            // Internal relation to courses table
            array('maintable'           => 'context',
                  'maintablefield'      => 'instanceid',
                  'secondarytable'      => 'course',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE));

        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Unusually, the enrolment table isn't directly synced with the external DB. It is in a 1-1
     * relationship with the courses table, so we need to have a cache that keys the enrol ids
     * by course uniquekey.
     *
     * @param bool $onlystoreids
     *
     * @internal param bool $reset Will need to reload once stuff has been added
     *
     * @internal param array $mappedfields
     *
     * @return bool
     */
    public function populate_cache($onlystoreids = true) {

        global $DB;

        $this->empty_cache();

        $linkedtableobject  = $this->get_linked_table_object();
        $linkedtablename    = $linkedtableobject->get_internal_table_name(); // course
        $linkeduniquefield  = $linkedtableobject->get_internal_unique_field_name();
        $foreignkeyfield    = $this->get_internal_foregin_key_field();
        $internaltable      = $this->get_internal_table_name();
        $coursecontextlevel = CONTEXT_COURSE;

        // including path so we can use make_context_dirty() in role_assignments sync
        $sql       = "
                SELECT this.id,
                       link.{$linkeduniquefield}
                  FROM {{$internaltable}} this
            INNER JOIN {{$linkedtablename}} link
                    ON this.{$foreignkeyfield} = link.id
            INNER JOIN {databaseextended_flags} flags
                    ON link.id = flags.itemid AND flags.tablename = '{$linkedtablename}'
                 WHERE this.contextlevel = '{$coursecontextlevel}'
                     ";
        $recordset = $DB->get_recordset_sql($sql);
        while ($recordset->valid()) {
            $row = $recordset->current();
            $this->add_to_cache($row, $row->$linkeduniquefield);
            $recordset->next();
        }

        return true;
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'context';
    }
}
