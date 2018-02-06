<?php

class local_qmul_sync_plugin {

    /**
     * Returns plugin config value
     * @param  string $name
     * @param  string $default value if config does not exist yet
     * @return string value or default
     */
    public static function get_config($name, $default = NULL) {
        $result = get_config('local_qmul_sync', $name);
        if ($result === false) {
            $result = $default;
        }
        return $result;
    }

    /**
     * Sets plugin config value
     * @param  string $name name of config
     * @param  string $value string config value, null means delete
     * @return string value
     */
    public static function set_config($name, $value) {
        set_config($name, $value, "local_qmul_sync");
    }

    /**
     * Check for new CSV files, and import if they have changed
     */
    public function cron($force = false) {
        global $DB;

        if ($this->get_config('mode') == 'file') {
            $user_source = $this->get_config('user_source');
            if ($user_source) {
                $users = $this->import_csv($user_source, 'local_qmul_sync_users', array('idnumber'), $force);
            }
            $enrolment_source = $this->get_config('enrolment_source');
            if ($enrolment_source) {
                $enrolments = $this->import_csv($enrolment_source, 'local_qmul_sync_enrolments', array('user_id', 'course_id'), $force);
            }
            $course_source = $this->get_config('course_source');
            if ($course_source) {
                $courses = $this->import_csv($course_source, 'local_qmul_sync_courses', array('course_id'), $force);
            }
            $mapping_source = $this->get_config('mapping_source');
            if ($mapping_source) {
                $mapping = $this->import_csv($mapping_source, 'local_qmul_sync_mappings', array('id'), $force);
            }
        }
    }

    /**
     * Load a CSV file into a table
     */
    private function import_csv($filename, $table, $keyfields, $force = false) {
        global $CFG, $DB;

        $filename = $CFG->dataroot.'/mis_uploads/'.$filename;
        $stat = stat($filename);

        debugging("Synchronising $filename with $table");

        if (!$force) {
            if ($stat['mtime'] == self::get_config($table.'_mtime')) {
                debugging("File date matches... skipping import");
                return;
            }
        }
        $timestamp = time();
        if ($fh = fopen($filename, 'r')) {
            $DB->execute("LOCK TABLES {{$table}} WRITE");
            $fields = fgetcsv($fh);
            while ($row = fgetcsv($fh)) {
                $data = new stdClass();
                for ($i = 0; $i < count($row); $i++) {
                    $fieldname = strtolower($fields[$i]);
                    $data->$fieldname = $row[$i];
                }
                $where = array();
                foreach ($keyfields as $keyfield) {
                    $where[$keyfield] = $data->$keyfield;
                }
                $current = $DB->get_record($table, $where);
                if ($current) {
                    $data->id = $current->id;
                    $data->last_modified = $current->last_modified;
                    if ($current == $data) {
                        $present = new stdClass();
                        $present->id = $data->id;
                        $present->last_present = $timestamp;
                        $DB->update_record($table, $present);
                    } else {
                        $data->last_modified = $timestamp;
                        $data->last_present = $timestamp;
                        $DB->update_record($table, $data);
                    }
                } else {
                    $data->last_modified = $timestamp;
                    $data->last_present = $timestamp;
                    $data->id = $DB->insert_record($table, $data);
                }
            }
            $DB->execute("UNLOCK TABLES {{$table}}");
            fclose($fh);
            self::set_config($table.'_mtime', $stat['mtime']);
        } else {
            print("Couldn't open $filename");
        }
    }

    public static function mode() {
        return self::get_config('mode');
    }

    public static function proxydb() {
        global $CFG;
        static $db = null;

        if ($db == null) {
            // VS common MIS configuration
            if(isset($CFG->mis_host) && isset($CFG->mis_dbase) && isset($CFG->mis_user) && isset($CFG->mis_pass) && isset($CFG->mis_dbtype) ){
                $db = moodle_database::get_driver_instance($CFG->mis_dbtype, 'native');
                $db->connect($CFG->mis_host, $CFG->mis_user, $CFG->mis_pass, $CFG->mis_dbase, '', array());
            } else {
                $db = moodle_database::get_driver_instance(self::get_config('remote_type'), 'native');
                $db->connect(self::get_config('remote_hostname'), self::get_config('remote_username'), self::get_config('remote_password'), self::get_config('remote_database'), '', array());
            }
        }
        return $db;
    }

