<?php

/****************************************************************

File:       block/module_info/lib.php

Purpose:    Class used to create a connection to an external
database and to perform subsequent queries needed
to extract data

 ****************************************************************/

global $CFG;

/**
 * include the standard Adodb library
 * if adodb is removed from moodle in the future, we might need
 * to include it specially within ILP
 */
$adodb_dir = $CFG->dirroot . '/lib/adodb';
require_once( "$adodb_dir/adodb.inc.php" );
require_once( "$adodb_dir/adodb-exceptions.inc.php" );
require_once( "$adodb_dir/adodb-errorhandler.inc.php" );

class module_info_data_connection{

    protected $db;
    public $errorlist;    // Collect a list of errors.
    public $prelimcalls;  // Calls to be executed before the sql is called.

    /**
     * Constructor function
     *
     * @param array $cparams arguments used to connect to a db. array keys:
     * 			type: the type of connection mssql, mysql etc
     * 			host: host connection string
     * 			user: the username used to connect to db
     * 			pass: the password used to connect to the db
     * 			dbname: the dbname
     *
     * @return bool true if not errors encountered false if otherwise
     */
    public function __construct( $cparams=array()) {
        $this->db = false;
        $this->errorlist = array();
        $this->prelimcalls = array();

        $dbconnectiontype = $cparams['type'];

        // If the dbconnection is empty return false.
        if (empty($dbconnectiontype)) {
            return false;
        }

        $host   =   $cparams['host'];
        $user   =   $cparams['user'];
        $pass   =   $cparams['pass'];
        $dbname =   $cparams['dbname'];
        $debug  =   $cparams['debug'];

        // Build the connection.
        $connectioninfo = $this->get_mis_connection($dbconnectiontype, $host, $user, $pass, $dbname, $debug);

        // Return false if any errors have been found ( we can display errors if wanted ).
        $this->errorlist = $connectioninfo[ 'errorlist' ];
        if ( !empty($this->errorlist) ) {
            return false;
        }

        // Give the connection to the db var.
        $this->db = $connectioninfo[ 'db' ];
        return true;
    }

    /*
     * Returns true if connected, else returns false.
     */
    public function is_connected() {
        return $this->db != null;
    }

    public function real_escape_string($text) {
        if(!$this->is_connected()) {
            return $text;
        }
        return $this->db->qstr($text);
    }

    /**
     * Creates a connection to a database using the values given in the arguments
     *
     * @param string $type the type of connection to be used
     * @param string $host the hosts address
     * @param string $user the username that will be used to connect to db
     * @param string $pass the password used in conjunction with the username
     * @param string $dbname the name of the db that will be used
     */
    public function get_mis_connection( $type, $host, $user, $pass, $dbname, $debug = false ) {
        $errorlist = array();
        $db = false;

        // Trim any space chars (which seem to pass empty tests) and if empty return false.
        $trimtype = trim($type);
        if (empty($trimtype)) {
            return false;
        }

        try {
            $db = ADONewConnection( $type );
        } catch ( exception $e ) {
            $errorlist[] = $e->getMessage();
        }
        if ( $db ) {
            try {
                $db->SetFetchMode(ADODB_FETCH_ASSOC);
                $db->Connect( $host, $user, $pass, $dbname );
                $db->debug = $debug;
            } catch ( exception $e ) {
                $errorlist[] = $e->getMessage();
            }
        }
        return array (
            'errorlist' => $errorlist,
            'db' => $db
        );
    }

    /**
     * Take a result array and return a list of the values in a single field
     *
     * @param array of arrays $a
     * @param string $fieldname
     *
     * @return array of scalars
     */
    protected function get_column_valuelist( $a, $fieldname ) {
        $rtn = array();
        foreach ($a as $row) {
            $rtn[] = $row[ $fieldname ];
        }
        return $rtn;
    }

    /**
     * Takes an array in the format array($a=>array($b=> $c)) and returns
     * a string in the format $a $b $c
     *
     * @param array $paramarray the params that need to be converted to a string
     *
     * @return string
     */
    protected function arraytostring($paramarray) {
        $str = '';
        $and = '';
        if (!empty($paramarray) && is_array($paramarray)) {
            foreach ($paramarray as $k => $v) {
                $str = "{$str} {$and} ";
                // $str .= (is_array($v)) ? $k." ".$this->arraytostring($v) : " $k $v";
                /*
                 * Remove all ~ from fieldname - this is so that when a field is used twice in a query,
                 * you can use the ~ to make a unique array key, but still generate sql with the simple fieldname
                 * this will cause problems if the underlying database table has a fieldname with a ~ in it
                 */
                $str .= (is_array($v)) ? str_replace( '~' , '', $k ) ." ".$this->arraytostring($v) : " $k $v";
                $and = ' AND ';
            }
        }

        return $str;
    }

