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
 * Manages the sync for the role_assignments table.
 */
class enrol_databaseextended_role_assignments_table extends enrol_databaseextended_join_table {

    /**
     * @var bool Allow bulk inserts to the DB
     */
    protected $bulkinsert = true;

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     *
     * @return mixed
     */
    protected function add_instance($data) {

        global $DB;

        $instanceid = $this->enrolmentplugin->get_instance_id();

        $component = $this->enrolmentplugin->roles_protected() ?
            'enrol_'.$this->enrolmentplugin->get_name() : '';

        // This function uses the context cache, so not too bad.
        $context = context::instance_by_id($data->contextid, MUST_EXIST);

        // Create a new entry
        $ra               = new stdClass();
        $ra->roleid       = $data->roleid;
        $ra->contextid    = $context->id;
        $ra->userid       = $data->userid;
        $ra->component    = $component;
        $ra->itemid       = $instanceid;
        $ra->timemodified = time();
        $ra->modifierid   = 0;

        $ra->id = $DB->insert_record('role_assignments', $ra);

        // mark context as dirty - again expensive, but needed
        $context->mark_dirty();

        return $ra->id;
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    protected function delete_instance($thingtodelete) {

        $unenroleaction = $this->enrolmentplugin->get_config('unenrolaction');

        // Only remove roles if we have been told to via the settings.
        if ($unenroleaction == ENROL_EXT_REMOVED_SUSPENDNOROLES ||
            $unenroleaction == ENROL_EXT_REMOVED_UNENROL) {

            $enrolname = $this->enrolmentplugin->get_name();

            role_unassign($thingtodelete->roleid,
                          $thingtodelete->userid,
                          $thingtodelete->contextid,
                          'enrol_'.$enrolname,
                          $thingtodelete->itemid);
        }
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @return array
     */
    public function get_internal_existing_rows() {
        global $DB;
        // TODO we want ot make sure we use the right component
        $conditions = array('component' => 'enrol_'.$this->enrolmentplugin->get_name());
        // TODO do we want all fields?
        return $DB->get_records('role_assignment', $conditions);
    }

    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {

        global $DB;

        $relations = array(

            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'userid',
                  'secondarytable'      => 'user',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'userid',
                  'secondarytable'      => 'extenrolments',
                // works for unit tests. Probably not a good default
                  'secondarytablefield' => 'user',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'roleid',
                  'secondarytable'      => 'role',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'roleid',
                  'secondarytable'      => 'extenrolments',
                  'secondarytablefield' => 'role',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'contextid',
                  'secondarytable'      => 'context',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL, // kjhkjhkj kj kjh kjh kjh jkh kjh kjh kjhk
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'contextid',
                  'secondarytable'      => 'extenrolments',
                  'secondarytablefield' => 'course',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            // This lets us keep track of where role assignments came from. No idea why it's not called something.   hhhhh
            //
            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'itemid',
                  'secondarytable'      => 'enrol',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'role_assignments',
                  'maintablefield'      => 'itemid',
                  'secondarytable'      => 'extenrolments',
                  'secondarytablefield' => 'course',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_NON_UNIQUE),
        );

        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Defined specific SQL for the cache to use in populating itself.
     *
     * @param $fields
     *
     * @return string
     */
    protected function get_populate_cache_sql($fields = '*') {

        $table = $this->get_internal_table_name();
        $component = 'enrol_'.$this->enrolmentplugin->get_name();

        $sql = "
               SELECT {$fields}
                 FROM {{$table}} this
                WHERE this.component = '{$component}'";

        return $sql;

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
            parent::bulk_insert();
            return;
        }

        $component = $this->enrolmentplugin->roles_protected() ?
            'enrol_'.$this->enrolmentplugin->get_name() : '';

        // Create a new entry
        $ra               = new stdClass();
        $ra->roleid       = $data->roleid;
        $ra->contextid    = $data->contextid;
        $ra->userid       = $data->userid;
        $ra->component    = $component;
        $ra->itemid       = $data->itemid;
        $ra->timemodified = time();
        $ra->modifierid   = 0;

        // TODO needed?
        // mark context as dirty - again expensive, but needed
        // mark_context_dirty($context->path);

        parent::bulk_insert($ra);

    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'role_assignments';
    }
    
    /**
     * This determines whether we will be trying to get existing records and claimm them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
    	return $this->get_config('role_assignments_claim_existing', true);
    }
}
