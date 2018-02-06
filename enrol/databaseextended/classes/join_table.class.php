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
 * For tables that have a composite key (ids from several other tables)
 */
abstract class enrol_databaseextended_join_table extends enrol_databaseextended_table_base {

    /**
     * @var array The relations that together identify this record uniquely e.g. for groups_members,
     * group and user. The two (or more) internal fields will be used as a key (n array of values)
     * for the cache
     */
    protected $jointuniquemappingrelations;

    /**
     * Returns the field for this table containing the unique data that matches the primary unique
     * key in the external database. We have a join table e.g. user_enrolments, so this will be
     * two or more fields and we use an array
     *
     * @throws coding_exception
     * @return array
     */
    public function get_internal_unique_field_array() {

        static $internaluniquekey;

        // Cache if we can. Save CPU cycles for maximum performance on > 300,000 item loops.
        if (isset($internaluniquekey)) {
            return $internaluniquekey;
        }

        $jointuniquemappingrelations = $this->get_joint_unique_mapping_relations();

        if (count($jointuniquemappingrelations) < 2) {
            $message = 'Too few joint relations for the '.$this->get_internal_table_name().
                ' table. Cannot get unique key';
            throw new coding_exception($message);
        }

        $internaluniquekey = array();
        foreach ($jointuniquemappingrelations as $relation) {
            $internaluniquekey[] = $relation->maintablefield;
        }

        return $internaluniquekey;
    }

