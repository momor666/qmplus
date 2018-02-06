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
 * Settings for QMUL My Profile block.
 *
 * @package      blocks
 * @subpackage   qmul_myprofile
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// No direct script access.
defined('MOODLE_INTERNAL') || die();



$settings->add(new admin_setting_heading('headergeneral', get_string('setting_header_general', 'block_qmul_myprofile'),
                get_string('setting_header_general_desc', 'block_qmul_myprofile')));


$settings->add(new admin_setting_configtext('qmul_myprofile/title', get_string('setting_title', 'block_qmul_myprofile'),
                get_string('setting_title_desc', 'block_qmul_myprofile'), 'Logged In As', PARAM_RAW));

$settings->add(new admin_setting_configcheckbox('qmul_myprofile/use_name', get_string('setting_use_name', 'block_qmul_myprofile'), get_string('setting_use_name_desc', 'block_qmul_myprofile'), 1));


$settings->add(new admin_setting_heading('headersmart', get_string('setting_header_smart', 'block_qmul_myprofile'),
                get_string('setting_header_smart_desc', 'block_qmul_myprofile')));

$settings->add(new admin_setting_configtext('qmul_myprofile/baseurl', get_string('setting_baseurl', 'block_qmul_myprofile'),
                get_string('setting_baseurl_desc', 'block_qmul_myprofile'), 'http://dev.timetables.qmul.ac.uk/dEVSCI1314SWS/timetable.asp', PARAM_RAW));

$settings->add(new admin_setting_configtext('qmul_myprofile/day', get_string('setting_dayrange', 'block_qmul_myprofile'),
                get_string('setting_dayrange_desc', 'block_qmul_myprofile'), '1-5', PARAM_RAW));

$settings->add(new admin_setting_configtext('qmul_myprofile/week', get_string('setting_weekrange', 'block_qmul_myprofile'),
                get_string('setting_weekrange_desc', 'block_qmul_myprofile'), '1-52', PARAM_RAW));

$settings->add(new admin_setting_configcheckbox('qmul_myprofile/autoincrementweek', get_string('setting_autoincrementweekrange', 'block_qmul_myprofile'),
    get_string('setting_autoincrementweekrange_desc', 'block_qmul_myprofile'), 0));

$settings->add(new admin_setting_configtext('qmul_myprofile/period', get_string('setting_periodrange', 'block_qmul_myprofile'),
                get_string('setting_periodrange_desc', 'block_qmul_myprofile'), '1-2', PARAM_RAW));

$settings->add(new admin_setting_configtext('qmul_myprofile/style', get_string('setting_style', 'block_qmul_myprofile'),
                get_string('setting_style_desc', 'block_qmul_myprofile'), 'individual', PARAM_RAW));

$settings->add(new admin_setting_configtext('qmul_myprofile/template', get_string('setting_template', 'block_qmul_myprofile'),
                get_string('setting_template_desc', 'block_qmul_myprofile'), 'swsnet+object+individual', PARAM_RAW));

