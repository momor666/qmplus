<?php
// This file is part of The Bootstrap Moodle theme
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

defined('MOODLE_INTERNAL') || die();

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_qmul
 * @copyright 2016 Andrew Davidson, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . "/calendar/renderer.php");

class theme_qmul_core_calendar_renderer extends core_calendar_renderer {

    /**
     * Displays an event
     *
     * @param calendar_event $event
     * @param bool $showactions
     * @return string
     */
    public function event(calendar_event $event, $showactions=true) {
        global $CFG;

        $event = calendar_add_event_metadata($event);
        $context = $event->context;
        $output = '';

        $output .= html_writer::start_tag('div', array('class'=>'card-header'));
        if (!empty($event->icon)) {
            $icon = $event->icon;
        } else {
            $icon = $this->output->spacer(array('height' => 16, 'width' => 16));
        }

        if (!empty($event->referer)) {
            $output .= $this->output->heading($icon.$event->referer, 3, array('class' => 'referer m-0'));
        } else {
            $output .= $this->output->heading(
                $icon.format_string($event->name, false, array('context' => $context)),
                3,
                array('class' => 'name m-0')
            );
        }

        if (!empty($event->time)) {
            $output .= html_writer::tag('span', $event->time, array('class' => 'date badge badge-primary badge-pill'));
        } else {
            $output .= html_writer::tag('span', calendar_time_representation($event->timestart), array('class' => 'date'));
        }

        $output .= html_writer::end_tag('div');
        $output .= html_writer::start_tag('div', array('class'=>'card-block'));
        if (!empty($event->courselink)) {
            $output .= html_writer::tag('div', $event->courselink, array('class' => 'course'));
        }
        // Show subscription source if needed.
        if (!empty($event->subscription) && $CFG->calendar_showicalsource) {
            if (!empty($event->subscription->url)) {
                $source = html_writer::link($event->subscription->url, get_string('subsource', 'calendar', $event->subscription));
            } else {
                // File based ical.
                $source = get_string('subsource', 'calendar', $event->subscription);
            }
            $output .= html_writer::tag('div', $source, array('class' => 'subscription'));
        }

        $eventdetailshtml = '';
        $eventdetailsclasses = '';

        $eventdetailshtml .= format_text($event->description, $event->format, array('context' => $context));
        $eventdetailsclasses .= 'description';
        if (isset($event->cssclass)) {
            $eventdetailsclasses .= ' '.$event->cssclass;
        }

        $output .= html_writer::tag('blockquote', $eventdetailshtml, array('class' => 'blockquote '.$eventdetailsclasses));

        if (calendar_edit_event_allowed($event) && $showactions) {
            if (empty($event->cmid)) {
                $editlink = new moodle_url(CALENDAR_URL.'event.php', array('action'=>'edit', 'id'=>$event->id));
                $deletelink = new moodle_url(CALENDAR_URL.'delete.php', array('id'=>$event->id));
                if (!empty($event->calendarcourseid)) {
                    $editlink->param('course', $event->calendarcourseid);
                    $deletelink->param('course', $event->calendarcourseid);
                }
            } else {
                $editlink = new moodle_url('/course/mod.php', array('update'=>$event->cmid, 'return'=>true, 'sesskey'=>sesskey()));
                $deletelink = null;
            }

            $commands  = html_writer::start_tag('div', array('class'=>'commands'));
            $commands .= html_writer::start_tag('a', array('href'=>$editlink));
            $commands .= html_writer::tag('i', '', array('class'=>'glyphicon glyphicon-cogwheel'));
            $commands .= html_writer::end_tag('a');
            if ($deletelink != null) {
                $commands .= html_writer::start_tag('a', array('href'=>$deletelink));
                $commands .= html_writer::tag('i', '', array('class'=>'glyphicon glyphicon-remove'));
                $commands .= html_writer::end_tag('a');
            }
            $commands .= html_writer::end_tag('div');
            $output .= $commands;
        }
        $output .= html_writer::end_tag('div');

        return html_writer::tag('div', $output , array('class' => 'event card', 'id' => 'event_' . $event->id));
    }

}