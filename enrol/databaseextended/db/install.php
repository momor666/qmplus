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
 * Database enrolment plugin installation.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_enrol_databaseextended_install() {

    global $CFG;

    // Include all the tables. Must be at the bottom so that we have all the base classes first.
    require_once($CFG->dirroot.'/enrol/databaseextended/lib.php');

    $directory = $CFG->dirroot.'/enrol/databaseextended/tables';
    foreach (glob($directory."/*.php") as $filename) {
        require_once($filename);
        $bits = explode('.', $filename);
        $bits = explode('/', $bits[count($bits)-2]);
        $tablename = array_pop($bits);
        /* @var enrol_databaseextended_table_base $classname */
        $classname = 'enrol_databaseextended_'.$tablename.'_table';
        $classname::install();
    }
}
