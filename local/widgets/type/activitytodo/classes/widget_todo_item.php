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
 * Wrapper for outputting a todolist item into the widget
 *
 * @package   widgettype_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace widgettype_activitytodo;

use local_activitytodo\report_todo_item;

defined('MOODLE_INTERNAL') || die();

class widget_todo_item extends report_todo_item {

    public function get_formatted_activity_link() {
        $name = $this->cm->get_formatted_name();
        $name = shorten_text($name, 30, true);
        $link = $this->cm->url;
        if ($link) {
            $name = \html_writer::link($link, $name);
        }
        return $name;
    }
}
