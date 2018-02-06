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
 * External functions and service definitions.
 * INFO HERE: https://docs.moodle.org/dev/Adding_a_web_service_to_a_plugin#Declare_the_service
 *
 * @package    local_qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$services = array(
    'Moodle QM+ Dashboard web services'  => array(
        'functions' => array (
            'local_qmul_dashboard_FUNCTIONNAME', // EXAMPLE TO BE RENAMED
            'local_qmul_dashboard_FUNCTIONNAME', // EXAMPLE TO BE RENAMED
            'local_qmul_dashboard_FUNCTIONNAME', // EXAMPLE TO BE RENAMED
            'local_qmul_dashboard_FUNCTIONNAME', // EXAMPLE TO BE RENAMED
        ),
        'requiredcapability' => '', // if set, the web service user need this capability to access
        'restrictedusers' => 0,
        'enabled' => 1
    ),
);

$functions = array(
    // EXAMPLE TO BE RENAMED
    'local_qmul_dashboard_FUNCTIONNAME' => array(
        'classname'   => 'local_qmul_dashboard_external',
        'methodname'  => 'FUNCTIONNAME',
        'classpath'   => 'local/qmul_dashboard/externallib.php',
        'description' => '',
        'type'        => 'read',
        'capabilities'=> '',
    ),
);

