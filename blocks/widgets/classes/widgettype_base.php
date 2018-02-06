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
 * Base class for all widget types
 *
 * @package   block_widgets
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_widgets;

use renderer_base;

defined('MOODLE_INTERNAL') || die();

abstract class widgettype_base implements \templatable {
    /**
     * @var bool $inuse is this widget in use in the current block?
     */
    private $inuse;

    /**
     * @var int $blockinstanceid of the block displaying this widget - used as part of the unique id
     *                           for the widget
     */
    protected $blockinstanceid = null;

    /**
     * @var string
     */
    private $title = null;

    /**
     * widgettype_base constructor.
     * blockinstanceid + widgetinstanceid need to be specified if there is an instance of the widget to
     * be output, they are not needed if the class is only being used to retrieve the title / check capabilities
     * @param bool $inuse
     * @param int $blockinstanceid (optional)
     */
    public function __construct($inuse, $blockinstanceid = null) {
        $this->inuse = (bool)$inuse;
        $this->blockinstanceid = (int)$blockinstanceid;
        if ($this->inuse && !$this->blockinstanceid) {
            throw new \coding_exception('Must specify blockinstanceid if the block is in use');
        }
    }

    // -----------------------------------------------------------------
    // These functions must be implemented in your widget
    // -----------------------------------------------------------------

    /**
     * Retrieve the widget title (usually from a language string).
     * @return string
     */
    abstract protected function get_title_internal();

    /**
     * An array of HTML fragments to output as the content of the widget
     * @return string[]
     */
    abstract protected function get_items();

    // -----------------------------------------------------------------
    // These functions can be overridden to customise your widget
    // -----------------------------------------------------------------

    /**
     * Return the content of the footer (typically a link), null to skip the output of the footer.
     * @return string|null
     */
    protected function get_footer() {
        return null;
    }

    /**
     * Any extra CSS classes you want to apply to the outer element of the widget's HTML.
     * @return string[]
     */
    protected function get_extra_css_classes() {
        return [];
    }

    /**
     * Can the current user use (add/view) this widget?
     * @return bool
     */
    public function can_use() {
        $context = \context_system::instance();
        $capability = 'widgettype/'.$this->get_plugin_name().':use';
        return has_capability($capability, $context);
    }

    // -----------------------------------------------------------------
    // The rest of these functions are internal implementation details
    // -----------------------------------------------------------------

    /**
     * The title to display at the top of the widget
     * @return string
     */
    final public function get_title() {
        if ($this->title === null) {
            $this->title = $this->get_title_internal();
        }
        return $this->title;
    }

    /**
     * Is the widget in use in the current block?
     * @return bool
     */
    final public function is_in_use() {
        return $this->inuse;
    }

    /**
     * Mark this widget as in use.
     * @param bool $inuse
     */
    final public function set_in_use($inuse) {
        $this->inuse = (bool)$inuse;
    }

    /**
     * Get the name of the subplugin - this is taken from the name of the class
     * and must match the subdirectory the subplugin is installed in.
     * @return string
     */
    final public function get_plugin_name() {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Get the unique id for this instance of the widget - used to set the id attr
     * of the widget when output. May also be used as a prefix when storing per-instance
     * configuration for the widget.
     * @return string
     */
    final protected function get_uid() {
        global $USER;
        if ($this->blockinstanceid === null) {
            throw new \coding_exception('Must provide blockinstanceid  before calling get_uid()');
        }
        return $this->blockinstanceid.'-'.$this->get_plugin_name().'-'.$USER->id;
    }

    /**
     * Get the CSS classes to apply to the outer container for the widget.
     * @return string[]
     */
    private function get_css_classes() {
        $classes = [
            'widgets-widget',
            'widgettype-'.$this->get_plugin_name(),
        ];
        return array_merge($classes, $this->get_extra_css_classes());
    }

    /**
     * Pepare the content for rendering via a template
     * @param renderer_base $output
     * @return object
     */
    final public function export_for_template(renderer_base $output) {
        $ret = (object)[
            'title' => $this->get_title(),
            'items' => $this->get_items(),
            'classes' => implode(' ', $this->get_css_classes()),
            'id' => 'widget-'.$this->get_uid(),
        ];
        $footer = $this->get_footer();
        if ($footer !== null) {
            $ret->footer = $footer;
        }
        return $ret;
    }
}
