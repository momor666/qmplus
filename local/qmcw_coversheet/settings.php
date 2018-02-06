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
 *
 * @package    local
 * @subpackage qmcw_coversheet
 * @copyright  2017 Queen Mary University of London
 * @author     Damian Hippisley <d.j.hippisley@qmul.ac.uk>
 * @author     Vasileos Sotiras <v.sotiras@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig){

    // Create the new settings page for local plugin
    $settings = new admin_settingpage( 'local_qmcw_coversheet', 'QMUL Coversheet Settings' );

    $settings->add(new admin_setting_configtext('local_qmcw_coversheet/updatepassword',
        get_string('config_updatepassword', 'local_qmcw_coversheet'),
        get_string('config_desc_update_password', 'local_qmcw_coversheet'),
        '1234', PARAM_TEXT, 4));

    $ADMIN->add('localplugins', $settings);
}





