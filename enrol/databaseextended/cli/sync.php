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
 * CLI sync for full external database synchronisation.
 *
 * Sample cron entry:
 * # 5 minutes past 4am
 * 5 4 * * * $sudo -u www-data /usr/bin/php /var/www/moodle/enrol/databaseextended/cli/sync.php
 *
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - you need to change the "www-data" to match the apache user account
 *   - use "su" if "sudo" not available
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
$under_windows=(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

if($under_windows)
{
   $lockfile=dirname(__FILE__).'/databaseextended.sync.lck';
}
else
{
   $lockfile='/tmp/databaseextended.sync.lck';
}

require(dirname(__FILE__).'/../../../config.php');

if (!enrol_is_enabled('databaseextended')) {
   print 'enrol_databaseextended plugin is disabled, sync is disabled';
   exit(1);
}

if(file_exists($lockfile))
{
   $pid=trim(file_get_contents($lockfile));
   if(file_exists("/proc/$pid"))
   {
      print "Previous incarnation detected. If this is incorrect, manually remove file $lockfile";
      exit(2);
   }
   else
   {
      print "Clearing old lockfile\n";
      (unlink($lockfile) or exit(3));
   }
}

file_put_contents($lockfile,getmypid());

$logfile=trim(get_config('enrol_databaseextended','logfile'));
$emails=trim(get_config('enrol_databaseextended','syncemail'));

$start=time();

if ((float)substr($CFG->release, 0, 5) > 2.6) { // 2.8 > 2.6
    $event = \enrol_databaseextended\event\sync_started::create(array('context' => context_system::instance()));
    $event->trigger();
} else {
    add_to_log(SITEID, 'databaseextended', 'mail', '', "Starting database sync");
}




if($w=fopen($logfile,'w'))
{
   if(!$under_windows)
   {
      fclose($w);
//Redirect following output to log file
//Note that this code fails on Windows.
//So, don't use Windows. Duh.
      fclose(STDIN);
      fclose(STDOUT);
      fclose(STDERR);
      $STDIN = fopen('/dev/null', 'r');
      $STDERR=$STDOUT = fopen($logfile,'w');
   }
}
else
{
   print "Logfile $logfile is not writable for database extended sync\n";
   if($emails)
   {
      print "Forward this to one or all of these addresses: $emails\n";
   }
}

/* @var enrol_databaseextended_plugin $enrol */
   $enrol = enrol_get_plugin('databaseextended');

   $tables = array('course_categories',
                   'course',
                   'enrol',
                   'user',
                   'role',
                   'user_enrolments',
                   'role_assignments',
                   'context',
                   'groups',
                   'groups_members');
$enrol->start_timer();

$fail=$enrol->speedy_sync($tables);
$enrol->end_timer();

unlink($lockfile);

purge_all_caches();

if($fail and $w)
{
   $message=file_get_contents($logfile);

   if($mail=get_mailer())
   {
      $mail->Subject='Database Extended Sync log';
      $mail->From=$CFG->noreplyaddress;
      $mail->FromName='DB Ext sync job';
      $mail->IsHTML=false;
      $mail->Body="\n$message\n";
      foreach(explode(',',$emails) as $recip)
      {
         $mail->AddAddress($recip);
      }
      if($mail->Send())
      {
          $event = \enrol_databaseextended\event\email_sent::create(array('other' => $emails,
                                                                          'context' => context_system::instance()));
          $event->trigger();
      }
      else
      {
          $event = \enrol_databaseextended\event\email_failed::create(array('other' => $emails,
                                                                            'context' => context_system::instance()));;
          $event->trigger();
      }
   }
   else
   {
       print "Alas, no mailer found for my DB extended sync data. I die as I have lived - alone in a small server box. Alas and alack!\n";
       $event = \enrol_databaseextended\event\email_failed::create(array('other' => $emails,
                                                                         'context' => context_system::instance()));
       $event->trigger();
   }
}

$duration=time()-$start;
$event = \enrol_databaseextended\event\sync_finished::create(array('context' => context_system::instance()));
$event->trigger();