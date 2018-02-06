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
 * Database enrolment plugin.
 *
 * This plugin synchronises enrolment and roles with external database table.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012 ULCC {@link http://ulcc.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/enrol/databaseextended/classes/single_table.class.php');

/**
 * For odd tables like enrol, which need to be created to match another table 1-1. Each course
 * should have an enrol instance added after the course sync. Keeping them separate like this
 * simplifies the sync code and allows the same procedure to be used for both things.
 */
abstract class enrol_databaseextended_one_to_one_table extends enrol_databaseextended_single_table {

    /**
     * The table joins to another with conditions sometimes e.g. only enrol instances of this type,
     * or only a particular context level
     */
    abstract protected function get_extra_sql();

    /**
     * We will need to be able to check that stuff has worked for unit tests, so this will take the
     * unique identifier for the linked table e.g. course shortname, and return an id from this
     * table e.g. role id.
     *
     * @param $key
     *
     * @return mixed
     */
    public function get_id_by_linked_table_key($key) {

        global $DB;

        $relation = $this->get_single_internal_relation();

        $linkedtablename = $relation->secondarytable;
        $linkedtableobject = $this->get_linked_table_object();
        $linkedtableuniquefield = $linkedtableobject->get_internal_unique_field_name();
        $foreignkeyfield = $relation->maintablefield;
        $thistable = $this->get_internal_table_name();

        // TODO can this go in the superclass?
        $sql = "SELECT this.id
                  FROM {{$thistable}} this
            INNER JOIN {{$linkedtablename}} link
                    ON this.{$foreignkeyfield} = link.id
                 WHERE link.{$linkedtableuniquefield} = :keyvalue
                         ";
        $sql .= $this->get_extra_sql();
        $params = array('keyvalue' => $key);
        $expectedid = $DB->get_field_sql($sql, $params);
        return $expectedid;
    }

    /**
     * Returns the name of the field on this table which is linked to the other table.
     *
     * @return mixed
     */
    protected function get_internal_foregin_key_field() {
        $relation = $this->get_single_internal_relation();
        return $relation->maintablefield;
    }

    /**
     * We need to get the single relation for somethings. This just pops it off the end of array
     * of relations ard returns it.
     *
     * @throws coding_exception
     * @return mixed
     */
    protected function get_single_internal_relation() {
        $internalrelations = $this->get_internal_relations();
        if (count($internalrelations) !== 1) {
            $message = 'Wrong number of internal relations for the '.
                $this->get_internal_table_name().' table. Expected 1, but got '.
                count($internalrelations);
            throw new coding_exception($message);
        }
        return array_pop($internalrelations);
    }

    /**
     * Allows us to check other stuff.
     *
     * @return mixed
     */
    public function get_unique_mapping_relation() {

        return $this->get_single_internal_relation();
    }

    /**
     * Gets the table object that this table has a 1-1 relationship with.
     *
     * @return \enrol_databaseextended_table_base
     */
    protected function get_linked_table_object() {
        $relation = $this->get_single_internal_relation();
        $tablename = $relation->secondarytable;
        return $this->enrolmentplugin->get_table_object($tablename);
    }

    /**
     * This is a special case - we want to sync against an internal table 1-1.
     *
     * @return enrol_databaseextended_array_adapter
     */
    public function get_external_recordset() {

        // TODO make it so that this method is used automatically when there is only one internal
        // relation.
        $internal_relation = $this->get_internal_relations();
        $internalrelation = array_pop($internal_relation);
        $linkedtablename = $internalrelation->secondarytable;
        $tableclassname = $this->enrolmentplugin->get_table_classname($linkedtablename);
        $tableobject = $tableclassname::get_table_singleton();

        $externalarray = $tableobject->get_internal_existing_rows();

        $arrayadapter = new enrol_databaseextended_array_adapter($externalarray);

        // We want to ignore the site level course.

        // TODO ought to return a recordset perhaps to make it more consistent?
        return $arrayadapter;
    }

    /**
     * Getter for the name of the external table to sync to. Comes from the unique mapping relation.
     *
     * @param bool $withprefix
     *
     * @return bool
     */
    public function get_external_table_name($withprefix = true) {

        $relation = $this->get_single_internal_relation();
        $tablename = $relation->secondarytable;

        return $tablename;
    }

    /**
     * Returns the field for this table containing the unique data that matches the primary unique
     * key in the external database. If we have a join table e.g. user_enrolments, this will be
     * two fields, so we use an array.
     *
     * @return mixed
     */
    public function get_internal_unique_field_name() {

        if (isset($this->internaluniquekey)) {
            return $this->internaluniquekey;
        }

        $relation = $this->get_single_internal_relation();

        // We want the unique key from the other table.
        $tablename = $relation->secondarytable;
        $tableobject = $this->enrolmentplugin->get_table_object($tablename);
        $columnname = $tableobject->get_internal_unique_field_name();

        $this->internaluniquekey = $columnname;

        return $this->internaluniquekey;
    }

    /**
     * @see parent
     *
     * @param $thingtofindmatchfor
     * @param $listofotherthings
     *
     * @return bool
     */
    public function find_matching_thing_for_test($thingtofindmatchfor, $listofotherthings) {

        $expectedvalue = false;

        foreach ($listofotherthings as $possiblematch) {

            $relation = $this->get_single_internal_relation();

            $linkedtable =
                $this->enrolmentplugin->get_table_object($relation->secondarytable);

            $expectedinternalfieldname = $relation->maintablefield;
            if (isset($thingtofindmatchfor->$expectedinternalfieldname)) {
                // This will be a Moodle id.
                $expectedvalue = $thingtofindmatchfor->$expectedinternalfieldname;
            } else {
                $debugpause = '';
            }

            // This will be the text identifier unique to this Moodle table.
            $expectedexternalfieldname = $linkedtable->get_external_unique_key_field();
            $externalvalue = $possiblematch->$expectedexternalfieldname;
            // This Moodle id should match the id of the possible match.
            if (!$linkedtable->cache_is_primed()) {
                $linkedtable->populate_cache();
            }
            $matchvalue = $linkedtable->get_moodle_field_from_cache($externalvalue);

            if ($matchvalue == $expectedvalue) {
                return $possiblematch;
            }
        }
        return false;
    }

    /**
     * This determines whether we will be trying to get existing records and claim them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
        return false;
    }

    /**
     * Gets the row directly from Moodle tables rather than from the memory based cache. This is for use when
     * we are doing a single user sync and don't want the massive memory overhead of filling the whole cache.
     *
     * @param $externalkey
     * @param bool $findhidden
     * @return mixed
     */
    protected function retrieve_directly_from_table($externalkey, $findhidden = false) {

        global $DB;

        $notdeletedconditions = $this->get_sql_not_deleted_conditions();
        $linkedtable = $this->get_linked_table_object();


        $select = "
                    SELECT this.*";
        if ($this->use_flags()) {
            $select .= " , flags.externalname ";
        }
        $select .= "
                    FROM {{$this->get_internal_table_name()}} this
              INNER JOIN {{$linkedtable->get_internal_table_name()}} onetoonelinkedtable
                      ON this.{$this->get_internal_foregin_key_field()} = onetoonelinkedtable.id
                ";
        if ($this->use_flags()) {
            $select .= "
                    INNER JOIN {databaseextended_flags} flags
                            ON flags.tablename = '{$this->get_internal_table_name()}' AND flags.itemid = this.id ";
        }
        $where = " WHERE onetoonelinkedtable.{$this->get_internal_unique_field_name()} = :externalkey ";
        $params = array('externalkey' => $externalkey);

        if (!$findhidden) {
            $counter = 0;
            // We want to see just the ones that are visible.
            foreach ($notdeletedconditions as $name => $vale) {
                $where .= " AND {$name} = :value{$counter}";
                $params['value'.$counter] = $vale;
            }
        }

        $combinedsql = $select.$where.$this->get_extra_sql();
        return $DB->get_record_sql($combinedsql, $params);
    }
}
