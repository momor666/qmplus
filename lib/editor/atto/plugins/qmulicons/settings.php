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
 * @package    atto_qmulicons
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft0,
($
/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editoratto', new admin_category('atto_qmulicons', new lang_string('pluginname', 'atto_qmulicons')));

$settings = new admin_settingpage('atto_qmulicons_settings', new lang_string('settings', 'atto_qmulicons'));
if ($ADMIN->fulltree) {

    //Common for all
    $desc = new lang_string('icons_desc', 'atto_qmulicons');

    $liHeightClass = 'qmplus_attoicon';

    // icon 1
    $name = new lang_string('icon1', 'atto_qmulicons');
    $default = $liHeightClass.' modbook';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon1',
                                                $name,
                                                $desc,
                                                $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon2
    $name = new lang_string('icon2', 'atto_qmulicons');
    $default = $liHeightClass.' modpage';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon2',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 3
    $name = new lang_string('icon3', 'atto_qmulicons');
    $default = $liHeightClass.' modfolder';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon3',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 4
    $name = new lang_string('icon4', 'atto_qmulicons');
    $default = $liHeightClass.' modforum';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon4',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 5
    $name = new lang_string('icon5', 'atto_qmulicons');
    $default = $liHeightClass.' modimage';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon5',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 6
    $name = new lang_string('icon6', 'atto_qmulicons');
    $default = $liHeightClass.' modassignment';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon6',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 7
    $name = new lang_string('icon7', 'atto_qmulicons');
    $default = $liHeightClass.' moddoc';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon7',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 8
    $name = new lang_string('icon8', 'atto_qmulicons');
    $default = $liHeightClass.' modoublog';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon8',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 9
    $name = new lang_string('icon9', 'atto_qmulicons');
    $default = $liHeightClass.' modpdf';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon9',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 10
    $name = new lang_string('icon10', 'atto_qmulicons');
    $default = $liHeightClass.' modportfolio';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon10',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // icon 11
    $name = new lang_string('icon11', 'atto_qmulicons');
    $default = $liHeightClass.' modppt';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon11',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 12
    $name = new lang_string('icon12', 'atto_qmulicons');
    $default = $liHeightClass.' modquiz';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon12',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // icon 13
    $name = new lang_string('icon13', 'atto_qmulicons');
    $default = $liHeightClass.' modscorm';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon13',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // icon 14
    $name = new lang_string('icon14', 'atto_qmulicons');
    $default = $liHeightClass.' modurl';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon14',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 15
    $name = new lang_string('icon15', 'atto_qmulicons');
    $default = $liHeightClass.' modvideo';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon15',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // icon 16
    $name = new lang_string('icon16', 'atto_qmulicons');
    $default = $liHeightClass.' modzip';
    $setting = new admin_setting_configtextarea('atto_qmulicons/icon16',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    
}
