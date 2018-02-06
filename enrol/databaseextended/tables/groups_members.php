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

require_once($CFG->dirroot.'/enrol/databaseextended/classes/join_table.class.php');

/**
 * Manages the sync of the groups_members table.
 */
class enrol_databaseextended_groups_members_table extends enrol_databaseextended_join_table {

    /**
     * @var bool Allows bulk insert operations
     */
    protected $bulkinsert = true; // IDW Needs to be true because there is no external name

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     */
    protected function add_instance($data) {
        global $DB;

        $data->timeadded = time();
        $data->id        = $DB->insert_record($this->get_internal_table_name(), $data);
        $this->add_item_flag($data);
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    protected function delete_instance($thingtodelete) {
        global $DB;

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
                  FROM {groups_members} g
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

            array('maintable'           => 'groups_members',
                  'maintablefield'      => 'userid',
                  'secondarytable'      => 'user',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'groups_members',
                  'maintablefield'      => 'userid',
                  'secondarytable'      => 'extenrolments',
                // works for unit tests. Probably not a good default
                  'secondarytablefield' => 'user',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'groups_members',
                  'maintablefield'      => 'groupid',
                  'secondarytable'      => 'groups',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'groups_members',
                  'maintablefield'      => 'groupid',
                  'secondarytable'      => 'extenrolments',
                  'secondarytablefield' => 'groupname',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE)
        );
        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Lets the system know that we are flagging records that can be deleted by this script
     * @return bool
     */
    public function use_flags() {
        return true;
    }

    protected function get_internal_row($externalrow) {
    
    	global $DB;
    
    	$uniquefield = $this->get_internal_unique_field_array();
    	$tablename = $this->get_internal_table_name();
    
    	$sql = '';
    
    	$params = array();
    	foreach ($uniquefield as $keyfield) {
    		if(strlen($sql) > 0) {
    			$sql .= ' AND ';
    		}
    		// $sql .= "flags.{$keyfield} = :{$keyfield}"; // We need to have an ' AND ' in here
    		// ... and this is a 'keyfield' so it needs to reference the internal table and not the flag table...
    		$sql .= "internaltable.{$keyfield} = :{$keyfield}";
    		$params[$keyfield] = $externalrow->$keyfield;
    	}
    
    	return $DB->get_record($tablename, $params);
    }
    	
    /**
     * Preformats the data for bulk insert
     *
     * @param bool $data
     *
     * @internal param bool|\stdClass $row
     *
     * @return mixed
     * @throws coding_exception
     */
    public function bulk_insert($data = false) {
	
        if (!$data) {
        	// Flush the data
            parent::bulk_insert();
            return;
        }
        
        // Does this entry already exist internally?
        $internalrow = $this->get_internal_row($data);
        
        if($internalrow) {
        	$key = $this->get_internal_unique_field_array();
        	$keyvalues = array();
        	foreach ($key as $fieldname) {
        		$keyvalues[] = $data->$fieldname;
        	}
        	$this->bulk_add_flags($keyvalues);
        } else {
        	$data->timeadded = time();
        	parent::bulk_insert($data);
        }
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'groups_members';
    }

    /**
     * This determines whether we will be trying to get existing records and claimm them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
    	return $this->get_config('groups_members_claim_existing', true);
    }
    
}

