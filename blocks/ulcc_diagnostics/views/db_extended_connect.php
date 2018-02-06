<?php

/**
 * Moodle ULCC Admin page.
 *
 * @package    block_ulcc_diagnostic
 * @copyright  2011 onwards ULCC (http://www.ulcc.ac.uk)
 *
 */

require('../../../config.php');
//require_once("$CFG->libdir/pluginlib.php");

$p= (core_plugin_manager::instance()->get_plugins());


$string=get_strings(array('connection','dbhost','dbname','dbtype','dbuser','duration',
                          'lastsync','lastsyncstatus','noconnection','noentry','noresults',
                          'notrun','settings','dbextnotfound','goodcolour','warncolour',
                          'badcolour','dbextmainheading','dbconnection'),'block_ulcc_diagnostics');

if(!isset($p['enrol']['databaseextended']))
{
   print_error($string->dbextnotfound);
   exit;
}

//Colour codes for sync results
$leveloptions=array($string->goodcolour,$string->warncolour,$string->badcolour);

global $CFG,$USER,$DB;
require_once($CFG->dirroot.'/lib/adodb/adodb.inc.php');

$showrecs=optional_param('showrecs',10,PARAM_INT);

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/blocks/ulcc_diagnostics/views/db_connect.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_course($SITE);
$PAGE->set_pagetype('category-upload');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('report');
$PAGE->navbar->add($string->dbconnection);
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading($string->dbextmainheading);
echo $OUTPUT->box_start('generalbox dbdiag');

echo $OUTPUT->heading($string->settings);

$dbhost = get_config('enrol_databaseextended','dbhost');
$dbuser = get_config('enrol_databaseextended','dbuser');
$dbpass = get_config('enrol_databaseextended','dbpass');
$dbname = get_config('enrol_databaseextended','dbname');
$dbtype = get_config('enrol_databaseextended','dbtype');


$debugfile='';
$debugfilename=trim(get_config('enrol_databaseextended','logfile'));
   if (file_exists($debugfilename)) {
      $debugfile = file_get_contents($debugfilename);
   }

foreach(array('dbtype','dbhost','dbuser','dbname',) as $field)
{
   echo $string->$field;
   if (!get_config('enrol_databaseextended',$field)) {
      echo "<a href='$CFG->wwwroot/admin/settings.php?section=enrolsettingsdatabase' style='color:$string->badcolour'>$string->noentry</a>";
   }else{
      echo '<span style="color:green">'.get_config('enrol_databaseextended',$field).'</span>';
   }
   echo '<br />';
}

echo $string->lastsync;
if(!$sync=get_config('enrol_databaseextended','sync_results')) {
	echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:$string->badcolour">'.$string->notrun.'</a>';
}else{
   $sync=unserialize($sync);
   echo '<span style="color:green">'.userdate(reset($sync)->start).'</span>';

   print '<br/>';

   echo $string->lastsyncstatus;
   $syncresult=get_string('statusline'.($level=array_reduce($sync,function ($worst,$item){$worst=max($worst,$item->result); return $worst;},0)),'enrol_databaseextended');

   echo "<span style='color:{$leveloptions[$level]}'>$syncresult</span>";
}

echo '<br />';

