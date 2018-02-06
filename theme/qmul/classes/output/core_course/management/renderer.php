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

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_qmul
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_qmul\output\core_course\management;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/course/classes/management_renderer.php");

use html_writer;
use coursecat;
use moodle_url;
use course_in_list;
use lang_string;
use context_system;
use stdClass;
use action_menu;
use action_menu_link_secondary;

class renderer extends \core_course_management_renderer {

    public function grid_start($id = null, $class = null) {
        $gridclass = 'grid-row-r d-flex flex-wrap row';
        if (is_null($class)) {
            $class = $gridclass;
        } else {
            $class .= ' ' . $gridclass;
        }
        $attributes = array();
        if (!is_null($id)) {
            $attributes['id'] = $id;
        }
        return html_writer::start_div($class, $attributes);
    }

    public function grid_column_start($size, $id = null, $class = null) {

        if ($id == 'course-detail') {
            $size = 12;
            $bootstrapclass = 'col-md-'.$size;
        } else {
            $bootstrapclass = 'd-flex flex-wrap px-1 mb-1 w-100';
        }

        // Calculate Bootstrap grid sizing.

        // Calculate YUI grid sizing.
        if ($size === 12) {
            $maxsize = 1;
            $size = 1;
        } else {
            $maxsize = 12;
            $divisors = array(8, 6, 5, 4, 3, 2);
            foreach ($divisors as $divisor) {
                if (($maxsize % $divisor === 0) && ($size % $divisor === 0)) {
                    $maxsize = $maxsize / $divisor;
                    $size = $size / $divisor;
                    break;
                }
            }
        }
        if ($maxsize > 1) {
            $yuigridclass =  "grid-col-{$size}-{$maxsize} grid-col";
        } else {
            $yuigridclass =  "grid-col-1 grid-col";
        }

        if (is_null($class)) {
            $class = $yuigridclass . ' ' . $bootstrapclass;
        } else {
            $class .= ' ' . $yuigridclass . ' ' . $bootstrapclass;
        }
        $attributes = array();
        if (!is_null($id)) {
            $attributes['id'] = $id;
        }
        return html_writer::start_div($class, $attributes);
    }

