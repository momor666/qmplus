<?php
require_once($CFG->libdir.'/coursecatlib.php');
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
 *
 * @package    local
 * @subpackage qmcw_coversheet
 * @copyright  2017 Queen Mary University of London
 * @author     Damian Hippisley <d.j.hippisley@qmul.ac.uk>
 * @author     Vasileos Sotiras <v.sotiras@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 24/03/2017
 * Time: 14:24
 * mod/assign/externallib.php
 */

// load the pdf and barcode libraries
require_once($CFG->dirroot . '/lib/pdflib.php');
require_once($CFG->dirroot . '/lib/tcpdf/tcpdf_barcodes_1d.php');
require_once($CFG->dirroot . '/lib/tcpdf/tcpdf_barcodes_2d.php');
// TCPDF configuration
require_once($CFG->dirroot . '/lib/tcpdf/tcpdf_autoconfig.php');
// TCPDF static font methods and data
require_once($CFG->dirroot . '/lib/tcpdf/include/tcpdf_font_data.php');
// TCPDF static font methods and data
require_once($CFG->dirroot . '/lib/tcpdf/include/tcpdf_fonts.php');
// TCPDF static color methods and data
require_once($CFG->dirroot . '/lib/tcpdf/include/tcpdf_colors.php');
// TCPDF static image methods and data
require_once($CFG->dirroot . '/lib/tcpdf/include/tcpdf_images.php');
// TCPDF static methods and data
require_once($CFG->dirroot . '/lib/tcpdf/include/tcpdf_static.php');

/**
 * @param $user_id User ID
 * @param $course_id Course ID
 *
 * @return bool
 */
function local_qmcw_coversheet_isUserInCourse($user_id , $course_id ){
    global $DB ;
    $result = false;
    $factor = pow(10,10); // separate userid and courseid in the same numbered row id

    $user_in_course_sql = '
SELECT (e.courseid * '.$factor.' + ue.userid) id, e.courseid, ue.userid, ue.timecreated 
FROM {user_enrolments} ue
JOIN {enrol} e ON e.id = ue.enrolid AND e.status = 0 AND e.courseid IS NOT NULL
                        AND ue.status = 0 AND e.courseid = ? AND ue.userid = ?   
JOIN {user} u ON u.id = ue.userid AND u.deleted = 0 AND u.suspended = 0 AND u.id IS NOT NULL ';
    $user_in_course = $DB->get_records_sql($user_in_course_sql,array($course_id,$user_id));
    $result = ($user_in_course) ? true : false;
    return $result;
}

/**
 * @param $user_id User ID
 * @param $course_module_id Course Module ID
 *
 * @return array
 */
function local_qmcw_coursework_get_module_group_participants($user_id, $course_module_id )
{
    global $DB;
    $participants = array();
    $cm_team_mode = '';
    $sql = 'select ma.teamsubmission from {assign} ma join {course_modules} cm on cm.instance = ma.id and cm.id = ' . $course_module_id;
    $query = $DB->get_record_sql($sql);
    if (isset($query->teamsubmission)) {
        $cm_team_mode = $query->teamsubmission;
    }
    if($cm_team_mode == '1' || $cm_team_mode == '0'){
        if($cm_team_mode == '0') {
            $groupsize = 1;
        } else {
            $sql = '
                select groupid
                from
                  (select @c := 0 , @userid := ' . $user_id . ', @cmid := ' . $course_module_id . ') cntrs
                ,(
                    select gm.groupid
                    from {groups_members} gm
                    join {groups} mg on mg.id = gm.groupid and gm.userid =  @userid
                    join {course} mc on mc.id = mg.courseid
                    join {course_modules} cm on cm.course = mc.id and cm.id = @cmid
                    join {assign} a on a.id = cm.instance and a.teamsubmission = 1
                    order by 1 limit 1
                ) d1
                ';
            $query = $DB->get_record_sql($sql);
            if ($query) {
                $groupid = $query->groupid;
                $sql = 'SELECT count(userid) groupsize FROM {groups_members} WHERE groupid = ' . $groupid;
                $query = $DB->get_record_sql($sql);
                if ($query) {
                    $groupsize = $query->groupsize;
                }
            }
        }
        if($cm_team_mode != '0'){
            $participation = 'SELECT @c := (ifnull(@c,1) +1) id
								, case ma.teamsubmission
								when "1" then mgm.groupid
								else mu.id
								end as barcodegroup
								, mgm.groupid
								, mgm.userid
								, ma.teamsubmission
								, ma.teamsubmissiongroupingid
								, ma.name assignmentname
								, cm.groupmode cmgroupmode
								, cm.id cmid
								, cm.idnumber cmidnumber
								, cm.course courseid
								, cm.groupingid
								, mu.lastname userlastname
								, mu.firstname userfirstname
								, '. $groupsize .' as groupsize
								, mar.lastname MarkerLastName
								, mar.firstname MarkerFirstName
								FROM 
										{groups_members} mgm
								JOIN (SELECT course FROM {course_modules} cm WHERE cm.id = ' . $course_module_id . ' ) crs
								JOIN {groups} mg ON mg.courseid = crs.course AND mg.id = mgm.groupid
								JOIN {user} mu ON mu.id = mgm.userid AND mu.deleted = 0 AND mu.suspended = 0 
								JOIN {context} mc ON mc.instanceid = crs.course AND mc.contextlevel = '.CONTEXT_COURSE.'
								JOIN {role_assignments} ra ON ra.userid = mu.id AND mc.id = ra.contextid
								JOIN {role} ro ON ro.id = ra.roleid AND ro.archetype = "student"
								JOIN {course_modules} cm ON cm.id = ' . $course_module_id . '
								JOIN {assign} ma ON ma.id = cm.instance 
								JOIN {course} co ON co.id = crs.course
								LEFT JOIN {user} mar ON mar.id = co.marker 
								, (
									SELECT groupid 
									FROM {groups_members} gm 
									JOIN {groups} mg ON mg.id = gm.groupid AND gm.userid = ' . $user_id . ' 
									    AND mg.courseid = (SELECT course FROM {course_modules} cm WHERE cm.id = ' . $course_module_id . ')
								) grpid
								, (SELECT @c := 0 ) cntrs
								 WHERE mgm.groupid = grpid.groupid
								';
            $participants = $DB->get_records_sql($participation);
        } else {
            $params = array('userid' => $user_id, 'cmid' => $course_module_id);
            $participation = '
           select   @c := (ifnull(@c,1) +1) id
                    , mu.id barcodegroup 
                    , null AS groupid
                    , mu.id AS userid
                    , ma.teamsubmission
                    , ma.teamsubmissiongroupingid
                    , ma.name assignmentname
                    , cm.groupmode cmgroupmode
                    , cm.id cmid
                    , cm.idnumber cmidnumber
                    , cm.course courseid
                    , cm.groupingid
                    , mu.lastname userlastname
                    , mu.firstname userfirstname
                    , 1 as groupsize
                    , mar.lastname MarkerLastName
                    , mar.firstname MarkerFirstName
            from {user}  mu
            join {role_assignments}  ra on ra.userid = mu.id and mu.id = :userid  and mu.deleted = 0 and mu.suspended = 0
            join {role}  mr on mr.id = ra.roleid and mr.archetype = "student"
            join {context}  mc on mc.id = ra.contextid and mc.contextlevel = 50
            join {course_modules}  cm on cm.id = :cmid
            join {course} co on co.id = cm.course and co.id = mc.instanceid
            left join {assign}  ma on ma.id = cm.instance
            left join {user}  mar on ma.id = co.marker, 
             (select @c := 0 ) ctrl
          
';
            $participants = $DB->get_records_sql($participation, $params);
        }

    }

    return $participants;
}

