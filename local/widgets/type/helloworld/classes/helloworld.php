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
 * Main class for the widget
 *
 * @package   widgettype_helloworld
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace widgettype_helloworld;

defined('MOODLE_INTERNAL') || die();

class helloworld extends \block_widgets\widgettype_base {
    /**
     * Get the title to display for this widget.
     * @return string
     */
    public function get_title_internal() {
        return get_string('pluginname', 'widgettype_helloworld');
    }

    /**
     * Return the main content for the widget.
     * @return string[]
     */
    public function get_items() {
        return ['Example', '<strong>item</strong>', 'output'];
    }

    /**
     * Return the footer content for the widget.
     * @return string
     */
    public function get_footer() {
        global $USER;
        $url = new \moodle_url('/user/profile.php', ['id' => $USER->id]);
        return \html_writer::link($url, 'Example link');
    }
}
