<?php
/**
* Version details
*
* @package    reportsdash
* @copyright  2013 ULCC, University of London
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

function xmldb_block_qmplus_course_overview_uninstall() {
   global $DB;

   // Switch the normal course_overview block back on
   $DB->set_field('block', 'visible', '1', array('name' => 'course_overview'));

   return true;
}
