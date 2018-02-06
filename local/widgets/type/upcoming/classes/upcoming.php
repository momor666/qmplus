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
 * @package   widgettype_upcoming
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace widgettype_upcoming;

use html_writer;

defined('MOODLE_INTERNAL') || die();

class upcoming extends \block_widgets\widgettype_base {

    const MAX_EVENTS = 5;

    /**
     * Get the title to display for this widget.
     * @return string
     */
    public function get_title_internal() {
        return get_string('pluginname', 'widgettype_upcoming');
    }

    /**
     * Return the main content for the widget.
     * @return string[]
     */
    public function get_items() {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot.'/calendar/lib.php');

        $courses = calendar_get_default_courses();
        list($courses, $groups, $users) = calendar_set_filters($courses);
        $defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
        if (isset($CFG->calendar_lookahead)) {
            $defaultlookahead = intval($CFG->calendar_lookahead);
        }
        $lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);
        $maxevents = self::MAX_EVENTS;

        $events = calendar_get_upcoming($courses, $groups, $users, $lookahead, $maxevents);
        if (!$events) {
            return [get_string('noevents', 'widgettype_upcoming')];
        }
        $ret = [];
        foreach ($events as $event) {
            $event = new \calendar_event($event);
            $event = calendar_add_event_metadata($event);
            $context = $event->context;

            $output = '';
            if (!empty($event->icon)) {
                $output .= $event->icon;
            } else {
                $output .= $OUTPUT->spacer(array('height' => 16, 'width' => 16));
            }
            if (!empty($event->referer)) {
                $output .= html_writer::span($event->referer, 'referer name d-block');
            } else {
                $output .= html_writer::span(
                    format_string($event->name, false, array('context' => $context)),
                    'name d-block'
                );
            }
            $output .= ' ';
            $icon = '<svg width="12px" height="12px" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g transform="translate(-845.000000, -654.000000)" fill="#03A9F4">
                        <path d="M852,657.25 L852,660.75 C852,660.822917 851.976563,660.882812 851.929687,660.929688 C851.882812,660.976563 851.822917,661 851.75,661 L849.25,661 C849.177083,661 849.117188,660.976563 849.070312,660.929688 C849.023437,660.882812 849,660.822917 849,660.75 L849,660.25 C849,660.177083 849.023437,660.117188 849.070312,660.070312 C849.117188,660.023437 849.177083,660 849.25,660 L851,660 L851,657.25 C851,657.177083 851.023437,657.117188 851.070312,657.070312 C851.117188,657.023437 851.177083,657 851.25,657 L851.75,657 C851.822917,657 851.882812,657.023437 851.929687,657.070312 C851.976563,657.117188 852,657.177083 852,657.25 Z M855.25,660 C855.25,659.229163 855.059898,658.518232 854.679687,657.867188 C854.299477,657.216143 853.783857,656.700523 853.132812,656.320313 C852.481768,655.940102 851.770837,655.75 851,655.75 C850.229163,655.75 849.518232,655.940102 848.867187,656.320313 C848.216143,656.700523 847.700523,657.216143 847.320312,657.867188 C846.940102,658.518232 846.75,659.229163 846.75,660 C846.75,660.770837 846.940102,661.481768 847.320312,662.132813 C847.700523,662.783857 848.216143,663.299477 848.867187,663.679688 C849.518232,664.059898 850.229163,664.25 851,664.25 C851.770837,664.25 852.481768,664.059898 853.132812,663.679688 C853.783857,663.299477 854.299477,662.783857 854.679687,662.132813 C855.059898,661.481768 855.25,660.770837 855.25,660 Z M857,660 C857,661.088547 856.731774,662.092443 856.195313,663.011719 C855.658851,663.930994 854.930994,664.658851 854.011719,665.195312 C853.092443,665.731774 852.088547,666 851,666 C849.911453,666 848.907557,665.731774 847.988281,665.195312 C847.069006,664.658851 846.341149,663.930994 845.804688,663.011719 C845.268226,662.092443 845,661.088547 845,660 C845,658.911453 845.268226,657.907557 845.804688,656.988281 C846.341149,656.069006 847.069006,655.341149 847.988281,654.804688 C848.907557,654.268226 849.911453,654 851,654 C852.088547,654 853.092443,654.268226 854.011719,654.804688 C854.930994,655.341149 855.658851,656.069006 856.195313,656.988281 C856.731774,657.907557 857,658.911453 857,660 Z"></path>
                    </g>
                </g>
            </svg>';
            if (!empty($event->time)) {
                $output .= html_writer::span($icon.$event->time, 'date mr-1');
            } else {
                $attrs = array('class' => 'date mr-1');
                $output .= html_writer::tag('span', $icon.calendar_time_representation($event->timestart), $attrs);
            }

            $cssclass = !empty($event->cssclass) ? $event->cssclass : '';
            $cssclass .= ' event';
            $output = html_writer::div($output, $cssclass);

            $ret[] = $output;
        }

        return $ret;
    }

    /**
     * Return the footer content for the widget.
     * @return string
     */
    public function get_footer() {
        $url = new \moodle_url('/calendar/view.php');
        return html_writer::link($url, get_string('viewcalendar', 'widgettype_upcoming'), array('class'=>'btn btn-primary'));
    }
}
