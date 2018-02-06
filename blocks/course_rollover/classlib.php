<?php

/****************************************************************

File:     /block/course_rollover/classlib.php

Purpose:  Class to manage course rollovers

****************************************************************/

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

// No direct script access.
defined('MOODLE_INTERNAL') || die();
/* * #@+
 * Defaults for the block course rollover
 */
define('COURSE_ROLLOVER', 'block_course_rollover');
global $DB, $CFG, $EXTDB;

require_once $CFG->libdir . '/adodb/adodb.inc.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->libdir . '/classes/user.php';

/**
 *
 * course_rollover is a class to manage course rollovers this class uses various aspects of moodle API including 
 *  update_course reset_course_userdata which are both used to rest moodle courses.   
 *
 * @package    classlib
 * @category   course_rollover
 * @copyright  2013 Queen Mary University Gerry Hall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

 */
class course_rollover
{

    public $params;
    public $course_rollover_config;
    public $form;
    public $mis_course;

	/**
	 * __construct create a new course_rollover all properties are public in this class the main purpose of this object to
	 * is to keep all main functions and properties under one roof 
	 *
	 * @param string $config 
	 * @param string $id
	 * @access public
     * @static
	 * @author Gerry G Hall
	 * @static 
	 */
    function __construct($config, $id = 0)
    {
        GLOBAL $EXTDB;
        // all parameters like the course id etc
        $this->params = new stdclass();
        // the form when submitted
        $this->form = new stdclass();
        // set the course id
		
        $this->params->id = $id;
        // set the block config
        $this->course_rollover_config = $config;
        // create and connect to a external database
        $EXTDB = course_rollover::db_init($this->course_rollover_config);
    }
	/**
	 * db_init
	 *
	 * @param object $config block config object
	 * @param boolean $force is a connection could be new or reuesed
	 * @return  GLOBAL $EXTDB.
	 * @author Gerry G Hall
	 */
	static function db_init($config, $force = false)
	{
	    GLOBAL $EXTDB, $CFG;

        if (empty($EXTDB) || $force) {
            if(isset($CFG->mis_dbtype)){
                $EXTDB = ADONewConnection($CFG->mis_dbtype);
            } else {
                $EXTDB = ADONewConnection($config->dbconnectiontype);
            }
            // transition to common MIS configuration
            if( isset( $CFG->mis_host ) ){
                $config->db_host = $CFG->mis_host;
                $config->mis_host = $CFG->mis_host;
            }
            if( isset( $CFG->mis_dbase ) ){
                $config->db_name = $CFG->mis_dbase;
                $config->mis_dbase = $CFG->mis_dbase;
            }
            if( isset( $CFG->mis_user ) ){
                $config->db_user = $CFG->mis_user;
                $config->mis_user = $CFG->mis_user;
            }
            if( isset( $CFG->mis_pass ) ){
                $config->db_pass = $CFG->mis_pass;
                $config->mis_pass = $CFG->mis_pass;
            }
            $EXTDB->Connect($config->db_host, $config->db_user, $config->db_pass, $config->db_name, true);
	        $EXTDB->SetFetchMode(ADODB_FETCH_ASSOC);
	    }
	    return $EXTDB;
	}
	

