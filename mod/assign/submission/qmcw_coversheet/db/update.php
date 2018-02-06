<?php

/**
 * Upgrade code for install
 *
 * @package   assignsubmission_qmcw_coversheet
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * upgrade this assignment instance - this function could be skipped but it will be needed later
 * @param int $oldversion The old version of the assign module
 * @return bool
 */


function xmldb_assignsubmission_qmcw_coversheet_upgrade($oldversion){
    global $DB;
    $dbman = $DB->get_manager();

    // Add the submissiondate column
    if($oldversion < 2017110103){

        // Define field submissiondate to be added to assignsubmission_coversheet.
        $table = new xmldb_table('assignsubmission_coversheet');
        $field = new xmldb_field('submissiondate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'scanid');

        // Conditionally launch add field submissiondate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Qmcw_coversheet savepoint reached.
        upgrade_plugin_savepoint(true, 2017110103, 'assignsubmission', 'qmcw_coversheet');
    }



}