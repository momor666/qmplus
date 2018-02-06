<?php


require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(dirname(__FILE__).'/lib.php');

require_login();

if (!has_capability('report/qmplus:view', context_system::instance())) {
    header('HTTP/1.1 403 Forbidden');
    die();
}

$option = $_REQUEST['val'];
$report = $_REQUEST['report'];
$datefrom = $_REQUEST['datefrom'];
$dateto = $_REQUEST['dateto'];

$option = str_replace("/", "-",$option);
$option = str_replace(".", "",$option);

$filename = ($option != '') ? $report . '-' . $option : $report;

// Create dir to store temp  report files.
$myDir = $CFG->dataroot . '/qmplus_reports';
if (!is_dir($myDir)) {
    mkdir($myDir, 0777, true); // true for recursive create
}

$zipname = $myDir.'/'.$filename.'.zip';

ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.
header("Content-type: application/zip");
header("Content-Disposition: attachment; filename=$filename.zip");

$zip = new ZipArchive;
$zip->open($zipname, ZipArchive::CREATE);

switch ($report) {
    case "mime":
        $csvFiles = report_qmplus_generate_mime_report($option,$datefrom,$dateto);
        break;
    case "blacklisted":
        $csvFiles = report_qmplus_generate_blacklisted_report($option,$datefrom,$dateto);
        break;
    case "coursebackup":
        $csvFiles = report_qmplus_generate_backup_report($datefrom,$dateto);
        break;
    case "coursefiles":
        $csvFiles = report_qmplus_generate_course_report($option,$datefrom,$dateto);
        break;
    case "configlog":
        $csvFiles = report_qmplus_generate_configlog_report($datefrom,$dateto);
        break;


}

$unlinkFiles = array();

foreach ($csvFiles as $file) {

    $csv = $myDir.'/'.$file['filename'];
    //$f = fopen($csv, 'w');
    $f = fopen($csv, 'w');

    foreach ($file['data'] as $fields) {
        fputcsv($f, $fields);
    }

    fclose($csv, 'w');
    //close the file
    $zip->addFile($csv, $file['filename']);

    $unlinkFiles[] = $csv;

}

// close the archive
$zip->close();

header("Content-length: " . filesize($zipname));
header("Pragma: no-cache");
header("Expires: 0");

ob_clean();
flush();
readfile($zipname);
@unlink($zipname);

// Delete temp csv files after zip close
foreach ($unlinkFiles as $csv) {
    unlink($csv);
}
