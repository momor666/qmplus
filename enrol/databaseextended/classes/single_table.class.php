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

require_once($CFG->dirroot.'/enrol/databaseextended/classes/table_base.class.php');

/**
 * For tables that have a unique key
 */
abstract class enrol_databaseextended_single_table extends enrol_databaseextended_table_base {

    /**
     * @var stdClass The unique keys that match internal and external tables
     */
    protected $uniquemappingrelation;

    /**
     * Returns the field for this table containing the unique data that matches the primary unique
     * key in the external database. If we have a join table e.g. user_enrolments, this will be
     * two fields, so we use an array
     *
     * @throws coding_exception
     * @return mixed
     */
    public function get_internal_unique_field_name() {

        static $internaluniquekey;

        if (isset($internaluniquekey)) {
            return $internaluniquekey;
        }

        $uniquemappingrelation = $this->get_unique_mapping_relation();
        if (!empty($uniquemappingrelation)) {
            // Normal sync e.g. categories: we get the only unique relation there is.
            $internaluniquekey = $uniquemappingrelation->maintablefield;
        } else {
            // Table that is not directly synced e.g. enrol, which has a 1-1 relationship with
            // another table. This means that the unique key won't be there, so we use the unique
            // key of the table it is joined to. If this is not a 1-1 relationship, this won't work.
            $relations = $this->get_internal_relations();
            if (count($relations) !== 1) {
                $message = 'Cannot get unique key for '.$this->get_internal_table_name();
                throw new coding_exception($message);
            }
            $relation = array_pop($relations);
            $internaluniquekey = $relation->maintablefield;
        }
        return $internaluniquekey;
    }

    /**
     * Checks that the config variables necessary to do the sync on this table have been provided.
     * subclasses can add other checks, but should call this too.
     *
     * @return bool
     */
    public function check_config_vars_present() {

        if (!$this->sync_enabled()) {
            return true;
        }
        // No primary key relation to link the internal and external together.
        $uniquemappingrelation = $this->get_unique_mapping_relation();

        // No table to connect to.
        if (empty($uniquemappingrelation->secondarytable)) {
            return false;
        }
        // Missing fields.
        if (empty($uniquemappingrelation->maintablefield)) {
            return false;
        }
        if (empty($uniquemappingrelation->secondarytablefield)) {
            return false;
        }

        return true;
    }

    /**
     * Getter for the name of the external table to sync to. Comes from the unique mapping relation
     *
     * @param bool $withprefix
     *
     * @throws coding_exception
     * @return bool
     */
    public function get_external_table_name($withprefix = true) {

        global $CFG, $DB;

        $uniquemappingrelation = $this->get_unique_mapping_relation();
        if (empty($uniquemappingrelation)) {
            throw new coding_exception('No uniquemappingrelation for '.
                                           $this->get_internal_table_name());
        }
        $tablename = $uniquemappingrelation->secondarytable;

        // To make unit tests work when we create external tables in the internal test DB.
        if ($withprefix &&
            (isset($CFG->unittestprefix) && $DB->get_prefix() == $CFG->unittestprefix)
        ) {
            return $CFG->unittestprefix.$tablename;
        } else {
            return $tablename;
        }
    }

    /**
     * Fetches the data from the external database that will be synced with the internal
     * Moodle table.
     *
     * @return ADORecordSet
     */
    public function get_external_recordset() {

        // Get the fields we want to sync from the mappings automatically.
        $sqlfields = array();
        $uniquemappingrelation = $this->get_unique_mapping_relation();
        $sqlfields[] = $uniquemappingrelation->secondarytablefield;

        // If we only have one field and nothing else to map, it suggests that we have a
        // table that is not being synced to it's own table, but rather to a column
        // in another one e.g. roles sync to the role column in the enrolments table.
        // There will be a lot of duplicates in this case, so we want to keep it simple.
        // n.b. duplicates as we may not have a unique roles table externally. We may
        // simply be saying 'what roles are referenced from the enrolments table?'
        // TODO does this cover all use cases?

        $relations = $this->get_mapped_relations();
        foreach ($relations as $relation) {
            // Can't use the mapping if it doesn't map to an internal and external field.
            if (empty($relation->maintablefield)) {
                continue;
            }
            if (empty($relation->secondarytablefield)) {
                continue;
            }
            $sqlfields[] = $relation->secondarytablefield;
        }

        $sqlfields = array_unique($sqlfields); // Sometimes there will be dupes.

        $sql = $this->db_get_sql($this->get_external_table_name(), array(), $sqlfields);
        /* @var ADORecordSet $rs */
        return $this->externaldb->Execute($sql);
    }