/**
 * Get the School where this course category originates from
 * @param $course_category_id Course Category ID
 *
 * @return mixed School
 */
function local_qmcw_coversheet_get_course_category_school($course_category_id){
    global $DB;
    $category = null;
    try {
        // get the root category for this course
        $top_categories = coursecat::get($course_category_id,IGNORE_MISSING );
        if($top_categories){
            $parent_cats = $top_categories->get_parents();
            $school_cat_id = $parent_cats[0];
            $category = $DB->get_record('course_categories',array('id' => $school_cat_id));
        }
    }
    catch(Error $e){}
    catch(Throwable $t) {}
    catch(Exception $e){}

    return $category;
}

/**
 * Get the barcode identifier to be used for producing the barcode.
 * It could be part of the plugin settings
 * @return string
 */
function local_qmcw_coversheet_get_barcode_identifier(){
    /* This should be overwritten by the configuration value of the plugin */
    return 'QM+CW';
}

/**
 * Get the barcode separator character.
 * It could be part of the plugin settings
 * @return string
 */
function local_qmcw_coversheet_get_barcode_separator(){
    /* this could be part of the plugin configuration parameters */
    return '-';
}

/**
 * @param $user_id
 * @param $course_module_id
 *
 * @return string
 */
function local_qmcw_coversheet_get_barcode_data($user_id, $course_module_id){
    $barcode_data = '';
    $group_flag = '';
    $participants = local_qmcw_coursework_get_module_group_participants($user_id,$course_module_id);

    if( count($participants) > 0 ){

        $group_flag .=  ( $participants[1]->teamsubmission == "0") ? 'S' : 'G';
        $barcode_identifier = local_qmcw_coversheet_get_barcode_identifier();
        $separator = local_qmcw_coversheet_get_barcode_separator();
        $group_id = str_pad($participants[1]->barcodegroup, 10, '0', STR_PAD_LEFT);
        $course_module_id = str_pad($course_module_id, 10, '0', STR_PAD_LEFT);
        $barcode_data = $barcode_identifier . $separator . $group_flag . $separator . $group_id . $separator . $course_module_id;
    }

    return $barcode_data;
}

/**
 * Get the barcode as image, so it can be part of an HTML or TCPDF output
 * @param $barcode_data
 * @param $barcode_style
 * @param $barcode_line_thickness
 * @param $barcode_height
 * @param $barcode_columns
 * @param $barcode_rows
 * @param $red
 * @param $green
 * @param $blue
 *
 * @return string
 */
