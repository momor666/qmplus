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
 * Version details.
 *
 * @package    blocks
 * @subpackage assessment_information
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_course_resources($course){
	global $DB;
	$dbman = $DB->get_manager();

	$assignment_tables = array('assign','turnitintool');
	$assessment_tables = array('page');
	$resources = array();

	foreach ($assignment_tables as $table) {
		if($dbman->table_exists($table)){
			$result = $DB->get_records_sql("
				SELECT CONCAT('".$table."', id) as 'id' , id as 'itemid',
					course, '$table' as 'table','assign' as 'type', name
				FROM {". $table ."}
				WHERE course = ?
			", array($course->id));
			$resources = array_merge($resources, $result);
		}
	}

	foreach ($assessment_tables as $table) {
		if($dbman->table_exists($table)){
			$result = $DB->get_records_sql("
				SELECT CONCAT('".$table."', id) as 'id' , id as 'itemid',
					course, '$table' as 'table','page' as 'type', name
				FROM {". $table ."}
				WHERE course = ?
			", array($course->id));
			$resources = array_merge($resources, $result);
		}
	}
	
	var_dump($resources);
}