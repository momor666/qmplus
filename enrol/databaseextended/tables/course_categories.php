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
 * Sync object for the course categories table
 */
class enrol_databaseextended_course_categories_table extends enrol_databaseextended_single_table {

    /**
     * Allow multiple inserts. Disabled because the need to add things to the cache with an
     * id after each insert is a pain
     * @var bool
     */
    protected $bulkinsert = false;

    /**
     * Adds the relations that this table has to the database
     */
    public static function install() {
        global $DB;

        $relations = array(

            // This relation links the courses table to the categorises table. A foreign key.
            array('maintable'           => 'course_categories',
                  'maintablefield'      => 'parent',
                  'secondarytable'      => 'course_categories',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            // This is the default unique field for the categories.
            array('maintable'           => 'course_categories',
                  'maintablefield'      => 'idnumber',
                  'secondarytable'      => 'extcategories',
                  'secondarytablefield' => 'name',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'course_categories',
                  'maintablefield'      => 'description',
                  'secondarytable'      => 'extcategories',
                  'secondarytablefield' => 'description',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_NON_UNIQUE),
            array('maintable'           => 'course_categories',
                  'maintablefield'      => 'name',
                  'secondarytable'      => 'extcategories',
                  'secondarytablefield' => 'name',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'         => ENROL_DATABASEEXTENDED_NON_UNIQUE),
            array('maintable'           => 'course_categories',
                  'maintablefield'      => 'parent',
                  'secondarytable'      => 'extcategories',
                  'secondarytablefield' => 'parent',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_NON_UNIQUE)
        );
        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     *
     * @return bool|int
     */
    protected function add_instance($data) {

        global $DB;
        
        $uniquefield = $this->get_internal_unique_field_name();
        
        if (!empty($data->$uniquefield)) {
            // Whitespace in external table on one row and not another will lead to dupes here.
            $existingcategory = $DB->get_record($this->get_internal_table_name(), array("{$uniquefield}" => $data->$uniquefield));
            
            if($existingcategory) {
                if ($this->should_try_to_claim_existing_items()) {
                    if (isset($data->externalname)) {
                        // Don't lose this if it's there!
                        $existingcategory->externalname = $data->externalname;
                    }
                    // Claim it as our own!
                    $this->add_item_flag($existingcategory);
                    return true;
                }
            }
        }
            
        $data->sortorder = 999;
        $data->timemodified = time();
        $data->id = $DB->insert_record($this->get_internal_table_name(), $data);
        $this->add_item_flag($data);
        // We need to use the cache to find parent ids on subsequent passes.
        $data->notfordeletion = true;
        $this->add_to_cache($data);    

        return $data;
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param stdClass $thingtodelete
     */
    protected function delete_instance($thingtodelete) {

        global $DB;

        // Don't delete the Miscellaneous category or root mapping
        // TODO use flags to do this?
        if ($thingtodelete->id == 1 || $thingtodelete->id == 0) {
            return;
        }

        // TODO make this dynamic based on internal relations
        // Set all child categories to be attached to Miscellaneous as a new parent.
        $tablename = $this->get_internal_table_name();
        $idofmiscellaneouscategory = $this->get_default_id();
        $sql       = "UPDATE {{$tablename}}
                         SET parent = {$idofmiscellaneouscategory}
                       WHERE parent = :parent ";
        $params    = array('parent' => $thingtodelete->parent);
        $DB->execute($sql, $params);

        // Sort out all courses.
        $tablename = 'course';
        $sql       = "UPDATE {{$tablename}}
                         SET category = {$idofmiscellaneouscategory}
                       WHERE category = :id ";
        $params    = array('id' => $thingtodelete->id);
        $DB->execute($sql, $params);

        //$this->delete_item_flag($thingtodelete->id);

        //$DB->delete_records($this->get_internal_table_name(), array('id' => $thingtodelete->id));
    }

    /**
     * Wrapper for the main cross reference which will take account of self-joins by doign repeated
     * runs of the function tiull it works (i.e. nothing is skipped due to missing cache values.
     *
     * @param \ADORecordset $externaldata
     *
     * @param bool $reset
     * @internal param $tablescache
     *
     * @internal param $internaldata
     * @return array|bool
     */
    public function cross_reference($externaldata, $reset = false) {

        $finished = parent::cross_reference($externaldata, true);

        while ($finished === false) {
            $finished = parent::cross_reference($externaldata);
        }

        return $finished;
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @throws coding_exception
     * @return array
     */
    public function get_internal_existing_rows() {
        global $DB;

        $uniquefield = $this->get_internal_unique_field_name();
        if (!is_string($uniquefield)) {
            $message = 'Must have single unique field for course_categories table sync';
            throw new coding_exception($message);
        }
        // TODO what if we are using flags to determine uniqueness?
        $sql = "SELECT c.{$uniquefield},
                       c.id,
                       c.visible
                  FROM {course_categories} c
                 WHERE c.{$uniquefield} IS NOT NULL
                   AND c.{$uniquefield} != '' ";
        return $DB->get_records_sql($sql);
    }

    /**
     * Override to add the default category
     *
     * @param bool $onlystoreids
     *
     * @return bool
     */
    public function populate_cache($onlystoreids = true) {
        global $DB;

        // TODO this needs to be a setting
        // Only for unit tests.
        $rootcategoryname = $this->enrolmentplugin->get_config('rootcategory', 'root');

        // The root category id is 0 but no record exists. It is just used for top level categories
        // as a default. We need to make sure the mapping cache works for this. Also need to
        // make sure there there is a flag there as a placeholder. Needs updating if the
        // root category setting has changed, so we check every time.
        $conditions = array('tablename' => 'course_categories',
                            'itemid' => 0);
        $rootflag = $DB->get_record('databaseextended_flags', $conditions);

        if (!$rootflag) {
            $rootflag = new stdClass();
            $rootflag->tablename = 'course_categories';
            $rootflag->itemid = 0;
            $rootflag->externalname = $rootcategoryname;
            $DB->insert_record('databaseextended_flags', $rootflag);
        } else if ($rootflag->externalname !== $rootcategoryname) {
            $rootflag->externalname = $rootcategoryname;
            $DB->update_record('databaseextended_flags', $rootflag);
        }

        $result = parent::populate_cache($onlystoreids);

        if ($onlystoreids) {
            $this->add_to_cache(0, $rootcategoryname);
        } else {
            $categoriesuniquekey                = $this->get_internal_unique_field_name();

            $rootcategory                       = new stdClass();
            $rootcategory->id                   = 0;
            $rootcategory->$categoriesuniquekey = $rootcategoryname;
            $rootcategory->notfordeletion       = true;
            $this->add_to_cache($rootcategory, $rootcategoryname);
        }
        
        return $result;
    }

    /**
     * Tells us to add flags on creation and only delete items that have one
     * @return bool
     */
    public function use_flags() {
        return true;
    }

    /**
     * Fixes up the paths etc
     */
    protected function post_add_hook() {
        global $DB;

        $DB->execute('update {course_categories} set `parent`=0,`path`=concat("/",id) where `parent`<>0 and `parent`=`id`');
        
        fix_course_sortorder();
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'course_categories';
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

        static $defaultcategoryid;

        if (isset($defaultcategoryid)) {
            return $defaultcategoryid;
        }

        $defaultcategoryid = $this->get_config('defaultcategory');

        // May have been deleted since settings were made.
        $defaultcategory = $DB->record_exists('course_categories',
                                              array('id' => $defaultcategoryid));
        if ($defaultcategory) {
            return $defaultcategoryid;
        }

        // Just grab the one with the lowest id.
        $categories = $DB->get_records('course_categories', array(),
                                       'sortorder', 'id', 0, 1);
        // Not assuming that miscellaneous will always be at id 1.
        $defaultcategory = reset($categories);
        $defaultcategoryid = $defaultcategory->id;
        return $defaultcategoryid;
    }

    /**
     * This determines whether we will be trying to get existing records and claimm them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
        return $this->get_config('course_categories_claim_existing', true);
    }

    /**
     * Sanity check to test whether data that will be synchronised is consistent
     * @param ADORecordset|array $externalthing
     *
     * @return bool
     */
    public function sanity_check($externalthing) {

        $extcatcheck = $this->check_inconsistent_rows();
        return $extcatcheck && parent::sanity_check($externalthing);

    }

    /**
     * Extra implementation of the sanity_check function,
     * check added to find the inconsistent data in the external course categories table
     * @return bool
     */
    private function check_inconsistent_rows() {

        global $DB;

        // Get name of the external table (secondarytable) mapped to course_categories.
        $table = $this->get_external_table_name(); // Return table name for the query without prefix.

        // Query to get the name of external parent column based on mapping.
        $conditions = array('maintable' => $this->get_internal_table_name(),
                            'maintablefield' => 'parent',
                            'secondarytable' => $table);
        $extparentrow = $DB->get_record('databaseextended_relations', $conditions);

        $parent = $extparentrow->secondarytablefield;

        // Retrieve name of external idnumber column
        $conditions = array('maintable' => $this->get_internal_table_name(),
                            'maintablefield' => 'idnumber',
                            'secondarytable' => $table);
        $extidnumrow = $DB->get_records('databaseextended_relations', $conditions);

        $idnumber = reset($extidnumrow)->secondarytablefield;

        // Retrieve name of rootcategory, specified in the external database connection setting page, so
        // it can be ignored when checking for PK match.
        $conditions = array('plugin' => 'enrol_databaseextended',
                            'name' => 'rootcategory');
        $row = $DB->get_record('config_plugins', $conditions);
        $rootcategory =  $row->value;

        // Check whether any categories in the external table have parent ids that
        // do not match any of PKs in that table.
        $sql="SELECT * FROM {$table} a
              WHERE a.parent NOT IN (SELECT idnumber FROM {$table} b )
              AND a.{$parent} <> '{$rootcategory}'";

        $result = $this->externaldb->Execute($sql);

        $inconsistent_rows = $result->_numOfRows;

        if ($inconsistent_rows > 0) {
            echo "\nFound " . $inconsistent_rows . ' inconsistent rows. The external categories data is not clean, ';
            echo 'so tell the admin at the other end.' . "\n";
            echo 'They should use this query on their DB to see the categories that have missing parents:' . "\n";
            echo $sql . "\n\n";
        }

        return empty($inconsistent_rows);

    }

}

