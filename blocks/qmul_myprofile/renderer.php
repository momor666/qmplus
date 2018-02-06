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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    block
 * @subpackage qmul_myprofile
 * @copyright  2013 Queen Mary University Gerry Hall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_qmul_myprofile_renderer extends plugin_renderer_base
{

    public function render_picture()
    {
        GLOBAL $USER;
        $html = '';
        $html .= html_writer::start_tag('div', array('class' => "picture"));
        $avatar = new user_picture($USER);
        $avatar->courseid = $this->page->course->id;
        $avatar->link = true;
        $avatar->size = 100;
        $avatar->class = 'profilepicture';
        $html .= parent::render($avatar);
        return $html .= html_writer::end_tag('div');
    }

    /**
     * Helpers
     */
    public function render_label_data_element($label, $data, $extra)
    {
        $html = html_writer::start_tag('div', $extra);
        if (!empty($label)) {
            $html .= html_writer::start_tag('span', array('class' => 'title'));
            $html .= $label . ' : ';
            $html .= html_writer::end_tag('span');
        }
        $html .= html_writer::end_tag('span', array('class' => 'data'));
        $html .= $data;
        $html .= html_writer::end_tag('span');
        return $html .= html_writer::end_tag('div');
    }

    public function render_data($text, $elem, $extra = array(), $type = false)
    {
        $html = html_writer::start_tag($elem, $extra);
        switch ($type) {
            case 'html':
                break;
            case 'email':
                $html .= obfuscate_mailto($text);
                break;
            case 'plain':
                $html .= format_string($text);
                break;
            default:
                $html .= format_string($text);
                break;
        }

        return $html .= html_writer::end_tag($elem);
    }

    public function render_smart_link()
    {
        GLOBAL $USER;
        $config = get_config('qmul_myprofile');
        $params = array();

        $html = html_writer::start_tag('div', array('class' => 'smart'));
        if (strlen($USER->idnumber) == 9) {
            $params['objectclass'] = 'student+set';
            $linkstring = get_string('student_linktext', 'block_qmul_myprofile');
        } elseif (strlen($USER->idnumber) == 6) {
            $params['objectclass'] = 'staff';
            $linkstring = get_string('staff_linktext', 'block_qmul_myprofile');
        } else {
            // No IDNUMBER set, so don't try to build a timetable link
            return '';
        }
		$params['week'] = (empty($config->week)) ? '' :$config->week;
        $params['day'] =  (empty($config->day)) ? '' : $config->day;
		$params['period'] =  (empty($config->period)) ? '' : $config->period;
        $params['identifier'] = $USER->idnumber;
        $params['style'] = $config->style;
        $params['template'] = $config->template;

        $html .= html_writer::link(
            new moodle_url($config->baseurl, $params), $linkstring, array('target' => '_BLANK')
        );

        return $html .= html_writer::end_tag('div');
    }

}