    /**
     * Builds an sql query using the given parameter and returns the results of the query
     *
     * @param string $table the name of the table or view that will be queried
     * @param array  $whereparams array holding params that should be used in the where statement
     * 				 format should be $k = field => array( $k= operand $v = field value)
     * 				 e.g array('id'=>array('='=>'1')) produces id = 1
     * @param mixed  $fields array or string of the fields that should be returned
     * @param array  $addionalargs additional arguments that may be used the:
     * 				 'sort' the field that should be sorted by and DESC or ASC
     * 				 'group' the field that results should be grouped by
     * 				 'lowerlimit' lower limit of results
     * 				 'upperlimit' should be used in conjunction with lowerlimt to limit results
     *
     * @return mixed
     */
    public function return_table_values ($table, $whereparams=null, $fields='*', $addionalargs=null) {
        // Check if the fields param is an array if it is implode.
        $fields = (is_array($fields)) ? implode(', ', $fields) : $fields;

        // Create the select statement.
        $select = "SELECT {$fields} ";

        // Create the from.
        $from = "FROM {$table} ";

        // Get the where.
        $wheresql = $this->arraytostring($whereparams);

        $where = (!empty($wheresql)) ? "WHERE {$wheresql} " : "";

        $sort = '';
        if (isset($addionalargs['sort'])) {
            $sort = (!empty($addionalargs['sort'])) ? "ORDER BY {$addionalargs['sort']} " : "";
        }

        $group = '';
        if (isset($addionalargs['group'])) {
            $group = (!empty($addionalargs['group'])) ? "GROUP BY {$addionalargs['group']} " : "";
        }

        $limit = '';
        if (isset($addionalargs['lowerlimt'])) {
            $limit = (!empty($addionalargs['lowerlimit'])) ? "LIMIT {$addionalargs['lowerlimit']} " : "";
        }
        if (isset($addionalargs['upperlimt'])) {
            if (empty($limit)) {
                $limit = (!empty($addionalargs['upperlimt'])) ? "LIMIT {$addionalargs['upperlimt']} " : "";
            } else {
                $limit .= (!empty($addionalargs['upperlimt'])) ? ", {$addionalargs['upperlimt']} " : "";
            }
        }

        $sql = $select.$from.$where.$sort.$group.$limit;
        $result = (!empty($this->db)) ? $this->execute($sql) : false;
        return (!empty($result->fields)) ? $result->getRows() : false;
    }

    /**
     * Recursive function to convert an array into a value
     *
     * @param $val
     *
     * @return mixed
     */
    protected function arraytovar($val) {
        if (is_array($val)) {
            if (!is_array(current($val))) {
                return current($val);
            } else {
                return $this->arraytovar(current($val));
            }
        }

        return $val;
    }

    /**
     * Builds a stored procedure query using the arguments given and returns the result
     *
     * @param string $procedurename the name of the stored proceudre being called
     * @param mixed array or string $procedureargs variables passed to stored procedure
     *
     * @return mixed
     */
    public function return_stored_values($procedurename, $procedureargs='') {

        if (is_array($procedureargs)) {
            $temp = array();
            foreach ($procedureargs as $p) {
                $val = $this->arraytovar($p);

                if (!empty($val)) {
                    $temp[] = $val;
                }
            }

            $args = implode(', ', $temp);
        } else {
            $args = $procedureargs;
        }
        $sql = "EXECUTE {$procedurename} {$args}";

        $result = (!empty($this->db)) ? $this->execute($sql) : false;
        return (!empty($result->fields)) ? $result->getRows() : false;
    }

    /**
     * Step through an array of $key=>$value and assign them
     * to the class $params array
     *
     * @param array $arrayvar the array that will hold the params
     * @param array $params the params that will be passed to $arrayvar
     */
    protected function set_params( &$arrayvar, $params ) {
        foreach ($params as $key => $value) {
            $arrayvar[ $key ] = $value;
        }
    }

