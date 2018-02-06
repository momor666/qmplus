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
//deny acess from direct url
defined('MOODLE_INTERNAL') || die();
//pluginname
$string['pluginname'] = 'Assessment Information';
//main block
$string['show_block_text'] = 'Show Assessment Information';
$string['hide_block_text'] = 'Hide Assessment Information';
$string['default_assessment_header'] = 'Assessment Overview';
$string['default_assignment_header'] = 'Submit Assignments';
$string['default_extra_section_header'] = 'Extra Section';
//settings form
$string['config_assessment_header'] = 'Assessment overview heading';
$string['config_assignment_header'] = 'Submit assignments heading';
$string['config_assignment_text'] = 'Assignments Text';
$string['config_enable_extra_section'] = 'Enable extra section';
$string['config_extra_section_header'] = 'Extra Section Heading';
$string['configure_subheadings'] = 'Assignment Subheadings';
$string['config_subheadings_title'] = 'Title';
$string['config_subheadings_text'] = 'Text';
$string['config_subheadings_background'] = 'Background Color';
$string['messages_category_courseid_error'] = 'Course doesnot exist.';
$string['heading_category_settings'] = 'Premix resources configuration for theme "{$a}"';
$string['label_courseid'] = 'Course to add premix resources';
$string['coursenotdefined'] = 'Please select a premix resources cointainer course for this theme first, to setup 
    			premix activities/resources.';
$string['premix_resourses_heading'] = 'Available premix resources';
$string['premixresourcesnotavailable'] = 'There are no premix resources found in the course <i>{$a}</i>';
$string['premixresourcesavailable'] = '<i>{$a}</i> contains the following premix resources.';
$string['notatoplevelcategory'] = 'Not a top level category';
//configuration
$string['allowedcolorslabel'] = 'Subheadings background color scheme';
$string['allowedcolorsdesc'] = 'Please enter the allowed colors for subheadings background in HEX code seperated by semicolon(;)';
$string['defaultallowedcolors'] = '#2647a0;#3d67ce;#edf5ff;#86a5ec';
//premissions
$string['assessment_information:addinstance'] = 'Add a new assessment information block';
$string['assessment_information:managesettings'] = 'Manage category level settings for assessment information block';
$string['config_additional_subheading_add_string'] = "Add additional subheading";