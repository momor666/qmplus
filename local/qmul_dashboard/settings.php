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
 * Settings details.
 *
 * @package    local
 * @subpackage qmul_dashboard_app
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;


if ($hassiteconfig) { // needs this condition or there is error on login page
    /*
    $settings = new admin_settingpage(
        'local_qmul_dashboard',
        get_string('pluginname', 'local_qmul_dashboard')
    );

    //heading
    $setting = new admin_setting_heading(
        'local_qmul_dashboard' . '/heading',
        '', get_string('settings', 'local_qmul_dashboard')
    );

    $setting->plugin = 'local_qmul_dashboard';
    $settings->add($setting);

    // FB App ID
    $setting = new admin_setting_configtext(
        'local_qmul_dashboard' . '/appID',
        get_string('appID', 'local_qmul_dashboard'),
        get_string('appIDdescr', 'local_qmul_dashboard'),
        '', PARAM_TEXT
    );
    $setting->plugin = 'local_qmul_dashboard';
    $settings->add($setting);

    // FB App Secret
    $setting = new admin_setting_configtext(
        'local_qmul_dashboard' . '/appSecret',
        get_string('appSecret', 'local_qmul_dashboard'),
        get_string('appSecretdescr', 'local_qmul_dashboard'),
        '', PARAM_TEXT
    );
    $setting->plugin = 'local_qmul_dashboard';
    $settings->add($setting);
'/local/qmul_dashboard/edit.php'
    $ADMIN->add('localplugins', $settings);
    */


    $settings = new admin_externalpage('local_qmul_dashboard', get_string('pluginname', 'local_qmul_dashboard'), $CFG->wwwroot . '/local/qmul_dashboard/edit.php');

    $ADMIN->add('localplugins', $settings);

}