$extdb = ADONewConnection($dbtype);
if ($dbhost && $dbuser && $dbpass && $dbname) {
   try {
      $ok = true;
      $extdb->Connect($dbhost, $dbuser, $dbpass, $dbname, true);
   } catch (Exception $e) {
      $ok = false;
      echo '<a href="' . $CFG->wwwroot . '/admin/settings.php?section=enrolsettingsdatabase" style="color:$string->badcolour">' . $string->noconnection . '</a>';
      echo '<p>' . $extdb->ErrorMsg() . '</p>';
   }


   if ($ok) {
      $extdb->debug = true;
      $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
      $extdb->connectSID = true;

      echo '<span style="color:green">' . $string->connection . '</span>';
   }

   echo '<br />';

   print "<form method='get'><P>Show: <input type='text' size=4 value='$showrecs' name='showrecs'> records";
   print "<P><input type='submit' value='change'></form>";

   $externaltables = $DB->get_records_sql('select distinct maintable,secondarytable from {databaseextended_relations} where internal=0');

   if (!empty($sync)) {
      $tables = array_merge(array_keys($sync), array_keys($externaltables));
   } else {
      $tables = array_keys($externaltables);
   }

//Remove duplicates and sort
   $tables = array_flip(array_flip($tables));

   foreach ($tables as $table) {
      echo $OUTPUT->heading($table);

// 1. There is an external table with no sync data
// 2. There is sync data but no external table
// 3. There is an external table with sync data
      if (isset($externaltables[$table]->secondarytable)) //#1 or #3. Either way we try to display external table
      {
         echo $string->lastsyncstatus;

         if (isset($sync[$table]->result)) //#3
         {
            $syncresult = get_string('statusline' . ($level = $sync[$table]->result), 'enrol_databaseextended');
            print "<span style='color:{$leveloptions[$level]}'>$syncresult</span></br>";

            print $string->lastsync;
            print userdate($sync[$table]->start) . "<br>";

            $duration = $sync[$table]->time;
            $unit = get_string('seconds');
            if ($duration > 60) {
               $duration /= 60;
               $unit = get_string('minutes');
            }
            if ($duration > 60) {
               $duration /= 60;
               $unit = get_string('hours');
            }
            print $string->duration . ' ' . round($duration, 2) . " $unit<br>";
         } else //#1
         {
            echo "<span style='color:$string->badcolour'>" . $string->notrun . "</span><br>";
         }

         $result = '';
         try {
            $result = $extdb->SelectLimit("SELECT * FROM {$externaltables[$table]->secondarytable}", $showrecs, 0);
            ($resultCount = $extdb->GetOne("SELECT count(*) FROM {$externaltables[$table]->secondarytable}") or $resultCount = 0);
         } catch (Exception $e) {
            $result = '';
         }

         if ($result) {
            if ($table == 'role_assignments') {
               print '<p>' . get_string('remoteentries', 'block_ulcc_diagnostics', $resultCount) . '<br>';
               ($local = $DB->count_records($table, array('component' => 'enrol_databaseextended')) or $local = 0);
               print '<p>' . get_string('localentries', 'block_ulcc_diagnostics', $local) . '<br>';
            } elseif ($table == 'xxxxuser_enrolments') {
               print '<p>' . get_string('remoteentries', 'block_ulcc_diagnostics', $resultCount) . '<br>';
               ($local = $DB->count_records_sql("select count(*) FROM {enrol} e
                                                            JOIN {user_enrolments} ue ON e.id=ue.enrolid
                                                            WHERE e.enrol='databaseextended'") or $local = 0);
               print '<p>' . get_string('localentries', 'block_ulcc_diagnostics', $local) . '<br>';
            }

            echo '<span style="color:green">';
            echo '<table>';
            echo '<tr>';
            foreach (array_keys($result->fields) as $key) {
               echo "<th>$key</th>";
            }
            echo '</tr>';
            while (!$result->EOF) {
               echo '<tr>';
               foreach (array_keys($result->fields) as $key) {
                  echo '<td>' . $result->fields[$key] . '</td>';
               }
               $result->MoveNext();
               echo '</tr>';
            }
            echo '</table></span>';
         } else {
            echo '<span style="color:$string->badcolour">' . $string->noresults . ': ' . $extdb->ErrorMsg() . '</span>';
         }
      } else //#2
      {
         $l = new stdClass;
         echo $string->lastsyncstatus;
         $syncresult = get_string('statusline2', 'enrol_databaseextended');
         print "<span style='color:$string->badcolour'>$syncresult</span></br>";

         $date = userdate($sync[$table]->start);
         print get_string('tablenotexist', 'block_ulcc_diagnostics', (array('date' => $date, 'table' => $table)));
      }
      echo '<br />';
   }
}
if($debugfile)
{
   print $OUTPUT->heading(get_string('previoussyncoutput','block_ulcc_diagnostics',2));
   print "<pre>$debugfile</pre>";
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