    /**
     * We need to keep a load of internal Moodle data cached so we can use the ids to build up
     * the synced records. Internall, the cache is a multidimensional array like this:
     *
     * $cache[$tablename][$externalkey] = $internalid;
     *
     * @param  int|stdClass    $row
     *
     * @param string|array $externalkey Allows a field to be used that normally wouldn't. Used
     * to add the root category.
     *
     * @return bool
     */
    protected function add_to_cache($row, $externalkey = '') {

        if (!isset($this->cache)) {
            $this->cache = array();
        }

        $externalkeyfield = $this->get_internal_unique_field_name();

        // Get the key from the supplied object.
        if (!$externalkey) {

            // TODO this will ignore stuff that has no flag, leading to duplicates.
            // If we have an externalname being used as the key, we can't add this thing to
            // the cache without it.
            if (!isset($row->$externalkeyfield)) {
                // Data problem - no key so we can't cache it.
                // TODO need to warn when stuff is added from outside that has an empty field.
                return false;
            }

            $externalkey = $row->$externalkeyfield;
        }

        $this->cache[$externalkey] = $row;
        return true;
    }

    /**
     * Get the internal id for a particular external identifier. Right now, it returns
     *
     * $cache[$tablename][$externalkey] = $internalid;
     *
     * @param string|array $externalkey
     *
     * @param bool         $tablename
     * @param bool         $findhidden Will return items set to be hidden (instead of deleted) in
     * earlier syncs
     *
     * @return bool
     */
    public function retrieve_from_cache($externalkey, $tablename = false, $findhidden = false) {

        // We want to ignore any that are suspended/hidden but have them available to reanimate.
        $notdeletedconditions = $this->get_sql_not_deleted_conditions();

        if (!$tablename || $tablename == $this->get_internal_table_name()) {

            if ($this->cache_is_primed()) {

                if (isset($this->cache[$externalkey])) {
                    $item = $this->cache[$externalkey];
                    if (!$findhidden) {
                        // We don't want to get ones that are hidden back, so check all conditions and
                        // return false if they are not met.
                        foreach ($notdeletedconditions as $key => $value) {
                            if (!isset($item->$key)) {
                                continue;
                            }
                            if ($item->$key != $value) {
                                return false;
                            }
                        }
                    }

                    return $item;
                }
                return false;
            } else {
                // Not using the cache e.g. on login for a single user, so we just use SQL.
                // Get SQL.

                return $this->retrieve_directly_from_table($externalkey, $findhidden);

                // Possibly including flags.
                // Retrieve item.
            }

        }

        // If it's from another table, we need to pass this request on to the right object.
        $table = $this->enrolmentplugin->get_table_object($tablename);
        return $table->retrieve_from_cache($externalkey);
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

        $select = "
                    SELECT maintable.*";
        if ($this->use_flags()) {
            $select .= " , flags.externalname ";
        }
        $select .= "
                    FROM {{$this->get_internal_table_name()}} maintable
                ";
        if ($this->use_flags()) {
            $select .= "
                    INNER JOIN {databaseextended_flags} flags
                            ON flags.tablename = '{$this->get_internal_table_name()}' AND flags.itemid = maintable.id ";
        }
        $where = " WHERE {$this->get_internal_unique_field_name()} = :externalkey ";
        $params = array('externalkey' => $externalkey);

        if (!$findhidden) {
            $counter = 0;
            // We want to see just the ones that are visible.
            foreach ($notdeletedconditions as $name => $vale) {
                $where .= " AND {$name} = :value{$counter}";
                $params['value'.$counter] = $vale;
                $counter++;
            }
        }

        return $DB->get_record_sql($select.$where, $params);
    }

    /**
     * Removes an item from the cache so that as things are deleted, it remains an acccurate
     * reflection of the Moodle table (if it's being used for an id cache) or so that it only
     * holds items that need to be deleted (if we are using it for a comparison cache when it is
     * this table that is being synced)
     *
     * @param $key
     *
     * @throws coding_exception
     * @return bool
     */
    public function remove_from_cache($key) {

        $keyfield = $this->get_internal_unique_field_name();

        if (is_string($key) && !is_string($keyfield)) {
            $message = 'Trying to remove an item with a standard table key ('.$key.
                ') from a join table cache ('.
                $this->get_internal_table_name().')';
            throw new coding_exception($message);
        }

        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            return true;
        }