    /**
     * This function makes any calls to the database that need to be made before the sql statement is run
     * The function uses the $prelimcalls var
     */
    private function make_prelimcall() {
        if (!empty($this->prelimcalls)) {
            foreach ($this->prelimcalls as $pc) {
                try {
                    $res = $this->db->Execute( $pc );
                } catch (exception $e) {
                    // We wont do anything if these calls fail.
                }
            }
        }
    }

    /**
     * Marks the start of a transaction. Note that this is not supported in all databases (e.g. in mysqli but not mysql)
     *
     * @return bool
     */
    public function begin_transaction() {
        try {
            $res = $this->db->BeginTrans();
        } catch (exception $e) {
            return false;
        }
        return $res;
    }

    /**
     * Marks the end of a transaction. Any changes up to this point are committed.
     *
     * @return bool
     */
    public function commit_transaction() {
        try {
            $res = $this->db->CommitTrans();
        } catch (exception $e) {
            return false;
        }
        return $res;
    }

    /**
     * Cancels all changes made since the last begin_transaction() method call, and ends the transaction.
     *
     * @return bool
     */
    public function rollback_transaction() {
        try {
            $res = $this->db->RollbackTrans();
        } catch (exception $e) {
            return false;
        }
        return $res;
    }

    /**
     * Executes the given sql query
     *
     * @param string $sql
     *
     * @return array of arrays
     */
    public function execute( $sql ) {
        $this->make_prelimcall();
        try {
            $res = $this->db->Execute( $sql );
        } catch (exception $e) {
            return false;
        }
        return $res;
    }

    /**
     * Intended to return just the front item from an array of arrays (eg a recordset)
     * if just the array is sent, just the first row will be returned
     * if 2nd argument sent, then just the value of that field in the first row will be returned
     *
     * @param array $a
     * @param string $fieldname
     *
     * @return mixed (array or single value)
     */
    public static function get_top_item( $a , $fieldname=false ) {
        $toprow = array_shift( $a );
        if ( $fieldname ) {
            return $toprow[ $fieldname ];
        }
        return $toprow;
    }
}

/**
 * Print (or return) the start of a collapsible region, that has a caption that can
 * be clicked to expand or collapse the region. If JavaScript is off, then the region
 * will always be expanded.
 *
 * @param string $classes class names added to the div that is output.
 * @param string $id id added to the div that is output. Must not be blank.
 * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
 * @param string $userpref the name of the user preference that stores the user's preferred default state.
 *      (May be blank if you do not wish the state to be persisted.
 * @param boolean $default Initial collapsed state to use if the user_preference it not set.
 * @param boolean $return if true, return the HTML as a string, rather than printing it.
 *
 * @return string|void if $return is false, returns nothing, otherwise returns a string of HTML.
 */
