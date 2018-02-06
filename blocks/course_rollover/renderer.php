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

/*File:       block/course_rollover/renderer.php

Purpose:    Class with collection of methods that
            handle rendering of visual aspects of the block

-****************************************************************/

/**
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    renderer
 * @category   '$PWD'
 */
defined('MOODLE_INTERNAL') || die();

class block_course_rollover_renderer extends plugin_renderer_base
{

    public function display_rollover_comfirmation($cr, $course)
    {

        $html = parent::heading(get_string('rollover_comfirmation_heading', 'block_course_rollover')." ($course->shortname)", 3);
        $html .= $this->render_label_data_element(
                get_string('label_schedule_date', 'block_course_rollover'), userdate($cr->form->scheduled_date, '%A, %d %B %Y')
        );
        $html .= html_writer::empty_tag('br');
        $datearr = course_rollover::check_in_range(
                $cr->course_rollover_config->schedule_day, $cr->course_rollover_config->cutoff_day, $cr->form->scheduled_date
        );
        if (isset($datearr['error'])) {

            $a = new stdclass();
            $a->scheduled_date = userdate($cr->form->scheduled_date, '%A, %d %B %Y');
            $a->schedule_day = userdate($cr->course_rollover_config->schedule_day, '%A, %d %B %Y');
            $a->cutoff_day = userdate($cr->course_rollover_config->cutoff_day, '%A, %d %B %Y');

            $html .= get_string("messages_{$datearr['message']}", 'block_course_rollover', $a);
            unset($a);
        }

        if (empty($cr->mis_course)) { // no sits module
            $html .= $this->display_no_sits_course_data();
        } else {
            // there is a sits module found lets display the data
            $html .= $this->display_sits_course_data($cr->mis_course);
        }

        return $html;
    }

    /**
     * display_sits_course_data
     * displays the sits module information with optional description
     */
    public function display_sits_course_data($mis_course)
    {
        $html = parent::heading(get_string('header_sits_module_info', 'block_course_rollover'), 3);
        $string = get_string('header_sits_module_info_desc', 'block_course_rollover');
        if (strlen(trim($string)) != 0) {
            $html .= parent::box($string);
        }

        $html .= $this->render_label_data_element(get_string('display_sits_module_name', 'block_course_rollover'), $mis_course['COURSE_NAME'] . ' ' . $mis_course['COURSE_SHORT_NAME']);
        return $html .= html_writer::empty_tag('br');
    }

    /**
     * display_current_course_data
     * displays the current module information with optional description
     */
    public function display_current_course_data($moodle_course)
    {
        $html = parent::heading(s(get_string('header_current_module_info', 'block_course_rollover')), 3);
        $string = get_string('header_current_module_info_desc', 'block_course_rollover');
        if (strlen(trim($string)) != 0) {
            $html .= parent::box(s($string));
        }

        $html .= $this->render_label_data_element('Module Name', $moodle_course->fullname);
        return $html .= html_writer::empty_tag('br');
    }

    public function display_no_sits_course_data()
    {
        $html = parent::heading(s(get_string('header_no_sits_module_info', 'block_course_rollover')), 3);
        $string = get_string('header_no_sits_module_info_desc', 'block_course_rollover');
        if (strlen(trim($string)) != 0) {
            $html .= parent::box($string);
        }
        return $html .= html_writer::empty_tag('br');
    }

    public function schedule_footer($data, $cr,$type='course')
    {
        $state = ($data[key($data)]) ? 'success' : 'fail';
        $html = html_writer::start_tag('p');
        $html.= html_writer::tag('span', get_string('messages_' . key($data) . '_' . $state, 'block_course_rollover'), array('class' => 'block_course_has_schedule'));
        $html .= html_writer::end_tag('p');

        if($type == 'course_cat'){
            $url = new moodle_url('/course/management.php', array('categoryid' => $cr->params->id));
            $html .= html_writer::tag(
                        'strong', html_writer::link($url, 'Return to course category'), array('class' => 'footer_link')
            );
            return $html;
        }
        $url = new moodle_url('/course/view.php', array('id' => $cr->params->id));
        $html .= html_writer::tag(
                        'strong', html_writer::link($url, 'Return to course'), array('class' => 'footer_link')
        );
        return $html;
    }
    
