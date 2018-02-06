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
 * Manages the sync of the roles table.
 */
class enrol_databaseextended_role_table extends enrol_databaseextended_single_table {

    /**
     * @var bool Setting up roles is complex, so we leave this to Moodle
     */
    protected $syncenabled = true;

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     */
    protected function add_instance($data) {

        $name = isset($data->name) ? $data->name : $data->shortname;
        $description = isset($data->description) ? $data->description : $data->shortname;

        create_role($name, $data->shortname, $description);
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    protected function delete_instance($thingtodelete) {
        // Probably best not to do this in case of errors.
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @return array
     */
    public function get_internal_existing_rows() {
        global $DB;
        return $DB->get_records('role');
    }

    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {
        global $DB;
        $relations = array(
            // This is a mapping that needs to be used to sync distinct values.
            array('maintable'           => 'role',
                  'maintablefield'      => 'shortname',
                  'secondarytable'      => 'extenrolments',
                  'secondarytablefield' => 'role',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE));

        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'role';
    }

    /**
     * If we don't have a Moodle id for this table for an outside field in a row, we can
     * either create that row with a default value fo this table, or create the record for
     * this table, then supply the id. This is the default-value option.
     *
     * @return bool|int
     */
    public function get_default_id() {

        global $DB;

        static $defaultroleid;

        if (isset($defaultroleid)) {
            return $defaultroleid;
        }

        $defaultroleid = $this->get_config('defaultrole');

        // May have been deleted since settings were made.
        $defaultrole = $DB->record_exists('role',
                                          array('id' => $defaultroleid));
        if ($defaultrole) {
            return $defaultroleid;
        } else {
            $defaultroleid = false;
            return false;
        }
    }
    
    /**
     * This determines whether we will be trying to get existing records and claimm them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
    	return $this->get_config('role_claim_existing', true);
    }
}