function mod_info_collapsible_region_start($classes, $id, $caption, $userpref = '', $default = false, $return = false) {
    global $CFG, $PAGE, $OUTPUT;

    // Work out the initial state.
    if (!empty($userpref) and is_string($userpref)) {
        user_preference_allow_ajax_update($userpref, PARAM_BOOL);
        $collapsed = get_user_preferences($userpref, $default);
    } else {
        $collapsed = $default;
        $userpref = false;
    }

    if ($collapsed) {
        $classes .= ' collapsed';
    }

    $output = '';
    $output .= '<div id="' . $id . '" class="collapsibleregion ' . $classes . '">';
    $output .= '<div id="' . $id . '_sizer">';
    $output .= '<div id="' . $id . '_caption" class="collapsibleregioncaption">';
    $output .= '<a href="#" id="' . $id . '_caption_anchor">' . $caption . '</a>';
    $output .= '</div><div id="' . $id . '_inner" class="collapsibleregioninner">';
    $PAGE->requires->js_init_call('M.block_module_info.init_collapsible_region', array($id, $userpref, get_string('clicktohideshow')), true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Close a region started with print_collapsible_region_start.
 *
 * @param boolean $return if true, return the HTML as a string, rather than printing it.
 *
 * @return string|void if $return is false, returns nothing, otherwise returns a string of HTML.
 */
function mod_info_collapsible_region_end($return = false)
{
    $output = '</div></div></div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Returns the core information from sits
 * @param $course_id
 */

function get_core_information_from_sits($course_id){
    global $CFG;
    $cparams = array('type' => get_config( 'block_module_info', 'dbconnectiontype' ),
        'host' => get_config( 'block_module_info', 'dbhost' ),
        'user' => get_config( 'block_module_info', 'dbuser' ),
        'pass' => get_config( 'block_module_info', 'dbpass' ),
        'dbname' => get_config( 'block_module_info', 'dbname' ),
        'debug' => false);
    // VS transition to common configuration for MIS access
    if(isset($CFG->mis_host) && isset($CFG->mis_dbase)  && isset($CFG->mis_user)  && isset($CFG->mis_pass)  && isset($CFG->mis_dbtype) ){
        $cparams = array('type' => $CFG->mis_dbtype,
            'host' => $CFG->mis_host,
            'user' => $CFG->mis_user,
            'pass' => $CFG->mis_pass,
            'dbname' => $CFG->mis_dbase,
            'debug' => false);
    }

    $dbc = new module_info_data_connection($cparams);
    $table = get_config('block_module_info','dbtable');
    //create the key that will be used in sql query
    $keyfields = array(get_config('block_module_info','extcourseid') => array('=' => "'$course_id'"));

    $module_code_field = get_config('block_module_info','module_code');
    $module_level_field = get_config('block_module_info','module_level');
    $module_credit_field = get_config('block_module_info','module_credit');
    $module_semester_field = get_config('block_module_info','module_semester');
    $module_convenor_field = get_config('block_module_info','module_convenor');

    $fields = array();
    if(! empty ($module_code_field)) {
        $fields[] = $module_code_field;
    }
    if(! empty ($module_level_field)) {
        $fields[] = $module_level_field;
    }
    if(! empty ($module_credit_field)) {
        $fields[] = $module_credit_field;
    }
    if(! empty ($module_semester_field)) {
        $fields[] = $module_semester_field;
    }
    if(! empty ($module_convenor_field)) {
        $fields[] = $module_convenor_field;
    }

    $core_information = array(
        'module_code' => '',
        'module_level' => '',
        'module_credit' => '',
        'module_semester' => '',
        'module_convenor' => ''
    );

    $fields = $dbc->return_table_values($table, $keyfields, $fields);
    if(!empty($fields)){
        foreach($fields as $field) {
            if (! empty($field[$module_code_field])) {
                $core_information['module_code'] = $field[$module_code_field];
            }
            if (! empty($field[$module_level_field])) {
                $core_information['module_level'] = $field[$module_level_field];
            }
            if (! empty($field[$module_credit_field])) {
                $core_information['module_credit'] = $field[$module_credit_field];
            }
            if (! empty($field[$module_semester_field])) {
                $core_information['module_semester'] = $field[$module_semester_field];
            }
            if (! empty($field[$module_convenor_field])) {
                $core_information['module_convenor'] = $field[$module_convenor_field];
            }
        }
    }
    return $core_information;
}

function get_user_information($convenor_email){
    global $DB;
    return $DB->get_record('user', array('email' => $convenor_email));
}

function get_course_staffs($course_id){
    global $DB;
    //enrol instances
    list($instancessql, $params) = $DB->get_in_or_equal(array_keys(enrol_get_instances($course_id, false)), SQL_PARAMS_NAMED);
    //context instances
    $course_context = context_course::instance($course_id);
    $contextids = $course_context->get_parent_context_ids();
    $contextids[] = $course_context->id;
    list($contextsql, $contextparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
    $params += $contextparams;
    //sql query
    $sql = "SELECT DISTINCT u.id, CONCAT(u.firstname,' ',u.lastname) as name
            FROM {user} u JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid $instancessql)
            JOIN {enrol} e ON (e.id = ue.enrolid)
              LEFT JOIN {user_lastaccess} ul ON (ul.courseid = e.courseid AND ul.userid = u.id)
              LEFT JOIN {groups_members} gm ON u.id = gm.userid AND gm.groupid IN ( SELECT g.id FROM {groups} g WHERE g.courseid = e.courseid )
            WHERE u.deleted = 0
              AND u.confirmed = 1
              AND (SELECT COUNT(1) FROM {role_assignments} ra WHERE ra.userid = u.id AND ra.roleid IN (37, 38, 39) AND ra.contextid $contextsql > 0)
            ORDER BY u.firstname ASC";
    $enrolled_users = $DB->get_records_sql($sql, $params);
    return $enrolled_users;
}

/**
 * Serves the documents.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 *
 * @return bool false if file not found, does not return if found - justsend the file
 */
function block_module_info_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $fileareas = array('documents');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/block_module_info/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}