    public function schedule_empty_courselist($cr)
    {
        $html = html_writer::start_tag('p');
        $html.= html_writer::tag('span', get_string('messages_schedule_empty_courselist', 'block_course_rollover'), array('class' => 'block_course_has_schedule'));
        $html .= html_writer::end_tag('p');

        $url = new moodle_url('/course/management.php', array('categoryid' => $cr->params->id));
        $html .= html_writer::tag(
            'strong', html_writer::link($url, 'Return to course category'), array('class' => 'footer_link')
        );
        return $html;
    }

    public function display_scheduled_overview()
    {
        $table = new html_table();
        $table->head = array('Module', 'Schedule Time', 'Scheduled By');
        $table->size = array('60%', '20%', '20%');
        $table->align = array('left', 'center', 'center');
        $table->attributes['class'] = 'scaletable localscales generaltable';
        $table->data = $data;

        $html = parent::heading(s(get_string('header_scheduled_overview', 'block_course_rollover')), 3);
        $string = get_string('header_scheduled_overview_desc', 'block_course_rollover');
        if (strlen(trim($string)) != 0) {
            $html .= parent::box(s($string));
        }
        return $html .= html_writer::empty_tag('br');
    }

    public function block_course_has_schedule(&$content, $instanceid, $courseid, $scheduled)
    {
        if ($scheduled->status == 1) {
            $content->text = null;
            $content->footer = null;
        } else {
            $a = new stdclass();
            $a->scheduled_date = userdate($scheduled->scheduletime, '%A, %d %B %Y');
            //TODO do we need a editmode ??
            $url = new moodle_url('/blocks/course_rollover/view.php', array('blockid' => $instanceid, 'id' => $courseid));
            $content->text = html_writer::start_tag('p');
            //if rollover is completed
            if($scheduled->status == 400){
                //get userdata
                global $DB;
                $user = $DB->get_record('user', array('id' => $scheduled->userid));
                $a->name = $user->firstname.' '.$user->lastname;
                $content->text .= html_writer::tag('span', get_string('block_course_rollover_completed', 'block_course_rollover', $a), array('class' => 'block_course_has_schedule'));
            } elseif(in_array($scheduled->status, array(510,520,530,540))){
                $content->text .= html_writer::tag('span', get_string('block_course_rollover_error', 'block_course_rollover', $a), array('class' => 'block_course_has_schedule'));
            } else {
                $content->text .= html_writer::tag('span', get_string('block_course_has_schedule', 'block_course_rollover', $a), array('class' => 'block_course_has_schedule'));
            }

            if (has_capability('block/course_rollover:manage', $this->page->context)) {
                $config = get_config('course_rollover');
                $rangetest = course_rollover::check_in_range($config->schedule_day, $config->cutoff_day, time());
                if (!isset($rangetest['error']) || $rangetest['error'] != true) {
                    $content->footer = html_writer::tag(
                        'div', html_writer::link($url, get_string('block_course_has_schedule_link', 'block_course_rollover')), array('class' => 'block_footer_desc')
                    );
                    $cancel_url = new moodle_url('/blocks/course_rollover/cancel_rollover.php', array('id' => $courseid, 'confirm' => 0));
                    $content->footer .= html_writer::tag(
                        'div', html_writer::link($cancel_url, get_string('block_cancel_rollover','block_course_rollover'), array('class' => 'block_footer_desc'))
                    );
                }
            }

            $content->text .= html_writer::end_tag('p');
        }
    }