    /**
     * @static rollover 
     *
     * @param string $course 
     * @return void
	 * @access public
	 * @static
	 * @author Gerry G Hall
     */
    static function rollover($course_to_rollover)
    {
        global $CFG, $DB, $EXTDB, $USER;
		$config = get_config('course_rollover');
        $module = $EXTDB->GetRow("SELECT * FROM {$config->db_table} WHERE {$config->data_mapping_course_id} = '" . $course_to_rollover->modcode . "'");
		$course_to_rollover->status = 400;
		$state = true;

        $course_reset_data = json_decode($course_to_rollover->course_reset_data);   
		
		try {
            // we only update courses that are in the MIS dataset
            if ($module) {
                $course_data = new stdClass();
                $coursecontext = get_context_instance(CONTEXT_COURSE, $course_to_rollover->courseid);
                $course_data->id = $course_to_rollover->courseid;
                if($course_reset_data->overridefullname && $course_reset_data->fullname){
                    $course_data->fullname = $course_reset_data->fullname;
                }else{
                    $course_data->fullname = $module['COURSE_NAME'];
                }
                if($course_reset_data->overrideshortname && $course_reset_data->shortname){
                    $course_data->shortname = $course_reset_data->shortname;
                }else{
                    $course_data->shortname = $module['COURSE_SHORT_NAME'];
                }
                $course_data->idnumber = $module['COURSE_ID'];
                $course_data->visible = 0;
                $course_data->summary_editor = array(
                    'text' => $module['MODULE_DESC'],
                    'format' => '1'
                );
            } else {
				
				$course_data = new stdClass();
				$course_data->id = $course_to_rollover->courseid;
                if(isset($course_reset_data->overridefullname) && $course_reset_data->overridefullname && $course_reset_data->fullname){
                    $course_data->fullname = $course_reset_data->fullname;
                }
                if(isset($course_reset_data->overrideshortname) && $course_reset_data->overrideshortname && $course_reset_data->shortname){
                    $course_data->shortname = $course_reset_data->shortname;
                }
				$course_data->idnumber = $course_to_rollover->modcode;
				$course_data->visible = 0;
                
            }
            //update the course
			update_course($course_data);
				
		} catch (Exception $e) {
		   	error_log('Course update_course Failed' . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
		   	error_log(json_encode($course_data) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
			error_log(json_encode($e) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
            $course_to_rollover->status = 510;
            $state = false;
        }
		try {
		    // we reset All courses that in in the course_rollover table
            $activities_default_settings = json_decode(get_config('course_rollover','activities_settings'));
            $course_reset_data = (object)array_merge((array)$activities_default_settings, (array)$course_reset_data);
            course_rollover::delete_activity_grades($course_reset_data);
            //unenrol users
            course_rollover::unenrol_users($course_to_rollover->courseid, $course_reset_data->unenrol_users);
            $course_reset_data->unenrol_users = array();
            //reset courses
            reset_course_userdata($course_reset_data);
            course_rollover::delete_course_assignments($course_reset_data);
            course_rollover::delete_activity_grades($course_reset_data);
		} catch (Exception $e) {
			error_log(json_encode($e) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
            $course_to_rollover->status = 520;
            $state = false;

		}
		try {
			error_log('Course enrol_get_plugins delete_instance database' . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
			$instances = enrol_get_instances($course_to_rollover->courseid, false);
            $plugins = enrol_get_plugins(false);
            foreach ($instances as $i) {
                if ($i->enrol == 'database') {
                    $plugin = $plugins['database'];
                    $plugin->delete_instance($i);
                }
            }
			
		} catch (Exception $e) {
				error_log('Course enrol_get_plugins delete_instance database Failed' . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
				error_log(json_encode($instances) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
				error_log(json_encode($plugins) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
				error_log(json_encode($e) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
	            $course_to_rollover->status = 530;
	            $state = false;
			
		}
        
    	try {
	            // we want to tell the user if the rollover / reset was successful
	            $eventdata = new stdclass();
	            $eventdata->submitttedtime = userdate(time(), '%A, %d %B %Y');
	            $eventdata->schedule = $course_to_rollover;
	            $eventdata->action = 'rollover';
				if($recipient = $DB->get_record('user', array('id'=> $course_to_rollover->userid))){
					$eventdata->recipient = $recipient;
				} else{
					$eventdata->recipient = get_admin();
				}
								
	            events_trigger('course_rollover_commpleted', $eventdata);
				unset($eventdata);
    	} catch (Exception $e) {
				error_log(json_encode($eventdata) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
				error_log(json_encode($e) . time() ."\n", 3, $CFG->dataroot . "/temp/course_rollover_errors.log");
	            $course_to_rollover->status = 540;
    	}

        $DB->update_record(COURSE_ROLLOVER, $course_to_rollover);
        return $state;
    }
	/**
	 * get_form_modcode generates either a select or text element for the activities_settings_form
	 *
	 * @param moodle_form $mform 
	 * @param string $modcode the module code for the course this is a sits module 
	 * @param $editmode if rollover is being edited
     * @return void
	 * @access public
	 * @static
	 * @author Gerry G Hall
	 */
    static function get_form_modcode(&$mform, $modcode, $editmode = false)
    {
        global $PAGE;

        //  the course in sits
        $newmodcode = $editmode ? $modcode : course_rollover::get_next_year_modcode($modcode);

        $elementgroup = array();
        $elementgroup[] = $mform->createElement('text', "modcode", '', 'maxlength="100" size="20"');
        $mform->setType('modcode',PARAM_TEXT);
        $mform->setDefault("modcode", $newmodcode);
        $elementgroup[] = $mform->createElement('checkbox', "overridemodcode", '', "Enter Code");
        $mform->disabledIf("modcode","overridemodcode",'notchecked');
        $mform->addGroup($elementgroup, "modcode_group", get_string('modcode_label', 'block_course_rollover'), ' ', false);
        $mform->addHelpButton("modcode_group", 'mod_code_text', COURSE_ROLLOVER);

        if(course_rollover::check_sits_module_exists($newmodcode)){
            $mform->addElement('static', 'modcode_exists_override_warning', '', get_string('modcode_exists_override_warning', COURSE_ROLLOVER));
        } else {
            $mform->addElement('static', 'modcode_notexists_override_warning', '', get_string('modcode_notexists_override_warning', COURSE_ROLLOVER));
        }
    }
    /**
     * get_form_modcode_cat generates either a select or text element for the activities_settings_form
     *
     * @param moodle_form $mform 
     * @param $id course id 
     * @param $modcode module code in sits
     * @param $editmode if rollover is being edited
     * @return void
     * @access public
     * @static
     */
    static function get_form_modcode_cat(&$mform, $course, $modcode, $editmode = false)
    {
        global $PAGE;

        $id = $course->id;

        $newmodcode = $editmode ? $modcode : course_rollover::get_next_year_modcode($modcode);

        $elementgroup = array();
        $elementgroup[] = $mform->createElement('text', "modcode[$id]", '', 'maxlength="100" size="20"');
        $mform->setType("modcode[$id]",PARAM_TEXT);
        $mform->setDefault("modcode[$id]", $newmodcode);
        $elementgroup[] = $mform->createElement('checkbox', "overridemodcode[$id]", '', "Enter Code");
        $mform->disabledIf("modcode[$id]","overridemodcode[$id]",'notchecked');
        $mform->addGroup($elementgroup, "modcode_group[$id]", "Course ID Number", ' ', false);
        $mform->addHelpButton("modcode_group[$id]", 'mod_code_text', COURSE_ROLLOVER);

        if(course_rollover::check_sits_module_exists($newmodcode)){
            $mform->addElement('static', 'modcode_exists_override_warning', '', get_string('modcode_exists_override_warning', COURSE_ROLLOVER));
        } else {
            $mform->addElement('static', 'modcode_notexists_override_warning', '', get_string('modcode_notexists_override_warning', COURSE_ROLLOVER));
        }
    }
	/**
	 * get_course_list returns a list of courses from a external database thats has been setup in the settings.php for this block
	 * as course id in College edit with a 2 digit year the SQL uses this to filter the result. 
	 * @param string $courseid 
	 * @return array of modules found in the MIS database where the id is the key and the short name is the value
	 * @access public
	 * @static
	 */
    static function get_course_list($courseid = false)
    {
        GLOBAL $EXTDB;
        $config = get_config('course_rollover');
        $result = array();
        $current_year = date('y');
        // fetch course list
        $sql = "SELECT {$config->data_mapping_shortname},  {$config->data_mapping_course_id} FROM {$config->db_table}";
        if ($courseid !== false) {
            $sql .=" WHERE {$config->data_mapping_category} = (SELECT {$config->data_mapping_category} FROM   {$config->db_table} WHERE {$config->data_mapping_course_id} = '$courseid')";
        }
        $sql .= " AND {$config->data_mapping_course_id} LIKE '%{$current_year}' ORDER BY  {$config->data_mapping_shortname} DESC";
        $rs = $EXTDB->Execute($sql);

        if (!$rs) {
            //TODO Add error stuff!
            print_error('', '');
        } else if (!$rs->EOF) {
            // load the row into $rec in the while statment
            while ($rec = $rs->FetchRow()) {
                $result[$rec[$config->data_mapping_course_id]] = $rec[$config->data_mapping_shortname];
            }
        }
        return $result;
    }
    /**
     * get_next_year_modcode returns mode code for next session if modcode 
     * as course id in College edit with a 2 digit year the SQL uses this to filter the result. 
     * @param string $courseid 
     * @return if found modcode else false
     * @access public
     * @static
     */
    static function get_next_year_modcode($courseid = '')
    {
        GLOBAL $EXTDB;
        if(!$courseid){
            return $courseid;
        }
        //find next year modcode
        $current_year = substr($courseid, -2,2);
        if(!is_numeric($current_year))
            return $courseid;
        $next_year = (int)$current_year+1;
        $next_year_modcode = substr($courseid, 0, -2).$next_year;
        return $next_year_modcode;
    }
    /**
     * get_course_mapping gets the mapping for a given course
     * 
     * @param string $courseid 
     * @return array of modules found in the MIS database where the id is the key and the short name is the value
     * @access public
     * @static
     */

    static function get_course_mappings($courseid)
    {
        GLOBAL $EXTDB;
        $noresults = array(array('',get_string('no_mappings_available','block_course_rollover')));
        
        if(empty($courseid)){
            return $noresults;
        }
        $sql = "SELECT Course,Criteria FROM qem_mappings WHERE id IN ( SELECT MAPPINGID FROM course_mappings WHERE QMPLUSCOURSE ='".$courseid."' )";
        $rs = $EXTDB->Execute($sql);

        $result = array();
        if (!$rs) {

        } else if (!$rs->EOF) {
            // load the row into $rec in the while statment
            while ($rec = $rs->FetchRow()) {
                $result[] = array($rec['Course'],$rec['Criteria']);
            }
        }
        if(empty($result)){
                return $noresults;
        }
        return $result;
    }
    /**
     * check_sits_module_exists get a module for the MIS database / external database that has been setup via the setting.php page for this block
     *
     * @param string $mod_code the module code 
     * @return a module record for the external data source
     */
    static function check_sits_module_exists($modcode)
    {
        GLOBAL $EXTDB;
        $config = get_config('course_rollover');
        if (empty($modcode)) {
            return false;
        }
        $sql = "SELECT {$config->data_mapping_course_id} FROM   {$config->db_table} WHERE {$config->data_mapping_course_id} = '$modcode'";
        return $EXTDB->GetRow($sql);
    }
	/**
	 * get_sits_module git a module for the MIS database / external database that has been setup via the setting.php page for this block
	 *
	 * @param string $mod_code the module code 
	 * @return a module record for the external data source
	 * @author Gerry G Hall
	 */
    function get_sits_module($mod_code)
    {
		
        GLOBAL $EXTDB;
        if (empty($mod_code)) {
            return false;
        }
        $sql = "SELECT * FROM {$this->course_rollover_config->db_table} WHERE {$this->course_rollover_config->data_mapping_course_id} = '" . $mod_code . "'";
        return $this->mis_course = $EXTDB->GetRow($sql);
    }
	/**
	 * static function scheduled - tell if a course has been scheduled or not
	 *
	 * @param string $courseid the course id
	 * @return boolean
	 * @author Gerry G Hall
	 */
    static function scheduled($courseid)
    {
        GLOBAL $DB;

        if ($schedule = $DB->get_record(COURSE_ROLLOVER, array('courseid' => $courseid))) {
            return $schedule;
        }
        return false;
    }

	/**
	 * schedule
	 * schedule a course rollover for a given course this function triggers a moodle event 'course_rollover_scheduled'
	 *
	 * @param string $general_resets a object that contains the reset values that have been set in the settings.php page of this block
	 * this is stored in the config_plugins table under the 'course_rolllover' namespace value general_resets
	 * @param string $activities_settings a object that contains the reset values for activities that have been set in the settings.php page of this block
	 * this is stored in the config_plugins table under the 'course_rolllover' namespace value general_resets
	 * @param string $form the a object that contains the values submitted by the user
	 * @param string $schedule  a moodle course that is to be scheduled for a rollover  
	 * @return void
	 */
    static function schedule($general_resets, $activities_settings, $form, $schedule)
    {
		$config = get_config('course_rollover');
        GLOBAL $DB, $USER;
        $returned = array();
        $savedata = new stdclass();
        $savedata->userid = $USER->id;
        $savedata->courseid = $schedule->id;
        $savedata->idnumber = $schedule->idnumber;
        $savedata->shortname = $schedule->shortname;
        $savedata->summary = $schedule->summary;
        $savedata->scheduletime = $form->scheduled_date;
        $savedata->modcode = $form->modcode;
        $form->unenrol_users = explode(',', $form->unenrol_users);		
		if($form->modcode == '0') {
			$savedata->status = 300; // no code
		}elseif ((int)$form->scheduled_date > (int)$config->cutoff_day) {
			$savedata->status = 600; // extended
		}else {
			$savedata->status = 200; //scheduled
		}

        // unset a few things to keep the reset object as clean as posable
        unset($form->submitbutton);
        unset($form->mform_showadvanced_last);
        unset($form->modcode);
        //unset($form->scheduled_date);

        $savedata->course_reset_data =
                json_encode(
                (object) (
                array_fill_keys(
                        array_keys(
                                array_flip(
                                        explode(',', $general_resets)
                                )
                        ), 1
                )
                + array(
                    'activities' => array_fill_keys(
                        array_keys(
                                array_flip(
                                        explode(',', $activities_settings)
                                )
                        ), 1
                    )
                )
                + (array)
                $form
                )
        );
        $form->unenrol_users = implode(',', $form->unenrol_users);

        $eventdata = new stdClass();
        $eventdata->component = COURSE_ROLLOVER;
        $eventdata->submitttedtime = userdate(time(), '%A, %d %B %Y');
		$eventdata->recipient = $USER;
        
        if ($scheduled = course_rollover::scheduled($schedule->id)) {
            $savedata->id = $scheduled->id;
			//@TODO add a status code to the scheduled course 410 as it has been updated
            $returned['update'] = $DB->update_record(COURSE_ROLLOVER, $savedata);
            $eventdata->action = 'update';
			//add_to_log($savedata->id, "course_rollover",  "update", "/blocks/course_rollover/view.php?id=", $course->savedata ,$USER->id);
        } else {
            $returned['insert'] = $DB->insert_record(COURSE_ROLLOVER, $savedata);
            $eventdata->action = 'schedule';
			//add_to_log($savedata->id, "course_rollover","scheduled", "/blocks/course_rollover/view.php?id=", $course->savedata,$USER->id);
        }
		$eventdata->schedule = $savedata;
		try {
			// trigger event to tell the right people that a course rollover has been preformed
	        events_trigger('course_rollover_scheduled', $eventdata);
		} catch (Exception $e) {
			//@TODO add a status code to the scheduled course 415
		}
        unset($eventdata);
        return $returned;
    }

	/**
	 * get_schedule_status
	 *
	 * @param string $courseid the id of the course you would like the status 
	 * @return void
	 * @author Gerry G Hall
	 */
    static function get_schedule_status($courseid)
    {
		GLOBAL $DB;
        if ($status = $DB->get_field_select(COURSE_ROLLOVER, 'status', 'courseid = ?', array($courseid))) {
            return $status;
        }
        return 0;
    }
	/**
	 * get_report_types
	 *
	 * @param mixed $type this can be either the name or the value of a report
	 * @return if the name is entered then the value is returned if the value is enter the name is returned simply callong the function will return the
	 * all report types as an array
	 * @author Gerry G Hall
	 */
    static function get_report_types($type = false)
    {

        $reorttypes = array('all' => 100, 'scheduled' => 200, 'notscheduled' => 0, 'processed' => 400, 'error' => 500, 'nocode' => 300, 'extended' =>600);
        // we use numbers here like 0 so it must really be false
		if ($type === false) {
            return $reorttypes;
        }

        if (is_int($type)) {
            return array_search($type, $reorttypes);
        } elseif (is_string($type)) {
            return array_search($type, array_flip($reorttypes));
        }
    }
	
    /**
     * get_report_table
     * 
     * @param array $params 
     * @return object that contains a moodle tables and a row count of the report
	 * @author Gerry G Hall
     */
    static function get_report_table($params)
    {
        GLOBAL $DB;

        if (!course_rollover::get_report_types($type = $params['type'])) {
            print_error('no report type found');
        }
		$config = get_config('course_rollover');
        $report = new stdclass();
        $sql = "SELECT
						%s
				FROM
					{block_course_rollover} cr,
					{user} u
				WHERE
					u.id = cr.userid
				%s
			";

        $columns = "cr.shortname, cr.modcode, cr.idnumber , FROM_UNIXTIME(cr.scheduletime,'%d-%m-%y') as `scheduled date`,CONCAT(u.firstname , ' ' , u.lastname)";
        $and = ' AND status = ?';

        $not_scheduled_sql = '
			SELECT
			%s
			FROM
				{course} c
			LEFT JOIN
				{block_course_rollover} cr
			ON
				c.`id` = cr.courseid
			WHERE
				cr.courseid  IS NULL
			AND
				c.`visible` =1
			AND
				c.`category` NOT IN (SELECT id from {course_categories} where parent = 10)';

        $report->table = new html_table();
        
        // add column sorting feature for the data table
        if ($params['dir'] == '' || $params['dir'] == 'ASC') $dir = 'DESC'; else $dir = '';

        $prmCourse = $params;
        $prmCourse['ord'] = 'shortname';
        $prmCourse['dir'] = $dir;
        $urlCourse = new moodle_url('/blocks/course_rollover/schedule_report.php', $prmCourse);        

        $prmTime = $params;
        $prmTime['ord'] = 'scheduletime';
        $prmTime['dir'] = $dir;
        $urlTime = new moodle_url('/blocks/course_rollover/schedule_report.php', $prmTime);

        $prmCourse['chr'] = '&#8597;'; //'&#10702'; //       
        $prmTime['chr'] =   ' &#8597;'; //'&#10702'; //

        switch ($params['ord']) {
            case 'shortname':
                if ($prmCourse['dir'] == 'DESC') $prmCourse['chr'] = ' &#9650;';
                else $prmCourse['chr'] = ' &#9660;';
            break;             
            case 'scheduletime':
                if ($prmTime['dir'] == 'DESC') $prmTime['chr'] = ' &#9650;';
                else $prmTime['chr'] = ' &#9660;';
            break; 
        }

        $report->table->head = array(
            '<a href="' .  $urlCourse . '">Course ' . $prmCourse['chr'] . '</a>',
            'New Module Code', 
            'Old Module Code', 
            '<a href="' .  $urlTime . '">Schedule Time ' . $prmTime['chr'] . '</a>', 
            'Scheduled By');

        $ordersql = sprintf(" ORDER BY %s %s", $params['ord'], $params['dir']);
        // 21/07/32014 -----------------------------------

        $report->table->size = array('40%', '15%', '15%', '15%', '15%');
        $report->table->align = array('left', 'center', 'center', 'center', 'left');

        //$DB->set_debug(true);
        
        switch ($type) {
            case 0: // not scheduled
                $sqlparam = null;
                // add column sorting feature for the data table
                $report->table->head = array(
                    'Course Title', 
                    '<a href="' .  $urlCourse . '">Course Short Code ' . $prmCourse['chr'] . '</a>',
                    'idNumber'
                );
                // 21/07/32014 -----------------------------------                $report->table->size = array('60%', '20%', '20%');
                $report->table->align = array('left', 'left', 'left');
                $countsql = sprintf($not_scheduled_sql, 'COUNT(c.id) as count');
                $datasql = sprintf($not_scheduled_sql, 'c.fullname, c.shortname, c.idnumber');
                break;
            case 100: // display all
                $sqlparam = null;
                $countsql = sprintf($sql, 'COUNT(cr.id) as count', '');
                $datasql = sprintf($sql, $columns, '');
                break;
            case 200: // scheduled
            case 300: // no code
            case 400: // processed
				$sqlparam = array($type);
                $countsql = sprintf($sql, 'COUNT(cr.id) as count', $and);
                $datasql = sprintf($sql, $columns, $and);
				break;
            case 500: // error
				array_push($report->table->head , 'Error Code');
				$columns .= ', status as `error code`';
				$and = ' AND status between ? AND ? ';

                $sqlparam = array(499 , 599);
                $countsql = sprintf($sql, 'COUNT(cr.id) as count', $and);
                $datasql = sprintf($sql, $columns, $and);
                break;
		   	case 600: // extended
				$and = ' AND cr.scheduletime > ?';
	            $sqlparam = array($config->cutoff_day);
	            $countsql = sprintf($sql, 'COUNT(cr.id) as count', $and);
	            $datasql = sprintf($sql, $columns, $and);
	            break;
        }

        $report->count = $DB->count_records_sql($countsql, $sqlparam);

        $result = $DB->get_records_sql($datasql . $ordersql, $sqlparam, $params['page'] * $params['limit'], $params['limit']);

        $report->table->attributes['class'] = 'scaletable localscales generaltable';
		//foreach($result as &$row) {
		//		$row->shortname = html_writer::link(new moodle_url('/course/view.php', array('idnumber'=>$row->idnumber)),$row->shortname, array('title'=>$row->shortname));
		//}

        $report->table->data = $result;
        return $report;
    }
	/**
	 * validate_mod_code 
	 * @TODO this code is not currently being used
	 * @param string $current 
	 * @param string $new 
	 * @return void
	 * @author Gerry G Hall
	 */
    static function validate_mod_code($current, $new)
    {
        if (trim($new) != trim($current)) {

        }
    }

	/**
	 * check_in_range
	 *
	 * @param string $start start date
	 * @param string $end end date
	 * @param string $date the dat
	 * @return array with message , error and date
	 * @author Gerry G Hall
	 */
	static function check_in_range($start, $end, $date)
	{
	    $return = array();
	    $return['date'] = false;
	    if (($date < $start)) {

	        $return['message'] = 'start_date_error';
	        $return['error'] = true;
	    } elseif ($date > $end) {

	        $return['message'] = 'comfirmation_extention';
	        $return['error'] = true;
	    }

	    $return['output'] = 'scheduled_date';
	    $return['date'] = userdate($date, '%A, %d %B %Y');

	    return $return;
	}
	/**
	 * Sends a confirmation message to the course Administrator confirming that the rollover was scheduled and/or processed.
	 *
	 * @param object $a lots of useful information that can be used in the message
	 *      subject and body.
	 *
	 * @return int|false as for {@link message_send()}.
	 */
	static function notification($obj)
	{
		GLOBAL $DB;
        $a = new stdClass();
		$a->coursename = $DB->get_field('course', 'fullname', array('id'=> $obj->schedule->courseid), IGNORE_MISSING);
	    $a->username = fullname($obj->recipient);
	    $a->userusername = $obj->recipient->username;
	    $a->idnumber = $obj->schedule->modcode;
		$a->submitttedtime = $obj->submitttedtime;
	    $a->scheduledate = userdate($obj->schedule->scheduletime, '%A, %d %B %Y');
	    $a->shortname = $obj->schedule->shortname;
	    // Prepare message
	    $eventdata = new stdClass();
	    $eventdata->component = COURSE_ROLLOVER;
	    $eventdata->name = 'confirmation';
	    $eventdata->notification = 1;
	    $eventdata->userfrom = core_user::get_noreply_user();
	    $eventdata->userto = $obj->recipient;
	    
		$eventdata->subject = get_string('emailconfirmsubject_' . $obj->action, COURSE_ROLLOVER, $a);
	    $eventdata->fullmessage = get_string('emailconfirmbody_' . $obj->action, COURSE_ROLLOVER, $a);
	
	    $eventdata->fullmessageformat = FORMAT_PLAIN;
	    $eventdata->fullmessagehtml = '';

	    $eventdata->smallmessage = get_string('emailconfirmsmall', COURSE_ROLLOVER, $a);
	    //TODO find why moodle_url returns empty object here
        //$eventdata->contexturl = new moodle_url('/blocks/course_rollover/view.php', array('id' => $obj->schedule->courseid));
	    $eventdata->contexturlname = $a->coursename;
		$return  = message_send($eventdata);
		return $return;
	}

    static function delete_course_assignments($data) {
        global $CFG, $DB;
        $deleteassigntypes = array('turnitintool', 'turnitintooltwo');
        //hard delete turnitintool assign types
        foreach ($deleteassigntypes as $assigntype) {
            $assignments = course_rollover::get_activities_by_module($data->id, $assigntype);
            foreach ($assignments as $assignment){
                course_delete_module($assignment->id);
            }
        }
        //delete assign activity type if selected in settings
        if(isset($data->reset_assignments_remove) && $data->reset_assignments_remove){
            $assignments = course_rollover::get_activities_by_module($data->id, 'assign');
            foreach ($assignments as $assignment){
                course_delete_module($assignment->id);
            }
        }
        return true;
    }

    //delete activity grades
    static function delete_activity_grades($data){
        global $DB;
        //field contaning mod instance id in grade tables
        $instance_names = array(
            'quiz' => 'quiz',
            'assign' => 'assignment',
            'workshop' => 'assessmentid'
        );

        if(isset($data->reset_activity_grades) && $data->reset_activity_grades){
            $activity_grade_items = $DB->get_records('grade_items', array('courseid' => $data->id, 'itemtype' => 'mod'));
            foreach($activity_grade_items as $activity_grade_item){
                $activity_grade_table = $activity_grade_item->itemmodule.'_grades';
                $dbman = $DB->get_manager();
                if($dbman->table_exists($activity_grade_table) && ($instance_field = $instance_names[$activity_grade_item->itemmodule])){
                    //delete records from [mod]_grades table
                    $DB->delete_records($activity_grade_table, array(
                            $instance_names[$activity_grade_item->itemmodule] => $activity_grade_item->iteminstance)
                    );
                }
            }
        }
    }

    //unenrol users
    static function unenrol_users($courseid, $roles){
        global $DB;
        //get list of role assignments for current roles which will be deleted
        $role_assignments_delete = $DB->get_records_sql("
              SELECT DISTINCT ra.id
              FROM {context} ct
                JOIN {role_assignments} ra ON ra.contextid = ct.id AND ct.contextlevel = 50
              WHERE ct.instanceid = ? AND ra.roleid IN (".implode(",",$roles).")", array( $courseid )
        );
        $role_assignments_delete_ids = array_keys($role_assignments_delete);
        if($role_assignments_delete_ids){
            $DB->delete_records_select("role_assignments","id IN (".implode(",",$role_assignments_delete_ids).")");
            //get list of user enrolments which are not present in role assignments
            //which will be deleted
            $user_enrolments_delete = $DB->get_records_sql("
                    SELECT ue.id
                    FROM {user_enrolments} ue
	                  JOIN {enrol} en ON en.id = ue.enrolid
	                  JOIN {context} cn on cn.instanceid = en.courseid  AND cn.contextlevel = 50
	                  LEFT JOIN {role_assignments} ra ON ra.contextid = cn.id AND ra.userid = ue.userid
                    WHERE en.courseid= ? AND ra.id IS NULL", array( $courseid )
            );
            $user_enrolments_delete_ids = array_keys($user_enrolments_delete);
            if($user_enrolments_delete_ids){
                $DB->delete_records_select("user_enrolments","id IN (".implode(",",$user_enrolments_delete_ids).")");
            }
        }
    }

    static function get_activities_by_module($courseid, $activity) {
        global $DB;
        $course_modules = $DB->get_records_sql("
                SELECT cm.id 
                FROM {course_modules} as cm 
                JOIN {modules} as m 
                    ON m.id = cm.module 
                WHERE cm.course = ? and m.name = ? 
            ", array($courseid , $activity));
        return $course_modules;
    }
}