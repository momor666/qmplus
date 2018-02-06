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
// No direct script access.
defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    $settings->add( new admin_setting_configtextarea(
    	'block_assessment_information/allowedcolors', 
    	new lang_string('allowedcolorslabel', 'block_assessment_information'),
    	new lang_string('allowedcolorsdesc', 'block_assessment_information'),
    	new lang_string('defaultallowedcolors', 'block_assessment_information'),
    	PARAM_RAW
    ));
    global $DB;
    $themes = $DB->get_records_select(
    	'course_categories',
    	'parent = 0 AND theme IS NOT NULL',
    	array(),
    	'',
    	'theme'
    );
    // foreach ($themes as $key => $theme) {
    // 	$settings->add( new admin_setting_configtextarea(
    // 		'block_assessment_information/resources_'.$key, 
    // 		'Default Resources for '.$key,
    // 		'Specify the pre configured resource for the theme',
    // 		'page=5;',
    // 		PARAM_RAW
    // 	));
    // }
}