    public function block_course_schedule(&$content, $instanceid, $courseid)
    {
        if(!isset($content->text)){
            $content->text = '';
        }

        if(has_capability('block/course_rollover:manage', $this->page->context)) {
            $url = new moodle_url('/blocks/course_rollover/view.php', array('blockid' => $instanceid, 'id' => $courseid));
            $content->text .= html_writer::start_tag('p');
            $content->text .= html_writer::tag('span', get_string('block_footer_desc', 'block_course_rollover'), array('class' => 'block_footer_desc'));
            $content->text .= html_writer::end_tag('p');

            $config = get_config('course_rollover');
            $rangetest = course_rollover::check_in_range($config->schedule_day, $config->cutoff_day, time());
            if (!isset($rangetest['error']) || $rangetest['error'] != true) {
                $content->footer = html_writer::tag(
                    'span', html_writer::link($url, get_string('block_footer_link', 'block_course_rollover')), array('class' => 'block_footer_desc')
                );
            }
        } else {
            $content->text .= html_writer::start_tag('p');
            $content->text .= html_writer::tag('span', get_string('block_course_no_schedule', 'block_course_rollover'), array('class' => 'block_footer_desc'));
            $content->text .= html_writer::end_tag('p');
        }
    }

    /**
     * Course Rollover Reports
     */
    public function sceduled_rollover_report($params)
    {
        $baseurl = new moodle_url('/blocks/course_rollover/schedule_report.php', $params);
        $report = course_rollover::get_report_table($params);

        if ($report->count == 0) {
            return parent::notification(get_string('no_records_found', 'block_course_rollover'));
        } else {

        }
        $html = html_writer::table($report->table);
        $html .= parent::paging_bar($report->count, $params['page'], $params['limit'], $baseurl);
        return $html;
    }

    /**
     * Helpers
     */
    public function render_label_data_element($label, $data, $extra = array())
    {
        $html = html_writer::start_tag('div', $extra);
        $html .= html_writer::start_tag('strong');
        $html .= s($label) . ' : ';
        $html .= html_writer::end_tag('strong');
        $html .= html_writer::end_tag('em');
        $html .= s($data);
        $html .= html_writer::end_tag('em');
        return $html .= html_writer::end_tag('div');
    }
    /**
     * display_mappings_table returns the html structure for course
     * @param $courseid course id number
     * @return $html html structure for mapping
     */
    public function display_mappings_table($courseid)
    {
        global $DB;
        $course = $DB->get_record('course', array('id' => $courseid));
        $mappings = local_qmul_sync_plugin::qem_mappings_for_course($course);
        if (count($mappings) > 0) {
            $text  = html_writer::tag('b','Course Mappings for "'.$course->idnumber.'"');
            $text .= html_writer::start_tag('ul', array('class' => 'qem-mappings'));
            foreach ($mappings as $id => $description) {
                $url = new moodle_url('https://webapps2.is.qmul.ac.uk/qem/view-mapping.action', array('ID'=>$id));
                $text .= html_writer::tag('li', html_writer::link($url, $description, array('target'=>'_blank')));
            }
            $text .= html_writer::end_tag('ul');
        } else {
            $text  = html_writer::tag('b','Course Mappings for "'.$course->idnumber.'"');
            $text .= "<br/>There are no enrolment mappings for this course.";
        }
        return html_writer::tag('div', $text, array('class' => 'qem-mappings box generalbox'));
    }
    /**
     * display_select_unselect_links returns the html structure for select links
     * @return $html html structure for links
     */
    public function display_select_unselect_links(){

        $js = "function toggle(state) {
                    checkboxDiv = document.getElementsByClassName('confirm_checkbox');
                    for(var i=0; i < checkboxDiv.length; i++) {
                        inputs = checkboxDiv[i].getElementsByTagName('input');
                        for( var j = 0; j < inputs.length; j++){
                            inputs[j].checked = state;
                        }
                    }
                    return false;
                }";
        $html = '';
        $html .= html_writer::script($js);
        $html .= html_writer::start_tag('div');
        $html .= html_writer::link('#', 'Select All',array('onclick'=>'return toggle(true);'));
        $html .= ' / ';
        $html .= html_writer::link('#', 'Deselect All',array('onclick'=>'return toggle(false)'));
        $html .= html_writer::end_tag('div');
        return $html;
    }
}
