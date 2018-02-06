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
 * Version details.
 *
 * @package    blocks
 * @subpackage assessment_information
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
defined('TOPIC_ZERO_SECTION') || define('TOPIC_ZERO_SECTION','52');

class block_assessment_information_renderer extends plugin_renderer_base
{
	private $coursepage;
	private $modinfo;

	function block_content(&$content, $instanceid, $config, $assessment_information){

		global $COURSE;

		$this->coursepage = $this->page;
		$this->modinfo = get_fast_modinfo($COURSE);

		$section_wrapper = 'section-wrap';

		if ($this->page->user_is_editing()){
			//add required javascripts
            $this->page->requires->js_init_call('M.block_assessment_information.add_handles');
			$this->page->requires->yui_module('moodle-topiczero-modchooser',
	        'M.block_assessment_information.init_chooser',
	        	array(
	        		array(
	        			'courseid' => $COURSE->id,
	        			'closeButtonTitle' => get_string('close', 'editor')
	        		)
	        	)
	        );
	      	//set section class to editing mode
	        $section_wrapper = 'section-wrap editing';
		}
		//start content
		$html = '';
		//block toggle start
		$html .= html_writer::start_tag('p', array('class'=>'show-content'));
		$html .= html_writer::link(
			'javascript:toggleAndChangeText(
				"'.get_string('show_block_text', 'block_assessment_information').'",
				"'.get_string('hide_block_text', 'block_assessment_information').'"
			);',
			get_string('hide_block_text', 'block_assessment_information').' ▼',
			array('id'=>'aTag')
		);
		$html .= html_writer::end_tag('p');
		//block toggle end

		//block content start
		$html .= html_writer::start_div($section_wrapper, array('id'=>'show-content',
			'style'=>'min-height:0px'));

		//assessment information start
		$html .= html_writer::start_div(null, array('id'=>'coursebox-1'));
		//header
		$assessment_header = isset($config->assessment_header)
			? $config->assessment_header
			: get_string('default_assessment_header', 'block_assessment_information');
		$html .= html_writer::tag('h2', $assessment_header);
		//list assessment resources
		if($this->coursepage->user_is_editing()){
			$resources = $assessment_information->get_course_resources('assessment',0);
		} else {
			$resources = $assessment_information->get_course_resources('assessment');
		}
		$html .= $this->get_resources_list($resources,'assessment');

		//$courserenderer = $this->page->get_renderer('core','course');
        $html .= $this->get_activity_chooser_control  ($COURSE,
        	$assessment_information->topic_zero_section);

		$html .= html_writer::end_div();
		//assessment overview end

		//submit assignments start
		$html .= html_writer::start_div('',array('id'=>'coursebox-2'));
		//header
		$assignment_header = isset($config->assignment_header)
			? $config->assignment_header
			: get_string('default_assignment_header', 'block_assessment_information');
		$html.= html_writer::tag('h2', $assignment_header);
		if(isset($config->assignment_text) && $config->assignment_text['text']){
			$html .= html_writer::tag('p', $config->assignment_text['text'],
				array('class'=>'subheadings_text')
			);
		}
		//list assignment resources
		if($this->coursepage->user_is_editing()){
			$resources = $assessment_information->get_course_resources('assignment',0);
		} else {
			$resources = $assessment_information->get_course_resources('assignment');
		}
		$html .= $this->get_resources_list($resources,'assignment');

		//subheadings start
		if(isset($config->subheadings_title) && $config->subheadings_title){
			$html .= $this->get_assignment_subheadings($assessment_information, $this, $config);
		}
		//subheadings end
		$html .= html_writer::end_div();
		//submit assignments end

		//extra section start
		if(isset($config->enable_extra_section) && $config->enable_extra_section){
			$html .= html_writer::start_div('',array('id'=>'coursebox-1'));
			//header
			$extra_section_header = isset($config->extra_section_header)
				? $config->extra_section_header
				: get_string('default_extra_section_header', 'block_assessment_information');
			$html.= html_writer::tag('h2', $extra_section_header);
			//list subheadings resources
			if($this->coursepage->user_is_editing()){
				$resources = $assessment_information->get_course_resources('extra',0);
			} else {
				$resources = $assessment_information->get_course_resources('extra');
			}
			$html .= $this->get_resources_list($resources,'extra');
			$html .= html_writer::end_div();
		}
		//extra section end
		$html .= html_writer::end_div();
		//block content end

		$content->text = $html;

