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
 * Settings that allow configuration of the list of tex examples in the equation editor.
 *
 * @package    atto_qmulcolumns
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft0,
($
/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editoratto', new admin_category('atto_qmulcolumns', new lang_string('pluginname', 'atto_qmulcolumns')));

$settings = new admin_settingpage('atto_qmulcolumns_settings', new lang_string('settings', 'atto_qmulcolumns'));
if ($ADMIN->fulltree) {

    //Common for all
    $desc = new lang_string('columns_desc', 'atto_qmulcolumns');


    // column 1
    $name = new lang_string('column1', 'atto_qmulcolumns');
    $default = 'coursebox-3';
    $setting = new admin_setting_configtextarea('atto_qmulcolumns/column1',
                                                $name,
                                                $desc,
                                                $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // column2
    $name = new lang_string('column2', 'atto_qmulcolumns');
    $default = 'coursebox-1';
    $setting = new admin_setting_configtextarea('atto_qmulcolumns/column2',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // column 3
    $name = new lang_string('column3', 'atto_qmulcolumns');
    $default = 'coursebox-2';
    $setting = new admin_setting_configtextarea('atto_qmulcolumns/column3',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

}
