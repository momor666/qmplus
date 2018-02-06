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
 * Extra DB steps during installation
 *
 * @package   block_landingpage
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_landingpage_install() {
    global $DB;

    $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');

    // Check that we have at least one category defined.
    if (empty($categories)) {
        $defaultcategory = (object)[
            'name' => get_string('profiledefaultcategory', 'admin'),
            'sortorder' => 1,
        ];
        $defaultcategory->id = $DB->insert_record('user_info_category', $defaultcategory);
    } else {
        $defaultcategory = reset($categories);
    }

    $fields = [
        [
            'shortname' => 'landingpage',
            'name' => 'Landing page',
            'datatype' => 'menu',
            'categoryid' => $defaultcategory->id,
            'param1' => '',
        ],
    ];

    foreach ($fields as $field) {
        if (!$DB->record_exists('user_info_field', ['shortname' => $field['shortname']])) {
            $DB->insert_record('user_info_field', (object)$field);
        }
    }

}