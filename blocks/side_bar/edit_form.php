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
 * Instance configuration for the block allowing the title to be changed.
 *
 * @package    block_side_bar
 * @see        block_site_main_menu
 * @author     Justin Filip <jfilip@remote-learner.ca>
 * @copyright  2011 onwards Justin Filip
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_side_bar_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $DB;

        // Field for editing Side Bar block title.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_side_bar'));
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('select', 'config_location_method', get_string('locationmethod', 'block_side_bar'), array(
            'number' => get_string('sectionnumber', 'block_side_bar'),
            'name' => get_string('sectionname', 'block_side_bar')
        ));
        $mform->setType('config_location_method', PARAM_TEXT);

        $sql = "SELECT s.id, s.section FROM {course_sections} s WHERE s.course = ? ORDER BY s.section ASC";
        $configselect = $DB->get_records_sql_menu($sql, array($this->block->page->course->id));

        $mform->addElement('select', 'config_section_id', get_string('configsection', 'block_side_bar'), $configselect);
        $mform->setType('config_section_id', PARAM_INT);

        $mform->addElement('text', 'config_section_name', get_string('sectionname', 'block_side_bar'));
        $mform->setType('config_section_name', PARAM_TEXT);

        $mform->addElement('static', 'blockinfo', get_string('blockinfo', 'block_side_bar'),
            '<a target="_blank" href="http://ned.ca/sidebar">http://ned.ca/sidebar</a>');

    }
}