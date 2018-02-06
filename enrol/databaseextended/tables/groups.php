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

require_once($CFG->dirroot.'/enrol/databaseextended/classes/single_table.class.php');

/**
 * Manages the data in the groups table for the sync.
 */
class enrol_databaseextended_groups_table extends enrol_databaseextended_single_table {

    /**
     * @var bool Allow bulk inserts
     */
    //protected $bulkinsert = true;
    protected $bulkinsert = false;

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     * @return \stdClass
     */
    protected function add_instance($data) {

        global $DB;
        
        // TODO - this is slow and should be already taken care of for the primary external
        // key field, so we just need to use the cache to check if we have a dupe of the other one.
        // Check if the unique field name already exists.
        // This will happen if we have dupes in the external data.
        
        $uniquefield = $this->get_internal_unique_field_name();
        
        if (!empty($data->$uniquefield)) {
        	// Whitespace in external table on one row and not another will lead to dupes here.
        	$existinggroup = $DB->get_record('groups', array("{$uniquefield}" => $data->$uniquefield));
        	if ($existinggroup) {
        		if ($this->should_try_to_claim_existing_items()) {
        			if (isset($data->externalname)) {
        				// Don't lose this if it's there!
        				$existinggroup->externalname = $data->externalname;
        			}
        			// Claim it as our own!
        			$this->add_item_flag($existinggroup);
        			return true;
        		}
        	}
        }
        
		
        $data->id = $DB->insert_record($this->get_internal_table_name(), $data);

        if ($this->use_flags()) {
            $this->add_item_flag($data);
        }

        return $data;
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    protected function delete_instance($thingtodelete) {

        global $DB;

        // TODO delete group members.
        $this->delete_item_flag($thingtodelete->id);

        $DB->delete_records($this->get_internal_table_name(), array('id' => $thingtodelete->id));
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @return array
     */
    public function get_internal_existing_rows() {
        global $DB;

        $tablename = $this->get_internal_table_name();

        $sql = "SELECT g.*
                  FROM {groups} g
            INNER JOIN {databaseextended_flags} f
                    ON (c.id = f.itemid AND f.tablename = '{$tablename}')
                 ";
        return $DB->get_records_sql($sql);
    }

    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {

        global $DB;

        $relations = array(
            // This realtion links the courses table to the catgoriges table. A foreign key.
            array('maintable'           => 'groups',
                  'maintablefield'      => 'courseid',
                  'secondarytable'      => 'course',
                // works for unitests. Probably no a good default
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'groups',
                  'maintablefield'      => 'courseid',
                  'secondarytable'      => 'extenrolments',
                // works for unitests. Probably no a good default
                  'secondarytablefield' => 'course',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_NON_UNIQUE),
            array('maintable'           => 'groups',
                  'maintablefield'      => 'name',
                  'secondarytable'      => 'extenrolments',
                // works for unitests. Probably no a good default
                  'secondarytablefield' => 'groupname',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE)
        );

        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Groups tbale shas no way to specify what created it
     *
     * @return bool
     */
    public function use_flags() {
        return true;
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'groups';
    }

    /**
     * This determines whether we will be trying to get existing records and claim them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
        return $this->get_config('groups_claim_existing', true);
    }

}

