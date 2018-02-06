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
 * Links and settings
 *
 * Contains settings used by logs report.
 *
 * @package    report_qmplus
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_category('qmplusreports', 'QM+'));

$ADMIN->add('qmplusreports', new admin_externalpage('reportqmplus', get_string('filereports', 'report_qmplus'),
        $CFG->wwwroot . "/report/qmplus/index.php", 'report/qmplus:view'));
$ADMIN->add('qmplusreports', new admin_externalpage('reportqmplus2', get_string('blacklisted', 'report_qmplus'),
    $CFG->wwwroot . "/report/qmplus/blacklisted.php", 'report/qmplus:view'));
$ADMIN->add('qmplusreports', new admin_externalpage('reportqmplus3', get_string('backupreports', 'report_qmplus'),
    $CFG->wwwroot . "/report/qmplus/backup.php", 'report/qmplus:view'));
$ADMIN->add('qmplusreports', new admin_externalpage('reportqmplus4', get_string('coursefilesreports', 'report_qmplus'),
    $CFG->wwwroot . "/report/qmplus/coursefiles.php", 'report/qmplus:view'));
$ADMIN->add('qmplusreports', new admin_externalpage('reportqmplus5', get_string('configlogreports', 'report_qmplus'),
    $CFG->wwwroot . "/report/qmplus/configlog.php", 'report/qmplus:view'));


$settings = new admin_settingpage('report_qmplus_settings', new lang_string('settings', 'report_qmplus'));

$name = new lang_string('externalUsers', 'report_qmplus');
$desc = new lang_string('externalUsersDescr', 'report_qmplus');
$default = '';
$setting = new admin_setting_configtextarea('report_qmplus/externalUsers', $name, $desc, $default, PARAM_RAW, 90, 4);
$settings->add($setting);


$name = new lang_string('taskFrequency', 'report_qmplus');
$desc = new lang_string('taskFrequencyDescr', 'report_qmplus');
$default = '0';
$setting = new admin_setting_configselect('report_qmplus/taskFrequency', $name, $desc, $default,
    array(
        24 => '24h',
        12 => '12h',
        6 => '6h',
        4 => '4h',
        2 => '2h',
        1 => '1h'
    ));
$settings->add($setting);


$name = new lang_string('blacklistedSettings', 'report_qmplus');
$desc = new lang_string('blacklistedSettingsDescr', 'report_qmplus');
$default = 'exe,
bat,
com,
pif,
scr,
cmd,';
$setting = new admin_setting_configtextarea('report_qmplus/blacklistedSettings', $name, $desc, $default, PARAM_RAW, 90, 4);
$settings->add($setting);
