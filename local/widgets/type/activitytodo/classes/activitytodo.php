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
 * @package   widgettype_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace widgettype_activitytodo;

use local_activitytodo\todo_item;

defined('MOODLE_INTERNAL') || die();

class activitytodo extends \block_widgets\widgettype_base {

    const MAX_ITEMS = 5;

    private $todoitems = null;

    public function can_use() {
        $context = \context_system::instance();
        return parent::can_use() && has_capability('local/activitytodo:manage', $context);
    }

    /**
     * Get the title to display for this widget.
     * @return string
     */
    public function get_title_internal() {
        return get_string('pluginname', 'widgettype_activitytodo');
    }

    /**
     * @return widget_todo_item[]
     */
    private function get_todo_items() {
        global $USER;
        if ($this->todoitems === null) {
            do {
                $todoitems = todo_item::fetch_all(['userid' => $USER->id]);
                $this->todoitems = array_slice($todoitems, 0, self::MAX_ITEMS);
                $this->todoitems = widget_todo_item::wrap_items($this->todoitems); // May delete items if course is deleted.
            } while (count($this->todoitems) < self::MAX_ITEMS && count($todoitems) >= self::MAX_ITEMS);
        }
        return $this->todoitems;
    }

    /**
     * Return the main content for the widget.
     * @return string[]
     */
    public function get_items() {
        global $OUTPUT, $PAGE;

        static $initjs = false;
        if (!$initjs) {
            $PAGE->requires->strings_for_js(['todoitemremoved', 'undo'], 'local_activitytodo');
            $PAGE->requires->js_call_amd('widgettype_activitytodo/manageactivities', 'init');
        }

        $todoitems = $this->get_todo_items();

        $ret = [];
        if (!$todoitems) {
            $ret[] = get_string('noitems', 'local_activitytodo');
        } else {
            foreach ($todoitems as $todoitem) {
                $ret[] = $OUTPUT->render_from_template('widgettype_activitytodo/item',
                                                       $todoitem->export_for_template($OUTPUT));
            }
        }
        return $ret;
    }

    /**
     * Return the footer content for the widget.
     * @return string
     */
    public function get_footer() {
        if (!$this->get_todo_items()) {
            return null;
        }
        $url = new \moodle_url('/local/activitytodo/index.php');
        return \html_writer::link($url, get_string('viewtodo', 'widgettype_activitytodo'), array('class'=>'btn btn-primary'));
    }
}
