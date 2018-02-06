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

include_once($CFG->dirroot.'/enrol/databaseextended/exceptions/cache.php');
require_once($CFG->dirroot.'/enrol/databaseextended/lib.php');

/**
 * Base class for all sync tables. We use one subclass per DB table.
 */
abstract class enrol_databaseextended_table_base {

    /**
     * @var enrol_databaseextended_table_base This holds the singletons for all subclasses as
     * an array. They are used this way because the classes are acting as caches for the mapped
     * data from their tables, so when we want to know the Moodle id for a particular key we find
     * in the external DB, we ask the table object for that table. This makes the cache
     * extendable as every time we add a new table class, it will automatically load mapped data
     * when it is instatiated at the start of the speedy sync, ready to use.
     */
    static protected $tablesingletonscache;

    /**
     * @var string unique field of the internal sync table, corresponding to
     */
    protected $internaluniquekey;

    /**
     * @var string The name of the external sync table
     */
    protected $externaltable;

    /**
     * @var string Primary key of the external sync table
     */
    protected $externaluniquekey;

    /**
     * @var enrol_databaseextended_plugin an instance of the main enrolment plugin
     */
    protected $enrolmentplugin;

    /**
     * @var ADOConnection
     */
    protected $externaldb;

    /**
     * @var array of arrays - Data relations between internal and external
     */
    protected $mappingrelations;

    /**
     * @var bool Flag to tell the sync whether to use the bulk insert method for this table or not
     */
    protected $bulkinsert = false;

    /**
     * Some tables should not be synced e.g. users. They are just for caching
     *
     * @var bool
     */
    protected $syncenabled = true;

    /**
     * @var array Holds records prior to bulk insertion into the table
     */
    protected $bulkinsertcache = array();

    /**
     * Keeps track of items that need to have flags added
     *
     * @var array
     */
    protected $bulkflagcache = array();

    /**
     * Tables will be linked to other tables and so we need to have the correct ids in order to add
     * them to new records as we create them. The form is:
     *
     * $mappingrelations[] = aray('internaltable' => role,
     *                            'internalfield' => 'shortname',
     *                            // 'externaltable' => <we already know this>
     *                            'externalfield' => 'ROLEID');
     *
     *
     * @var array With 'internalfield', 'internaltable', 'externalfield'
     */
    protected $internalrelations;

    /**
     * @var array Keeps track of all of the existing instances of the items in this table, indexed
     * by the field
     */
    protected $cache;

    /**
     * @var array Fields that must exist in the external data - we discard the row if any
     * are missing
     */
    protected $mandatoryfields = array();

    /**
     * @var array Hold any error messages
     */
    protected $problems = array();

    /**
     * @var array Keeps track of what values have been asked for from the cache but were not there
     */
    protected $missingvalues = array();

    /**
     * @var bool Tells us whether we are just syncing for a single user or not. Affects the use of the cache.
     */
    protected $issingleuser = false;

    protected $errorStatus = 0;

    /**
     * @param $enrolmentplugin
     * @param $externaldb
     */
    protected function __construct($enrolmentplugin, $externaldb) {

        $this->enrolmentplugin = $enrolmentplugin;
        $this->externaldb = $externaldb;
    }

    function refresh()
    {
       $this->errorStatus=enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_OK;
    }

    /**
     * We want to have a single instance of each table so that we can reuse and cached data. This
     * manages it as a singleton pattern. The benefit is having each class act as a cache
     * for it's own data.
     *
     * @static
     * @param bool|enrol_databaseextended_plugin $enrolmentplugin
     * @param ADOConnection|bool                 $extdb Sent separately to the plugin to
     * allow unit testing
     *
     * @throws coding_exception
     * @return enrol_databaseextended_table_base
     */
    public static function get_table_singleton($enrolmentplugin = false, $extdb = false) {

        $classname = get_called_class();

        if (isset(self::$tablesingletonscache[$classname])) {
           $t=self::$tablesingletonscache[$classname];
           $t->refresh();
            // This also allows us to unit test by initialising the tables with a fake external
            // DB object before instantiating the enrolment plugin.
            return $t;
        }

        if (!$enrolmentplugin) {
            $message = 'Cannot get new '.$classname.' table without enrolment plugin instance';
            throw new coding_exception($message);
        }
        if (!$extdb) {
            $message = 'Cannot get new '.$classname.' table without extdb';
            throw new coding_exception($message);
        }

        // Make a new one if it's not there.
        /* @var $tableobjects[] enrol_databaseextended_speedy_table */
        if (class_exists($classname)) {
            $t=self::$tablesingletonscache[$classname] = new $classname($enrolmentplugin, $extdb);
            $t->refresh();
            return self::$tablesingletonscache[$classname];
        } else {
            $message = 'Missing table class: '.$classname;
            throw new coding_exception($message);
        }
    }

    /**
     * @param string $configname
     * @param string $default
     *
     * @return string
     */
    protected function get_config($configname, $default = null) {
        return $this->enrolmentplugin->get_config($configname, $default);
    }

    /**
     * Some places want to be able to list all the relations, both unique mapped and others.
     * @return array
     */
    public function get_mapped_relations() {
        global $DB;

        static $relations;

        if (isset($relations)) {
            return $relations;
        }

        $conditions = array('maintable' => $this->get_internal_table_name(),
                            'internal' => ENROL_DATABASEEXTENDED_EXTERNAL);
        $relations = $DB->get_records('databaseextended_relations', $conditions);

        return $relations;
    }

    /**
     * Returns an array of internal relations - effectively the foreign key relations for this table
     *
     * @return array
     */
    public function get_internal_relations() {
        global $DB;

        static $relations;

        if (isset($relations)) {
            return $relations;
        }
        $conditions = array('maintable' => $this->get_internal_table_name(),
                            'internal' => ENROL_DATABASEEXTENDED_INTERNAL);
        $relations = $DB->get_records('databaseextended_relations', $conditions);

        return $relations;
    }

    /**
     * Checks that the config variables necessary to do the sync on this table have been provided.
     * subclasses can add other checks, but should call this too
     *
     * @return bool
     */
    abstract public function check_config_vars_present();

