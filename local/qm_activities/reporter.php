<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 26/06/2017
 * Time: 11:38
 * QM+ Activities reporting plugin
 */

/** @noinspection UntrustedInclusionInspection */
require_once '../../config.php';
/** @noinspection UntrustedInclusionInspection */
require_once  './locallib.php';
$urlparams  = array();
require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_url(__DIR__ . '/' . $string_reporter, $urlparams);
$PAGE->set_title( $string_report_page_title );
# $PAGE->set_course($SITE);
$PAGE->requires->jquery();
// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);
$error = null;
if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}

if(isguestuser($USER->id)){
    $urltogo= $CFG->wwwroot.'/';
    redirect($urltogo);
}

$mode   = optional_param('mode','', PARAM_ALPHA);
$id     = optional_param('id',0, PARAM_INT);
$from   = optional_param('from','', PARAM_ALPHANUMEXT);
$to     = optional_param('to','', PARAM_ALPHANUMEXT);

$from   = strtotime($from);
$to     = strtotime($to .' +1 day -1 second');

if( ! in_array($mode,array('school','category','course','teacher','student'))){
    $error = $string_report_error;
    $id = 0;
    $from = 0;
    $to = 0;
} else {
    try {
        if(gettype($from)== 'string'){
            $from   = strtotime( $from  );
        }
        if(gettype($to) == 'string'){
            $to     = strtotime( $to );
        }

    } catch (Error $error){

    } catch (Throwable $throwable){

    } catch (Exception $exception){

    }
}

if((int)$from == 0 || (int)$to == 0 || (  (int)$from >  (int)$to ) ){
    $range = local_qm_activities_get_timestamp_range(time(),'acyear');
    (int)$from == 0 ? $from = $range['from'] : $from ;
    (int)$to <= (int)$from ? $to = $range['to'] : $to ;
}

// Connection creation
$cacheAvailable = false;
if(extension_loaded('Memcache') ){
    $memcache = new Memcache;
    @$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
}

echo $OUTPUT->header();

if( isset( $mode ) ){
    $allow_exec = false;
    echo '<h2>'.$string_report_page_title.'</h2><br/>';
    echo '<strong>'.html_writer::link( ( new moodle_url( $string_menu ) ) ,$string_back_to_menu.'</strong><br /><br /><br />');

    if( $error > ''){
        echo $error.'<br/>';
    }

    // test request permissions
    # $req_user_id = 7 ; // debug / development statement  // COURSE Administrator
    # $req_user_id = 240 ; // debug / development statement // SCHOOL Administrator
    #
    $req_user_id = (int) $USER->id ; // original statement
    $allow_exec = local_qm_activities_check_user_request_permissions( $req_user_id , $mode , $id , $from , $to );
    if($allow_exec) {
        $calendar = local_qm_activities_get_calendar($mode,$id,$from,$to);
        echo '<i>'.local_qm_activities_calendar_record( $req_user_id , $mode , $id , $from , $to ).'</i>';
        if($calendar){
            echo local_qm_activities_export_calendar_button($mode, $id, $from, $to);
            echo local_qm_activities_calendar_table($calendar, $from, $to, $mode);
            echo local_qm_activities_calendar_grid ($calendar, $from, $to);
        }
    } else {
        echo ''.local_qm_activities_calendar_record( $req_user_id , $mode , $id , $from , $to ).'';
        echo $string_request_not_permitted.'<br/> ';
    }
}
echo $OUTPUT->footer();
