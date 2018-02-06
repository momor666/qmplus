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

class block_course_profile extends block_base
{
    function init() {
        $this->title = get_string('pluginname', 'block_course_profile');
    }

    function has_config() {
        return false;
    }

    function hide_header() {
        return true;
    }

    function instance_allow_config() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }
        $syscontext = context_system::instance();

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        $config = $this->config;

        if (empty($config->courseid)) {
            return true;
        }

        $featuredcourses = explode(',', $config->courseid);

        $this->content->text = html_writer::start_tag('ul', array('id' => 'courseslider'));

        foreach ($featuredcourses as $courseid) {
            $course = $DB->get_record('course', array('id'=>$courseid));
            if (!$course) {
                return '';
            }
            $course = new course_in_list($course);

            $url = '';
            foreach ($course->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                        '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                        $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            }

            $slider = new stdClass();
            $slider->image = $url;
            $slider->url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
            $caption = html_writer::tag('h3', 'Featured', array('class'=>'featuredtitle'));
            $slider->caption = $caption.html_writer::tag('h4', $course->fullname, array('class'=>'coursename'));

            if (!empty($course->summary)) {
                $substr = strip_tags($course->summary);
                if (strlen($substr) > 100) {
                    $slider->caption .= html_writer::tag('p', substr($substr, 0, 100).'...');
                } else {
                    $slider->caption .= html_writer::tag('p', $substr);
                }
            }

            $this->content->text .= $OUTPUT->render_from_template('block_course_profile/featuredslide', $slider);

        }

        $this->content->text .= html_writer::end_tag('ul');

        $PAGE->requires->jquery();
        $PAGE->requires->jquery_plugin('courseprofile', 'block_course_profile');

        return $this->content;
    }

    public function get_aria_role() {
        return 'complementary';
    }
}

