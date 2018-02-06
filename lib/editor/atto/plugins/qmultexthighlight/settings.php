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
 * @package    atto_qmultexthighlight
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('editoratto', new admin_category('atto_qmultexthighlight', new lang_string('pluginname', 'atto_qmultexthighlight')));

$settings = new admin_settingpage('atto_qmultexthighlight_settings', new lang_string('settings', 'atto_qmultexthighlight'));
if ($ADMIN->fulltree) {

    // Highlight 1
    $name = new lang_string('highlight1', 'atto_qmultexthighlight');
    $desc = new lang_string('highlight1_desc', 'atto_qmultexthighlight');
    $default = 'highlight-1';

    $setting = new admin_setting_configtextarea('atto_qmultexthighlight/highlight1',
                                                $name,
                                                $desc,
                                                $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Highlight 2
    $name = new lang_string('highlight2', 'atto_qmultexthighlight');
    $desc = new lang_string('highlight2_desc', 'atto_qmultexthighlight');
    $default = 'highlight-2';

    $setting = new admin_setting_configtextarea('atto_qmultexthighlight/highlight2',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Highlight 3
    $name = new lang_string('highlight3', 'atto_qmultexthighlight');
    $desc = new lang_string('highlight3_desc', 'atto_qmultexthighlight');
    $default = 'highlight-3';

    $setting = new admin_setting_configtextarea('atto_qmultexthighlight/highlight3',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);

    // Highlight 4
    $name = new lang_string('highlight4', 'atto_qmultexthighlight');
    $desc = new lang_string('highlight4_desc', 'atto_qmultexthighlight');
    $default = 'highlight-4';

    $setting = new admin_setting_configtextarea('atto_qmultexthighlight/highlight4',
        $name,
        $desc,
        $default,PARAM_RAW, 40, 1);
    $settings->add($setting);


}