function local_qmcw_coversheet_get_barcode_image($barcode_data, $barcode_style, $barcode_line_thickness, $barcode_height, $barcode_columns, $barcode_rows, $red, $green, $blue){
    if(!isset($red)){ $red = 0 ;}
    if(!isset($green)){ $green = 0 ;}
    if(!isset($blue)){ $blue = 0 ;}
    if(!isset($barcode_style)){ $barcode_style = 2;}
    if(!isset($barcode_height)){ $barcode_height = 35;}
    if(!isset($barcode_columns)){ $barcode_columns = 3;}
    if(!isset($barcode_rows)){ $barcode_rows = 3;}
    if(!isset($barcode_data)){ $barcode_data = '';}

    $barcode_color = array($red, $green, $blue);

    if($barcode_style == 1) {
        /* C128 style */
        if(!isset($barcode_line_thickness)){ $barcode_line_thickness = 1.5;}
        $barcode = new TCPDFBarcode($barcode_data, 'C128');
        $barcode_image = '<img valign="top" align="middle" src="data:image/png;base64,'
            . base64_encode($barcode->getBarcodePNGData($barcode_line_thickness, $barcode_height, $barcode_color)) . '" />';
    } elseif($barcode_style == 2) {
        /* QRCODE style */
        $qrcode = new TCPDF2DBarcode($barcode_data,'QRCODE,H');
        $barcode_image = '<img valign="top" align="right"  src="data:image/png;base64,'
            . base64_encode($qrcode->getBarcodePngData( 3, 3, $barcode_color )) . '" />';
    } elseif($barcode_style == 3){
        /* C93 style */
        if(!isset($barcode_line_thickness)){ $barcode_line_thickness = 2;}
        $barcode = new TCPDFBarcode($barcode_data, 'C93');
        $barcode_image = '<img valign="top" align="middle" src="data:image/png;base64,'
            . base64_encode($barcode->getBarcodePNGData($barcode_line_thickness, $barcode_height, $barcode_color)) . '" />';
    } else {
        /* C39 style */
        if(!isset($barcode_line_thickness)){ $barcode_line_thickness = 1.5;}
        $barcode = new TCPDFBarcode($barcode_data, 'C39');
        $barcode_image = '<img valign="top" align="middle" src="data:image/png;base64,'
            . base64_encode($barcode->getBarcodePNGData($barcode_line_thickness, $barcode_height, $barcode_color)) . '" />';
    }
    return $barcode_image;
}

/**
 * @param $barcode_group_mode
 * @param $barcode_group_id
 * @param $barcode_coursework_id
 *
 * @return bool
 */
function local_qmcw_coversheet_check_barcode_values($barcode_group_mode , $barcode_group_id, $barcode_coursework_id ){
    global $DB;
    $return = false;
    if($barcode_group_mode == 'G'){
        $course_record = $DB->get_record('course_modules',array('id'=>$barcode_coursework_id),'course');
        if($course_record) {
            $course_id = $course_record->course;
            $group_record = $DB->get_record('groups',array('id'=>$barcode_group_id,'courseid'=>$course_id),'id,courseid');
            if($group_record){
                $return = ( $group_record->id == $barcode_group_id && $group_record->courseid == $course_id );
            }
        }
    } elseif($barcode_group_mode == 'S'){
        $user_id = $barcode_group_id;
        $course_record = $DB->get_record('course_modules',array('id'=>$barcode_coursework_id),'course');
        if($course_record){
            $course_id = $course_record->course;
            $return = local_qmcw_coversheet_isUserInCourse($user_id , $course_id );
        }
    }
    return $return;
}

/**
 * @param $barcode_value
 *          this value should come from the scanner
 *
 * @return bool
 *          the answer is a boolean state of correct or not combination of values in the barcode
 */
