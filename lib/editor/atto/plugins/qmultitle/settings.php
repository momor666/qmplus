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
 * Settings that allow configuration of the list of tex examples in the qmultitle editor.
 *
 * @package    atto_qmultitle
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editoratto', new admin_category('atto_qmultitle', new lang_string('pluginname', 'atto_qmultitle')));

$settings = new admin_settingpage('atto_qmultitle_settings', new lang_string('settings', 'atto_qmultitle'));
if ($ADMIN->fulltree) {

    // Header 1
    $name = new lang_string('header1', 'atto_qmultitle');
    $desc = new lang_string('header1_desc', 'atto_qmultitle');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header1',
                                                $name,
                                                $desc,
                                                $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // Header 2
    $name = new lang_string('header2', 'atto_qmultitle');
    $desc = new lang_string('header2_desc', 'atto_qmultitle');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header2',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // Header 2 lowercase underline
    $name = new lang_string('header2_lowercase', 'atto_qmultitle');
    $desc = new lang_string('header2_lowercase_desc', 'atto_qmultitle');
    $default = 'lowercase';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header2_lowercase',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Header 2 darkblue
    $name = new lang_string('header2_darkblue', 'atto_qmultitle');
    $desc = new lang_string('header2_darkblue_desc', 'atto_qmultitle');
    $default = 'darkblue';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header2_darkblue',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Header 2 lightblue
    $name = new lang_string('header2_lightblue', 'atto_qmultitle');
    $desc = new lang_string('header2_lightblue_desc', 'atto_qmultitle');
    $default = 'lightblue';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header2_lightblue',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // Header 3
    $name = new lang_string('header3', 'atto_qmultitle');
    $desc = new lang_string('header3_desc', 'atto_qmultitle');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header3',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Header 3
    $name = new lang_string('header3_primary', 'atto_qmultitle');
    $desc = new lang_string('header3_primary_desc', 'atto_qmultitle');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header3_primary',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Header 3 lowercase underline
    $name = new lang_string('header3_lowercase', 'atto_qmultitle');
    $desc = new lang_string('header3_lowercase_desc', 'atto_qmultitle');
    $default = 'lowercase';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header3_lowercase',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Header 3 darkblue
    $name = new lang_string('header3_darkblue', 'atto_qmultitle');
    $desc = new lang_string('header3_darkblue_desc', 'atto_qmultitle');
    $default = 'darkblue';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header3_darkblue',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Header 3 lightblue
    $name = new lang_string('header3_lightblue', 'atto_qmultitle');
    $desc = new lang_string('header3_lightblue_desc', 'atto_qmultitle');
    $default = 'lightblue';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header3_lightblue',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // Header 4
    $name = new lang_string('header4', 'atto_qmultitle');
    $desc = new lang_string('header4_desc', 'atto_qmultitle');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header4',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Header 5
    $name = new lang_string('header5', 'atto_qmultitle');
    $desc = new lang_string('header5_desc', 'atto_qmultitle');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header5',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // Header 3 lightblue
    $name = new lang_string('header5_lightbrown', 'atto_qmultitle');
    $desc = new lang_string('header5_lightbrown_desc', 'atto_qmultitle');
    $default = 'lightbrown';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header5_lightbrown',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


    // Header 6
    $name = new lang_string('header6', 'atto_qmultitle');
    $desc = new lang_string('header6_desc', 'atto_qmultitle');
    $default = '';

    $setting = new admin_setting_configtextarea('atto_qmultitle/header6',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


}
