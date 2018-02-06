<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 03/07/2017
 * Time: 08:56
 * QM+ Activities reporting plugin
 */

$err = false;
define('my_debg',false);
if( my_debg ){
    ini_set('display_errors',1);
    error_reporting(E_ALL);
}

/** @noinspection UntrustedInclusionInspection */
require_once '../../config.php';
/** @noinspection UntrustedInclusionInspection */
require_once  './locallib.php';
$urlparams = array();
$PAGE->set_url(__DIR__ . '/' . $string_form_export_calendar, $urlparams);
# $PAGE->set_url('./calendar_export.php', $urlparams);
$PAGE->set_context(context_system::instance());
// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);


$mode = required_param('mode',PARAM_ALPHA );
$id = required_param('id',PARAM_INT );
$from = required_param( 'from', PARAM_INT );
$to = required_param( 'to', PARAM_INT );
$err = ! in_array( $mode, array('school', 'category','course','teacher','student' )) || (int) $id < 1;
$permission = false;
if($err){
    echo $string_report_error;
} else {

// Connection creation
    $cacheAvailable = false;
    if(extension_loaded('Memcache') ){
        $memcache = new Memcache;
        @$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
    }

    $uid = (int)$USER->id ;

    $permission = local_qm_activities_get_report_permission( $uid, $id , true );
}
// records shown for adminitrators and teachers only, not to other students but themselves
$err = ! $permission;
// get the records only if permitted
if( ! $err  ){
    $calendar = local_qm_activities_get_calendar($mode,$id,$from,$to);
    $err = ! ($calendar);
}

if(! $err ){
// the iCal date format. Note the Z on the end indicates a UTC timestamp.
    define('DATE_ICAL', 'Ymd\THis\Z');
    define('DATE_HCAL', 'd M Y H:i:s');

// max line length is 75 chars. New line is \\n
    if( ! my_debg ){
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=qmplus_calendar_activities_'.$mode.'_'.$id.'_'.(date('d-M-Y',$from)).'_'.(date('d-M-Y',$to)).'.ics');
    }

    $location = 'Queen Mary University of London';
    $output = "BEGIN:VCALENDAR
METHOD:PUBLISH
VERSION:2.0
PRODID:-//Queen Mary//University of London//EN\r\n";

// loop over events
    foreach ($calendar as $id => $appointment):
        if($appointment['to'] != 'N/A'){
            $output .= PHP_EOL.'BEGIN:VEVENT
SUMMARY:'.($appointment['name']).'
UID:'.$id.'
DESCRIPTION: <a href="'. $appointment['module_url']->__toString(). '">'.$appointment['name'].'</a>'."\n".'
        <a href="'. $appointment['course_url']->__toString(). '">'.$appointment['coursename'].'</a>'."\n".'
        <a href="'. $appointment['category_url']->__toString(). '">'.$appointment['category'].'</a>'."\n" ;
            if(isset($appointment['school_url'])){
                $output .= '
        <a href="'. $appointment['school_url']->__toString(). '">'.$appointment['school'].'</a>'."\n";
            }
            $output .='
        From   ' . ($appointment['from'] != 'N/A' ? date(DATE_HCAL, $appointment['from']) : $appointment['from']) ."\n".'
        To     ' . ($appointment['to'] != 'N/A' ? date(DATE_HCAL, ($appointment['to'])) : $appointment['to'] ) ."\n".'
        Latest ' . ($appointment['latest'] != 'N/A' ? date(DATE_HCAL, ($appointment['latest'])) : $appointment['latest'] )."\n".'
DTSTART:' . date(DATE_ICAL, (int)($appointment['to']) - 7200 ) . '
DTEND:' . ($appointment['to'] != 'N/A' ? date(DATE_ICAL, ($appointment['to'])) : ($appointment['to'] )). '
LAST-MODIFIED:' . ($appointment['from'] != 'N/A' ? date(DATE_ICAL, $appointment['from']) : $appointment['from']). '
LOCATION:'. $location .' // '. $appointment['name'].'
DTSTAMP:'.date(DATE_ICAL, time()).'
ORGANIZER:QMUL
END:VEVENT
';
        }
    endforeach;

// close calendar
    $output .= "END:VCALENDAR\n";
    echo $output;

} else {
    echo $OUTPUT->header();
    echo '<strong>'.$string_report_error.'</strong>';
    echo $OUTPUT->footer();
}

