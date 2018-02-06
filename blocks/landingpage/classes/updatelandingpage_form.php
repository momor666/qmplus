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
 * Form (to display within the block) to update the landing page.
 *
 * @package   block_landingpage
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_landingpage;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

class updatelandingpage_form extends \moodleform {

    /**
     * @retrun string[]
     */
    private function get_user_schools() {
        if (!isset($this->_customdata['userschools'])) {
            throw new \coding_exception('Must specify userschools when creating form');
        }
        return $this->_customdata['userschools'];
    }

    /**
     * @return bool
     */
    private function is_use_radio() {
        return !empty($this->_customdata['useradio']);
    }

    protected function definition() {
        global $PAGE;

        $mform = $this->_form;

        // Where to return after the form is submitted.
        $mform->addElement('hidden', 'returnurl', $PAGE->url->out());
        $mform->setType('returnurl', PARAM_URL);

        // Whether or not to show all schools (only for the default landing page).
        $mform->addElement('hidden', 'showallschools', 0);
        $mform->setType('showallschools', PARAM_BOOL);

        // List of schools to choose between.
        if ($this->is_use_radio()) {
            $radio = [];
            foreach ($this->get_user_schools() as $val => $disp) {
                $tag = \html_writer::start_tag('div', array('class'=>'holder'));
                $tag .= \html_writer::span($disp, 'schoolname');
                $tag .= \html_writer::end_tag('div');
                $radio[] = $mform->createElement('radio', 'school', '', $tag, $val);
            }
            $mform->addGroup($radio, 'school_sel', get_string('chooseschool', 'block_landingpage'), [' '], false);
            $mform->addRule('school_sel', get_string('chooseschoolrequired', 'block_landingpage'), 'required', null, 'client');
        } else {
            $mform->addElement('select', 'school', get_string('chooseschool', 'block_landingpage'), $this->get_user_schools());
        }

        $this->add_action_buttons(false);
    }
}
