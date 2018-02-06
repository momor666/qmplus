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
 * External functions.
 *
 * @package    local_qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once(__DIR__.'/lib.php');



class local_qmul_dashboard_external extends external_api {


//    ################### TEMPLATE
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function FUNCTIONNAME_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters().
        // The external_function_parameters constructor expects an array of external_description.
        return new external_function_parameters(
        // a external_description can be: external_value, external_single_structure or external_multiple structure
            array('PARAM1' => new external_value(PARAM_TYPE, 'human description of PARAM1'))
        );
    }

    /**
     * The function itself
     * @return string welcome message
     */
    public static function FUNCTIONNAME($PARAM1) {

        //Parameters validation
        $params = self::validate_parameters(self::FUNCTIONNAME_parameters(),
            array('PARAM1' => $PARAM1));

        //Note: don't forget to validate the context and check capabilities

        return $returnedvalue=1;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function FUNCTIONNAME_returns() {
        return new external_value(PARAM_TYPE, 'human description of the returned value');
    }
//    #######################################################

//    ##################### EXAMPLE
//    public static function gradereport_user_get_grades_table_parameters() {
//        return new external_function_parameters (
//            array(
//                'courseid' => new external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED),
//                'userid'   => new external_value(PARAM_INT, 'Return grades only for this user (optional)', VALUE_DEFAULT, 0)
//            )
//        );
//    }
//
//    /**
//     * Returns a list of grades tables for users in a course.
//     *
//     * @param int $courseid Course Id
//     * @param int $userid   Only this user (optional)
//     *
//     * @return array the grades tables
//     * @since Moodle 2.8
//     */
//    public static function gradereport_user_get_grades_table($courseid, $userid = 0) {
//        global $CFG;
//
//
//        $warnings = array();
//
//        // Validate the parameter.
//        $params = self::validate_parameters(self::gradereport_user_get_grades_table_parameters(),
//            array(
//                'courseid' => $courseid,
//                'userid' => $userid)
//        );
//
//
//    }
//    #######################################################



}