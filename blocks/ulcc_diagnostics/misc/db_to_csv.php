<?php
require('../../../config.php');
global $CFG,$USER,$DB;
require_once($CFG->dirroot.'/lib/adodb/adodb.inc.php');

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));


$host = get_config('enrol_database','dbhost');
$user = get_config('enrol_database','dbuser');
$pass = get_config('enrol_database','dbpass');
$db = get_config('enrol_database','dbname');
$table = $_GET['table'];
$file = $table;

$csv_output = '';

if(!$table) {
    echo 'Please include ?table={table} to URL';
}else{
    $extdb = ADONewConnection(get_config('enrol_database','dbtype'));
    
    if($extdb->Connect(get_config('enrol_database','dbhost'), get_config('enrol_database','dbuser'), get_config('enrol_database','dbpass'), get_config('enrol_database','dbname'), true)) {
        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $extdb->Execute("SELECT * FROM ".$table);
        $i = 0;
        if ($result->RecordCount() > 0) {
            foreach (array_keys($result->fields) as $key) {
                $csv_output .= $key."; ";
                $i++;
            }
        }
        $csv_output .= "\n";
        
        while (!$result->EOF) {
            foreach(array_keys($result->fields) as $key) {
                $csv_output .= $result->fields[$key]."; ";
            }
        $csv_output .= "\n";
        $result->MoveNext();
        }
        
        $filename = $file."_".date("Y-m-d_H-i",time());
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: csv" . date("Y-m-d") . ".csv");
        header( "Content-disposition: filename=".$filename.".csv");
        print $csv_output;
        exit;
    }else{
        print $extdb->ErrorMsg();
    }
}
?>