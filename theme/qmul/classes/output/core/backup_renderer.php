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

class theme_qmul_core_backup_renderer extends core_backup_renderer {

    protected function backup_detail_pair($label, $value) {
        static $count = 0;
        $count ++;
        $html  = html_writer::start_tag('div', array('class' => 'detail-pair row mb-1'));
        $html .= html_writer::tag('label', $label, array('class' => 'detail-pair-label col-md-3 m-0', 'for' => 'detail-pair-value-'.$count));
        $html .= html_writer::tag('div', $value, array('class' => 'detail-pair-value col-md-9 m-0', 'name' => 'detail-pair-value-'.$count));
        $html .= html_writer::end_tag('div');
        return $html;
    }

    public function render_restore_category_search(restore_category_search $component) {
        $url = $component->get_url();

        $output = html_writer::start_tag('div', array('class' => 'restore-course-search form-inline row mb-1'));
        $output .= html_writer::start_tag('div', array('class' => 'rcs-results col-12'));

        $table = new html_table();
        $table->head = array('', get_string('name'), get_string('description'));
        $table->data = array();

        if ($component->get_count() !== 0) {
            foreach ($component->get_results() as $category) {
                $row = new html_table_row();
                $row->attributes['class'] = 'rcs-course';
                if (!$category->visible) {
                    $row->attributes['class'] .= ' dimmed';
                }
                $context = context_coursecat::instance($category->id);
                $row->cells = array(
                    html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'targetid', 'value' => $category->id)),
                    format_string($category->name, true, array('context' => context_coursecat::instance($category->id))),
                    format_text(file_rewrite_pluginfile_urls($category->description, 'pluginfile.php', $context->id,
                        'coursecat', 'description', null), $category->descriptionformat, array('overflowdiv' => true))
                );
                $table->data[] = $row;
            }
            if ($component->has_more_results()) {
                $cell = new html_table_cell(get_string('moreresults', 'backup'));
                $cell->attributes['class'] = 'notifyproblem';
                $cell->colspan = 3;
                $row = new html_table_row(array($cell));
                $row->attributes['class'] = 'rcs-course';
                $table->data[] = $row;
            }
        } else {
            $cell = new html_table_cell(get_string('nomatchingcourses', 'backup'));
            $cell->colspan = 3;
            $cell->attributes['class'] = 'notifyproblem';
            $row = new html_table_row(array($cell));
            $row->attributes['class'] = 'rcs-course';
            $table->data[] = $row;
        }
        $output .= html_writer::table($table);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'rcs-search col-12'));
        $attrs = array(
            'type' => 'text',
            'name' => restore_category_search::$VAR_SEARCH,
            'value' => $component->get_search(),
            'class' => 'form-control'
        );
        $output .= html_writer::empty_tag('input', $attrs);
        $attrs = array(
            'type' => 'submit',
            'name' => 'searchcourses',
            'value' => get_string('search'),
            'class' => 'btn btn-secondary'
        );
        $output .= html_writer::empty_tag('input', $attrs);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function render_restore_course_search(restore_course_search $component) {
        $url = $component->get_url();

        $output = html_writer::start_tag('div', array('class' => 'restore-course-search form-inline row mb-1'));
        $output .= html_writer::start_tag('div', array('class' => 'rcs-results col-md-12'));

        $table = new html_table();
        $table->head = array('', get_string('shortnamecourse'), get_string('fullnamecourse'));
        $table->data = array();
        if ($component->get_count() !== 0) {
            foreach ($component->get_results() as $course) {
                $row = new html_table_row();
                $row->attributes['class'] = 'rcs-course';
                if (!$course->visible) {
                    $row->attributes['class'] .= ' dimmed';
                }
                $row->cells = array(
                    html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'targetid', 'value' => $course->id)),
                    format_string($course->shortname, true, array('context' => context_course::instance($course->id))),
                    format_string($course->fullname, true, array('context' => context_course::instance($course->id)))
                );
                $table->data[] = $row;
            }
            if ($component->has_more_results()) {
                $cell = new html_table_cell(get_string('moreresults', 'backup'));
                $cell->colspan = 3;
                $cell->attributes['class'] = 'notifyproblem';
                $row = new html_table_row(array($cell));
                $row->attributes['class'] = 'rcs-course';
                $table->data[] = $row;
            }
        } else {
            $cell = new html_table_cell(get_string('nomatchingcourses', 'backup'));
            $cell->colspan = 3;
            $cell->attributes['class'] = 'notifyproblem';
            $row = new html_table_row(array($cell));
            $row->attributes['class'] = 'rcs-course';
            $table->data[] = $row;
        }
        $output .= html_writer::table($table);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'rcs-search col-md-12'));
        $attrs = array(
            'type' => 'text',
            'name' => restore_course_search::$VAR_SEARCH,
            'value' => $component->get_search(),
            'class' => 'form-control'
        );
        $output .= html_writer::empty_tag('input', $attrs);
        $attrs = array(
            'type' => 'submit',
            'name' => 'searchcourses',
            'value' => get_string('search'),
            'class' => 'btn btn-secondary'
        );
        $output .= html_writer::empty_tag('input', $attrs);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }

}