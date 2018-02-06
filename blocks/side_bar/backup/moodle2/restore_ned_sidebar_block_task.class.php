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
 * Define all the backup steps that will be used by the backup_block_task
 * @package    block_side_bar
 * @copyright  2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  2016 Nelson Moller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised restore task for the side bar block
 * (using execute_after_tasks for recoding of target activity)
 *
 * @copyright  2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_side_bar_block_task extends restore_block_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
    }

    /**
     * Define the associated file areas
     */
    public function get_fileareas() {
        return array(); // No associated fileareas.
    }

    /**
     * Define special handling of configdata.
     */
    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata.
    }

    /**
     * This function, executed after all the tasks in the plan
     * have been executed, will perform the recode of the
     * target activity for the block. This must be done here
     * and not in normal execution so we are sure everything is
     * at its finale place.
     */
    public function after_restore() {
        global $DB;

        // Get the blockid.
        $blockid = $this->get_blockid();

        if ($configdata = $DB->get_field('block_instances', 'configdata', array('id' => $blockid))) {
            $config = unserialize(base64_decode($configdata));
            if (!empty($config->section_id)) {
                // Get the mapping and replace it in config.
                if ($mapping = restore_dbops::get_backup_ids_record($this->get_restoreid(),
                    'course_section', $config->section_id)) {

                    // Update the parent module id (the id from mdl_quiz etc...)
                    $config->section_id = $mapping->newitemid;

                    // Encode and save the config.
                    $configdata = base64_encode(serialize($config));
                    $DB->set_field('block_instances', 'configdata', $configdata, array('id' => $blockid));

                    // Arrange the replace link in the course_sections summary.
                    $newsection = $DB->get_record('course_sections', array('id' => $mapping->newitemid));

                    // Update the Side Bar section with the required values to make it work.
                    $reseturl = new moodle_url('/blocks/side_bar/reset.php?cid='.$newsection->course);
                    $newsection->name          = get_string('sidebar', 'block_side_bar');
                    $newsection->summary       = get_string('sectionsummary', 'block_side_bar',
                        (string)html_writer::link($reseturl, $reseturl)
                    );
                    $newsection->summaryformat = FORMAT_HTML;
                    $newsection->visible       = true;
                    $DB->update_record('course_sections', $newsection);

                }
            }
        }
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        return array();
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        return array();
    }
}
