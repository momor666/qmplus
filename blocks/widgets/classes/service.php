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
 * Entry point for web services
 *
 * @package   block_widgets
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_widgets;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

class service extends external_api {

    // -------------------------------
    // Add widget
    // -------------------------------

    public static function add_parameters() {
        return new external_function_parameters(
            [
                'blockinstanceid' => new external_value(PARAM_INT),
                'type' => new external_value(PARAM_ALPHA),
            ]
        );
    }

    public static function add($blockinstanceid, $type) {
        global $PAGE;
        $manager = new widget_manager($blockinstanceid);
        $manager->add_widget($type);

        $PAGE->set_context(\context_block::instance($blockinstanceid));
        $output = $PAGE->get_renderer('block_widgets');
        $widget = $manager->export_single_widget($output, $type);

        $ret = ['status' => true];
        if ($widget) {
            $ret['widget'] = $widget;
        }
        return $ret;
    }

    public static function add_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
                'widget' => new external_single_structure(
                    [
                        'title' => new external_value(PARAM_RAW),
                        'items' => new \external_multiple_structure(
                            new external_value(PARAM_RAW)
                        ),
                        'classes' => new external_value(PARAM_TEXT),
                        'id' => new external_value(PARAM_ALPHANUMEXT),
                        'footer' => new external_value(PARAM_RAW, '', VALUE_OPTIONAL)
                    ],
                    '', VALUE_OPTIONAL)
            ]
        );
    }

    // -------------------------------
    // Remove widget
    // -------------------------------

    public static function remove_parameters() {
        return new external_function_parameters(
            [
                'blockinstanceid' => new external_value(PARAM_INT),
                'type' => new external_value(PARAM_ALPHA),
            ]
        );
    }

    public static function remove($blockinstanceid, $type) {
        $manager = new widget_manager($blockinstanceid);
        $manager->remove_widget($type);
        return ['status' => true];
    }

    public static function remove_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
            ]
        );
    }

    // -------------------------------
    // Sort widgets
    // -------------------------------

    public static function sort_parameters() {
        return new external_function_parameters(
            [
                'blockinstanceid' => new external_value(PARAM_INT),
                'types' => new external_multiple_structure(
                    new external_value(PARAM_ALPHA)
                )
            ]
        );
    }

    public static function sort($blockinstanceid, $types) {
        $manager = new widget_manager($blockinstanceid);
        $manager->sort_widgets($types);
        return ['status' => true];
    }

    public static function sort_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL),
            ]
        );
    }

}