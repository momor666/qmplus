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
 * Output functions
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_activitytodo\report_todo_item;

defined('MOODLE_INTERNAL') || die();

class local_activitytodo_renderer extends plugin_renderer_base {
    /**
     * @param report_todo_item[] $reportitems
     * @return string
     */
    public function items($reportitems) {
        if (!$reportitems) {
            return html_writer::tag('p', get_string('noitems', 'local_activitytodo'));
        }

        $this->page->requires->strings_for_js(['updatenote', 'todoitemremoved', 'undo'], 'local_activitytodo');
        $opts = [
            'icons' => [
                'loading' => $this->output->pix_icon('i/ajaxloader', ''),
            ],
        ];
        $this->page->requires->js_call_amd('local_activitytodo/manageactivities', 'init', [$opts]);

        $out = '';

        foreach ($reportitems as $item) {
            $data = $item->export_for_template($this);
            $out .= $this->render_from_template('local_activitytodo/item', $data);
        }

        $out = html_writer::div($out, 'activitytodo-items');
        return $out;
    }
}