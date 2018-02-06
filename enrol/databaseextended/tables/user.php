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
 * User table object acts as a cache only
 */
class enrol_databaseextended_user_table extends enrol_databaseextended_single_table {

    /**
     * @var bool This table is dealt with by the auth plugins
     */
    protected $syncenabled = false;

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     */
    protected function add_instance($data) {
        // Deliberately empty - users are synced by auth plugins
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    protected function delete_instance($thingtodelete) {
        // Deliberately empty - users are synced by auth plugins
    }

    /**
     * Override to stop unit tests from throwing an exception
     * @return bool
     */
    public function get_external_recordset() {
        return false;
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @return array
     */
    public function get_internal_existing_rows() {
    }

    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {
        global $DB;
        $relations = array(
            // There is no external mapping as we don't sync this directly. All we need is to know
            // which field is to be used as a unique key in order to fill the cache.
            array('maintable'           => 'user',
                  'maintablefield'      => 'username',
                  'secondarytable'      => '',
                  'secondarytablefield' => '',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE));

        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'user';
    }
}
