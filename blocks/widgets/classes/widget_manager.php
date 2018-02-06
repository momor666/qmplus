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
 * Class for managing widgets within a block instance
 *
 * @package   block_widgets
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_widgets;

use renderer_base;

defined('MOODLE_INTERNAL') || die();

class widget_manager implements \templatable {

    /** Maximum number of widgets that can be enabled at any time */
    const MAX_WIDGETS = 4;

    /** @var int */
    private $blockinstanceid;
    /** @var widgettype_base[] */
    private $allwidgets = null;
    /** @var object|null|false */
    private $widgetlist = null;

    public function __construct($blockinstanceid) {
        $this->blockinstanceid = $blockinstanceid;
    }

    private function get_used_widget_list() {
        global $DB, $USER;
        if ($this->widgetlist === null) {
            // Check the blockinstanceid matches a 'widgets' block, as well as retrieving any existing config.
            $sql = "
              SELECT w.*, bi.id AS validinstanceid
                FROM {block_instances} bi
                LEFT JOIN {block_widgets} w ON w.blockinstanceid = bi.id AND w.userid = :userid
               WHERE bi.id = :blockinstanceid AND bi.blockname = 'widgets'
            ";
            $params = ['blockinstanceid' => $this->blockinstanceid, 'userid' => $USER->id];
            $this->widgetlist = $DB->get_record_sql($sql, $params);
            if (empty($this->widgetlist->validinstanceid)) {
                // No widgets block exists with the given blockinstanceid.
                throw new \moodle_exception('invalidblockinstanceid', 'block_widgets');
            }
            if (!isset($this->widgetlist->id)) {
                // No config found for the current user + block.
                $this->widgetlist = false;
            }
        }
        if (empty($this->widgetlist->widgets)) {
            return [];
        }
        return explode(',', $this->widgetlist->widgets);
    }

    private function save_used_widget_list($types) {
        global $DB, $USER;
        if ($this->widgetlist === null) {
            $this->get_used_widget_list(); // Make sure any record is loaded.
        }
        $widgets = implode(',', $types);
        if (!$this->widgetlist) {
            $this->widgetlist = (object)[
                'blockinstanceid' => $this->blockinstanceid,
                'userid' => $USER->id,
                'widgets' => $widgets,
            ];
            $this->widgetlist->id = $DB->insert_record('block_widgets', $this->widgetlist);
        } else {
            if ($this->widgetlist->widgets != $widgets) {
                $this->widgetlist->widgets = $widgets;
                $DB->set_field('block_widgets', 'widgets', $this->widgetlist->widgets, ['id' => $this->widgetlist->id]);
            }
        }
    }

    public function add_widget($type) {
        $widgets = $this->get_all_widgets();
        if (!isset($widgets[$type])) {
            return; // Invalid widget type - ignore the request.
        }
        if ($widgets[$type]->is_in_use()) {
            return; // Already in use - ignore the request.
        }
        $types = array_merge($this->get_used_widget_list(), [$type]);
        if (count($types) > self::MAX_WIDGETS) {
            return;
        }
        $this->save_used_widget_list($types);
        $widgets[$type]->set_in_use(true);
    }

    public function remove_widget($type) {
        $widgets = $this->get_all_widgets();
        if (!isset($widgets[$type])) {
            return; // Invalid widget type - ignore the request.
        }
        if (!$widgets[$type]->is_in_use()) {
            return; // Widget not in use - ignore the request.
        }
        $types = array_diff($this->get_used_widget_list(), [$type]);
        $this->save_used_widget_list($types);
        $widgets[$type]->set_in_use(false);
    }

    public function sort_widgets($types) {
        $widgets = $this->get_all_widgets();
        $validlist = [];
        foreach ($types as $type) {
            if (isset($widgets[$type]) && $widgets[$type]->is_in_use()) {
                $validlist[] = $type;
            }
        }
        $this->save_used_widget_list($validlist);
    }

    /**
     * Get a list of all known widgets the current user can use.
     * @return widgettype_base[] $type => $instance
     */
    private function get_all_widgets() {
        if ($this->allwidgets === null) {
            $this->allwidgets = [];

            // Get a list of each widget type.
            $types = \core_component::get_plugin_list('widgettype');

            // Instantiate the widget with the details from the block config.
            $inuse = $this->get_used_widget_list();
            foreach ($types as $type => $path) {
                $classname = '\\widgettype_'.$type.'\\'.$type;
                $this->allwidgets[$type] = new $classname(in_array($type, $inuse), $this->blockinstanceid);
            }

            // Check if the user can use that widget type.
            foreach ($this->allwidgets as $idx => $widget) {
                if (!$widget->can_use()) {
                    unset($this->allwidgets[$idx]);
                }
            }
        }
        return $this->allwidgets;
    }

    /**
     * Returns a list of all available widgets, sorted alphabetically by title.
     * @return widgettype_base[] $type => $instance
     */
    public function get_available_widgets() {
        $widgets = $this->get_all_widgets();
        uasort($widgets, function (widgettype_base $a, widgettype_base $b) {
            $titlea = $a->get_title();
            $titleb = $b->get_title();
            if ($titlea < $titleb) {
                return -1;
            }
            if ($titlea > $titleb) {
                return 1;
            }
            return 0;
        });
        return $widgets;
    }

    /**
     * Returns a list of all widgets that are currently in use in the current block,
     * sorted based on the configured display order.
     * @return widgettype_base[] $type => $instance
     */
    public function get_used_widgets() {
        $widgets = $this->get_all_widgets();
        $ret = [];
        foreach ($this->get_used_widget_list() as $type) {
            $ret[$type] = $widgets[$type];
        }
        return $ret;
    }

    public function export_for_template(renderer_base $output) {
        $ret = (object)[
            'widgets' => [],
            'availablewidgets' => [],
        ];

        foreach ($this->get_used_widgets() as $widget) {
            $ret->widgets[] = $widget->export_for_template($output);
        }
        foreach ($this->get_available_widgets() as $widget) {
            $ret->availablewidgets[] = (object)[
                'type' => $widget->get_plugin_name(),
                'title' => $widget->get_title(),
                'inuse' => $widget->is_in_use(),
            ];
        }

        if (!empty($ret->widgets)) {
            $ret->haswidgets = 1;
        }

        return $ret;
    }

    public function export_single_widget(renderer_base $output, $type) {
        $used = $this->get_used_widgets();
        if (!isset($used[$type])) {
            return null;
        }
        return $used[$type]->export_for_template($output);
    }
}