    /**
     * Checks that the config variables necessary to do the sync on this table have been provided.
     * subclasses can add other checks, but should call this too
     *
     * @return bool
     */
    public function check_config_vars_present() {
        // No primary key relation to link the internal and external together.

        if (!$this->sync_enabled()) {
            return true;
        }

        $jointuniquemappingrelations = $this->get_joint_unique_mapping_relations();
        if (empty($jointuniquemappingrelations) ||
            count($jointuniquemappingrelations) < 2
        ) {

            return false;
        } else {
            // No table to connect to.
            foreach ($jointuniquemappingrelations as $relation) {
                if (empty($relation->secondarytable)) {
                    return false;
                }
                // Missing fields.
                if (empty($relation->maintablefield)) {
                    return false;
                }
                if (empty($relation->secondarytablefield)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Getter for the name of the external table to sync to. Comes from the unique mapping relation.
     *
     * @param bool $withprefix
     *
     * @return bool
     */
    public function get_external_table_name($withprefix = true) {

        global $CFG, $DB;

        // We have a join table, so we won't have a unique relation. We ought to have more
        // than one relation, all pointing to the same table.
        $tablename = '';
        $jointuniquemappingrelations = $this->get_joint_unique_mapping_relations();
        foreach ($jointuniquemappingrelations as $relation) {
            if (empty($tablename)) {
                $tablename = $relation->secondarytable;
                break;
            }
        }

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
     * @param array $extraconditions if supplied, these will be added to the WHERE clause e.g. to limit to a single user
     * @return ADORecordSet
     */
    public function get_external_recordset($extraconditions = array()) {

        // Get the fields we want to sync from the mappings automatically.
        $sqlfields = array();

        // All non-unique bits.
        $nonuniquerelations = $this->get_mapped_relations();
        $jointuniquemappingrelations = $this->get_joint_unique_mapping_relations();

        // If we only have one field and nothing else to map, it suggests that we have a
        // table that is not being synced to it's own table, but rather to a column
        // in another one e.g. roles sync to the role column in the enrolments table.
        // There will be a lot of duplicates in this case, so we want to keep it simple.
        // n.b. duplicates as we may not have a unique roles table externally. We may
        // simply be saying 'what roles are referenced from the enrolments table?'
        // TODO does this cover all use cases?

        foreach ($jointuniquemappingrelations as $relation) {
            $sqlfields[] = $relation->secondarytablefield;
        }
        foreach ($nonuniquerelations as $relation) {
            // Can't use the mapping if it doesn't map to an internal and external field.
            if (empty($relation->maintablefield)) {
                continue;
            }
            if (empty($relation->secondarytablefield)) {
                continue;
            }
            $sqlfields[] = $relation->secondarytablefield;
        }

        $sql = $this->db_get_sql($this->get_external_table_name(), $extraconditions, $sqlfields);
        /* @var ADORecordSet $rs */
        return $this->externaldb->Execute($sql);
    }

    /**
     * We need to keep a load of internal Moodle data cached so we can use the ids to build up
     * the synced records. Internall, the cache is a multidimensional array like this:
     *
     * $cache[$tablename][$externalkey] = $internalid;
     *
     * @param  stdClass    $row
     *
     * @param string|array $externalkey Allows a field to be used that normally wouldn't. Used
     * to add the root category.
     *
     * @return bool|void
     * @internal param $externalkey
     * @internal param $tablename
     */
    protected function add_to_cache($row, $externalkey = '') {

        if (!isset($this->cache)) {
            $this->cache = array();
        }

        $externalkeyfield = $this->get_internal_unique_field_array();

        $currentcache = &$this->cache;
        // We need to make a multidimensional array for the cache. May be two or three keys
        // so we must loop over them to make the cache items. This array will be in the same
        // order that the relations appear in the database.
        foreach ($externalkeyfield as $index => $key) {
            // Last one should be an array item, not an array.
            if (($index + 1) < count($externalkeyfield)) { // Arrays start at 0.
                if (!isset($currentcache[$row->$key])) {
                    $currentcache[$row->$key] = array();
                }
                $currentcache = &$currentcache[$row->$key];
            } else {
                // TODO dupe check?
                $currentcache[$row->$key] = $row;
            }
        }
        
        // TODO Is there ever a scenario when this function will return false?
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
     * @param bool         $findhidden
     *
     * @throws coding_exception
     * @return stdClass|bool
     */
    public function retrieve_from_cache($externalkey, $tablename = false, $findhidden = false) {

        $notdeletedconditions = $this->get_sql_not_deleted_conditions();

        if (!is_array($externalkey)) {
            $message = 'Non-array cache key used for join table '.$this->get_internal_table_name();
            throw new coding_exception($message);
        }

        if (!$tablename || $tablename == $this->get_internal_table_name()) {

            if (!$this->cache_is_primed()) {
                return $this->retrieve_directly_from_table($externalkey, $findhidden);
            }

            // Multidimensional array as cache.
            $currentcache = &$this->cache;
            foreach ($externalkey as $index => $key) {
                if ($index + 1 == count($externalkey)) {
                    // TODO exception on empty?
                    if (isset($currentcache[$key])) {
                        $item = $currentcache[$key];
                        if (!$findhidden) {
                            // We don't want to get ones that are hidden back, so check
                            // all conditions and return false if they are not met.
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
                    } else {
                        return false;
                    }
                } else {
                    // Move through the cache hierarchy. Necessary so we can have arbitrary
                    // levels of nesting (an arbitrary length $externalkey arrays to access
                    // them).
                    if (isset($currentcache[$key])) {
                        $currentcache = &$currentcache[$key];
                    } else {
                        return false;
                    }
                }
            }
        }

        // If it's from another table, we need to pass this request on to the right object.
        $table = $this->enrolmentplugin->get_table_object($tablename);
        return $table->retrieve_from_cache($externalkey, $tablename, $findhidden);
    }

    /**
     * Removes an item from the cache so that as things are deleted, it remains an acccurate
     * reflection of the Moodle table (if it's being used for an id cache) or so that it only
     * holds items that need to be deleted (if we are using it for a comparison cache when it is
     * this table that is being synced)
     *
     * @param array $key
     *
     * @throws coding_exception
     * @return bool
     */
    public function remove_from_cache($key) {

        $keyfield = $this->get_internal_unique_field_array();

        // Make sure we have the right format of key for this cache.
        if (!is_array($key)) {
            $message = 'Trying to remove an item using a non-join table key ('.
                $key.') from a join table cache ('.
                $this->get_internal_table_name().')';
            throw new coding_exception($message);
        }
        if (!is_array($keyfield)) {
            $message = 'Trying to remove an item with a join table key ('.
                implode(', ', $key).') from a non-join table cache ('.
                $this->get_internal_table_name().')';
            throw new coding_exception($message);
        }

        // Cache may have several levels of multidimensionalness.
        $currentcache = &$this->cache;
        $deleted = false;
        foreach ($key as $index => $keyitem) {
            if (($index + 1) == count($key)) {
                // Should only be one array item with this value, but this is the easiest way.
                // Might leave an empty array.
                unset($currentcache[$keyitem]);
                $deleted = true;
            } else {
                // Move down through the hierarchy.
                if (isset($currentcache[$keyitem])) {
                    $currentcache = &$currentcache[$keyitem];
                } else {
                    // Nothing left in the cache at a higher level, so we can stop.
                    break;
                }
            }
        }

        // Now delete any arrays that are empty to keep it tidy. Need to start from the deepest
        // layer and move out, as we may have to delete several as the inner ones empty.
        if ($deleted) { // If nothing's deleted, we can't have an empty array.
            array_pop($key); // Already got rid of one.
            while (!empty($key)) {
                $currentcache =& $this->cache;

                // Take us to the deepest array and check it.
                foreach ($key as $cachekey => $cacheindex) {
                    if (($cachekey + 1) == count($key)) {
                        // Last one tests for emptiness.
                        if (empty($currentcache[$cacheindex])) {
                            unset($currentcache[$cacheindex]);
                            // This means that we now move one level up on the next cycle of the
                            // while loop.
                            array_pop($key);
                        } else {
                            // All higher levels will be non-empty. Avoid infinite recursion.
                            break 2;
                        }
                    } else {
                        $currentcache = &$currentcache[$cacheindex];
                    }
                }
            }
        }

        return $deleted;
    }

    /**
     * See parent.
     * The cache for the internal join tables is keyed by Moodle ids in the order in which
     * the relations appear. This saves memory for what may be a very large cache. It also means
     * that we want the corresponding Moodle ids for this row, not the external key as we would
     * for a single table.
     *
     * @param object $row
     *
     * @return mixed
     */
    protected function get_unique_key_values_from_row($row) {

        // Returns array('userid', 'enrolid').
        $keyfield = $this->get_internal_unique_field_array();

        $externalkey = array();
        foreach ($keyfield as $key) {
            $externalkey[] = $row->$key;
        }
        return $externalkey;
    }

    /**
     * Not used right now, but ought to return an array of it ever is.
     *
     * @throws coding_exception
     */
    public function get_external_unique_key_field() {
        $message = 'Cannot ask for external unique key of a join table! ('.
            $this->get_internal_table_name().')';
        throw new coding_exception($message);
    }

    /**
     * The unit tests want to be able to match created things with expected things. Here,
     *
     * @param $thingtofindmatchfor
     * @param $listofotherthings
     *
     * @return bool
     */
    public function find_matching_thing_for_test($thingtofindmatchfor, $listofotherthings) {

        foreach ($listofotherthings as $potentialmatch) {

            $match = true;
            $internalrelations = $this->get_internal_relations();

            foreach ($this->get_joint_unique_mapping_relations() as $relation) {

                $relationmatches = false;

                $internalrelation = false;
                foreach ($internalrelations as $possibleinternalrelation) {
                    if ($possibleinternalrelation->maintablefield == $relation->maintablefield) {
                        $internalrelation = $possibleinternalrelation;
                        break;
                    }
                }

                $internalrelationtable =
                    $this->enrolmentplugin->get_table_object($internalrelation->secondarytable);

                $expectedinternalfieldname = $relation->maintablefield;
                $expectedexternalfieldname = $relation->secondarytablefield;
                $expectedmoodleid = false;
                if (isset($thingtofindmatchfor->$expectedinternalfieldname)) {
                    $expectedmoodleid = $thingtofindmatchfor->$expectedinternalfieldname;
                } else {
                    // Problem!
                    // TODO exception?
                    $debugpause = '';
                }
                $externalvalue = $potentialmatch->$expectedexternalfieldname;
                if (!$internalrelationtable->cache_is_primed()) {
                    $internalrelationtable->populate_cache();
                }
                $moodleid = $internalrelationtable->get_moodle_field_from_cache($externalvalue);
                if ($moodleid == $expectedmoodleid) {
                    $relationmatches = true;
                }

                // Must have all matching or we fail.
                $match = $match && $relationmatches;
            }

            if ($match) {
                return $potentialmatch;
            }
        }

        return false;
    }

    /**
     * Checks to see if this relation (an external mapping one) has a corresponding internal one.
     *
     * @param stdClass $relation
     * @return bool
     */
    public function is_required_relation($relation) {
        $internalrelations = $this->get_internal_relations();
        foreach ($internalrelations as $internalrelation) {
            if ($internalrelation->maintablefield == $relation->maintablefield) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns an array of the relations that this join table uses to define it's rows uniquely.
     *
     * @return array
     */
    protected function get_joint_unique_mapping_relations() {

        global $DB;

        static $relations;

        if (isset($relations)) {
            return $relations;
        }

        $conditions = array('maintable' => $this->get_internal_table_name(),
                            'internal' => ENROL_DATABASEEXTENDED_EXTERNAL,
                            'uniquefield' => ENROL_DATABASEEXTENDED_UNIQUE);
        $relations = $DB->get_records('databaseextended_relations', $conditions);

        return $relations;
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

        $jointuniquemappingrelations = $this->get_joint_unique_mapping_relations();
        $mandatoryfields = array();
        // It may be that the chosen primary key is not one of the default mandatory fields.
        // This makes sure it isn't overlooked.
        if (!empty($jointuniquemappingrelations)) {
            foreach ($jointuniquemappingrelations as $relation) {
                if (!empty($relation->maintablefield)) {

                    $mandatoryfields[] = $relation->maintablefield;
                }
            }
            return $mandatoryfields;
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

        $uniquekey = $this->get_internal_unique_field_array();

        $params = array();
        foreach ($uniquekey as $keyfield) {
            $params[$keyfield] = $incomingrow->$keyfield;
        }

        // Is there a record and a flag?
        try {
            $existingrecord = $this->get_existing_internal_row($incomingrow);
        } catch (Exception $e) {
            echo 'Found duplicate records whilst claiming existing (join table): '.$this->get_internal_table_name().
                ' '.implode(',', $params);
            return true;
        }

        if ($existingrecord) {
            $incomingrow->id = $existingrecord->id;

            if ($this->use_flags()) {
                $this->add_item_flag($incomingrow);
            }
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

        if ($this->use_flags()) {

            $sql = "
                SELECT *
                  FROM {{$tablename}} internaltable
            INNER JOIN {databaseextended_flags} flags
                    ON flags.tablename = :tablename
                   AND flags.itemid = internaltable.id
                 WHERE {$sql}
            ";

            $params['tablename'] = $tablename;

            return $DB->get_record_sql($sql, $params);
        } else {
            return $DB->get_record($tablename, $params);
        }
    }
    	
    /**
     * Gets the row directly from Moodle tables rather than from the memory based cache. This is for use when
     * we are doing a single user sync and don't want the massive memory overhead of filling the whole cache.
     *
     * @param array $externalkey
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

        // The $externalkey ought to be in the same order as the fields.
        $fields = $this->get_internal_unique_field_array();
        $counter = 0;
        $where = array();
        $params = array();
        foreach ($fields as $index => $fieldname) {
            $where[] = " {$fieldname} = :externalkey{$counter} ";
            $params['externalkey'.$counter] = $externalkey[$index];
            $counter++;
        }

        if (!$findhidden) {
            // We want to see just the ones that are visible.
            foreach ($notdeletedconditions as $name => $vale) {
                $where[] = "{$name} = :value{$counter}";
                $params['value'.$counter] = $vale;
                $counter++;
            }
        }

        if (!empty($where)) {
            $where = 'WHERE '.implode(' AND ', $where);
        }

        return $DB->get_record_sql($select.$where, $params);
    }

    /**
     * Triggered when a single user logs on.
     *
     * @todo belongs in the superclass probably.
     *
     * @param stdClass $user
     * @return bool
     */
    public function single_user_sync($user) {

        $this->issingleuser = true;

        $usertable = enrol_databaseextended_user_table::get_table_singleton();
        $currentuseridentifier = $usertable->get_external_unique_identifier_from_id($user->id);

        if (empty($currentuseridentifier)) {
            return false;
        }

        $nameofexternaluserfield = $this->get_external_field_for_joined_table('user');

        $externaldata = $this->get_external_recordset(array($nameofexternaluserfield => $currentuseridentifier));

        if (empty($externaldata)) {
            return false;
        }

        $numberofrowsskipped = 0;
        $numberofrowsadded = 0;
        $numberofrowsupdated = 0;
        $internalmappedfieldnames = $this->get_internal_mapped_fields();
        $donesofar = 0;

        // Get all of the existing rows for this user. Allows us to mark them off as we find them in the external source,
        // then delete any that are left.
        $useriduniquefield = $this->get_internal_fieldname_for_joined_table('user');
        $existingrecords = $this->get_single_user_existing_records($useriduniquefield, $user);

        // First loop creates and updates.

        // N.b. arrays of objects have items passed by reference, so the following
        // operations modify the array despite no reference operator & symbol.

        if ($externaldata instanceof ADORecordSet) {
            $externaldata->MoveFirst();
        }

        while (($externalrow = $externaldata->FetchRow()) !== false) {

            // DB stuff needs sorting out, but internal data for 1-1 tables is OK.
            if ($externaldata instanceof ADORecordset) {
                // This switched the row to use internal field names
                // Using a new name so that we don't reassign to the recordset.
                try {
                    $moodlenamedrow = $this->get_sanitised_external_row_with_internal_field_names($externalrow);
                } catch (Exception $e) {
                    // Missing a field or something. The tablecache will hold the errors for us.
                    $numberofrowsskipped++;
                    continue;
                }
            } else {
                $moodlenamedrow = $externalrow;
            }

            // Doing this at the top as the continue possibility lower down means we
            // may skip some otherwise.
            $donesofar++;

            try {
                // This switches the values to Moodle ids when needed, so we ought to have a row
                // ready for DB insertion now.
                $this->substitute_for_moodle_ids($moodlenamedrow);
            } catch (Exception $e) {
                // If we can't create links to the other bits of Moodle we need to e.g.
                // specified course doesn't exist, then we can go no further.
                $numberofrowsskipped++;
                continue;
            }

            // The $externalrow ends up as the new DB row.
            $externalkey = $this->get_unique_key_values_from_row($moodlenamedrow);
            $internalrow = $this->retrieve_from_cache($externalkey);

            if (!$internalrow) { // Create it.

                // May have just been hidden by a previous sync.
                $hiddenrow = $this->retrieve_from_cache($externalkey, false, true);
                if (!$this->reanimate($hiddenrow) && !$this->claim_existing_record($moodlenamedrow)) {

                    // There is a use case where we want to allow the users to add courses etc manually, using the correct id
                    // number and then for the sync script to take over. Without this, we will end up with duplicates,
                    // especially when we first enable the script.
                    $this->add_instance($moodlenamedrow);
                    $numberofrowsadded++;
                }
            } else {
                if (!$this->disable_updates()) {
                    // Might need an update. Join tables will never get here if external changes -
                    // they'll delete and recreate because the cache won't find the old record.
                    $this->do_update_if_necessary($moodlenamedrow,
                                                  $internalmappedfieldnames,
                                                  $internalrow);
                    $numberofrowsupdated++;
                }
                // Remove all the ones that exist externally, leaving those that need deleting.
                unset($existingrecords[$internalrow->id]);
            }
        }

        $this->post_add_hook();

        $this->delete_items($existingrecords);

        if ($numberofrowsskipped) {
            return false;
        }

        return true;
    }

    /**
     * Gets all the records from this table in Moodle which are related to this specific user.
     *
     * @param string $useriduniquefield Name of the field in the table that has a Moodle userid in it.
     * @param stdClass $user
     * @return array
     */
    public function get_single_user_existing_records($useriduniquefield, $user) {

        global $DB;

        $sql = $this->get_populate_cache_sql();
        $sql .= " AND {$useriduniquefield} = :userid ";

        $params = array('userid' => $user->id);
        $existingrecords = $DB->get_records_sql($sql, $params);

        return $existingrecords;
    }
}