		//$content->footer = $this->get_add_activities_resources_link();
	}

	public static function get_assignment_subheadings($assessment_information,$that,$config){
		$html = '';
		if(!is_array($config->subheadings_title)) {
            if ($config->subheadings_background) {
                $html .= html_writer::tag('h3', $config->subheadings_title, array(
                    'class' => 'subheadings_title',
                    'style' => 'background:' . $config->subheadings_background
                ));
            } else {
                $html .= html_writer::start_tag('p', array('class' => 'modassignment'));
                $html .= html_writer::tag('strong', $config->subheadings_title);
                $html .= html_writer::end_tag('p');
            }
            if ($config->subheadings_text['text']) {
                $html .= html_writer::tag('p', $config->subheadings_text['text'],
                    array('class' => 'subheadings_text')
                );
            }
            //list subheadings resources
            if($that->coursepage->user_is_editing()){
                $resources = $assessment_information->get_course_resources('subheadings',0);
            } else {
                $resources = $assessment_information->get_course_resources('subheadings');
            }
            $html .= $that->get_resources_list($resources,'subheadings');
        } else {
            $totalSubHeadings = sizeof($config->subheadings_title);
            for ($i = 0; $i<$totalSubHeadings; $i++) {
                if ($config->subheadings_background[$i]) {
                    $html .= html_writer::tag('h3', $config->subheadings_title[$i], array(
                        'class' => 'subheadings_title',
                        'style' => 'background:' . $config->subheadings_background[$i]
                    ));
                } else {
                    $html .= html_writer::start_tag('p', array('class' => 'modassignment'));
                    $html .= html_writer::tag('strong', $config->subheadings_title[$i]);
                    $html .= html_writer::end_tag('p');
                }
                if ($config->subheadings_text[$i]['text']) {
                    $html .= html_writer::tag('p', $config->subheadings_text[$i]['text'],
                        array('class' => 'subheadings_text')
                    );
                }
                $subheadings='subheadings';
                if ($i!=0){
                    $subheadings.='-'.$i;
                }
                //list subheadings resources
                if($that->coursepage->user_is_editing()){
                    $resources = $assessment_information->get_course_resources($subheadings,0);
                } else {
                    $resources = $assessment_information->get_course_resources($subheadings);
                }
                $html .= $that->get_resources_list($resources,$subheadings);
            }
        }
		return $html;
	}

	public function get_resources_list($resources, $section){
		$html = '';

		$html .= html_writer::start_tag('ul', array('class'=>'resource-list',
			'id'=>$section));
		foreach ($resources as $resource) {
			if(!array_key_exists($resource->itemid, $this->modinfo->cms)) {
				continue;
			}
			$visibleclass = $resource->visible ? ' hide-link' : ' show-link';
			$html .= html_writer::start_div('resource clearfix'.$visibleclass);
			if($this->coursepage->user_is_editing()){
				$moveicon = html_writer::empty_tag('img',
                        array('src' => $this->pix_url('t/move')->out(false),
                            'alt' => 'move',
                            'title' => get_string('move')));
                $moveurl = new moodle_url($this->page->url, array(
                	'moveresource' => 1, 'itemid' => $resource->id
                ));
                $moveurl = html_writer::link($moveurl, $moveicon);
                $html .= html_writer::tag('div', $moveurl, array('class' => 'move'));
			}
			$mod = $this->modinfo->cms[$resource->itemid];
			$resource_link  = html_writer::link(
				new moodle_url($resource->url),
				$mod->get_formatted_name()
			);
			$html .= html_writer::tag('li', $resource_link, array(
				'id'=>$resource->id,
				'style'=>'background:url('.$mod->get_icon_url().') no-repeat'
			));

			if($this->coursepage->user_is_editing()){
				$toggleclass = $resource->visible ? 'hide' : 'show';
				$html .= html_writer::link(
					'#', ucfirst($toggleclass), array(
						'title'=>ucfirst($toggleclass) .' Link',
						'class'=>'remove-link',
						'data-id'=>$resource->id,
						'data-state'=>$resource->visible
					)
				);
			}
			$html .= html_writer::end_div();
		}
		$html .= html_writer::end_tag('ul');

		return $html;
	}
	//This is a function copied from course renderer
	function get_activity_chooser_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $CFG;

        $vertical = !empty($displayoptions['inblock']);

        // check to see if user can add menus and there are modules to add
        if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))
                || !$this->page->user_is_editing()
                || !($modnames = get_module_types_names()) || empty($modnames)) {
            return '';
        }

        // Retrieve all modules with associated metadata
        $modules = get_module_metadata($course, $modnames, $sectionreturn);
        $urlparams = array('section' => $section);

        // We'll sort resources and activities into two lists
        $activities = array(MOD_CLASS_ACTIVITY => array(), MOD_CLASS_RESOURCE => array());

        foreach ($modules as $module) {
            if (isset($module->types)) {
                // This module has a subtype
                // NOTE: this is legacy stuff, module subtypes are very strongly discouraged!!
                $subtypes = array();
                foreach ($module->types as $subtype) {
                    $link = $subtype->link->out(true, $urlparams);
                    $subtypes[$link] = $subtype->title;
                }

                // Sort module subtypes into the list
                $activityclass = MOD_CLASS_ACTIVITY;
                if ($module->archetype == MOD_CLASS_RESOURCE) {
                    $activityclass = MOD_CLASS_RESOURCE;
                }
                if (!empty($module->title)) {
                    // This grouping has a name
                    $activities[$activityclass][] = array($module->title => $subtypes);
                } else {
                    // This grouping does not have a name
                    $activities[$activityclass] = array_merge($activities[$activityclass], $subtypes);
                }
            } else {
                // This module has no subtypes
                $activityclass = MOD_CLASS_ACTIVITY;
                if ($module->archetype == MOD_ARCHETYPE_RESOURCE) {
                    $activityclass = MOD_CLASS_RESOURCE;
                } else if ($module->archetype === MOD_ARCHETYPE_SYSTEM) {
                    // System modules cannot be added by user, do not add to dropdown
                    continue;
                }
                $link = $module->link->out(true, $urlparams);
                $activities[$activityclass][$link] = $module->title;
            }
        }

        $straddactivity = get_string('addactivity');
        $straddresource = get_string('addresource');
        $sectionname = get_section_name($course, $section);
        $strresourcelabel = get_string('addresourcetosection', null, $sectionname);
        $stractivitylabel = get_string('addactivitytosection', null, $sectionname);

        $output = html_writer::start_tag('div', array('class' => 'section_add_menus', 'id' => 'add_menus-section-' . $section));

        if (!$vertical) {
            $output .= html_writer::start_tag('div', array('class' => 'horizontal'));
        }

        if (!empty($activities[MOD_CLASS_RESOURCE])) {
            $select = new url_select($activities[MOD_CLASS_RESOURCE], '', array(''=>$straddresource), "ressection$section");
            $select->set_help_icon('resources');
            $select->set_label($strresourcelabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!empty($activities[MOD_CLASS_ACTIVITY])) {
            $select = new url_select($activities[MOD_CLASS_ACTIVITY], '', array(''=>$straddactivity), "section$section");
            $select->set_help_icon('activities');
            $select->set_label($stractivitylabel, array('class' => 'accesshide'));
            $output .= $this->output->render($select);
        }

        if (!$vertical) {
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::end_tag('div');

        if (course_ajax_enabled($course) && $course->id == $this->page->course->id) {
            // modchooser can be added only for the current course set on the page!
            $straddeither = get_string('addresourceoractivity');
            // The module chooser link
            $modchooser = html_writer::start_tag('div', array('class' => 'mdl-right'));
            $modchooser.= html_writer::start_tag('div', array('class' => 'section-modchooser'));
            $icon = $this->output->pix_icon('t/add', '');
            $span = html_writer::tag('span', $straddeither, array('class' => 'section-modchooser-text'));
            $modchooser .= html_writer::tag('span', $icon . $span, array('class' => 'section-assessment-modchooser-link'));
            $modchooser.= html_writer::end_tag('div');
            $modchooser.= html_writer::end_tag('div');

            // Wrap the normal output in a noscript div
            $usemodchooser = get_user_preferences('usemodchooser', $CFG->modchooserdefault);
            if ($usemodchooser) {
                $output = html_writer::tag('div', $output, array('class' => 'hiddenifjs addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'visibleifjs addresourcemodchooser'));
            } else {
                // If the module chooser is disabled, we need to ensure that the dropdowns are shown even if javascript is disabled
                $output = html_writer::tag('div', $output, array('class' => 'show addresourcedropdown'));
                $modchooser = html_writer::tag('div', $modchooser, array('class' => 'hide addresourcemodchooser'));
            }
            $courserenderer = $this->page->get_renderer('core','course');
            $output = $courserenderer->course_modchooser($modules, $course) . $modchooser . $output;
        }

        return $output;
    }
    function premix_resources_settings($courseid){
    	global $DB, $PAGE;
    	$html = '';
    	$html .= html_writer::start_div('premix-resourses-wrap');
    	$html .= html_writer::tag('h2',get_string('premix_resourses_heading','block_assessment_information'));
    	if (!$courseid) {
    		$html .= html_writer::tag('p',get_string('coursenotdefined','block_assessment_information'));
    	} else {
    		$course = $DB->get_record('course', array('id'=>$courseid));
    		course_create_sections_if_missing($course,TOPIC_ZERO_SECTION);
    		$section = $DB->get_record('course_sections', array('section'=>TOPIC_ZERO_SECTION, 'course'=>$courseid));
    		$premixresources = $DB->count_records('course_modules',array(
				'course' => $course->id,
				'section' => $section->id
			));
			if($premixresources){
				$html .= html_writer::tag('p',get_string('premixresourcesavailable','block_assessment_information',$course->fullname));
				$courserenderer = $PAGE->get_renderer('core','course');
				$html .= $courserenderer->course_section_cm_list($course, $section, 0);
			} else {
				$html .= html_writer::tag('p',get_string('premixresourcesnotavailable','block_assessment_information',$course->fullname));
			}
			$html .= $this->get_activity_chooser_control($course,TOPIC_ZERO_SECTION);
    	}
    	$html .= html_writer::end_div();
    	return $html;
    }
}