    protected function listing_pagination(coursecat $category, $page, $perpage, $showtotals = false) {
        $html = '';
        $totalcourses = $category->get_courses_count();
        $totalpages = ceil($totalcourses / $perpage);
        if ($showtotals) {
            if ($totalpages == 0) {
                $str = get_string('nocoursesyet');
            } else if ($totalpages == 1) {
                $str = get_string('showingacourses', 'moodle', $totalcourses);
            } else {
                $a = new stdClass;
                $a->start = ($page * $perpage) + 1;
                $a->end = min((($page + 1) * $perpage), $totalcourses);
                $a->total = $totalcourses;
                $str = get_string('showingxofycourses', 'moodle', $a);
            }
            $html .= html_writer::div($str, 'listing-pagination-totals dimmed');
        }

        if ($totalcourses <= $perpage) {
            return $html;
        }
        $aside = 2;
        $span = $aside * 2 + 1;
        $start = max($page - $aside, 0);
        $end = min($page + $aside, $totalpages - 1);
        if (($end - $start) < $span) {
            if ($start == 0) {
                $end = min($totalpages - 1, $span - 1);
            } else if ($end == ($totalpages - 1)) {
                $start = max(0, $end - $span + 1);
            }
        }
        $items = array();
        $baseurl = new moodle_url('/course/management.php', array('categoryid' => $category->id));
        if ($page > 0) {
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => 0)), get_string('first'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => $page - 1)), get_string('prev'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
            $btn = html_writer::tag('a', '...', array('class'=>'page-link'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item disabled'));
        }
        for ($i = $start; $i <= $end; $i++) {
            $class = '';
            if ($page == $i) {
                $class = 'active';
            }
            $pageurl = new moodle_url($baseurl, array('page' => $i));
            $btn = $this->action_button($pageurl, $i + 1, null, '', get_string('pagea', 'moodle', $i+1));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item '.$class));
        }
        if ($page < ($totalpages - 1)) {
            $btn = html_writer::tag('a', '...', array('class'=>'page-link'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item disabled'));
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => $page + 1)), get_string('next'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => $totalpages - 1)), get_string('last'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
        }

        $html .= html_writer::div(join('', $items), 'listing-pagination pagination my-12');
        return $html;
    }

    protected function search_pagination($totalcourses, $page, $perpage, $showtotals = false, $search = '') {
        $html = '';
        $totalpages = ceil($totalcourses / $perpage);
        if ($showtotals) {
            if ($totalpages == 0) {
                $str = get_string('nocoursesfound', 'moodle', s($search));
            } else if ($totalpages == 1) {
                $str = get_string('showingacourses', 'moodle', $totalcourses);
            } else {
                $a = new stdClass;
                $a->start = ($page * $perpage) + 1;
                $a->end = min((($page + 1) * $perpage), $totalcourses);
                $a->total = $totalcourses;
                $str = get_string('showingxofycourses', 'moodle', $a);
            }
            $html .= html_writer::div($str, 'listing-pagination-totals dimmed');
        }

        if ($totalcourses < $perpage) {
            return $html;
        }
        $aside = 2;
        $span = $aside * 2 + 1;
        $start = max($page - $aside, 0);
        $end = min($page + $aside, $totalpages - 1);
        if (($end - $start) < $span) {
            if ($start == 0) {
                $end = min($totalpages - 1, $span - 1);
            } else if ($end == ($totalpages - 1)) {
                $start = max(0, $end - $span + 1);
            }
        }
        $items = array();
        $baseurl = $this->page->url;
        if ($page > 0) {
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => 0)), get_string('first'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => $page - 1)), get_string('prev'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
            $btn = html_writer::tag('a', '...', array('class'=>'page-link'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item disabled'));
        }
        for ($i = $start; $i <= $end; $i++) {
            $class = '';
            if ($page == $i) {
                $class = 'active';
            }
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => $i)), $i + 1, null);
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item '.$class));
        }
        if ($page < ($totalpages - 1)) {
            $btn = html_writer::tag('a', '...', array('class'=>'page-link'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item disabled'));
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => $page + 1)), get_string('next'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
            $btn = $this->action_button(new moodle_url($baseurl, array('page' => $totalpages - 1)), get_string('last'));
            $items[] = html_writer::tag('div', $btn, array('class'=>'page-item'));
        }

        $html .= html_writer::div(join('', $items), 'listing-pagination pagination my-12');
        return $html;
    }

    protected function action_button(moodle_url $url, $text, $id = null, $class = null, $title = null, array $attributes = array()) {
        if (isset($attributes['class'])) {
            $attributes['class'] .= ' page-link';
        } else {
            $attributes['class'] = 'page-link';
        }
        if (!is_null($id)) {
            $attributes['id'] = $id;
        }
        if (!is_null($class)) {
            $attributes['class'] .= ' '.$class;
        }
        if (is_null($title)) {
            $title = $text;
        }
        $attributes['title'] = $title;
        if (!isset($attributes['role'])) {
            $attributes['role'] = 'button';
        }
        return html_writer::link($url, $text, $attributes);
    }

    public function course_detail(course_in_list $course) {
        $details = \core_course\management\helper::get_course_detail_array($course);
        $fullname = $details['fullname']['value'];

        $html  = html_writer::start_div('course-detail card');
        $html .= html_writer::start_div('card-header');
        $html .= html_writer::tag('h3', $fullname, array('id' => 'course-detail-title', 'class'=>'card-title', 'tabindex' => '0'));
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('card-block');
        $html .= $this->course_detail_actions($course);
        foreach ($details as $class => $data) {
            $html .= $this->detail_pair($data['key'], $data['value'], $class);
        }
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    public function course_search_form($value = '', $format = 'plain') {

        //Don't print this
        return '';

        static $count = 0;
        $formid = 'coursesearch';
        if ((++$count) > 1) {
            $formid .= $count;
        }

        switch ($format) {
            case 'navbar' :
                $formid = 'coursesearchnavbar';
                $inputid = 'navsearchbox';
                $inputsize = 20;
                break;
            case 'short' :
                $inputid = 'shortsearchbox';
                $inputsize = 12;
                break;
            default :
                $inputid = 'coursesearchbox';
                $inputsize = 30;
        }

        $strsearchcourses = get_string("searchcourses");
        $searchurl = new moodle_url('/course/management.php');

        $output = html_writer::start_div('col-md-12');
        $output .= html_writer::start_tag('form', array('class'=>'card', 'id' => $formid, 'action' => $searchurl, 'method' => 'get'));
        $output .= html_writer::start_tag('fieldset', array('class' => 'coursesearchbox invisiblefieldset'));
        $output .= html_writer::tag('div', $this->output->heading($strsearchcourses.': ', 3), array('class'=>'card-header'));
        $output .= html_writer::start_div('card-block');
        $output .= html_writer::start_div('input-group');
        $output .= html_writer::empty_tag('input', array('class'=>'form-control', 'type' => 'text', 'id' => $inputid,
            'size' => $inputsize, 'name' => 'search', 'value' => s($value)));
        $output .= html_writer::start_tag('span', array('class' =>'input-group-btn'));
        $output .= html_writer::tag('button', get_string('go'), array('class'=>'btn btn-primary', 'type' => 'submit'));
        $output .= html_writer::end_tag('span');
        $output .= html_writer::end_div();
        $output .= html_writer::end_div();
        $output .= html_writer::end_tag('fieldset');
        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_div();

        return $output;
    }

    public function category_listing(coursecat $category = null) {

        if ($category === null) {
            $selectedparents = array();
            $selectedcategory = null;
        } else {
            $selectedparents = $category->get_parents();
            $selectedparents[] = $category->id;
            $selectedcategory = $category->id;
        }
        $catatlevel = \core_course\management\helper::get_expanded_categories('');
        $catatlevel[] = array_shift($selectedparents);
        $catatlevel = array_unique($catatlevel);

        $listing = coursecat::get(0)->get_children();

        $attributes = array(
            'class' => 'ml-1',
            'role' => 'tree',
            'aria-labelledby' => 'category-listing-title'
        );

        $html  = html_writer::start_div('category-listing card w-100');
        $html .= html_writer::tag('h3', get_string('categories'), array('class'=>'card-header', 'id' => 'category-listing-title'));
        $html .= html_writer::start_div('card-block');
        $html .= $this->category_listing_actions($category);
        $html .= html_writer::start_tag('ul', $attributes);
        foreach ($listing as $listitem) {
            // Render each category in the listing.
            $subcategories = array();
            if (in_array($listitem->id, $catatlevel)) {
                $subcategories = $listitem->get_children();
            }
            $html .= $this->category_listitem(
                $listitem,
                $subcategories,
                $listitem->get_children_count(),
                $selectedcategory,
                $selectedparents
            );
        }
        $html .= html_writer::end_tag('ul');
        $html .= $this->category_bulk_actions($category);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    public function course_listing(coursecat $category = null, course_in_list $course = null, $page = 0, $perpage = 20) {

        if ($category === null) {
            $html = html_writer::start_div('select-a-category');
            $html .= html_writer::tag('h3', get_string('courses'),
                array('id' => 'course-listing-title', 'tabindex' => '0'));
            $html .= $this->output->notification(get_string('selectacategory'), 'notifymessage');
            $html .= html_writer::end_div();
            return $html;
        }

        $page = max($page, 0);
        $perpage = max($perpage, 2);
        $totalcourses = $category->coursecount;
        $totalpages = ceil($totalcourses / $perpage);
        if ($page > $totalpages - 1) {
            $page = $totalpages - 1;
        }
        $options = array(
            'offset' => $page * $perpage,
            'limit' => $perpage
        );
        $courseid = isset($course) ? $course->id : null;
        $class = '';
        if ($page === 0) {
            $class .= ' firstpage';
        }
        if ($page + 1 === (int)$totalpages) {
            $class .= ' lastpage';
        }

        $html  = html_writer::start_div('card course-listing'.$class, array(
            'data-category' => $category->id,
            'data-page' => $page,
            'data-totalpages' => $totalpages,
            'data-totalcourses' => $totalcourses,
            'data-canmoveoutof' => $category->can_move_courses_out_of() && $category->can_move_courses_into()
        ));
        $html .= html_writer::tag('h3', $category->get_formatted_name(),
            array('id' => 'course-listing-title', 'tabindex' => '0', 'class'=>'card-header'));
        $html .= html_writer::start_div('card-block');
        $html .= $this->course_listing_actions($category, $course, $perpage);
        $html .= $this->listing_pagination($category, $page, $perpage);
        $html .= html_writer::start_tag('ul', array('class' => 'ml', 'role' => 'group'));
        foreach ($category->get_courses($options) as $listitem) {
            $html .= $this->course_listitem($category, $listitem, $courseid);
        }
        $html .= html_writer::end_tag('ul');
        $html .= $this->listing_pagination($category, $page, $perpage, true);
        $html .= $this->course_bulk_actions($category);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    public function category_bulk_actions(coursecat $category = null) {
        // Resort courses.
        // Change parent.
        if (!coursecat::can_resort_any() && !coursecat::can_change_parent_any()) {
            return '';
        }
        $strgo = new lang_string('go');

        $html  = html_writer::start_div('category-bulk-actions bulk-actions card');
        $html .= html_writer::start_div('card-block');
        $html .= html_writer::div(get_string('categorybulkaction'), 'accesshide', array('tabindex' => '0'));
        if (coursecat::can_resort_any()) {
            $selectoptions = array(
                'selectedcategories' => get_string('selectedcategories'),
                'allcategories' => get_string('allcategories')
            );
            $form = html_writer::start_div();
            if ($category) {
                $selectoptions = array('thiscategory' => get_string('thiscategory')) + $selectoptions;
                $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'currentcategoryid', 'value' => $category->id));
            }
            $form .= html_writer::div(
                html_writer::select(
                    $selectoptions,
                    'selectsortby',
                    'selectedcategories',
                    false,
                    array('aria-label' => get_string('selectcategorysort'))
                )
            );
            $form .= html_writer::div(
                html_writer::select(
                    array(
                        'name' => get_string('sortbyx', 'moodle', get_string('categoryname')),
                        'namedesc' => get_string('sortbyxreverse', 'moodle', get_string('categoryname')),
                        'idnumber' => get_string('sortbyx', 'moodle', get_string('idnumbercoursecategory')),
                        'idnumberdesc' => get_string('sortbyxreverse' , 'moodle' , get_string('idnumbercoursecategory')),
                        'none' => get_string('dontsortcategories')
                    ),
                    'resortcategoriesby',
                    'name',
                    false,
                    array('aria-label' => get_string('selectcategorysortby'))
                )
            );
            $form .= html_writer::div(
                html_writer::select(
                    array(
                        'fullname' => get_string('sortbyx', 'moodle', get_string('fullnamecourse')),
                        'fullnamedesc' => get_string('sortbyxreverse', 'moodle', get_string('fullnamecourse')),
                        'shortname' => get_string('sortbyx', 'moodle', get_string('shortnamecourse')),
                        'shortnamedesc' => get_string('sortbyxreverse', 'moodle', get_string('shortnamecourse')),
                        'idnumber' => get_string('sortbyx', 'moodle', get_string('idnumbercourse')),
                        'idnumberdesc' => get_string('sortbyxreverse', 'moodle', get_string('idnumbercourse')),
                        'timecreated' => get_string('sortbyx', 'moodle', get_string('timecreatedcourse')),
                        'timecreateddesc' => get_string('sortbyxreverse', 'moodle', get_string('timecreatedcourse')),
                        'none' => get_string('dontsortcourses')
                    ),
                    'resortcoursesby',
                    'fullname',
                    false,
                    array('aria-label' => get_string('selectcoursesortby'))
                )
            );
            $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'bulksort', 'value' => get_string('sort')));
            $form .= html_writer::end_div();

            $html .= html_writer::start_div('col-md-12');
            $html .= html_writer::start_div('detail-pair row yui3-g');
            $html .= html_writer::div(html_writer::span(get_string('sorting')), 'pair-key span3 yui3-u-1-4');
            $html .= html_writer::div($form, 'pair-value span9 yui3-u-3-4');
            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
        }
        if (coursecat::can_change_parent_any()) {
            $options = array();
            if (has_capability('moodle/category:manage', context_system::instance())) {
                $options[0] = coursecat::get(0)->get_formatted_name();
            }
            $options += coursecat::make_categories_list('moodle/category:manage');
            $select = html_writer::select(
                $options,
                'movecategoriesto',
                '',
                array('' => 'choosedots'),
                array('aria-labelledby' => 'moveselectedcategoriesto')
            );
            $submit = array('type' => 'submit', 'name' => 'bulkmovecategories', 'value' => get_string('move'));
            $html .= html_writer::div(get_string('moveselectedcategoriesto'), '', array('id' => 'moveselectedcategoriesto')) .
                        $select . html_writer::empty_tag('input', $submit);
        }
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    public function category_listitem(coursecat $category, array $subcategories, $totalsubcategories,
                                      $selectedcategory = null, $selectedcategories = array()) {

        $isexpandable = ($totalsubcategories > 0);
        $isexpanded = (!empty($subcategories));
        $activecategory = ($selectedcategory === $category->id);
        $attributes = array(
            'class' => 'listitem listitem-category',
            'data-id' => $category->id,
            'data-expandable' => $isexpandable ? '1' : '0',
            'data-expanded' => $isexpanded ? '1' : '0',
            'data-selected' => $activecategory ? '1' : '0',
            'data-visible' => $category->visible ? '1' : '0',
            'role' => 'treeitem',
            'aria-expanded' => $isexpanded ? 'true' : 'false'
        );
        $text = $category->get_formatted_name();
        if ($category->parent) {
            $a = new stdClass;
            $a->category = $text;
            $a->parentcategory = $category->get_parent_coursecat()->get_formatted_name();
            $textlabel = get_string('categorysubcategoryof', 'moodle', $a);
        }
        $courseicon = $this->output->pix_icon('i/course', get_string('courses'));
        $bcatinput = array(
            'type' => 'checkbox',
            'name' => 'bcat[]',
            'value' => $category->id,
            'class' => 'bulk-action-checkbox',
            'aria-label' => get_string('bulkactionselect', 'moodle', $text),
            'data-action' => 'select'
        );

        if (!$category->can_resort_subcategories() && !$category->has_manage_capability()) {
            // Very very hardcoded here.
            $bcatinput['style'] = 'visibility:hidden';
        }

        $viewcaturl = new moodle_url('/course/management.php', array('categoryid' => $category->id));
        if ($isexpanded) {
            $icon = $this->output->pix_icon('t/switch_minus', get_string('collapse'), 'moodle', array('class' => 'tree-icon', 'title' => ''));
            $icon = html_writer::link(
                $viewcaturl,
                $icon,
                array(
                    'class' => 'float-sm-left',
                    'data-action' => 'collapse',
                    'title' => get_string('collapsecategory', 'moodle', $text),
                    'aria-controls' => 'subcategoryof'.$category->id
                )
            );
        } else if ($isexpandable) {
            $icon = $this->output->pix_icon('t/switch_plus', get_string('expand'), 'moodle', array('class' => 'tree-icon', 'title' => ''));
            $icon = html_writer::link(
                $viewcaturl,
                $icon,
                array(
                    'class' => 'float-sm-left sibling-cat',
                    'data-action' => 'expand',
                    'title' => get_string('expandcategory', 'moodle', $text)
                )
            );
        } else {
            $icon = $this->output->pix_icon(
                'i/navigationitem',
                '',
                'moodle',
                array('class' => 'tree-icon', 'title' => get_string('showcategory', 'moodle', $text))
            );
            $icon = html_writer::span($icon, 'float-sm-left parent-cat');
        }
        $actions = \core_course\management\helper::get_category_listitem_actions($category);
        $hasactions = !empty($actions) || $category->can_create_course();

        $html = html_writer::start_tag('li', $attributes);
        $html .= html_writer::start_div('row');
        $html .= html_writer::start_div('col-md-6');
        $html .= html_writer::start_div('float-sm-left ba-checkbox');
        $html .= html_writer::empty_tag('input', $bcatinput).'&nbsp;';
        $html .= html_writer::end_div();
        $html .= $icon;
        if ($hasactions) {
            $textattributes = array('class' => 'float-sm-left categoryname');
        } else {
            $textattributes = array('class' => 'float-sm-left categoryname without-actions');
        }
        if (isset($textlabel)) {
            $textattributes['aria-label'] = $textlabel;
        }
        $html .= html_writer::link($viewcaturl, $text, $textattributes);
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('float-right col-md-6 text-right');
        if ($category->idnumber) {
            $html .= html_writer::tag('span', s($category->idnumber), array('class' => 'dimmed idnumber'));
        }
        if ($hasactions) {
            $html .= $this->category_listitem_actions($category, $actions);
        }
        $countid = 'course-count-'.$category->id;
        $html .= html_writer::span(
            html_writer::span($category->get_courses_count(), 'badge badge-default') .
            html_writer::span(get_string('courses'), 'accesshide', array('id' => $countid)) .
            $courseicon,
            'course-count dimmed',
            array('aria-labelledby' => $countid)
        );
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        if ($isexpanded) {
            $html .= html_writer::start_tag('ul',
                array('class' => 'ml-1', 'role' => 'group', 'id' => 'subcategoryof'.$category->id));
            $catatlevel = \core_course\management\helper::get_expanded_categories($category->path);
            $catatlevel[] = array_shift($selectedcategories);
            $catatlevel = array_unique($catatlevel);
            foreach ($subcategories as $listitem) {
                $childcategories = (in_array($listitem->id, $catatlevel)) ? $listitem->get_children() : array();
                $html .= $this->category_listitem(
                    $listitem,
                    $childcategories,
                    $listitem->get_children_count(),
                    $selectedcategory,
                    $selectedcategories
                );
            }
            $html .= html_writer::end_tag('ul');
        }
        $html .= html_writer::end_tag('li');
        return $html;
    }

    public function category_listitem_actions(coursecat $category, array $actions = null) {
        if ($actions === null) {
            $actions = \core_course\management\helper::get_category_listitem_actions($category);
        }
        $menu = new action_menu();
        $menu->attributes['class'] .= ' category-item-actions item-actions';
        $hasitems = false;
        foreach ($actions as $key => $action) {
            $hasitems = true;
            $menu->add(new \action_menu_link(
                $action['url'],
                $action['icon'],
                $action['string'],
                in_array($key, array('show', 'hide', 'moveup', 'movedown')),
                array('data-action' => $key, 'class' => 'action-'.$key)
            ));
        }
        if (!$hasitems) {
            return '';
        }
        return $this->render($menu);
    }

    protected function detail_pair($key, $value, $class ='') {
        $html = html_writer::start_div('detail-pair row yui3-g '.preg_replace('#[^a-zA-Z0-9_\-]#', '-', $class));
        $html .= html_writer::div(html_writer::span($key), 'pair-key col-md-4 yui3-u-1-4 font-weight-bold');
        $html .= html_writer::div(html_writer::span($value), 'pair-value col-md-8 yui3-u-3-4');
        $html .= html_writer::end_div();
        return $html;
    }

    public function course_detail_actions(course_in_list $course) {
        $actions = \core_course\management\helper::get_course_detail_actions($course);
        if (empty($actions)) {
            return '';
        }
        $options = array();
        foreach ($actions as $action) {
            $options[] = $this->action_link($action['url'], $action['string'], null, array('class'=>'btn btn-secondary'));
        }
        return html_writer::div(join('', $options), 'btn-group btn-group-sm listing-actions course-detail-listing-actions');
    }

    public function course_listing_actions(coursecat $category, course_in_list $course = null, $perpage = 20) {
        $actions = array();
        if ($category->can_create_course()) {
            $url = new moodle_url('/course/edit.php', array('category' => $category->id, 'returnto' => 'catmanage'));
            $actions[] = html_writer::link($url, get_string('createnewcourse'), array('class'=>'btn btn-secondary'));
        }
        if ($category->can_request_course()) {
            // Request a new course.
            $url = new moodle_url('/course/request.php', array('return' => 'management'));
            $actions[] = html_writer::link($url, get_string('requestcourse'), array('class'=>'btn btn-secondary'));
        }
        if ($category->can_resort_courses()) {
            $params = $this->page->url->params();
            $params['action'] = 'resortcourses';
            $params['sesskey'] = sesskey();
            $baseurl = new moodle_url('/course/management.php', $params);
            $fullnameurl = new moodle_url($baseurl, array('resort' => 'fullname'));
            $fullnameurldesc = new moodle_url($baseurl, array('resort' => 'fullnamedesc'));
            $shortnameurl = new moodle_url($baseurl, array('resort' => 'shortname'));
            $shortnameurldesc = new moodle_url($baseurl, array('resort' => 'shortnamedesc'));
            $idnumberurl = new moodle_url($baseurl, array('resort' => 'idnumber'));
            $idnumberdescurl = new moodle_url($baseurl, array('resort' => 'idnumberdesc'));
            $timecreatedurl = new moodle_url($baseurl, array('resort' => 'timecreated'));
            $timecreateddescurl = new moodle_url($baseurl, array('resort' => 'timecreateddesc'));
            $menu = new action_menu(array(
                new action_menu_link_secondary($fullnameurl,
                                               null,
                                               get_string('sortbyx', 'moodle', get_string('fullnamecourse'))),
                new action_menu_link_secondary($fullnameurldesc,
                                               null,
                                               get_string('sortbyxreverse', 'moodle', get_string('fullnamecourse'))),
                new action_menu_link_secondary($shortnameurl,
                                               null,
                                               get_string('sortbyx', 'moodle', get_string('shortnamecourse'))),
                new action_menu_link_secondary($shortnameurldesc,
                                               null,
                                               get_string('sortbyxreverse', 'moodle', get_string('shortnamecourse'))),
                new action_menu_link_secondary($idnumberurl,
                                               null,
                                               get_string('sortbyx', 'moodle', get_string('idnumbercourse'))),
                new action_menu_link_secondary($idnumberdescurl,
                                               null,
                                               get_string('sortbyxreverse', 'moodle', get_string('idnumbercourse'))),
                new action_menu_link_secondary($timecreatedurl,
                                               null,
                                               get_string('sortbyx', 'moodle', get_string('timecreatedcourse'))),
                new action_menu_link_secondary($timecreateddescurl,
                                               null,
                                               get_string('sortbyxreverse', 'moodle', get_string('timecreatedcourse')))
            ));
            $menu->attributes['class'] .= ' btn btn-secondary';
            $menu->set_menu_trigger(get_string('resortcourses'));
            $actions[] = $this->render($menu);
        }
        $strall = get_string('all');
        $menu = new action_menu(array(
            new action_menu_link_secondary(new moodle_url($this->page->url, array('perpage' => 5)), null, 5),
            new action_menu_link_secondary(new moodle_url($this->page->url, array('perpage' => 10)), null, 10),
            new action_menu_link_secondary(new moodle_url($this->page->url, array('perpage' => 20)), null, 20),
            new action_menu_link_secondary(new moodle_url($this->page->url, array('perpage' => 50)), null, 50),
            new action_menu_link_secondary(new moodle_url($this->page->url, array('perpage' => 100)), null, 100),
            new action_menu_link_secondary(new moodle_url($this->page->url, array('perpage' => 999)), null, $strall),
        ));
        if ((int)$perpage === 999) {
            $perpage = $strall;
        }
        $menu->attributes['class'] .= ' courses-per-page btn btn-secondary';
        $menu->set_menu_trigger(get_string('perpagea', 'moodle', $perpage));
        $actions[] = $this->render($menu);
        return html_writer::div(join('', $actions), 'btn-group btn-group-sm listing-actions course-listing-actions');
    }

    /**
     * Renderers the actions that are possible for the course category listing.
     *
     * These are not the actions associated with an individual category listing.
     * That happens through category_listitem_actions.
     *
     * @param coursecat $category
     * @return string
     */
    public function category_listing_actions(coursecat $category = null) {
        $actions = array();

        $cancreatecategory = $category && $category->can_create_subcategory();
        $cancreatecategory = $cancreatecategory || coursecat::can_create_top_level_category();
        if ($category === null) {
            $category = coursecat::get(0);
        }

        if ($cancreatecategory) {
            $url = new moodle_url('/course/editcategory.php', array('parent' => $category->id));
            $actions[] = html_writer::link($url, get_string('createnewcategory'), array('class'=>'btn btn-secondary'));
        }
        if (coursecat::can_approve_course_requests()) {
            $actions[] = html_writer::link(new moodle_url('/course/pending.php'), get_string('coursespending'), array('class'=>'btn btn-secondary'));
        }
        if (count($actions) === 0) {
            return '';
        }
        return html_writer::div(join(' | ', $actions), 'listing-actions category-listing-actions btn-group btn-group-sm');
    }

}