    /**
     * Getter for the name of the external table to sync to. Comes from the unique mapping relation
     *
     * @param bool $withprefix
     *
     * @return bool
     */
    abstract public function get_external_table_name($withprefix = true);

    /**
     * Fetches the data from the external database that will be synced with the internal
     * Moodle table.
     *
     * @return ADORecordSet
     */
    abstract public function get_external_recordset();

    /**
     * Takes a raw row from the external recordset and makes sure it is formatted ready for the
     * sync process
     *
     * @param $externalrow
     * @throws coding_exception
     * @return stdClass|bool
     */
    protected function get_sanitised_external_row_with_internal_field_names($externalrow) {

        // Standardise the field names.
        $cleanedrow = $this->db_decode($externalrow);
        $relations = $this->get_mapped_relations();

        // Check we have all the internal moodle fields that we need.
        foreach ($this->mandatoryfields as $mandatoryfield) {

            // What does the external DB call this field?
            $extfieldname = $this->get_corresponding_external_field_name($mandatoryfield);

            if (empty($cleanedrow[$extfieldname])) {
                // Invalid record - must have all mandatory fields.
                throw new coding_exception('Incoming row does not have a field named '.$extfieldname.
                                               $this->enrolmentplugin->get_line_end());
            }
        }
        // Make an object which has the external data in it, keyed by the internal Moodle
        // field names for this table. Allows easy id look ups later.
        $thingtocreate = new stdClass();
        foreach ($relations as $relation) {
            $internalfieldname = $relation->maintablefield;
            $externalfieldname = $relation->secondarytablefield;

            // Possibly duff data in the relations table.
            if (empty($internalfieldname) || empty($externalfieldname)) {
                continue;
            }

            // Possibly misconfigured. Ought to get this noticed earlier, but as we have checked the essential fields,
            // we can let it go.
            if (!array_key_exists($externalfieldname, $cleanedrow)) {
                continue;
            }

            // Avoid whitespace problems - Moodle will write to DB with trimmed value so cache
            // won't match unless we trim here.
            $thingtocreate->{$internalfieldname} = trim($cleanedrow[$externalfieldname]);
        }

        return $thingtocreate;
    }

    /**
     * If the external DB has given us no data at all, we probably don't want to wipe out all
     * existing data, so we assume there has been as error and return
     *
     * @param ADORecordset|array $externalthing
     *
     * @return bool
     */
    public function sanity_check($externalthing) {

        if ($this->issingleuser) {
            // Without this, we will find that if all of a user's enrolments are removed
            // e.g. they leave the university, then they will not be unenrolled.
            return true;
        }

        if (is_array($externalthing)) {
            // One-to-one table.
            if (empty($externalthing)) {
                return false;
            }
            return true;
        }

        if (!$externalthing) {
            return false;
        }
        if ($externalthing->EOF) {
            return false;
        }

        if ($externalthing->NumRows() === 0) {
            return false;
        }

        return true;
    }

