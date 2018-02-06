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
 * Version details.
 *
 * @package    blocks
 * @subpackage assessment_information
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_assessment_information extends block_base
{

    public function init()
    {
        $this->title = get_string('pluginname', 'block_assessment_information');
    }

    public function hide_header(){
        return true;
    }

    public function instance_allow_multiple()
    {
        return false;
    }
    
    function instance_allow_config() {
        return true;
    }

    public function applicable_formats()
    {
        return array(
            'course-view' => true,
        );
    }

	function has_config() {
        return true;
    }
	/**
	 * get_content moodle internal function that is used to get the content of a block 
	 *
	 */
    public function get_content()
    {
        global $CFG, $COURSE;
        require_once($CFG->dirroot . '/blocks/assessment_information/classlib.php');

        if($this->content !== NULL) {
            return $this->content;
        }
        $assessment_information = new assessment_information($COURSE->id,$this->page->theme->name);

        $this->content = new stdClass();

        $assessmentrenderer = $this->page->get_renderer('block_assessment_information');
        $assessmentrenderer->block_content($this->content, $this->instance->id, $this->config,
            $assessment_information);
		
        return $this->content;
    }
}