function local_qmcw_coversheet_is_barcode_valid($barcode_value){
    $return = isset($barcode_value);
    if( ! $return){
        return false;
    }
    $barcode_value = strtoupper($barcode_value);

    if($return){
        $separator = local_qmcw_coversheet_get_barcode_separator();
        $identifier = local_qmcw_coversheet_get_barcode_identifier();
        if(count( preg_split( '/'.$separator.'/' ,$barcode_value , 4)) == 4 ){
            list($barcode_identifier, $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
        } elseif(count( preg_split( '/'.$separator.'/' ,$barcode_value , 4)) == 3 ){
            $barcode_identifier = $identifier;
            list( $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
        }
        # list( $barcode_identifier, $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
        $return = ( $identifier == $barcode_identifier && in_array( $barcode_group_mode , array('G','S') ) && (int)($barcode_group_id) > 0 && (int)($barcode_coursework_id) > 0);
        if($return){
            $return = local_qmcw_coversheet_check_barcode_values($barcode_group_mode , $barcode_group_id, $barcode_coursework_id );
        }

    }
    return $return;
}

/*
 * check if the barcode is contains a usertype G or S and followed by two ints separated by hyphens
 * this is the only other valid form of barcode.
 *
 *
 *
 */
function local_qmcw_coversheet_is_abrr($barcode_value){
    $return = isset($barcode_value);
    if($return){
        $separator = local_qmcw_coversheet_get_barcode_separator();
        list( $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
        $return = (in_array( $barcode_group_mode , array('G','S')) && (int)($barcode_group_id)>0 && (int)($barcode_coursework_id) > 0);
        if($return){
            $return = local_qmcw_coversheet_check_barcode_values($barcode_group_mode , $barcode_group_id, $barcode_coursework_id );
        }
    }
    return $return;

}



/**
 * This correct an abbreviated form of the barcode so that the rest of the code only handles one
 *
 * @param $barcode
 * @return bool
 */
function local_qmcw_coversheet_correct_abbr_barcode($barcode){

    $identifier = local_qmcw_coversheet_get_barcode_identifier();
    if(strpos($barcode, $identifier ) === false){
        if(local_qmcw_coversheet_is_abrr($barcode)){
            return $identifier . '-' . $barcode ;
        }
    }

    return $barcode;
}

/**
 * @param $barcode_value
 * @return array
 *
 */
function local_qmcw_coversheet_get_barcode_elements($barcode_value){

    $is_abbr = local_qmcw_coversheet_is_abrr($barcode_value);

    $barcode = array();
    if($is_abbr){
        $separator = local_qmcw_coversheet_get_barcode_separator();
        list($barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
        $barcode['barcode_group_mode'] = $barcode_group_mode;
        $barcode['barcode_group_id'] = $barcode_group_id;
        $barcode['barcode_coursework_id'] = $barcode_coursework_id;
    }
    else{
        $separator = local_qmcw_coversheet_get_barcode_separator();
        list($barcode_identifier, $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
        $barcode['barcode_identifier'] = $barcode_identifier;
        $barcode['barcode_group_mode'] = $barcode_group_mode;
        $barcode['barcode_group_id'] = $barcode_group_id;
        $barcode['barcode_coursework_id'] = $barcode_coursework_id;
    }
    return $barcode;
}


/**
 * @param $barcode_value
 * @return bool
 */
function local_qmcw_coversheet_is_existing_scan($barcode_value){
    global $DB;

    $barcodeelements = local_qmcw_coversheet_get_barcode_elements($barcode_value);

    if($barcodeelements['barcode_group_mode'] == "G"){
        $params = array('groupid' => (int) $barcodeelements['barcode_group_id'], 'cmid' => (int) $barcodeelements['barcode_coursework_id']);
    }
    else{
        $params = array('userid' => (int) $barcodeelements['barcode_group_id'], 'cmid' => (int) $barcodeelements['barcode_coursework_id']);
    }

    if($DB->count_records('local_qmcw_scan', $params) == 1){
        return true;
    }
    return false;
}


/**
 * @param $barcode_value
 * @return mixed
 */
function local_qmcw_coversheet_get_scan_record($barcodeobj){
    global $DB;

    $params = array(
        'cmid' => $barcodeobj->cmid,
        'userid' => $barcodeobj->userid,
        'groupid' => $barcodeobj->groupid,
    );

    try {
        $scanrecord = $DB->get_record('local_qmcw_scan', $params);

    } catch (Error $error){
        // Error should be used to represent coding issues that require the attention of a programmer.
    } catch (Throwable $throwable){
        // PHP 7 - Throwable should be used for conditions that can be safely handled at runtime where another action can be taken and execution can continue.
    } catch (Exception $exception){
        // PHP 5.6 Exception should be used for conditions that can be safely handled at runtime where another action can be taken and execution can continue.
    }

    return $scanrecord;
}








/**
 * @param $barcode_value
 * @return array
 */
function local_qmcw_coversheet_get_barcode_members($barcode_value){
    global $DB;
    $members = array();
    $separator = local_qmcw_coversheet_get_barcode_separator();
    $identifier = local_qmcw_coversheet_get_barcode_identifier();
    list($barcode_identifier, $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
    if(in_array( $barcode_group_mode , array('G','S'))){
        if($barcode_group_mode == 'S'){
            $the_user = $DB->get_record('user',array('id'=>$barcode_group_id),'id,lastname,firstname');
            $members[] = array('cwid'=>$barcode_coursework_id,'id'=>$the_user->id,'lastname'=>$the_user->lastname,'firstname'=>$the_user->firstname);
        } else {
            $sql  = 'SELECT g.userid, u.lastname, u.firstname FROM {groups_members} g JOIN {user} u ON g.groupid = '.$barcode_group_id;
            $sql .= ' AND g.userid = u.id AND u.deleted = 0 AND u.suspended = 0 ORDER BY u.id' ;
            $the_users = $DB->get_records_sql( $sql );
            foreach($the_users as $the_user){
                $members[] = array('cwid'=>$barcode_coursework_id,'id'=>$the_user->id,'lastname'=>$the_user->lastname,'firstname'=>$the_user->firstname);
            }
        }
    }
    return $members;
}



/**
 * @param $barcode_value
 * @return null
 */
function local_qmcw_coversheet_get_barcode_course_id($barcode_value){
    global $DB;
    $course_id = null;
    $separator = local_qmcw_coversheet_get_barcode_separator();
    $identifier = local_qmcw_coversheet_get_barcode_identifier();
    list($barcode_identifier, $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$separator.'/' ,$barcode_value , 4);
    if(is_int($barcode_coursework_id)){
        $course = $DB->get_record('course_modules',array('id'=>$barcode_coursework_id),'id,course');
        if($course){
            $course_id = $course->course;
        }
    }
    return $course_id;
}


function local_qmcw_coversheet_get_scan_update_history($scanid)
{
    global $DB;
    //TODO: sort decencing
    if ($results = $DB->get_records('local_qmcw_update_log', array('scanid' => $scanid), 'timescanned')) {
        return $results;
    }
    return false;
}

/**
 * checks if role is admin or course admin
 *
 * @param $userid
 * @return bool
 *
 */
function local_qmcw_coversheet_is_admin_or_courseadmin($userid){

    $is_site_admin = local_qmcw_coversheet_is_a_site_admin($userid);
    $is_school_admin = local_qmcw_coversheet_is_school_admin($userid);
    $is_course_admin = local_qmcw_coversheet_is_course_admin($userid);

    if($is_site_admin || $is_course_admin || $is_school_admin){
        return true;
    }
    else{
        return false;
    }
}


/**
 * @param $userid
 *
 * @return bool if the user is a site administrator
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qmcw_coversheet_is_a_site_admin($userid){
    global $DB;
    $isadmin = false;
    $admins = get_admins();
    foreach( $admins as $admin ) {
        if ( $userid == $admin->id ) {
            $isadmin = true;
            break;
        }
    }
    return $isadmin;
}


/**
 * @param $user_id
 *
 * @return bool is a school administrator or not
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qmcw_coversheet_is_school_admin($user_id ){
    global $DB;
    $is_school_admin = false;
    if( (int)$user_id > 0 ){
        try {
            $school_admins = local_qmcw_coversheet_get_school_admin_categories( $user_id );
            if( count( $school_admins ) > 0 ) {
                $is_school_admin = true;
            }
            unset($school_admins);
        } catch (Error $error){

        } catch (Throwable $throwable){

        } catch (Exception $exception){

        }
    }
    return $is_school_admin;
}

/**
 * Is this user a course administrator ?
 * @param $user_id
 *
 * @return bool if the user is acourse administrator on any course
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qmcw_coversheet_is_course_admin($user_id ){
    $is_course_admin = false;
    if( (int) $user_id > 0  ) {
        $courses = local_qmcw_coversheet_get_course_admin_courses( $user_id );
        if(count($courses) > 0) {
            $is_course_admin = true;
        }
    }
    return $is_course_admin;
}

/**
 * @param $user_id
 *
 * @return array of courses administered by a user
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qmcw_coversheet_get_course_admin_courses ($user_id , $id_only = false ){
    global $DB;
    $course_admin_courses = array();
    if( (int)$user_id > 0 ){
        try {
            $course_admin_courses_sql = 'select 
@c := ifnull(@c,0)+1 id, u.id userid, concat_ws(" ",u.firstname, u.middlename, u.lastname, u.username) courseadmin, co.id courseid, co.fullname coursename
from (select @c := 0) dta
, {user} u 
join {role_assignments} ra on ra.userid = u.id and u.id = :userid
join {role} ro on ro.id = ra.roleid and ro.shortname like \'%course_admin%\' 
join {context} cx on cx.id = ra.contextid
join {course} co on co.id = cx.instanceid
order by 2,4 asc;';
            $course_admin_courses = $DB->get_records_sql( $course_admin_courses_sql , array('userid' => $user_id ) );
            if($id_only){
                $ids = array();
                foreach($course_admin_courses as $course_admin_course){
                    $ids[] = (int) $course_admin_course->courseid;
                }
                if( count( $ids ) > 1 ){
                    asort($ids);
                    $ids = array_unique($ids);
                }
                $course_admin_courses = $ids;
            }
        } catch (Error $error){

        } catch (Throwable $throwable){

        } catch (Exception $exception){

        }
    }
    return $course_admin_courses;
}

/**
 * @param $user_id
 *
 * @return array of schools administered by a user
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qmcw_coversheet_get_school_admin_categories($user_id){
    global $DB;
    $categories = array();
    if( (int)$user_id > 0 ){
        $sql = 'select @c:= ifnull(@c ,0)+1 id
, cc.id categoryid
, u.id userid, concat_ws(" ", u.firstname,u.middlename,u.lastname) schooladmin
, u.username
,ifnull(concat(cc.name),\'QM+\') as \'Category\' 
,concat_ws(\' \',r.archetype ,r.shortname) as \'Role\' 
from (select @c := 0 ) dta, {role_assignments} a 
join {role} r on r.id = a.roleid and r.shortname = \'administrator\' and a.userid = :user_id
join {context} cx on cx.id = a.contextid 
join {user} u on u.id = a.userid 
/*left */ join {course_categories} cc on cc.id = cx.instanceid
order by 2,3;';
        try {
            $categories = $DB->get_records_sql($sql,array('user_id' => (int)$user_id));
        } catch (Error $error){

        } catch (Throwable $throwable){

        } catch (Exception $exception){

        }
    }
    return $categories;
}




/**
 * This is used by the assign submission form to add a basic scan to the scan system for that user/group
 *
 * @param $cmid
 * @param $userid
 * @return bool
 */
function local_qmcw_coversheet_insert_scan($params){
    global $DB;

    try {
        //TODO: update using is_scanned function below, refactor using Barcode obj
        $conditions = array('userid' => $params->userid, 'cmid' => $params->cmid);
        $count = $DB->count_records('local_qmcw_scan', $conditions);
        if($count >= 1){
            //only one rec with cmid and user id pair can exist
            return false;
        }

        $result = $DB->insert_record('local_qmcw_scan', $params);
    } catch (Error $error){

    } catch (Throwable $throwable){

    } catch (Exception $exception){

    }
    return $result;

}


/**
 * @param $data
 * @return mixed
 */
function local_qmcw_coversheet_is_scanned($data){
    global $DB;

    if($data->usertype == "G"){
        $params = array('groupid' => (int) $data->scan->groupid, 'cmid' => (int) $data->scan->cmid);
    }
    else{
        $params = array('userid' => (int) $data->scan->userid, 'cmid' => (int) $data->scan->cmid);
    }

    $result = $DB->count_records('local_qmcw_scan', $params);

    if($result === 1){
        return $DB->get_record('local_qmcw_scan', $params);
    }

    return false;
}




/**
 * Splits barcode string, pre-processes for database entry
 *
 * @param $data
 * @return stdClass
 *
 */
function local_qmcw_coversheet_prepare_scan(&$data){
    global $USER, $DB;

    $barcodeobj = $data->barcodeobj;
    $now = time();

    //set up defaults
    $scan = new stdClass();
    $scan->userid = null;
    $scan->groupid = null;
    $scan->cmid = null;
    $scan->timescanned = $now;
    $scan->scanuserid = $USER->id;
    $scan->timesubmitted = null;
    $scan->timeupdated = null;

    //TODO: "DamianH" : are these used? they are in barcode obj
    $data->scanreference = $barcodeobj->identifier;
    $data->usertype = $barcodeobj->groupmode;

    $scan->cmid = $barcodeobj->cmid;
    $scan->userid = $barcodeobj->userid;
    $scan->groupid = $barcodeobj->groupid;

    $duedate = local_qmcw_coversheet_get_assign_user_duedate($scan->cmid, $scan->userid);

    if (isset($data->scandate) && ($data->scandate > 0)){
        //user has enabled scandate and supplied a non-default scan date
        $scan->timesubmitted = $data->scandate;
    }
    else {
        if($duedate > $now){
            //scan submission before duedate - why not record the scanned time
            $scan->timesubmitted = $now;
        }
        else{
            //scanned after the due date so default to duedate for admins' convenience
            $scan->timesubmitted = $duedate;
        }
    }

    $data->scan = $scan;
}

/**
 * Handles the extension date if required
 *
 * @param $cmid
 * @param $userid
 * @return int
 *
 *
 */
function local_qmcw_coversheet_get_assign_user_duedate($cmid, $userid){
    global $DB;

    $duedate = 0;

    try {
        $cm = get_coursemodule_from_id('',  $cmid, $courseid=0, $sectionnum=false, MUST_EXIST);
        $assign = $DB->get_record( 'assign' ,array('id'=> $cm->instance));
        $userflag = $DB->get_record('assign_user_flags', array('userid' => $userid, 'assignment' => $assign->id));

    }
    catch (Error $error){
        // Error should be used to represent coding issues that require the attention of a programmer.
    } catch (Throwable $throwable){
        // PHP 7 - Throwable should be used for conditions that can be safely handled at runtime where another action can be taken and execution can continue.
    } catch (Exception $exception){
        // PHP 5.6 Exception should be used for conditions that can be safely handled at runtime where another action can be taken and execution can continue.
    }


    if(isset($userflag->extensionduedate) && ($userflag->extensionduedate !== 0)){
        $duedate = $userflag->extensionduedate;
    }
    else if(isset($assign->duedate) && ($assign->duedate !== 0)){
        $duedate = $assign->duedate;
    }

    return $duedate;
}


/**
 * @param $data
 * @return stdClass
 */
function local_qmcw_coversheet_prepare_scan_update_record(&$data){
    global $USER;

    $updaterecord = new stdClass();
    $updaterecord->id = null;
    $updaterecord->scanuserid = null;
    $updaterecord->timescanned = time();
    $updaterecord->timesubmitted = null;
    $updaterecord->cmid = null;
    $updaterecord->userid = null;
    $updaterecord->groupid = null;
    $updaterecord->timeupdated = time();

    $updaterecord->id = $data->currentscan->id;
    $updaterecord->scanuserid = $USER->id;
    $updaterecord->cmid = $data->currentscan->cmid;
    $updaterecord->userid = $data->currentscan->userid;
    $updaterecord->groupid = $data->currentscan->groupid;

    $updaterecord->timesubmitted = $data->currentscan->timesubmitted;
    if(property_exists($data, 'updatesubmittime')){
        $updaterecord->timesubmitted  = $data->updatesubmittime;
        if($data->updatesubmittime == 0){
            $updaterecord->timesubmitted = $data->currentscan->timesubmitted;
        }
    }

    $data->scanupdaterecord = $updaterecord;
}

/**
 * This just sets the correct object names for database insertion
 * for the update log table. It uses the values from the currentscan
 * object before it is over-written by the updated data.
 *
 * @param $data
 * @return stdClass
 */
function local_qmcw_coversheet_prepare_update_log_entry(&$data){
    global $USER;

    $updatelog = new stdClass();
    $updatelog->scanid = null;
    $updatelog->scanuserid = null;
    $updatelog->timescanned = null;
    $updatelog->timesubmitted = null;
    $updatelog->updatenote = null;

    $updatelog->scanid = $data->currentscan->id;

    $updatelog->updatenote = $data->updatesubmissionnote;
    if(is_null($updatelog->updatenote)){
        $updatelog->updatenote = '';
    }

    //record the submit date of the current scan
    $updatelog->timesubmitted = $data->currentscan->timesubmitted;

    //previous time it was scanned
    $updatelog->timescanned = $data->currentscan->timescanned;

    //set update scan to current user
    $updatelog->scanuserid = $data->currentscan->scanuserid;

    $data->logentry = $updatelog;


}







/**
 * Test Cases:
 * 1. no submission entry in db - works fine no 'new' entry presupposed
 * 2. team submission - uses group id instead of uesrid?
 * 3. what happens when the assign is past its due date?
 * 4. always use scan update system to record dates of submission over the assign submission
 *
 *
 * @param $cmid
 * @param $userid
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 *
 *
 */
function local_qmcw_coversheet_save_submission($cmid, $userid, $scanid, $barcodeobj){
    global $CFG, $USER;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');
    require_once($CFG->dirroot . '/mod/assign/submission_form.php');

    $assign = $barcodeobj->getAssign();


    //permissions check
    // this should be required at the top of the scan form require_sesskey();
    //to avoid cc forgeries.

    $data = new stdClass();
    $data->id = $cmid;
    $data->userid = $userid;
    $data->scanid = $scanid;
    $data->action = 'savesubmission';
    $data->submitbutton = "Save changes";
    // Check that no one has modified the submission since we started looking at it.
    // This helps deals with delays in submitting the form mostly used for teamsubmission
    // This will not be required here as scans are instantaneous on submit of the form
    // scan cannot be submitted in by multiple users in teams anyway. One scan = one submission.
    $data->lastmodified = time();

    $notices = "";

    //note that this can return print_error notices
    return $assign->save_submission($data, $notices);

}


/**
 * @param $userid
 * @return bool|mixed
 */
function local_qmcw_coursework_get_user_by_id($userid){

    global $DB;

    try {
        return $DB->get_record('user', array('id' => $userid));
    } catch (Error $error){

    } catch (Throwable $throwable){

    } catch (Exception $exception){

    }

    return false;
}

/**
 * Use this to purge scan data fromthe database
 * For instance when an assign has been set to draft
 *
 */
function local_qmcw_coversheet_delete_scan_data($userid, $cmid){
    global $DB;

    try {
        $params = array('userid' => $userid, 'cmid' => $cmid);
        $scan = $DB->get_record('local_qmcw_scan', $params, 'id');
        if(isset($scan->id)){
            $DB->delete_records('local_qmcw_update_log', array('scanid' => $scan->id));
            return $DB->delete_records('local_qmcw_scan',  array('id' => $scan->id));
        }

    } catch (Error $error){
            // Handle error
    } catch (Throwable $throwable){

    } catch (Exception $exception){
       // Executed only in PHP 5, will not be reached in PHP 7
    }
    return false;
}

/**
 * @param $updategrade
 * @param $currentgrade
 * @return bool
 */
function local_qmcw_coversheet_is_grade_update($updategrade, $currentgrade){
    $updategrade = number_format((float) $updategrade, 5);
    $currentgrade = number_format((float) $currentgrade, 5);

    if($updategrade == $currentgrade){
        return false;
    }
    return true;

}


function local_qmcw_coversheet_save_grade($data){
    //TODO: "DamianH" : for group submissions remember applytoall
    //prepare data object
    $barcodeobj = $data->barcodeobj;
    $userid = $barcodeobj->userid;

    $gradedata = new stdClass();
    $gradedata->grade = floatval($data->updategrade);
    $gradedata->assignfeedbackcomments_editor = array('text' => '', 'format' => 1);
    $gradedata->editpdf_source_userid = $userid;
    $gradedata->id = $barcodeobj->cmid;
    $gradedata->ajax = 0;
    $gradedata->addattempt = 0;
    $gradedata->userid = 0;
    $gradedata->sendstudentnotifications = 1;
    $gradedata->action = "submitgrade";
    $gradedata->applytoall = 0; //empty
    $gradedata->attemptnumber = -1;


    if($barcodeobj->groupmode == 'G'){
        //do something to applytoall
    }

    $assign = $barcodeobj->getAssign();
    $submission = $assign->get_user_submission($userid, false);
    if($submission->latest == 1){
        $gradedata->attemptnumber = $submission->attemptnumber;
    }

    return $assign->save_grade($userid, $gradedata);

}


/**
 * @param $number
 * @return bool|string
 */
function local_qmcw_coversheet_number_format($number){
        return number_format((float) $number, 2, '.', '');
}


/**
 * @param $submissiontime
 * @param $barcodeobj
 * @param $userid
 * @return bool
 *
 */
function local_qmcw_coversheet_set_assignsubmission_submission_time($submissiontime, $barcodeobj, $userid){
    global $DB;

    $assign = $barcodeobj->getAssign();
    $submission = $assign->get_user_submission($userid, false);
    if($submission){
        $submission->timemodified = $submissiontime;
    }

    try {
        return $DB->update_record('assign_submission', $submission);
    } catch (Error $error){
            // Handle error
    } catch (Throwable $throwable){

    } catch (Exception $exception){
       // Executed only in PHP 5, will not be reached in PHP 7
    }
    return false;
}



/**
 * Class Barcode
 * Handles the barcode and all its related properties.
 *
 */
class Barcode {

    public $barcode = '';

    public $cmid;
    public $userid = null;
    public $groupid = null;
    public $groupmode;

    public $givenbarcode;

    public $separator = '-';
    public $identifier = 'QM+CW';

    public $syntax_valid = false;
    public $is_valid = false;
    public $is_abbr = false;

    public $is_already_scanned = false;
    public $placeholderbarcode = false;

    public $assigninstance = null;
    public $assigngradevalue = null;



    public function __construct($barcode = false, $placeholder = false, $cmid = false, $uid = false)
    {

        //set barcode property
        if($barcode){
            $this->givenbarcode = trim($barcode);
            $this->barcode = $this->givenbarcode;
        }
        else if ($cmid && $uid){
            $this->barcode = $this->getBarcodeString($cmid, $uid);
        }
        else if ($placeholder){
            $this->placeholderbarcode = $placeholder;
            $this->givenbarcode = $placeholder;
            $this->barcode = $placeholder;
        }

        //set object properties
        if($this->barcode){
            $this->setSyntaxValid($this->barcode);
            $this->setBarcodeProps($this->barcode);
            $this->setIsAlreadyScanned($this->cmid, $this->userid, $this->groupid);
            $this->setIsValid();
            $this->assigngradevalue = $this->getAssignGradeValue();
        }
        else{
            $this->is_valid = false;
            $this->syntax_valid = false;
            $this->state = 'cancel';
        }




    }


    public function getBarcodeString($cmid, $userid){

        $barcodestr = '';
        $group_flag = '';
        $participants = local_qmcw_coursework_get_module_group_participants($userid, $cmid,$cmid);

        if( count($participants) > 0 ){

            $group_flag .=  ( $participants[1]->teamsubmission == "0") ? 'S' : 'G';
            $barcode_identifier = local_qmcw_coversheet_get_barcode_identifier();
            $separator = local_qmcw_coversheet_get_barcode_separator();
            $group_id = str_pad($participants[1]->barcodegroup, 10, '0', STR_PAD_LEFT);
            $cmid = str_pad($cmid, 10, '0', STR_PAD_LEFT);
            $barcodestr = $barcode_identifier . $separator . $group_flag . $separator . $group_id . $separator . $cmid;
        }

        return $barcodestr;
    }



    /**
     * @return mixed
     */
    public function getBarcode()
    {
         return $this->barcode;
    }

    /**
     * @param mixed $barcode
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;
    }

    /**
     * @return mixed
     */
    public function getCmid()
    {
        return $this->cmid;
    }

    /**
     * @param mixed $cmid
     */
    public function setCmid($cmid)
    {
        $this->cmid = $cmid;
    }


    /**
     * @return mixed
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * @param null $userid
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;
    }

    /**
     * @return null
     */
    public function getGroupid()
    {
        return $this->groupid;
    }

    /**
     * @param null $groupid
     */
    public function setGroupid($groupid)
    {
        $this->groupid = $groupid;
    }

    /**
     * @param mixed $cmid
     */
    protected function setBarcodeProps()
    {

        list($barcode_identifier, $barcode_group_mode , $barcode_group_id, $barcode_coursework_id ) = preg_split( '/'.$this->separator.'/' ,$this->barcode , 4);
        $this->groupmode = $barcode_group_mode;
        $this->cmid = (int) $barcode_coursework_id;
        if($barcode_group_mode == 'G'){
            $this->groupid = (int) $barcode_group_id;
        }
        else{
            $this->userid = (int) $barcode_group_id;
        }

    }


    /**
     * @param bool $is_valid
     */
    public function setIsValid()
    {
        if(local_qmcw_coversheet_check_barcode_values($this->groupmode, $this->userid, $this->cmid)){
            $this->is_valid = true;
        }
    }



    /**
     * @return mixed
     */
    public function setSyntaxValid($barcode)
    {
        $pattern  = '/^(QM\+CW\-)?(S|G|s|g)\-[0-9]{0,10}\-[0-9]{0,10}$/';
        $match = preg_match($pattern,  $barcode);
        if($match) {
            $this->syntax_valid = true;
            if (strpos($barcode, $this->identifier) === false) {
                $this->is_abbr = true;
                $this->setBarcode(strtoupper($this->identifier . "-" . $this->barcode));
            }
        }
    }


    /**
     * @return bool
     */
    public function isAlreadyScanned()
    {
        return $this->is_already_scanned;
    }

    /**
     *
     *
     * @param $cmid
     * @param $userid
     * @param $groupid
     */
    public function setIsAlreadyScanned($cmid, $userid, $groupid){
        global $DB;

        //TODO : update this using local_qmcw_coversheet_check_barcode_values
        $params = array(
            'cmid' => $cmid,
            'userid' => $userid,
            'groupid' => $groupid,
        );

        $result = $DB->count_records('local_qmcw_scan', $params);

        if($result === 1){
            $this->is_already_scanned = true;
        }
    }

    /**
     * @return bool
     */
    public function isPlaceholderbarcode()
    {
        return $this->placeholderbarcode;
    }

    /**
     * @param bool $placeholderbarcode
     */
    public function setPlaceholderbarcode($placeholderbarcode)
    {
        $this->placeholderbarcode = $placeholderbarcode;
    }


    /**
     * @return Assign obj
     *
     */
    public function getAssign(){
        global $CFG;

        $cmid = $this->cmid;
        if($cmid == null){
            return false;
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        //permissions check
        // this should be required at the top of the scan form require_sesskey();
        //to avoid cc forgeries.

        //get assign
        list($course, $coursemodule) = get_course_and_cm_from_cmid($cmid, 'assign');
        $coursemodulecontext = context_module::instance($cmid);

        $assign = new assign($coursemodulecontext, $coursemodule, $course);

        return $assign;

    }


    /**
     * @return int
     *
     */
    public function getAssignId(){

        $assign = $this->getAssign();
        $instance = $assign->get_instance();
        return $instance->id;
    }

    /**
     * @param null $assigninstance
     */
    public function setAssigninstance($assigninstance)
    {
        $this->assigninstance = $assigninstance;
    }


    public function getAssignGrades(){

        $assign = $this->getAssign();
        $userid = $this->getUserid();

        if($assign == null || $userid == null){
            return false;
        }

        $grades = $assign->get_user_grade($userid, false);

        return $grades;
    }


    /**
     * @return null
     */
    public function getAssignGradeValue()
    {
        $assigngrade = null;
        $assigngrade = $this->getAssignGrades();

        if(isset($assigngrade->grade)){
            $grade = $assigngrade->grade;
            if($grade == "-1.00000"){
                return 0;
            }
            if($grade >= 0){
                return $grade;
            }
        }

        return 0;
    }


    /**
     * @param null $assigngradevalue
     */
    public function setAssignGradeValue($assigngradevalue)
    {
        $this->assigngradevalue = $assigngradevalue;
    }



}