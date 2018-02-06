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
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * A row in the relations table that represents an internal relationship i.e. a foreign key.
 * Moodle doesn't use referential integrity and therefore we can't rely on the XMLDB files
 * to tell us what tables link to where. Used in the DB relationshiptype field of the
 * enrol_databaseextended_relations table.
 */
define('ENROL_DATABASEEXTENDED_INTERNAL_RELATION', 1);

/*
 * This defines an internal-external mapping of non-unique data e.g. course start date.
 */
define('ENROL_DATABASEEXTENDED_MAPPING_RELATION', 2);

/*
 * This defines a 1-1 relationship between an internal table and an external table. It's used for
 * the primary sync routine, with the other types used to make sure we get the correct internal
 * Moodle ids for linked items. Used in the DB relationshiptype field of the
 * enrol_databaseextended_relations table. The keys must be unique on both sides and match.
 */
define('ENROL_DATABASEEXTENDED_MAPPING_UNIQUE_RELATION', 4);

/*
 * This is to signify that the relation is a 1-1 relation internally. e.g. Enrol - course
 */
define('ENROL_DATABASEEXTENDED_INTERNAL_SINGULAR_RELATION', 8);

/*
 * Refers to the internal field of the databaseextended_relations table
 */
define('ENROL_DATABASEEXTENDED_INTERNAL', 1);

/*
 * Refers to the internal field of the databaseextended_relations table
 */
define('ENROL_DATABASEEXTENDED_EXTERNAL', 0);

/*
 * Refers to the unique field of the databaseextended_relations table
 */
define('ENROL_DATABASEEXTENDED_UNIQUE', 1);

/*
 * Refers to the unique field of the databaseextended_relations table
 */
define('ENROL_DATABASEEXTENDED_NON_UNIQUE', 0);

global $CFG;

require_once($CFG->dirroot.'/lib/enrollib.php');