        return false;
    }

    /**
     * See parent.
     *
     * @param object $row
     *
     * @return mixed
     */
    protected function get_unique_key_values_from_row($row) {

        $keyfield = $this->get_internal_unique_field_name();

        return $row->$keyfield;
    }

    /**
     * Tells us what field the external table uses to refer to these records uniquely.
     *
     * @return string
     */
    public function get_external_unique_key_field() {
        $relation = $this->get_unique_mapping_relation();
        return $relation->secondarytablefield;
    }

    /**
     * @see parent.
     *
     * @param $thingtofindmatchfor
     * @param $listofotherthings
     *
     * @return bool
     */
    public function find_matching_thing_for_test($thingtofindmatchfor, $listofotherthings) {

        $expectedvalue = false;

        $uniquemappingrelation = $this->get_unique_mapping_relation();

        foreach ($listofotherthings as $possiblematch) {

            $expectedinternalfieldname = $uniquemappingrelation->maintablefield;
            $expectedexternalfieldname = $uniquemappingrelation->secondarytablefield;
            if (isset($thingtofindmatchfor->$expectedinternalfieldname)) {
                $expectedvalue = $thingtofindmatchfor->$expectedinternalfieldname;
            } else {
                $debugpause = '';
            }

            if ($possiblematch->$expectedexternalfieldname == $expectedvalue) {
                return $possiblematch;
            }
        }
        return false;
    }

    /**
     * Gets the single relation that is the unique identifier for this table.
     *
     * @return mixed
     */
    public function get_unique_mapping_relation() {

        global $DB;

        static $relation;

        if (isset($relation)) {
            return $relation;
        }

        $conditions = array('maintable' => $this->get_internal_table_name(),
                            'internal' => ENROL_DATABASEEXTENDED_EXTERNAL,
                            'uniquefield' => ENROL_DATABASEEXTENDED_UNIQUE);

        $relation = $DB->get_record('databaseextended_relations', $conditions);

        return $relation;
    }

    /**
     * Tells us what fields must exist internally.
     *
     * @return array
     * @throws coding_exception
     */
    protected function get_mandatory_internal_fields() {

        // It may be that the chosen primary key is not one of the default mandatory fields.
        // This makes sure it isn't overlooked.

        $uniquemappingrelation = $this->get_unique_mapping_relation();
        if (!empty($uniquemappingrelation) &&
            !empty($uniquemappingrelation->maintablefield)
        ) {

            return array($uniquemappingrelation->maintablefield);
        }

        $message = 'No mandatory fields available for '.$this->get_internal_table_name().'table';
        throw new coding_exception($message);
    }

    /**
     * This will try to find an existing row with the same key field as the incoming row. If one's there, it'll get
     * claimed by way of having a flag attached.
     *
     * @param stdClass $incomingrow
     * @return bool
     */
    protected function claim_existing_record($incomingrow) {

        if (!$this->should_try_to_claim_existing_items()) {
            return false;
        }

        $uniquekey = $this->get_internal_unique_field_name();

        try {
            $existingrecord = $this->get_existing_internal_row($incomingrow);
        } catch (Exception $e) {
           $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_WARNING);
            echo $this->enrolmentplugin->get_line_end().'Problem whilst claiming existing from '.$this->get_internal_table_name().': '.
                $incomingrow->$uniquekey.' '.$e->getMessage().'    ';
            return false;
        }

        if ($existingrecord) {
            $incomingrow->id = $existingrecord->id;

            $this->add_item_flag($incomingrow);
            // We need to use the cache to find parent ids on subsequent passes.
            $incomingrow->notfordeletion = true;
            $this->add_to_cache($incomingrow);

            return true;
        }

        return false;
    }

    /**
     * @param string $externalrow
     * @return bool|stdClass|void
     */
    protected function get_existing_internal_row($externalrow) {

        global $DB;

        $uniquefield = $this->get_internal_unique_field_name();
        $tablename = $this->get_internal_table_name();

        if ($this->use_flags()) {

            $sql = "
                SELECT *
                  FROM {{$tablename}} internaltable
            INNER JOIN {databaseextended_flags} flags
                    ON flags.tablename = :tablename
                   AND flags.itemid = internaltable.id
                 WHERE flags.externalname = :rowkey
            ";
            $params = array(
                'rowkey' => $externalrow->$uniquefield,
                'tablename' => $tablename
            );
            return $DB->get_record_sql($sql, $params);
        } else {
            return $DB->get_record($this->get_internal_table_name(), array($uniquefield => $externalrow->$uniquefield));
        }
    }

    /**
     * Given the id of an internal Moodle DB row, this will find the unique value that identifies it for the external DB.
     *
     * @param $id
     * @return mixed
     */
    public function get_external_unique_identifier_from_id($id) {

        global $DB;

        $sql = "
                SELECT {$this->get_internal_unique_field_name()}
                  FROM {{$this->get_internal_table_name()}} maintable
            ";

        if ($this->use_flags()) {
            $sql .= " INNER JOIN {databaseextended_flags} flags
                              ON flags.tablename = '{$this->get_internal_table_name()}'
                             AND flags.itemid = flags.id ";
        }

        $sql .= " WHERE maintable.id = :id ";

        return $DB->get_field_sql($sql, array('id' => $id));
    }
}
