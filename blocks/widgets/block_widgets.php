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
 * Main class for the block
 *
 * @package   block_widgets
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_widgets extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_widgets');
    }

    public function applicable_formats() {
        return ['all' => true];
    }

    function hide_header() {
        return true;
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = (object)[
            'text' => '',
            'footer' => '',
        ];

        $manager = new \block_widgets\widget_manager($this->instance->id);
        $output = $PAGE->get_renderer('block_widgets');
        $this->content->text .= $output->render_from_template('block_widgets/main', $manager->export_for_template($output));

        $opts = [
            'maxwidgets' => \block_widgets\widget_manager::MAX_WIDGETS,
        ];
        $PAGE->requires->js_call_amd('block_widgets/widgets', 'init', [$opts]);

        return $this->content;
    }
}