/**
 * Database enrolment plugin implementation, extended by ULCC to sync categories and groups.
 *
 * @author  Petr Skoda - based on code by Martin Dougiamas, Martin Langhoff and others
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_databaseextended_plugin extends enrol_plugin {

/*
 * Three levels of problems
 */

   const ENROL_DATABASEEXTENDED_STATUS_OK = 0;
   const ENROL_DATABASEEXTENDED_STATUS_WARNING = 1;
   const ENROL_DATABASEEXTENDED_STATUS_MAJOR = 2;

    /**
     * @var array holds the table objects instantiated by the factory. Sometimes they need to
     * reference each other, so the static get_table() method allows this.
     */
    static protected $tableobjects = array();

    /**
     * @var int timestamp
     */
    public $timestart;

    /**
     * @var ADOConnection Cached here for singleton access.
     */
    protected $extdb;

    /**
     * Destructor to keep the DB from being overrun by open connections.
     */
    public function __destruct() {
        if ($this->extdb instanceof ADOConnection) {
            $this->extdb->Close();
        }
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     *
     * @return bool
     */
    public function instance_deleteable($instance) {
        if (!enrol_is_enabled('databaseextended')) {
            return true;
        }
        if (!$this->get_config('dbtype')
            or !$this->get_config('dbhost')
            or !$this->get_config('remoteenroltable')
            or !$this->get_config('remotecoursefield')
            or !$this->get_config('remoteuserfield')) {

            return true;
        }

        // TODO: connect to external system and make sure no users are to be enrolled in this course.
        return false;
    }

    /**
     * Initialises a new connection with the external DB, caches it and makes sure we only ever
     * have one.
     *
     * @throws coding_exception
     * @return ADOConnection
     */
    protected function db_init() {
        global $CFG;

        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        if (isset($this->extdb) && !empty($this->extdb)) {
            return $this->extdb;
        }

        $dbtype = $this->get_config('dbtype');
        $dbhost = $this->get_config('dbhost');
        $dbuser = $this->get_config('dbuser');
        $dbpass = $this->get_config('dbpass');
        $dbname = $this->get_config('dbname');
        if (empty($dbtype) ||
            empty($dbhost) ||
            empty($dbuser) ||
            empty($dbname)
        ) {

            throw new coding_exception(get_string('dbconfigmissing', 'enrol_databaseextended'));
        }

        // Connect to the external database (forcing new connection).
        /* @var ADOConnection $extdb */
        $extdb = adonewconnection($this->get_config('dbtype'));
        if ($this->get_config('debugdb')) {
            $extdb->debug = true;
            ob_start(); // Start output buffer to allow later use of the page headers.
        }

        $extdb->Connect($this->get_config('dbhost'), $this->get_config('dbuser'),
                        $this->get_config('dbpass'), $this->get_config('dbname'), true);
        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($this->get_config('dbsetupsql')) {
            $extdb->Execute($this->get_config('dbsetupsql'));
        }

        if (!$extdb) {
            $extdb->Close();
            throw new coding_exception(get_string('dbcommunicationerror',
                                                  'enrol_databaseextended'));
        }

        $this->extdb = $extdb;

        return $extdb;
    }

    /**
     * Escapes stuff for the external DB depending on it's type
     *
     * @param $text
     * @return mixed
     */
    protected function db_addslashes($text) {
        // Using custom made function for now.
        if ($this->get_config('dbsybasequoting')) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    /**
     * Lets us know how much RAM we are using.
     */
    protected function print_current_memory_usage() {

        echo "Current memory in use: ";

        $mem_usage = memory_get_usage(true);

        if ($mem_usage < 1024) {
            echo $mem_usage." bytes";
        } else if ($mem_usage < 1048576) {
            echo round($mem_usage/1024, 2)." kilobytes";
        } else {
            echo round($mem_usage/1048576, 2)." megabytes";
        }
        echo "\n";
    }

    /**
     * Utility method to return the class of the table.
     *
     * @param $tablename
     * @return string|enrol_databaseextended_table_base Type hinting keeps the IDE happy
     */
    public function get_table_classname($tablename) {
        return 'enrol_databaseextended_'.$tablename.'_table';
    }

    /**
     * Takes a tablename and returns the table sync object if there is one
     *
     * @param $tablename
     * @param ADOConnection|bool $extdb Can be left without being passed in case we need to get a table that's already
     * initialised. Allows override so we can feed a mock object in for unit testing.
     * @throws coding_exception
     * @return enrol_databaseextended_table_base
     */
    public function get_table_object($tablename, $extdb = false) {
        $classname = $this->get_table_classname($tablename);
        if (!$extdb) {
            $extdb = $this->db_init();
        }
        if (class_exists($classname)) {
            return $classname::get_table_singleton($this, $extdb);
        } else {
            throw new coding_exception('Request for non-existent table class: '.$classname);
        }
    }

    /**
     * Instantiates all the singletons. No need to do anything with them. We need this so we can
     * sometimes use a mock DB object for unit testing.
     */
    public function initialise_all_tables() {

        global $CFG;

        $extdb = $this->db_init();

        $directory = $CFG->dirroot.'/enrol/databaseextended/tables';
        foreach (glob($directory."/*.php") as $filename) {
            require_once($filename);
            $bits = explode('.', basename($filename));
            $tablename=array_shift($bits);
            /* @var enrol_databaseextended_table_base $classname */
            $this->get_table_object($tablename, $extdb);
        }
    }

    /**
     * Triggers the sync process.
     *
     * @param array $tablestosync
     *
     * @param bool  $noisy Do we want output to tell us about progress?
     *
     * @throws coding_exception
     * @return int level of error (0= no error)
     */
    public function speedy_sync($tablestosync = array('course',
                                                      'user_enrolments'), $noisy = true) {
        if ($noisy) {
            $this->output_time();
        }

        $config_store=array();

        // Try-catch in case we are unit testing and there are no variables set, in which case
        // the extdb will already have been made and fed into the tables.
        try {
            $extdb = $this->db_init();
        } catch (Exception $e) {
            $extdb = false;
        }

        $this->initialise_all_tables();

        print $this->get_line_end();

        foreach ($tablestosync as $tablename) {
           $thisTableStart=time();

            if ($noisy) {
                echo get_string('startingsync', 'enrol_databaseextended', $tablename);
                $this->output_time();
                $this->output_memory_status().$this->get_line_end();
            }

            /* @var $classname enrol_databaseextended_table_base */
            $table = $this->get_table_object($tablename);

            if (!$table->sync_enabled()) {
                continue;
            }

            $status=$this->process_table($table,$tablename,$extdb);

            $this->output_memory_status();

            $final_state=max($status,$table->final_state());

            print get_string('statusline'.$final_state,'enrol_databaseextended').$this->get_line_end();
            print get_string('timetaken','enrol_databaseextended',$this->format_human_readable_time(time()-$thisTableStart)
                             .$this->get_line_end());

            echo get_string('endingsync', 'enrol_databaseextended', $tablename)
                 .$this->get_line_end();
            // Fresh start for each one. Populating the caches is a small overhead in CPU terms.
            enrol_databaseextended_table_base::empty_all_caches();

            unset($externalarray);
            $this->output_memory_status();
            echo $this->get_line_end();

            $item=new stdClass;
            $item->start=$thisTableStart;
            $item->result=$final_state;
            $item->time=(time()-$thisTableStart);

            $config_store[$tablename]=$item;

        }

        if ($extdb) {
            $extdb->Close();
        }

        $this->output_memory_status();

        set_config('sync_results',serialize($config_store),'enrol_databaseextended');

        return $final_state;

    }

    /**
     * Factor of previous function to allow bail-out without
     * losing status.
     *
     * @param enrol_databaseextended_table_base $table the table to process
     * @param string $tablename the name of the table as a string
     * @param $extdb Database connection to use
     * @return int|mixed
     * @throws coding_exception
     */
    private function process_table($table,$tablename,$extdb)
    {
       $status=static::ENROL_DATABASEEXTENDED_STATUS_OK;

       if (!$table->check_config_vars_present()) {
          $message = get_string('tablemissingconfig', 'enrol_databaseextended', $tablename);
          debugging($message, DEBUG_DEVELOPER);
          return max($status,static::ENROL_DATABASEEXTENDED_STATUS_MAJOR);
       }

       // We may need a lot of memory here.
       @set_time_limit(0);
       $configmemorylimit = $this->get_config('memorylimit');
       if (!$configmemorylimit) {
          $configmemorylimit = MEMORY_HUGE;
       }
       raise_memory_limit($configmemorylimit);

       $externalrecordset = $table->get_external_recordset();

       // Fill cache with actual rows, not ids.
       if(!$table->populate_cache(false)) {
          // Failed to populate cache. In this case the safest option is to continue, rather than wiping out what's already there
          echo get_string('populatecachefail', 'enrol_databaseextended', $tablename)
             .$this->get_line_end();

//Technically the max is redundant but we might want to add minor errors
//higher up the code some day, who knows?
          return max($status,static::ENROL_DATABASEEXTENDED_STATUS_MAJOR);
       }

       if (!$table->sanity_check($externalrecordset)) {
          echo get_string('tablefailedsanity', 'enrol_databaseextended', $tablename)
             .$this->get_line_end();
          return max($status,static::ENROL_DATABASEEXTENDED_STATUS_MAJOR);
       }

       // This makes sure that any linked tables are able to supply us with the id that
       // corresponds to a specified external unique key for that table.
       $linkedtablenames = $table->get_linked_internal_table_names();
       $linkedtables = array();
       foreach ($linkedtablenames as $linkedtablename) {
          if ($linkedtablename == $tablename) {
             continue; // We already filled the cache above. Don't want to refill it.
          }

          /* @var enrol_databaseextended_table_base $classname */
          $classname = $this->get_table_classname($linkedtablename);
          if (!class_exists($classname)) {
             throw new coding_exception('Missing class: '.$classname);
          }

          $linkedtables[$linkedtablename]  = $classname::get_table_singleton($this, $extdb);
          $linkedtables[$linkedtablename]->populate_cache();
       }

       $table->cross_reference($externalrecordset);

       $externalrecordset->Close();

       foreach ($linkedtables as $linkedtable) {
          /* @var enrol_databaseextended_table_base $linkedtable */
          $linkedtable->output_missing_values();
       }

       return $status;
    }

    /**
     * Hook called by enrol_course_updated(). We use it to make a  new enrolment instance.
     */
    public function course_updated($inserted, $course, $data) {

    }

    /**
     * Role assignment code needs the instance id. Cached here to save queries.
     *
     * @return mixed
     */
    public function get_instance_id() {
        global $DB;

        static $id = 0;
        if ($id) {
            return $id;
        }

        $conditions = array('enrol' => $this->get_name());
        $id = $DB->get_field('enrol', 'id', $conditions);

        return $id;
    }

    /**
     * Records when the sync process starts so that we can keep track of progress.
     */
    public function start_timer() {
        $this->timestart = time();
        $humanreadabletime = date('H:i:s', time());
        echo get_string('syncstarted', 'enrol_databaseextended', $humanreadabletime)
             .$this->get_line_end();

    }

    /**
     * Makes sure that we get lines breaking properly on both web and command line echo statements.
     *
     * @return string either HTML line break tag or line end character
     */
    public function get_line_end() {
        $sapi = php_sapi_name();

        if ($sapi == 'cli') {
            return "\n";
        } else {
            return '<br />';
        }
    }

    /**
     * Tells us how long the sync has been running.
     */
    private function output_time() {

        $humanreadabletime = $this->get_human_readable_time();

        echo get_string('syncrunningfor', 'enrol_databaseextended', $humanreadabletime).
             $this->get_line_end();
    }

    /**
     * Counts the seconds since the script started and tells us in human readable format.
     *
     * @return string
     */
    private function get_human_readable_time() {

        $seconds = time() - $this->timestart;

        return $this->format_human_readable_time($seconds);
    }
    
    protected function format_human_readable_time($seconds)
    {
        $units = array(
            60 * 60  => array(get_string('hour', 'enrol_databaseextended'),
                              get_string('hours', 'enrol_databaseextended')),
            60       => array(get_string('minute', 'enrol_databaseextended'),
                              get_string('minutes', 'enrol_databaseextended')),
            1        => array(get_string('second', 'enrol_databaseextended'),
                              get_string('seconds', 'enrol_databaseextended')),
        );

        $result = array();
        foreach ($units as $divisor => $unitname) {
            $units = intval($seconds / $divisor);
            if ($units) {
                $seconds %= $divisor;
                $name     = $units == 1 ? $unitname[0] : $unitname[1];
                $result[] = "$units $name";
            }
        }
        if ($result) {
            $humanreadabletime = implode(', ', $result);
        } else {
            $humanreadabletime = "0 ".get_string('seconds', 'enrol_databaseextended');
        }

        return $humanreadabletime;
    }

    /**
     * Outputs the metrics telling us how long the sync took overall.
     */
    public function end_timer() {
        $a = new stdClass();
        $a->humanreadabletime = $this->get_human_readable_time();
        $a->timenow  = date('H:i:s', time());
        echo get_string('syncfinished', 'enrol_databaseextended', $a).$this->get_line_end();
    }

    /**
     * This will make something like 0.....10%.....20%.. as the sync is done
     *
     * @param $donesofar
     * @param $total
     * @throws coding_exception
     * @return void
     */
    public function output_percent_done($donesofar, $total) {

        static $lastpercent = 0;

        if ($donesofar == 0) {
            $lastpercent = 0;
        }

        if ($total == 0) {
            throw new coding_exception('Percentage output for toal of zero');
        }

        $percent = intval( $donesofar / $total * 100);

        if ($donesofar == 0) {
            echo get_string('zeroprogress', 'enrol_databaseextended');
        } else if ($percent - $lastpercent >= 2) {
            // New thing every 2%. Get nearest, lowest 10% to compare.
            $currentnearest10 = floor($percent / 10) * 10;
            $lastnearest10 = floor($lastpercent / 10) * 10;
            if ($currentnearest10 != $lastnearest10) {
                echo $currentnearest10."%";
            } else {
                echo '.';
            }
            $lastpercent = $percent;
        }

        if ($donesofar == $total) {
            echo $this->get_line_end();
        }
    }

    /**
     * Lets us know how much memory is in use and how much has been used at peak up to this point
     *
     * @internal param int $memory If supplied, will just output this number of bytes in
     * human readable form
     */
    public function output_memory_status() {

        $currentmemory = $this->human_readable_bytes(memory_get_usage(true));
        $maxmemory = $this->human_readable_bytes(memory_get_peak_usage(true));

        $langbits = new stdClass();
        $langbits->current = $currentmemory;
        $langbits->max = $maxmemory;

        echo get_string('memoryuse', 'enrol_databaseextended', $langbits).$this->get_line_end();
    }

    /**
     * Convert a number of bytes into human readable form
     *
     * @param int $memory
     * @return string
     */
    public function human_readable_bytes($memory = 0) {
        $mod = 1024;

        $units = explode(' ', 'B KB MB GB TB PB');
        for ($i = 0; $memory > $mod; $i++) {
            $memory /= $mod;
        }
        return round($memory, 2).' '.$units[$i];
    }

    /**
     * Hook called by the login page that is meant to make sure the user's latest enrolments are added.
     */
    public function sync_user_enrolments($user) {

        if (!$this->get_config('synconlogin', 0)) {
            return false;
        }

        $this->initialise_all_tables();

        // Start with user enrolments and just do that. Can add more tables later if needed.
        $user_enrolments_table = $this->get_table_object('user_enrolments');
        $role_assignments_table = $this->get_table_object('role_assignments');

        $user_enrolments_table->single_user_sync($user);
        $role_assignments_table->single_user_sync($user);

    }

    /**
     * Lets us know if the table the user has entered into the settings actually exists at all.
     *
     * @param $tablename
     * @return bool
     */
    public function external_table_exists($tablename) {

        $extdb = $this->db_init();

        return (array_search($tablename,$extdb->MetaTables()) !== FALSE);

    }

    /**
     * Runs some basic tests on the external data set to see if it's consistent.
     */
    public function check_data_integrity() {

        $data_is_ok = true;

        $data_is_ok = $data_is_ok && $this->integrity_check_category_parents();
        $data_is_ok = $data_is_ok && $this->integrity_circular_category_parents();

        return $data_is_ok;
    }

    /**
     * @return bool
     */
    protected function integrity_check_category_parents() {
        global $DB;
        
        $extdb = $this->db_init();

        $categories_table = $this->get_table_object('course_categories');

        $external_categories_table = $categories_table->get_external_table_name();
        $external_categories_id_field = $categories_table->get_external_unique_key_field();
        $external_categories_parent_field = $DB->get_field('databaseextended_relations',
                                                           'secondarytablefield',
                                                           array('maintable' => 'course_categories',
                                                                 'maintablefield' => 'parent',
                                                                 'internal' => ENROL_DATABASEEXTENDED_EXTERNAL,));
        $root_category_id = $this->get_config('rootcategory');

        // Check for categories that have a parent id that does not exist.
        // This will lead to infinite recursion on the categories sync.
        $sql = "SELECT *
                  FROM {$external_categories_table} c
                 WHERE NOT EXISTS(SELECT 1
                                   FROM {$external_categories_table} cc
                                  WHERE cc.{$external_categories_id_field} = c.{$external_categories_parent_field})
                   AND c.{$external_categories_parent_field} != {$root_category_id}
                ";

        debugging($sql, DEBUG_DEVELOPER);

        /* @var ADORecordSet $orphaned_categories */
        $orphaned_categories = $extdb->Execute($sql);

        if ($orphaned_categories->NumRows() == 0) {
            echo 'OK: All categories have extant parents.' . $this->get_line_end();;
            return true;
        } else {
            echo 'Bad: Some categories have no parent category:' . $this->get_line_end();
            while (($externalrow = $orphaned_categories->FetchObj()) !== false) {
                echo $externalrow->$external_categories_id_field . $this->get_line_end();;
            }
            return false;
        }
    }

    /**
     * Looks for categories that reference other categories as parents, which then end up referencing back
     * to the original category.
     */
    protected function integrity_circular_category_parents() {

        $extdb = $this->db_init();

        $root_category_id = $this->get_config('rootcategory');
        $categories_table = $this->get_table_object('course_categories');
        $external_categories_id_field = $categories_table->get_external_unique_key_field();
        $external_categories_table = $categories_table->get_external_table_name();

        $sql = "SELECT *
                  FROM {$external_categories_table} c
                ";
        /* @var ADORecordSet $categories */
        $categories = $extdb->Execute($sql);

        $bad_categories = array();

        // Check up the tree of parents for each category.
        $number_of_categories_so_far = 0;
        $total = $categories->RowCount();
        while (!$categories->EOF) {
            $externalrow = $categories->FetchObj();
            $categories->MoveNext();

            $number_of_categories_so_far++;

            $category_name = $externalrow->$external_categories_id_field;

//            echo "Starting check for {$externalrow->$external_categories_id_field} ({$number_of_categories_so_far} of {$total})".$this->get_line_end();
            debugging("Starting check for {$category_name} ({$number_of_categories_so_far} of {$total})".$this->get_line_end(), DEBUG_DEVELOPER);
            $count = 0;
            $loop_category_name = $category_name;
            while ($loop_category_name !== $root_category_id && $count < 20) {
                $count++;
                $loop_category_name = $this->get_parent_category_name($loop_category_name);
            }
            if ($count == 20) {
                $bad_categories[] = $category_name;
                echo "Bad category: '{$category_name}'" . $this->get_line_end();
                echo $this->get_line_end();

                $count = 0;
                $loop_category_name = $category_name;
                while ($loop_category_name !== $root_category_id && $count < 20) {
                    $count++;
                    $old_name = $loop_category_name;
                    $loop_category_name = $this->get_parent_category_name($loop_category_name);
                    if ($loop_category_name === false) {
                        echo "Missing from database";
                        break;
                    }
                    echo "$old_name (parent: {$loop_category_name}) >> ";
                }
            } else {
                debugging("-- OK: Hit root after {$count} levels", DEBUG_DEVELOPER);
            }
        }

        if (empty($bad_categories)) {
            echo 'OK: No circular loops in category parents';
            return true;
        } else {
            echo 'Bad: looped 20 times when trying to follow up the tree of categories to root. Probably a circular reference somewhere, so check their parent ids.'.$this->get_line_end();
            foreach ($bad_categories as $category) {
                echo $category;
            }
            return false;
        }
    }

    /**
     * Retrieves the parent category field from the external DB row for this category.
     *
     * @param $category_name
     * @return bool
     */
    protected function get_parent_category_name($category_name) {
        global $DB;

        $extdb = $this->db_init();

        $categories_table = $this->get_table_object('course_categories');
        $external_categories_id_field = $categories_table->get_external_unique_key_field();

        $external_categories_table = $categories_table->get_external_table_name();
        $external_categories_parent_field = $DB->get_field('databaseextended_relations',
                                                           'secondarytablefield',
                                                           array('maintable' => 'course_categories',
                                                                 'maintablefield' => 'parent',
                                                                 'internal' => ENROL_DATABASEEXTENDED_EXTERNAL,));

        $sql = "SELECT {$external_categories_parent_field}
                  FROM {$external_categories_table}
                 WHERE {$external_categories_id_field} = ?
        ";
        $params = array($category_name);

        /* @var ADORecordSet $result */
        $result = $extdb->Execute($sql, $params);

        if ($result->RowCount() == 0) {
            return false;
        }

        return $result->FetchObj()->$external_categories_parent_field;

    }

}


global $CFG;
// Include all the tables. Must be at the bottom so that we have all the base classes first. This file is automatically included
// by the page that shows the enrolment plugins, so this awkwardness is needed.
$directory = $CFG->dirroot.'/enrol/databaseextended/tables';
foreach (glob($directory."/*.php") as $filename) {
    require_once($filename);
}

/**
 * Wraps an array of objects so we can use it in place of a recordset. This is for one-to-one
 * tables which use internal data arrays to sync to. It mimic and ADORecordset object and is
 * used by enrol_databaseextended_table_base::cross_reference()
 *
 */
class enrol_databaseextended_array_adapter {

    /**
     * @var bool True if there is any content. Must be in caps to work as an adapter!
     */
    public $EOF = false;

    /**
     * Constructor assigns array as class variable.
     *
     * @param array $array
     */
    public function __construct(array $array) {

        $this->array = $array;

        if (count($array) === 0) {
            $this->EOF = true;
        }
    }

    /**
     * Gets the next row from the array.
     *
     * @return object
     */
    public function fetchrow() {
        $item = current($this->array);
        next($this->array);
        return $item;

    }

    /**
     * Returns the array count.
     *
     * @return int|void
     */
    public function numrows() {
        return count($this->array);
    }

    /**
     * Returns the current key so we can write to the array.
     *
     * @return mixed
     */
    public function key() {
        return key($this->array);
    }

    /**
     * Sets the pointer back to the start.
     */
    public function reset() {
        reset($this->array);
    }

    /**
     * Destroys the object.
     */
    public function close() {
        // Free memory.
        unset ($this->array);
    }

}
