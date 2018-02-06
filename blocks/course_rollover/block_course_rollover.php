<?php

/****************************************************************

File:       block/course_rollover/block_course_rollover.php

Purpose:    This file holds the class definition for the block,
            and is used both to manage it as a plugin and to
            render it onscreen.

****************************************************************/


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
 * Course Rollover Block.
 *
 * @package      blocks
 * @subpackage   course_rollover
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once dirname(__FILE__) . '/locallib.php';
require_once dirname(__FILE__) . '/classlib.php';

GLOBAL $DB;

class block_course_rollover extends block_base
{

    public function init()
    {
        $this->title = get_string('pluginname', 'block_course_rollover');
    }

    public function instance_allow_multiple()
    {
        return false;
    }
	/**
     * don't allow the user to configure a block instance
     * @return bool Returns false
     */
    function instance_allow_config() {
        return false;
    }

    public function applicable_formats()
    {
        return array(
			'site' => false,
            'site-index' => true,
            'course' => true,
            'mod' => false,
            'report' => false,
			'my' => false
        );
    }

	function has_config() {
        return true;
    }
	/**
	 * get_content moodle internal function that is used to get the content of a block
	 *
	 * @return the content of block_course_rollover
	 * @author Gerry G Hall
	 */
    public function get_content()
    {
        global $PAGE, $COURSE;


        $currentcontext = $this->page->context;
        $renderer = $PAGE->get_renderer('block_course_rollover');

        $this->content = new stdClass();

		if ($this->show_content()) {
            if (has_capability('block/course_rollover:view', $currentcontext)) {
                if ($scheduled = course_rollover::scheduled($COURSE->id)) {
                    $config = get_config('course_rollover');
                    $reset_rollover_date = strtotime(
                        date('d',$config->schedule_day).'-'.
                        date('m',$config->schedule_day).'-'.
                        date('Y')
                    );
                    if($scheduled->scheduletime < $reset_rollover_date){
                        $renderer->block_course_schedule($this->content, $this->instance->id, $COURSE->id);
                    } else {
                        $renderer->block_course_has_schedule($this->content, $this->instance->id, $COURSE->id, $scheduled);
                    }
                } else {
                    $renderer->block_course_schedule($this->content, $this->instance->id, $COURSE->id);
                }
            } else {
                $this->content->footer = '';
            }
        }


        return $this->content;
    }

	/**
	 * show_content is a helper function to determine if the block should or should not show with in certain
	 * context and statuses
	 *
	 * @return void
	 *  @author Gerry G Hall
	 	 */
    private function show_content()
    {
	 global $PAGE, $COURSE;
        $return = true;
		$config = get_config('course_rollover');

        $return = (
                ($COURSE->id != SITEID) && //don't show if we on the frontpage (SITEID)
                ($PAGE->context->contextlevel == CONTEXT_COURSE) // must be course contextlevel
                );

        $return = ($this->instance->id == optional_param('blockid', 0, PARAM_INT)) ? false : $return;

        return $return;
    }
}
