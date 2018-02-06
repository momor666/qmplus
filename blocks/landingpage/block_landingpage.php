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
 * Main code for langing page block
 *
 * @package   block_landingpage
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_landingpage extends block_base {
    function has_config() {
        return true;
    }

    public function init() {
        $this->title = get_string('pluginname', 'block_landingpage');
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $showallschools = !empty($this->config->showallschools);

        if ($showallschools) {
            $schools = \block_landingpage\landingpage::instance()->get_all_schools();
        } else {
            $schools = \block_landingpage\landingpage::instance()->get_user_schools($USER->id);
            if (count($schools) < 2) {
                return null; // Only show the form if there is more than one school to choose from.
            }
        }

        // Add the form to the output.
        $this->content = (object)[
            'text' => '',
            'footer' => '',
        ];

        $desturl = new moodle_url('/blocks/landingpage/update.php');
        $custom = [
            'userschools' => $schools,
        ];
        $form = new \block_landingpage\updatelandingpage_form($desturl, $custom);
        $form->set_data((object)[
            'school' => \block_landingpage\landingpage::instance()->get_saved_school($USER),
            'showallschools' => $showallschools,
        ]);

        $this->content->text .= html_writer::tag('div', $form->render(), array('class'=>'col-12'));
        return $this->content;
    }
}