    /**
     * This will take the information about what is requested and what is not and work out which
     * rows need to be added and which need to be taken away. Implementation of the template method pattern.
     *
     * @param ADORecordset     $externaldata
     * @param bool $reset
     *
     * @throws coding_exception
     * @internal param array $tablescache with all of the table objects in it, keyed by table name
     *
     * @internal param $internaldata
     * @internal param bool $throwerroronmissingvalue Sometimes we want to run this more than
     * once e.g. a self join like category parent. This situation will find the cache empty
     * very often. Each pass will create the next level of the hierarchy.
     *
     * @return array
     */
    public function cross_reference($externaldata, $reset = false) {

        global $CFG;

        static $pass = 0; // How many times has this been run?
        if ($pass > 20) {
            // Categories loop over this function because we must have parents in place to
            // create children.
            $message = 'Probably inifinite recursion - check why cross_reference() has run 20'.
                ' times already for '.$this->get_internal_table_name();
            throw new coding_exception($message);
        }
        if ($reset) { // New count for each table.
            $pass = 0;
        }

        $this->enrolmentplugin->output_memory_status();

        $numberofrowsskipped = 0;
        $numberofrowsadded = 0;
        $numberofrowsupdated = 0;
        $internalmappedfieldnames = $this->get_internal_mapped_fields();
        $donesofar = 0;

        // First loop creates and updates.

        // N.b. arrays of objects have items passed by reference, so the following
        // operations modify the array despite no reference operator & symbol.
        $total = $externaldata->NumRows();
        if ($total) { // Avoid divide by zero errors.
            echo get_string('crossreferencing', 'enrol_databaseextended', $total).
                $this->enrolmentplugin->get_line_end();
            $this->enrolmentplugin->output_percent_done($donesofar, $total);
        }

        // Speeds up the bulk inserts. Must be sure we won't violate key constraints of course, but
        // the checking we're doing against the cache will take care of that.
        if ($this->bulkinsert_enabled()) {
            $this->disable_sql_keys();
        }

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
                   $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_WARNING);
                    // Missing a field or something. The tablecache will hold the errors for us.
                    if ($CFG->debug == DEBUG_DEVELOPER) {
                        echo $e->getMessage().$this->enrolmentplugin->get_line_end();
                    }
                    $numberofrowsskipped++;
                    continue;
                }
            } else {
                $moodlenamedrow = $externalrow;
            }

            // Doing this at the top as the continue possibility lower down means we
            // may skip some otherwise.
            $donesofar++;
            $this->enrolmentplugin->output_percent_done($donesofar, $total);

            try {
                // This switches the values to Moodle ids when needed, so we ought to have a row
                // ready for DB insertion now.
                $this->substitute_for_moodle_ids($moodlenamedrow);
            } catch (Exception $e) {
                // If we can't create links to the other bits of Moodle we need to e.g.
                // specified course doesn't exist, then we can go no further.
               $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_WARNING);
                $numberofrowsskipped++;
                if ($CFG->debug == DEBUG_DEVELOPER) {
                    echo $e->getMessage().$this->enrolmentplugin->get_line_end();
                }
                continue;
            }

            // The $externalrow ends up as the new DB row.
            $externalkey = $this->get_unique_key_values_from_row($moodlenamedrow);
            $internalrow = $this->retrieve_from_cache($externalkey);

            try
            {
               if (!$internalrow) { // Create it.

                  // May have just been hidden by a previous sync.
                  $hiddenrow = $this->retrieve_from_cache($externalkey, false, true);
                  // If reanimate works it won't delete the cache.
                  if ($this->reanimate($hiddenrow)) {
                     $this->mark_not_for_deletion($externalkey);
                  } else {

                     // There is a use case where we want to allow the users to add courses etc manually, using the corect id
                     // number and then for the sync script to take over. Without this, we will end up with duplicates,
                     // especially when we first enable the script.
                     if (!$this->claim_existing_record($moodlenamedrow)) {
                        if ($this->bulkinsert_enabled()) {
                           $this->bulk_insert($moodlenamedrow);
                        } else {
                           $this->add_instance($moodlenamedrow);
                        }
                     }
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
                  $this->mark_not_for_deletion($externalkey);
               }
            }
            catch(Exception $e)
            {
               echo $e->getMessage().$this->enrolmentplugin->get_line_end();
            }
        }

        // Flush any remaining records.
        if ($this->bulkinsert_enabled()) {
            $this->bulk_insert();
            $this->enable_sql_keys();
        }

        $this->post_add_hook();

        $this->enrolmentplugin->output_memory_status();
        $this->delete_from_cache();

        $pass++;

        $this->output_cross_reference_summary('added', $numberofrowsadded, $pass);
        $this->output_cross_reference_summary('updated', $numberofrowsupdated, $pass);
        $this->output_cross_reference_summary('skipped', $numberofrowsskipped, $pass);

        if ($numberofrowsskipped) {
            return false;
        }

        return true;
    }

    /**
     * Echos the summary of how many rows of x type were processed
     *
     * @param string $type skipped, added or updated
     * @param int $number
     * @param int $pass
     */
    protected function output_cross_reference_summary($type, $number, $pass = 1) {
        $a = new stdClass();
        $a->numberofrows = $number;
        $a->pass = $pass;
        $a->table = $this->get_internal_table_name();
        $message = get_string($type, 'enrol_databaseextended', $a);
        echo $message.$this->enrolmentplugin->get_line_end();
    }

    function final_state()
    {
       return $this->errorStatus;
    }

    /**
     * Checks whether a particular record needs updating by comparing each of the mapped
     * fields of each object. If necessary, the incoming object is mapped to the database.
     *
     * @param $externalrow
     * @param $mappedfieldnames
     * @param $internalrow
     */
    protected function do_update_if_necessary($externalrow, $mappedfieldnames, $internalrow) {

        $update = false;

        foreach ($externalrow as $externalfield => $externalvalue) {
            // Only check mapped fields.
            if (!in_array($externalfield, $mappedfieldnames)) {
                continue;
            }
            if (!isset($internalrow->$externalfield) ||
                ($internalrow->$externalfield != $externalvalue)
            ) {

                $update = true;
                $internalrow->$externalfield = $externalvalue;
            }
        }
        if ($update) {
            $this->update_instance($internalrow);
        }

        // Possibly, the only update needed is to the flag.
        if ($this->use_flags()) {
            // If we are doing an update, externalname can only be empty if it's not the unique key
            // i.e. $this->use_flags() and $uniquekey !== 'externalname' are implied because there
            // must have been a LEFT JOIN in populate_cache() for this to be empty.
            if (empty($internalrow->flagid)) {
                // Make a flag.
                $this->add_item_flag($internalrow);
            } else {
                $this->update_item_flag($internalrow);
            }
        }
    }

    /**
     * Deletes those that were not found in the external source
     */
    protected function delete_from_cache() {
        if (!empty($this->cache)) {
            if (!$this->issingleuser) {
//                debugging(get_string('cachelooping', 'enrol_databaseextended').
//                    $this->enrolmentplugin->get_line_end(), DEBUG_DEVELOPER);
                flush();
            }
            $this->delete_items($this->cache);
        } else {
            if (!$this->issingleuser) {
                debugging(get_string('nothingtodelete', 'enrol_databaseextended').
                $this->enrolmentplugin->get_line_end(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Enable DB keys again, so that indexes are rebuilt
     */
    private function enable_sql_keys() {
        global $DB;

        $table = $this->get_internal_table_name();
        $sql = "ALTER TABLE {{$table}} ENABLE KEYS";
        try {
            $DB->execute($sql);
        } catch (Exception $e) {
            // DB does not support this. No matter.
        }
    }

    /**
     * Turns off keys indexes if possible so that lots of inserts can be made quickly
     */
    public function disable_sql_keys() {
        global $DB;

        $table = $this->get_internal_table_name();
        $sql = "ALTER TABLE {{$table}} DISABLE KEYS";
        try {
            $DB->execute($sql);
        } catch (Exception $e) {
            // DB does not support this. No matter.
        }
    }

    /**
     * @abstract
     * Makes a new entry in the internal table, corresponding to an external row. Should
     * be called from all subclasses once the main record has been added
     *
     * @param stdClass $data newly created thing with Moodle id
     */
    abstract protected function add_instance($data);

    /**
     * @abstract
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    abstract protected function delete_instance($thingtodelete);

    /**
     * Maybe some fields are different now.
     *
     * @param $incoming
     *
     * @return bool
     */
    protected function update_instance($incoming) {
        global $DB;
        return $DB->update_record($this->get_internal_table_name(), $incoming);
    }

    /**
     * Loops over the items to be added and adds them (possibly in batches for speed)
     *
     * @param bool|object $row If not supplied, the cache will be flushed to DB
     */
    public function bulk_insert($row = false) {

        global $DB;

        if ($row) {
            $this->bulkinsertcache[] = $row;

            if ($this->use_flags()) {
                if ($this instanceof enrol_databaseextended_join_table) {

                    $key = $this->get_internal_unique_field_array();
                    $keyvalues = array();
                    foreach ($key as $fieldname) {
                        $keyvalues[] = $row->$fieldname;
                    }
                    $this->bulk_add_flags($keyvalues);
                } else {
                    $key = $this->get_internal_unique_field_name();
                    $this->bulk_add_flags($row->$key);
                }
            }
        }

        $message = '>>>> bulk_insert() bulkinsertcache size: '.count($this->bulkinsertcache).$this->enrolmentplugin->get_line_end();
        debugging($message, DEBUG_DEVELOPER);

        if ((count($this->bulkinsertcache) > 500) || !$row) {

            $message = '>>>> bulk_insert() about to bulk insert rows into Moodle <<<<'.$this->enrolmentplugin->get_line_end();
            debugging($message, DEBUG_DEVELOPER);

            if (count($this->bulkinsertcache) > 0) {
				
	            $fields = '';
	            $qms = array();
	            $params = array();
	
	            foreach ($this->bulkinsertcache as $rowtowrite) {
	
	                $rowarray = (array)$rowtowrite;
	
	                if (!$fields) {
	                    $fields = '('.implode(',', array_keys($rowarray)).')';
	                }
	                $newqms = array_fill(0, count($rowarray), '?');
	                $qms[] = '('.implode(',', $newqms).')';
	                $params = array_merge(array_values($rowarray), $params);
	            }
	
	            $qms = implode(',', $qms);
	
	            $table = $this->get_internal_table_name();
	
	            $sql = "INSERT INTO {{$table}} $fields VALUES{$qms}";
	
				
	            $DB->execute($sql, $params);
	
	            $this->bulkinsertcache = array();
	            
	            // Need to clear the flags cache now as we have all the ids. This should be the
	            // only time that the flags cache is triggered or else it'll look for items that have
	            // not yet been written by this method.
	            if ($this->use_flags()) {
	                $this->bulk_add_flags();
	            }
            }
        }
    }

    /**
     * Doing bulk inserts deprives us of the ids we need to add flags. This function will cache the
     * unique identifiers that allow us to get the ids we need for a bulk insert to the flags table.
     *
     * @param bool|string|array $rowidentifier
     */
    protected function bulk_add_flags($rowidentifier = false) {

        global $DB;

        if ($rowidentifier) {
            $this->bulkflagcache[] = $rowidentifier;
        }

        if (!$rowidentifier) {

            $message = '>>>> bulk_add_flags() about to bulk insert rows into Moodle <<<<'.$this->enrolmentplugin->get_line_end();
            debugging($message, DEBUG_DEVELOPER);

            if (count($this->bulkflagcache) > 0) {
                
				$message = ' bulk_add_flags() about to bulk insert '.count($this->bulkflagcache).' flags into Moodle <<<<'.$this->enrolmentplugin->get_line_end();
                debugging($message, DEBUG_DEVELOPER);

				// First retrieve all the ids for the rows that have been inserted so far.
				$tablename = $this->get_internal_table_name();

				if ($this instanceof enrol_databaseextended_join_table) {
					$key = $this->get_internal_unique_field_array();
					// Join table, so we need to search differently.
					$sql = "
						SELECT id
						FROM {{$tablename}}
						WHERE ";
					$clausesarray = array();
					$params = array();

					// Slightly hacky - we assume that the flag values are order the same as the key
					// fields, because the array of keyfields was used to add them in the first place.
					foreach ($this->bulkflagcache as $flagarray) {
						$flagclauses = array();
						foreach ($key as $keyindex => $keyfield) {
							$flagclauses[] = $keyfield.' = ? ';
							$params[] = $flagarray[$keyindex];
						}
						$clausesarray[] = '('.implode(' AND ', $flagclauses).')';
					}
					$sql .= implode(' OR ', $clausesarray);
				} else {
					$key = $this->get_internal_unique_field_name();

					list($insql, $params) = $DB->get_in_or_equal($this->bulkflagcache);
					$sql = "
						SELECT id
						FROM {{$tablename}}
						WHERE {$key} {$insql}
						";
				}

				$ids = $DB->get_records_sql($sql, $params);

				// Now, make a new bulk insert query that will add a flag for each one.
				$fields = '(itemid, tablename)';
				$params = array();
				$qms = array();
				foreach (array_keys($ids) as $id) {
					$qms[] = '(?, ?)';
					$params = array_merge(array($id,
												$tablename),
										  $params);
				}

				$qms = implode(',', $qms);

				$sql = "INSERT INTO {databaseextended_flags} $fields VALUES{$qms}";

				$DB->execute($sql, $params);

				$this->bulkflagcache = array();
			}
        }
    }

    /**
     * Loops over things to be removed and deletes them. Possibly in batches for speed.
     *
     * @param array $items
     *
     * @param int   $total
     *
     * @return int
     * @internal param bool $echo Don't make the recursions echo a total - just the
     * main function call
     */
    public function delete_items($items, $total = 0) {

        $deleted = 0;
        $donesofar = 0;
        $toplevel = false;

        if (!$total) { // Only on top of recursion.
            $total = count($items);
            if (!$this->issingleuser) {
                $this->enrolmentplugin->output_percent_done(0, $total);
            }
            $toplevel = true;
        }

        // Second loop deletes those that were not found in the external source.
        foreach ($items as $thingtodelete) {
            if (is_array($thingtodelete)) {
                // Recursion deals with multidimensional arrays.
                $deleted += $this->delete_items($thingtodelete, $total);
            } else {
                $donesofar++;
                if (is_object($thingtodelete)) {
                    // Don't delete the things flagged as safe.
                    if (empty($thingtodelete->notfordeletion)) {
                        $this->delete_instance($thingtodelete);
                        $this->remove_from_cache($this->get_unique_key_values_from_row($thingtodelete));
                        $deleted++;
                    }
                } else {
                    $this->delete_instance($thingtodelete);
                    $this->remove_from_cache($this->get_unique_key_values_from_row($thingtodelete));
                    $deleted++;
                }
                if (!$this->issingleuser) {
                    $this->enrolmentplugin->output_percent_done($donesofar, $total);
                }
            }
        }

        if ($toplevel && !$this->issingleuser) {
            echo 'Deleted '.$deleted.' things.'.$this->enrolmentplugin->get_line_end();
        }

        return $deleted; // To count at the end.

    }

    /**
     * Builds the SQL fragment to use with the external DB
     *
     * @param        $table
     * @param array  $conditions
     * @param array  $fields
     * @param bool   $distinct
     * @param string $sort
     *
     * @return string
     */
    protected function db_get_sql($table,
                                  array $conditions,
                                  array $fields,
                                  $distinct = true,
                                  $sort = "") {

        $fields = $fields ? implode(',', $fields) : "*";
        $where = array();
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $value = $this->db_encode($this->db_addslashes($value));

                $where[] = "$key = '$value'";
            }
        }

        $mandatoryinternalfields = $this->get_mandatory_internal_fields();

        // Must be data in all mandatory fields.
        foreach ($mandatoryinternalfields as $field) {
            $externalfield = $this->get_corresponding_external_field_name($field);
            $where[] = " {$externalfield} IS NOT NULL AND {$externalfield} != '' ";
        }

        $where = $where ? "WHERE ".implode(" AND ", $where) : "";

        $sort = $sort ? "ORDER BY $sort" : "";
        $distinct = $distinct ? "DISTINCT" : "";
        $sql = "SELECT $distinct $fields
                       FROM $table
                            $where
                            $sort";

        return $sql;
    }

    /**
     * Lets us know whether this table should be run through the sync process or just used as a
     * cache.
     *
     * @return bool
     */
    public function sync_enabled() {
        return $this->syncenabled;
    }

    /**
     * Escapes stuff for the external DB depending on it's type.
     *
     * @param $text
     *
     * @return mixed
     */
    protected function db_addslashes($text) {
        // Using custom made function for now.
        if ($this->get_config('dbsybasequoting')) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'',
                                      '"',
                                      "\0"),
                                array('\\\'',
                                      '\\"',
                                      '\\0'),
                                $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    /**
     * Escapes stuff for the external DB depending on it's type.
     *
     * @param $text
     *
     * @return array|string
     */
    protected function db_encode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_encode($value);
            }
            return $text;
        } else {
            return textlib::convert($text, 'utf-8', $dbenc);
        }
    }

    /**
     * Decodes stuff for the external DB depending on it's type.
     *
     * @param $text
     *
     * @return array|string
     */
    protected function db_decode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_decode($value);
            }
            return $text;
        } else {
            return textlib::convert($text, $dbenc, 'utf-8');
        }
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @return array
     */
    abstract public function get_internal_existing_rows();

    /**
     * In order ot make new records, we will need to DB ids of other data e.g. erolments will need
     * Moodle course ids and role ids. These are to be stored as arrays in class variables.
     * This tells us what other tables need to have populated caches before we can sync this one.
     *
     * @return array
     */
    public function get_linked_internal_table_names() {

        $internalrelations = $this->get_internal_relations();
        $tablenames = array();

        // For each relation get the stuff and cache it by the unique key.
        foreach ($internalrelations as $relation) {
            $tablename = $relation->secondarytable;
            $tableobject = $this->enrolmentplugin->get_table_object($tablename);

            if ($tableobject instanceof enrol_databaseextended_one_to_one_table) {
                // Need the linked table from the linked table.
                $tablenames =
                    array_merge($tablenames, $tableobject->get_linked_internal_table_names());
            }

            $tablenames[] = $tablename;
        }

        return array_unique($tablenames);
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
     * @internal param $externalkey
     * @internal param $tablename
     * @return bool
     */
    abstract protected function add_to_cache($row, $externalkey = '');

    /**
     * Get the internal id for a particular external identifier. Right now, it returns
     *
     * $cache[$tablename][$externalkey] = $internalid;
     *
     * @param string|array $externalkey
     *
     * @param bool $tablename
     * @param bool $findhidden
     * @return mixed
     */
    abstract public function retrieve_from_cache($externalkey, $tablename = false, $findhidden = false);

    /**
     * @abstract
     * USes the table's relations to get the unique value(s) that identify this record in the cache
     *
     * @param $row
     * @return mixed
     */
    abstract protected function get_unique_key_values_from_row($row);

    /**
     * Removes an item from the cache so that as things are deleted, it remains an accurate
     * reflection of the Moodle table (if it's being used for an id cache) or so that it only
     * holds items that need to be deleted (if we are using it for a comparison cache when it is
     * this table that is being synced)
     *
     * @param $key
     *
     * @return bool
     */
    abstract public function remove_from_cache($key);

    /**
     * This will go and get all of the ids from the specified Moodle table and store them keyed
     * by the specified mapping field e.g. an array of roleids keyed by role shortname (which
     * would be the field we have mirrored in the external enrolments table). This allows a new
     * enrolment to be added to the DB with the correct role id without making repeated DB queries.
     * For some tables, this may be a prohibitively large amount of data, even if it's just ids so
     * chunking may be needed for the non-speedy version.
     *
     * @param bool $onlystoreids To keep the memory small, this can be set to true. False if this is
     * the table we're syncing as we want a table full of the whole records so we can compare and
     * see what needs to be updated via a DB query.
     *
     * @internal param array $mappedfields any other fields we need the cache to have
     * @return bool
     */
    public function populate_cache($onlystoreids = true) {
    	
        global $DB;
        
        $this->empty_cache();

        $memoryatstart = memory_get_usage();
        $added = 0;
        $skipped = 0;
        // So we don't get an unprimed response with cache_primed() if there's no stuff.
        $this->cache = array();
        $fields = array('this.id');

        // If there is more than one (join table), we want all of them.
        if ($this instanceof enrol_databaseextended_join_table) {
            $uniquekey = $this->get_internal_unique_field_array();
            $fields = array_merge($fields, $uniquekey);
        } else {
            $uniquekey = $this->get_internal_unique_field_name();
            $fields[] = $uniquekey;
        }

        // Cache is huge if we get all fields. The only ones we need are the mapped ones and the
        // one(s) used to hide records for some tables.
        if (!$onlystoreids) {
            $internalfields = $this->get_internal_mapped_fields();
            $fields = array_merge($fields, $internalfields, $this->get_extra_fields());
        }
        $fields = implode(', ', array_unique($fields)); // Minimal to save memory.

        // Make sure we don't mess with pre-existing stuff. Some tables use flags to keep
        // track, others have.
        $sql = $this->get_populate_cache_sql($fields);
        if (!$sql) {
            $table = $this->get_internal_table_name();
            $select = "SELECT {$fields} ";
            $from = "    FROM {{$table}} this ";
            $where = '';
            if ($this->use_flags()) {

                // If we do a left join here, we'll end up with a cache full of stuff that doesn't have a flag,
                // which will then be deleted if it is not in the external DB. If the users want to add items that
                // will never be in the external sync, then this will be a problem.

                $from .= "
                   INNER JOIN {databaseextended_flags} flags
                           ON this.id = flags.itemid AND flags.tablename = '{$table}'
                    ";
            }

            $sql = $select.$from.$where;
        }

//        $message = '>>>> populate_cache get_recordset_sql() '.$sql.'<<<<'.$this->enrolmentplugin->get_line_end();
//        debugging($message, DEBUG_DEVELOPER);

        $recordset = $DB->get_recordset_sql($sql);
        while ($recordset->valid()) {
            $row = $recordset->current();
            if ($onlystoreids) {
                $addedsomething = $this->add_to_cache($row->id, $row->$uniquekey);
            } else {
                $addedsomething = $this->add_to_cache($row);
            }
            if (!$addedsomething) {
                // Keep track of how many didn't make it into the cache - may cause problems.
                $skipped++;
            } else {
                $added++;
            }
            $recordset->next();
        }

        $memoryatend = memory_get_usage();
        $sizeofcache = $memoryatend - $memoryatstart;

        echo 'Cache for '.$this->get_internal_table_name().' is '.
            $this->enrolmentplugin->human_readable_bytes($sizeofcache).
            $this->enrolmentplugin->get_line_end();
        echo $added.' items added to cache, '.$skipped.' skipped.'.
            $this->enrolmentplugin->get_line_end();

        return true;
    }



    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {
    }

    /**
     * Takes all of the external DB fields and swaps them for real Moodle ids if they need to be
     *
     * @param stdClass $externalrow
     * @throws coding_exception
     */
    protected function substitute_for_moodle_ids($externalrow) {

        // All external ids need to swapped with internal ones if we have foreign key relations.
        foreach ($this->get_internal_relations() as $relation) {

            // Get the object representing this linked table. Cache will already be populated.
            /* @var enrol_databaseextended_table_base $tablecache */
            $classname = $this->enrolmentplugin->get_table_classname($relation->secondarytable);
            $tablecache = $classname::get_table_singleton();
            if (!$tablecache->cache_is_primed() && !$this->issingleuser) {
                $message = 'Missing the '.$relation->secondarytable.' cache whilst syncing '.
                    $this->get_internal_table_name();
                throw new coding_exception($message);
            }

            // TODO this is not true - uses internal fieldnames now
            // The external row will have fields named as per the external DB. Internal relations
            // use the Moodle field names so we need to translate. This is done by using the mapped
            // relation (internal-external) that should exist for the field that this internal
            // relation refers to. e.g. the internal relation may say:
            // course->categoryid = course_categories->id
            // The mapping relation may say course->categoryid = extcourses->categoryname
            // So we want to get 'categoryname' so that we can reference that field in the
            // $externalrow variable.
            $internalfieldname = $relation->maintablefield;
            $externalfieldname = $this->get_corresponding_external_field_name($internalfieldname);

            // What does it call the linked item for this row e.g. the actual category name?
            if (!isset($externalrow->{$internalfieldname})) {
                $message = 'Cannot substitute the '.$externalfieldname.
                    ' field in the external row of the '.$this->get_internal_table_name().' table'.
                    ' because that field is not present in the row. Choose from: '.
                    implode(', ', array_keys(get_object_vars($externalrow)));
                throw new coding_exception($message);
            }
            $externalvalue = $externalrow->{$internalfieldname};

            // What internal row is already mapped to the external name in the cache?
            // Will throw exception if not found.
            $internalid = $tablecache->get_moodle_field_from_cache($externalvalue);

            if ($internalid === false) {

                // Use default if there is one.
                $internalid = $tablecache->get_default_id();
                if (!$internalid) {
                    // This is not always a problem - it could be that we are doing categories
                    // and the parent has not been created yet, or it could be that the external
                    // data is inconsistent.
                    $message = 'Missing item in the '.$relation->secondarytable.' mapping cache: '.
                        $externalvalue;
                    throw new coding_exception($message);
                }
            }

            // Switch 'em.
            $externalrow->{$internalfieldname} = intval($internalid);
        }
    }

    /**
     * We need to know what name the external DB uses for e.g. category id so we can combine the
     * internal and external relations to map the fields to real ids. This means that we star with
     * e.g. the courses table and work out that 1. it has an internal foreign key relation to
     * course_categories and 2. the field that's unique for course_categories is X
     *
     * @param string $internalfieldname
     *
     * @throws coding_exception
     * @return string
     */
    public function get_corresponding_external_field_name($internalfieldname) {

        $relations = $this->get_mapped_relations();
        // If this is not an id of an internal table, we are fine - just get the external name.
        foreach ($relations as $relation) {
            if ($relation->maintablefield == $internalfieldname) {
                return $relation->secondarytablefield;
            }
        }

        $message = 'No external mapping exists for the '.$internalfieldname.
            ' field in the '.$this->get_internal_table_name().' table.';
        throw new coding_exception($message);
    }

    /**
     * Tells us whether the mapping cache has already been populated.
     *
     * @return bool
     */
    public function cache_is_primed() {
        return isset($this->cache);
    }

    /**
     * Function to check whether this table is synced one to one with an internal table e.g.
     * enrolment instances with courses.
     *
     * @return bool
     */
    public function is_internal_one_to_one() {

        return ($this instanceof enrol_databaseextended_one_to_one_table);
    }

    /**
     * Allows us to wipe out all cached table objects so that they can be recreated e.g. for
     * unit testing the next function cleanly.
     */
    public static function destroy() {
        self::$tablesingletonscache = null;
    }

    /**
     * To save memory or switch from ids to whole rows and back, we need this.
     */
    public function empty_cache() {
        unset($this->cache);
        $this->missingvalues = array();
    }

    /**
     * Wipes all clean in the singletons cache.
     *
     * @static
     */
    static public function empty_all_caches() {

        if (!isset(self::$tablesingletonscache) || !is_array(self::$tablesingletonscache)) {
            return;
        }

        foreach (self::$tablesingletonscache as $table) {
            /* @var $table enrol_databaseextended_table_base */
            $table->empty_cache();
        }
    }

    /**
     * Used when we have an external name for a row in this table and we want to know the right
     * Moodle id so we can make a record for a different tbale that links to it.
     *
     * @param        $itemname
     *
     * @param string $field
     *
     * @throws cache_exception_dbx
     * @return bool|string Really an int in the form of a string
     */
    public function get_moodle_field_from_cache($itemname, $field = 'id') {

        $row = $this->retrieve_from_cache($itemname);

        if (!$row) {
            // Keep track of the things that have been missed and how many items needed them.
            if (!isset($this->missingvalues[$itemname])) {
                $this->missingvalues[$itemname] = 0;
            }
            $this->missingvalues[$itemname]++;
            $internaltablename = $this->get_internal_table_name();
            $message = 'Could not retrieve row from '.$internaltablename.
                ' cache using key: "'.$itemname.'"';
            throw new cache_exception_dbx($message, cache_exception_dbx::CACHE_MISSING_ROW, $internaltablename, $itemname);
        }

        if (is_object($row)) {

            if (!isset($row->$field)) {
                $internaltablename = $this->get_internal_table_name();
                $message = 'Asking for the '.$field.' field when the cache row doesn\'t have it. ('.
                    $internaltablename.')';
                throw new cache_exception_dbx($message, cache_exception_dbx::CACHE_ROW_MISSING_FIELD, $internaltablename, $field);
            }

            return $row->$field;
        } else {

            if ($field !== 'id') {
                $internaltablename = $this->get_internal_table_name();
                $message = 'Asking for a non-id field when the cache only has '.
                    $internaltablename.' ids';
                throw new cache_exception_dbx($message, cache_exception_dbx::CACHE_NON_ID_FIELD, $internaltablename, $field);
            }

            return $row;
        }
    }

    /**
     * Stuff that is in Moodle, but not in the external DB needs to be deleted. The cache keeps
     * track of this by flagging the ones that are updated as OK and then providing a list of
     * those which are not OK for the deletion routine.
     *
     * @param stdClass $cachekey
     */
    protected function mark_not_for_deletion($cachekey) {

        if ($this->is_self_referencing()) {
            $item = $this->retrieve_from_cache($cachekey);
            $item->notfordeletion = true;
            $this->add_to_cache($item);
        } else {
            // We can just delete it from the cache as it won't be needed for the rest of the sync.
            // Speeds up the deletion routine.
            $this->remove_from_cache($cachekey);
        }
    }

    /**
     * Some tables, rather than being deleted, are marked as invisible. If so, we need to add a
     * condition to the SQL used to populate the cache so that those ones are ignored.
     *
     * @return array
     */
    protected function get_sql_not_deleted_conditions() {
        // Default is to get all records.
        return array();
    }

    abstract public function get_external_unique_key_field();

    /**
     * Finds the matching thing from an array of expected things when supplied with a thing that
     * the sync just created. Used only for unit tests.
     *
     * @abstract
     * @param $thingtofindmatchfor
     * @param $listofotherthings
     */
    abstract public function find_matching_thing_for_test($thingtofindmatchfor, $listofotherthings);

    /**
     * Makes a new record in the item flags table so that we can tell later which ones to
     * delete if we need to.
     *
     * @param int $itemid
     *
     * @return bool
     */
    public function delete_item_flag($itemid) {

        global $DB;

        $conditions = array('itemid' => $itemid,
                            'tablename' => $this->get_internal_table_name());
        return $DB->delete_records('databaseextended_flags', $conditions);
    }

    /**
     * Makes a new record in the item flags table so that we can tell later which ones to
     * delete if we need to.
     *
     * @param stdClass $item
     *
     * @return bool
     */
    public function add_item_flag($item) {

        global $DB;

        $conditions = array('itemid' => $item->id,
                            'tablename' => $this->get_internal_table_name());
        $flag = $DB->get_record('databaseextended_flags', $conditions);

        $result = true;

        if (!$flag) {

            $newflag = new stdClass;
            $newflag->itemid = $item->id;
            $newflag->tablename = $this->get_internal_table_name();
            if (!empty($item->externalname)) { // Set explicitly by code somewhere in this table class.
                $newflag->externalname = $item->externalname;
            } else { // Store the unique field if we have it via relations.
                $uniquefield = $this->get_internal_unique_field_name();
                if (!empty($uniquefield)) {
                    $newflag->externalname = $item->$uniquefield;
                }
            }

            $result = $DB->insert_record('databaseextended_flags', $newflag);
        }

        return $result;
    }

    /**
     * Updates an item flag. Really just updates the externalname field.
     *
     * @param stdClass $item row from the cache that has already had any new external data added
     *
     * @throws coding_exception
     * @return bool
     */
    public function update_item_flag($item) {

        global $DB;

        if (empty($item->flagid)) {
            $message = 'Trying to update a '.$this->get_internal_table_name().
                ' flag without a flag id';
            throw new coding_exception($message);
        }

        $flag = new stdClass;
        $flag->id = $item->flagid;
        $flag->externalname = isset($item->externalname) ? $item->externalname : null;

        $result = $DB->update_record('databaseextended_flags', $flag);

        return $result;
    }

    /**
     * Some tables need to adjust stuff after a load of things have been added e.g. course
     * table needs call fix_course_sortorder(), but it's expensive, so we don't want to do this
     * too much
     */
    protected function post_add_hook() {
    }

    /**
     * If true, this class can be asked to created am item that is missing from the cache
     * e.g. if the external daata source has inconsistencies between the tables like a course
     * appearing in an enrolment record, but not in the enrolments table.
     *
     * @return bool
     */
    public function autocreate() {
        return false;
    }

    /**
     * Returns an array of internal fields that we need to have so we can get just these for
     * the cache, saving memory.
     *
     * @return array
     */
    protected function get_internal_mapped_fields() {

        $externalrelations = $this->get_mapped_relations();
        $fields = array();
        foreach ($externalrelations as $relation) {
            $fields[] = $relation->maintablefield;
        }

        return $fields;
    }

    /**
     * Some tables may need extra fields to be added to the cache when they are being synced (in
     * addition to the ones in their relations. Examples would be courses needing the 'visible'
     * field to hide/unhide them.
     *
     * @return array
     */
    protected function get_extra_fields() {
        return array();
    }
    /**
     * Echos a list of values requested from the cache which were missing and how many times
     * each one was asked for.
     *
     * @return string
     */
    public function output_missing_values() {

        if (empty($this->missingvalues)) {
            return;
        }

        $missingvaluesstring = get_string('cachemissingvalues',
                                          'enrol_databaseextended',
                                          $this->get_internal_table_name());
        echo $missingvaluesstring.$this->enrolmentplugin->get_line_end();

        foreach ($this->missingvalues as $item => $numberofrequests) {
            echo $item.' x'.$numberofrequests.$this->enrolmentplugin->get_line_end();
        }
    }

    /**
     * Each table has a way of us knowing whether the records are ones that have been
     * added by another part of Moodle, or whether they have been added by this script (in which
     * case we can delete them if necessary). This provides the SQL to tell us. Sometimes this
     * will be involving flags, but this is handled by by the individual functions via use_flags().
     *
     * @return string
     */
    protected function allowed_to_manage_sql() {
        return "";
    }

    /**
     * Does this table use the flags table to keep track of what stuff is created internally or not?
     * if so, every time a record is added, a flag will be added too. When the cache is populated,
     * only things that have a flag attached will be listed (mostly). Exceptions being things
     * to which other things are added e.g. categories. We don't want to create duplicates.
     * @return bool
     */
    public function use_flags() {
        return false;
    }

    /**
     * Defined specific SQL for the cache to use in populating itself.
     *
     * @param string $fields List of fields for SQL. Comma separated.
     * @return string
     */
    protected function get_populate_cache_sql($fields = '*') {
        return '';
    }

    /**
     * Checks to see if this table is OK for bulk inserts. Generally if there are extra operations
     * needed after each inserty, then it's not.
     *
     * @return bool
     */
    protected function bulkinsert_enabled() {
        return $this->bulkinsert;
    }

    /**
     * Some tables have a hierarchical organisation e.g. course_categories have a parent field
     * with an id from another course category. This means that some things need doing differently.
     *
     * @return bool
     */
    protected function is_self_referencing() {

        $selfreferencing = false;
        $relations = $this->get_internal_relations();

        foreach ($relations as $relation) {
            if ($relation->secondarytable == $relation->maintable) {
                $selfreferencing = true;
            }
        }

        return $selfreferencing;
    }

    /**
     * Some tables hide things instead of deleting them. This un-hides them if they reappear in the
     * external DB.
     *
     * @param stdClass $hiddenrow
     * @return bool
     */
    protected function reanimate($hiddenrow) {
        return false;
    }

    /**
     * Getter for the name of the table that this class syncs to.
     *
     * @abstract
     */
    public abstract function get_internal_table_name();

    /**
     * Returns the array of field names for a join table
     * @throws coding_exception
     * @return array
     */
    public function get_internal_unique_field_array() {
        throw new coding_exception('Should not be called on a non-join table');
    }

    /**
     * Returns the field name for a single table.
     *
     * @throws coding_exception
     * @return string
     */
    public function get_internal_unique_field_name() {
        throw new coding_exception('Should not be called on a non-single table');
    }

    /**
     * Returns an array of the fields which must be present.
     *
     * @abstract
     */
    abstract protected function get_mandatory_internal_fields();

    /**
     * If we don't have a Moodle id for this table for an outside field in a row, we can
     * either create that row with a default value fo this table, or create the record for
     * this table, then supply the id. This is the default-value option.
     *
     * @return bool|int
     */
    public function get_default_id() {
        return false;
    }

    /**
     * This determines whether we will be trying to get existing records and claim them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
        $configsetting = $this->enrolmentplugin->get_config($this->get_internal_table_name().'_claim_existing');
        return !empty($configsetting);
    }

    /**
     * This will try to find an existing row with the same key field as the incoming row. If one's there, it'll get
     * claimed by way of having a flag attached. Default to do nothing as one-to-one tables don't need
     * this.
     *
     * @param stdClass $incomingrow
     * @return bool
     */
    protected function claim_existing_record($incomingrow) {
        return false;
    }

    /**
     * If this returns true, then we will not update records, just create or delete them.
     *
     * @return bool
     */
    protected function disable_updates() {

        $setting = $this->enrolmentplugin->get_config($this->get_internal_table_name().'_disable_updates');

        return !empty($setting);
    }

    /**
     * @param $key
     * @return stdClass|bool
     */
    abstract protected function get_existing_internal_row($key);

    /**
     * @param stdClass $user
     * @return bool
     */
    public function single_user_sync($user) {
        return false;
    }

    /**
     * Supplies the data from the field in the Moodle tables that uniquely identifies this record in the external table.
     *
     * @param int $id id of the row in Moodle
     * @return string|bool
     */
    public function get_external_unique_identifier_from_id($id) {
        return false;
    }

    /**
     * If this table has a foreign key relationship with another (in Moodle), we want to be able to find what the
     * name of the external field for it is. e.g. user enrollments is linked to the user table. If we want to find out
     * what the name is of the user field in the external user enrollments table, this will tell us.
     *
     * @param $tablename
     * @throws coding_exception
     * @return string
     */
    protected function get_external_field_for_joined_table($tablename) {

        $thistableinternaluniquefield = $this->get_internal_fieldname_for_joined_table($tablename);

        if (!$thistableinternaluniquefield) {
            throw new coding_exception('No link the the '.$tablename.' table from the '.$this->get_internal_table_name().' table');
        }

        return $this->get_corresponding_external_field_name($thistableinternaluniquefield);
    }

    /**
     * Get the internal field name representing the Moodle foreign key to the joined table.
     *
     * @param string $tablename
     * @return bool
     */
    protected function get_internal_fieldname_for_joined_table($tablename) {
        $relations = $this->get_internal_relations();
        $thistableinternaluniquefield = false;
        foreach ($relations as $relation) {
            if ($relation->secondarytable == $tablename &&
                $relation->secondarytablefield == 'id'
            ) {

                $thistableinternaluniquefield = $relation->maintablefield;
            }
        }
        return $thistableinternaluniquefield;
    }

    protected function errorStatus($level)
    {
       $this->errorStatus=max($this->errorStatus,$level);
    }
}
