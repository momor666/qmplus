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
 * Form for editing Course Metadata block instances.
 *
 * @package   block_qmul_course_metadata
 * @copyright 2015 Queen Mary University of London
 * @author    Phil Lello <phil@dunlop-lello.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_qmul_course_metadata extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_qmul_course_metadata');
    }

    function has_config() {
        return true;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        global $CFG, $OUTPUT;

        require_once($CFG->libdir . '/filelib.php');

        if ($this->page->pagetype != 'course-edit') {
            return false;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $url = new moodle_url("$CFG->wwwroot/blocks/qmul_course_metadata/3rdparty/diff_match_patch_20121119/javascript/diff_match_patch.js");
        $this->page->requires->js($url);
        $this->page->requires->yui_module(
            'moodle-block_qmul_course_metadata-plugin',
            'M.block_qmul_course_metadata.plugin.init',
            array(local_qmul_sync_plugin::ajax_moodle_course($this->page->course->idnumber)));
        $this->page->requires->string_for_js('sits_diff', 'block_qmul_course_metadata');
        $this->page->requires->string_for_js('sits_none', 'block_qmul_course_metadata');
        $this->page->requires->string_for_js('sits_same', 'block_qmul_course_metadata');
        $this->page->requires->string_for_js('sits_help', 'block_qmul_course_metadata');
        $this->page->requires->string_for_js('sits_wrong', 'block_qmul_course_metadata');

        $this->content = new stdClass();
        $this->content->text  = html_writer::tag('span', $this->page->course->idnumber, array('class' => 'message'));
        $this->content->text .= ' ';
        $url = new moodle_url('#');
        $label = get_string('sitsdata', 'block_qmul_course_metadata');
        $this->content->text .= html_writer::link($url, $label, array('class' => 'view-link'));

        return $this->content;
    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked() {
        return false;
    }

}