    /**
     * Return array of SITS module codes enrolled onto the given course.
     *
     * @param $course stdClass course intance
     * @return array
     */
    public static function get_mapped_course_idnumbers($course) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $mappings = self::proxydb()->get_fieldset_sql('SELECT DISTINCT module_code FROM {moodle_enrolment} WHERE course_id=?', array($course->idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        $result = array();
        foreach ($mappings as $key) {
            $result[$key] = true;
        }
        return $result;
    }

    /**
     * Return array of SITS module codes for a given course idnumber
     *
     * @param string $idnumber
     * @return array
     */
    public static function sits_modules($idnumber) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $sitsmodules = self::proxydb()->get_records_sql_menu('SELECT DISTINCT module_code,module_code as label FROM {moodle_enrolment} WHERE course_id=?', array($idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $sitsmodules;
    }

    /**
     * Returns the SITS user record for a given moodle user
     *
     * @param $user stdClass Moodle user
     * @return stdClass SITS user record
     */
    public static function sits_user($user) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $sitsuser = self::proxydb()->get_record('moodle_users', array('IDNUMBER' => $user->idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $sitsuser;
    }

    /**
     * Returns the SITS user records for a set of SITS ids
     *
     * @param $studentids stdClass SITS ids
     * @return array SITS user records
     */
    public static function sits_users($studentids) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $ioe = self::proxydb()->get_in_or_equal($studentids);
            $sitsuser = self::proxydb()->get_records_sql("SELECT IDNUMBER,USERNAME,FIRSTNAME,LASTNAME,EMAIL,IDNUMBER,USERSOURCE,DEPARTMENT_CODE,DEPARTMENT_NAME,ENROL_STATUS,PROGRAMME_CODE,PROGRAMME_NAME,DEVELOPMENT_YEAR,ROUTE_CODE,ROUTE_NAME,LOCATION_CODE,INITIALS FROM moodle_users WHERE IDNUMBER {$ioe[0]}", $ioe[1]);
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $sitsuser;
    }

    /**
     * Return a recordset of all enrolments in a given course
     */
    public static function sits_course_enrolments($course) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $enrolments = self::proxydb()->get_recordset('moodle_enrolment', array('COURSE_ID' => $course->idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $enrolments;
    }

    /**
     * Return a recordset of all enrolments in a given SITS module/moodle course
     *
     * @param $module SITS module
     * @param $courseid Moodle course idnumber
     */
    public static function sits_module_enrolments($module, $courseid) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $enrolments = self::proxydb()->get_recordset('moodle_enrolment', array('MODULE_CODE' => $module, 'COURSE_ID' => $courseid));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $enrolments;
    }

    /**
     * Return the SITS enrolment record for a given user on a given course
     *
     * @param $user stdClass Moodle user record
     * @param $course stdClass Moodle course record
     * @return stdClass SITS enrolment record
     *
     * @todo: Fix this for when multiple enrolments mappings exist for a given user/course
     */
    public static function sits_user_enrolment($user, $course) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $enrolment = self::proxydb()->get_record('moodle_enrolment', array('USER_ID' => $user->idnumber, 'COURSE_ID' => $course->idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $enrolment;
    }

    public static function get_user_enrolments($user) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $enrolments = self::proxydb()->get_records('moodle_enrolment', array('USER_ID' => $user->idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $enrolments;
    }

    public static function sits_data_for_course($course) {
        return self::sits_data_for_course_code($course->idnumber);
    }

    public static function sits_data_for_course_code($code) {
        global $DB;

        switch (self::mode()) {
        case 'file':
            $data = $DB->get_record('local_qmul_sync_courses', array('course_id' => $code));
            break;
        case 'proxy':
            $data = self::proxydb()->get_record('moodle_course', array('COURSE_ID' => $code));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $data;
    }

    /**
     * Return the SITS data held for a given SITS module code
     *
     * @param string $module The SITS module code
     * @return stdClass Record representing the module
     */
    public static function sits_data_for_module($module) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $data = self::proxydb()->get_record('moodle_course', array('MODULE_CODE' => $module));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $data;
    }

    /**
     * Return an associative array of mapping ID to Criteria
     *
     * @param $course stdClass for a moodle course
     * @return array of ID => Criteria
     *
     * ID is the QEM id used in URLs
     * Criteria is the human readable description
     */
    public static function qem_mappings_for_course($course) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $data = self::proxydb()->get_records_sql_menu('SELECT ID,Criteria FROM {qem_mappings} WHERE id IN (SELECT MAPPINGID FROM {course_mappings} WHERE QMPLUSCOURSE=?)',    array($course->idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $data;
    }

    /**
     * Return an array of SITS module => Criteria for a given course
     *
     * @param $course stdClass for a moodle course
     * @return array of ID => Criteria
     *
     * ID is the SITS module used in URLs
     * Criteria is the human readable description
     */
    public static function qem_defaults_for_course($course) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $criterias = self::proxydb()->get_fieldset_sql("SELECT Criteria FROM {qem_mappings} WHERE ID=0 AND Course=?", array($course->idnumber));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        $cooked = array();
        foreach ($criterias as $criteria) {
            $parts = explode(' ', $criteria, 2);
            $cooked[$parts[0]] = $parts[1];
        }
        return $cooked;
    }
    /**
     * Return an array of valid SITS assessments for a given SITS module
     *
     * @param string $module The SITS module of interest
     * @return array Associative array of SITS assessment (with leading #) to description
     */
    public static function sits_assessments($module) {
        global $DB;

        switch (self::mode()) {
        case 'proxy':
            $data = self::proxydb()->get_records_sql_menu('SELECT concat("#",MAB_CODE),MAB_NAME FROM {module_assessments} WHERE COURSE_ID=?', array($module));
            break;
        default:
            throw new moodle_exception('notavailable');
            break;
        }
        return $data;
    }

    /**
     * Return data for an AJAX call on a moodle course.
     *
     * @param string $idnumber - The SITS module occurrence code
     * @return stdClass
     */
    public static function ajax_moodle_course($idnumber) {
        global $DB;

        $course = $DB->get_record('course', array('idnumber' => $idnumber));
        $sitsdata = self::sits_data_for_course_code($idnumber);
        $result = array(
            'sits' => $sitsdata,
            'moodle' => $course
        );
        return $result;
    }
}
