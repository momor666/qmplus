<?php

/****************************************************************

File:     /block/course_rollover/db/install.php

Purpose:  Form to create rollover a course

****************************************************************/

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
 * Form to create rollover a course.
 *
 * @package      blocks
 * @subpackage   course_template
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// No direct script access.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');

define('VIEWER_ROLE', 40);

class block_course_rollover_form extends moodleform
{
    private $activity_resets;

    private $courses;

    public function definition()
    {
        global $CFG, $DB;
        $this->build_rollover_form();
    }

    private function build_rollover_form()
    {
        global $CFG, $DB,$PAGE,$OUTPUT;
        $PAGE->requires->js_init_call('M.block_course_rollover.init');

        $mform = & $this->_form;
        $course_rollover_config = $this->_customdata['course_rollover_config'];
        //find out which general reset otions the user can set them self
        $general_resets = explode(',', $course_rollover_config->general_resets);
        $mform->addElement('header', 'scheduled_items', get_string('titles_scheduled_items_fields', 'block_course_rollover'), null, false);
        $mform->addElement('date_selector', 'scheduled_date', get_string('scheduled_date', 'block_course_rollover'), array('startyear' => date('Y'), 'stopyear' => date('Y'), 'timezone' => 99, 'optional' => false));
        $mform->addRule('scheduled_date', null, 'required', null, 'client');
        $mform->addHelpButton('scheduled_date', 'scheduled_date', 'block_course_rollover');
        
        if(isset($this->_customdata['type'])){
            //build the list of courses if page type is category
            $category = coursecat::get($this->_customdata['id']);
            $this->courses = $category->get_courses();

            if($this->courses){
                $this->initialize_activity_resets($course_rollover_config);
                //Default Rollover for all courses for Bulk Rollover
                $mform->addElement('header', 'default_rollover', get_string('default_rollover_courses', 'block_course_rollover'),null);
                $mform->addElement('html', '<p>'.get_string('default_rollover_desc','block_course_rollover').'</p>');

                $category_course = new StdClass();
                $category_course->id = 0;
                if($default_rollover_config = get_config('block_course_rollover','default_rollover_cat_'.$this->_customdata['id'])){
                    $this->category_reset_elements($mform, $category_course, $course_rollover_config, json_decode($default_rollover_config));
                } else {
                    $this->category_reset_elements($mform, $category_course, $course_rollover_config);
                }

                //course list for default rollover
                foreach ($this->courses as $key=>$course) {

                    if($DB->get_record('block_course_rollover', array('courseid'=>$course->id,'status'=>'400'))){
                        $mform->addElement('header', "modecodes[$course->id]", $course->shortname."<span class='status'>(already rolled over)</span>", null);
                        $rend = &$this->_form->defaultRenderer();
                        $rollover_header_template = str_replace('ftoggler', 'ftoggler rollovered', $rend->_headerTemplate);
                        $rend->setElementTemplate($rollover_header_template , "modecodes[$course->id]");
                    }else{
                        $mform->addElement('header', "modecodes[$course->id]", $course->shortname, null);
                    }

                    $mform->addElement('checkbox', "confirmrollover[$course->id]","Rollover this course",null,array('class'=>'confirm_checkbox'));
                    $mform->setDefault("confirmrollover[$course->id]", true);

                    $renderer = $PAGE->get_renderer('block_course_rollover');
                    $mappings_table = $renderer->display_mappings_table($course->id);
                    $mform->addElement('html',$mappings_table);

                    $current_rollover_settings = $DB->get_record('block_course_rollover', array('courseid'=>$course->id,'status'=>'200'));
                    if($current_rollover_settings){
                        course_rollover::get_form_modcode_cat($mform, $course, $current_rollover_settings->modcode, true);
                        $course_reset_data = json_decode($current_rollover_settings->course_reset_data);
                        $this->category_reset_elements($mform, $course, $course_rollover_config, $course_reset_data);
                    } else {
                        course_rollover::get_form_modcode_cat($mform, $course, $course->idnumber);
                        $this->category_reset_elements($mform, $course, $course_rollover_config);
                    }
                }
            } else {
                $mform->addElement('static', 'mod_code_text_desc', '', get_string('category_empty_courses', COURSE_ROLLOVER));
                $mform->addElement('hidden', 'id');
                $mform->setDefault('id', $this->_customdata['id']);
                $mform->setType('id',PARAM_INT);
                $mform->addGroup(array($mform->createElement('cancel', 'cancel', get_string('cancel'))), 'buttonar', '', array(' '), false);
                $mform->closeHeaderBefore('buttonar');
                return;
            }

        } else {

            $renderer = $PAGE->get_renderer('block_course_rollover');
            $mappings_table = $renderer->display_mappings_table($this->_customdata['id']);
            $mform->addElement('html',$mappings_table);

            $current_rollover_settings = $DB->get_record('block_course_rollover', array('courseid'=> $this->_customdata['id'] ,'status'=>'200'));
            if(isset($this->_customdata['edit_mode']->modcode) && $current_rollover_settings) {
                $default_modcode = $this->_customdata['edit_mode']->modcode;
                course_rollover::get_form_modcode($mform, $default_modcode, true);

            } else {
                $default_modcode = $this->_customdata['mod_code'];
                course_rollover::get_form_modcode($mform, $default_modcode);
            }
            //overide short and full names
            $course_reset_data = (object) array();
            if(isset($current_rollover_settings->course_reset_data)){
                $course_reset_data = json_decode($current_rollover_settings->course_reset_data);
            }
            if(isset($this->_customdata['edit_mode']) && ((
                    isset($course_reset_data->overridefullname) && $course_reset_data->overridefullname) ||
                    isset($course_reset_data->overrideshortname) && $course_reset_data->overrideshortname)){
                $mform->addElement('static','show_more', '', '<a data-id="course_names_wrap" class="course_names_toggle">Show Less..</a>');
                $mform->addElement('html','<div id="course_names_wrap" class="course_names_wrap">');
            } else {
                $mform->addElement('static','show_more', '', '<a data-id="course_names_wrap" class="course_names_toggle">Show More..</a>');
                $mform->addElement('html','<div id="course_names_wrap" class="course_names_wrap" style="display:none">');
            }

            $mform->addElement('static','wrap-label', '', '<b>Enter bespoke course full name and short name</b>');
            $elementgroup = array();
            $elementgroup[] = $mform->createElement('text', "fullname", '', 'maxlength="100" size="20"');
            $mform->setType('fullname',PARAM_TEXT);
            $elementgroup[] = $mform->createElement('checkbox', "overridefullname", '', "Enter Fullname");
            $mform->disabledIf("fullname","overridefullname",'notchecked');
            $mform->addGroup($elementgroup, "fullname_group", get_string('fullname', 'block_course_rollover'), ' ', false);
            $mform->addHelpButton("fullname_group", 'fullname', COURSE_ROLLOVER);

            $elementgroup = array();
            $elementgroup[] = $mform->createElement('text', "shortname", '', 'maxlength="100" size="20"');
            $mform->setType('shortname',PARAM_TEXT);
            $elementgroup[] = $mform->createElement('checkbox', "overrideshortname", '', "Enter Short Name");
            $mform->disabledIf("shortname","overrideshortname",'notchecked');
            $mform->addGroup($elementgroup, "shortname_group", get_string('shortname', 'block_course_rollover'), ' ', false);
            $mform->addHelpButton("shortname_group", 'shortname', COURSE_ROLLOVER);

            $mform->addElement('html','</div>');

            //roles reset
            $mform->addElement('header','reset_roles',get_string('header_roles', 'block_course_rollover'));
            $mform->addElement('advcheckbox','overrideroles['.VIEWER_ROLE.']','','Tick the box to remove Viewers',array('group'=>1),array(0,1));

            // resettable items general
            $general_resets = explode(',', $course_rollover_config->general_resets);
            if (count($general_resets) < 13) {
                $mform->addElement('header', 'reset_items', get_string('titles_reset_items_fields', 'block_course_rollover'), null, false);
            }
            $this->reset_elements($mform,$general_resets);

            //activity resets
            if ($allmods = $DB->get_records('modules')) {
                $activity_resets = explode(',', $course_rollover_config->activity_resets);
                foreach ($allmods as $mod) {
                    if (!$DB->count_records($mod->name, array('course' => $this->_customdata['id']))) {
                        continue; // skip mods with no instances
                    }
                    $modfile = $CFG->dirroot . "/mod/$mod->name/lib.php";
                    $mod_reset_course_form_definition = $mod->name . '_reset_course_form_definition';
                    $mod_reset__userdata = $mod->name . '_reset_userdata';
                    if (file_exists($modfile)) {
                        include_once($modfile);
                        if (function_exists($mod_reset_course_form_definition)) {
                            if (!in_array($mod->name, $activity_resets)) {
                                $mod_reset_course_form_definition($mform);
                            }
                        }
                    }
                }
            }
            //set default values of form
            $setdefault = array();
            $unenrol_users = array();
            if (isset($this->_customdata['edit_mode']->course_reset_data)) {
                $setdefault = json_decode($this->_customdata['edit_mode']->course_reset_data);
                $mform->setDefault('scheduled_date', $this->_customdata['edit_mode']->scheduletime);
                $unenrol_users = $setdefault->unenrol_users;
                unset($setdefault->unenrol_users);
            } else {
                //setting what has been setting by the administrator as the default
                if (!empty($course_rollover_config)) {
                    $setdefault = json_decode($course_rollover_config->activity_resets);
                    $unenrol_users = explode(',', $course_rollover_config->unenrol_users);
                }
            }
            if(!empty($setdefault)){
                foreach ($setdefault as $setting => $value) {
                    if ($mform->elementExists($setting) || in_array($setting, array(
                            //TODO: These elements always exists in form but somehow mform->elementExists returns false.
                            'overridemodcode',
                            'fullname',
                            'overridefullname',
                            'shortname',
                            'overrideshortname',
                        ))) {
                        $mform->setDefault($setting, $value);
                    }
                }
            }
            if(in_array(VIEWER_ROLE,$unenrol_users)){
                $mform->setDefault('overrideroles['.VIEWER_ROLE.']',1);
            }
        }
        $mform->addElement('hidden', 'id');
        $mform->setDefault('id', $this->_customdata['id']);
        $mform->setType('id',PARAM_INT);
        $mform->addElement('hidden', 'blockid');
        $mform->setType('blockid',PARAM_INT);
        // $mform->setDefault('blockid', $this->_customdata['blockid']);
        $mform->addElement('hidden', 'unenrol_users', $this->_customdata['course_rollover_config']->unenrol_users);
        $mform->setType('unenrol_users',PARAM_RAW);
        $mform->closeHeaderBefore('closeheader');
        $mform->addElement('static', 'closeheader');
        $mform->addElement('html','<div id="messages_warning">');
        $mform->addElement('html', '<p>'.get_string('messages_confirmation_warning', 'block_course_rollover').'</p>');
        $mform->addElement('html','</div>');
        $this->add_action_buttons(true, get_string('schedule_rollover', 'block_course_rollover'));

    }

    function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        if (array_key_exists('scheduled_date', $data)) {
            if ($data['scheduled_date'] < $this->_customdata['course_rollover_config']->schedule_day) {
                $errors['scheduled_date'] = get_string('messages_start_date_error', 'block_course_rollover');
            }
        }
        return $errors;
    }

    function reset_elements(&$mform, $general_resets){

        if (!in_array('reset_events', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_events', get_string('reset_events', 'block_course_rollover'), array("class"=>"reset_events"));
            $mform->setDefault('reset_events', 1);
            $mform->addHelpButton('reset_events', 'reset_events', 'block_course_rollover');
        }
        if (!in_array('reset_logs', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_logs', get_string('reset_logs', 'block_course_rollover'), array("class"=>"reset_logs"));
            $mform->setDefault('reset_logs', 1);
            $mform->addHelpButton('reset_logs', 'reset_logs', 'block_course_rollover');
        }
        if (!in_array('reset_notes', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_notes', get_string('reset_notes', 'block_course_rollover'), array("class"=>"reset_notes"));
            $mform->setDefault('reset_notes', 1);
            $mform->addHelpButton('reset_notes', 'reset_notes', 'block_course_rollover');
        }
        if (!in_array('reset_comments', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_comments', get_string('reset_comments', 'block_course_rollover'), array("class"=>"reset_comments"));
            $mform->setDefault('reset_comments', 1);
            $mform->addHelpButton('reset_comments', 'reset_comments', 'block_course_rollover');
        }

        if (!in_array('reset_course_completion', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_course_completion', get_string('reset_course_completion', 'block_course_rollover'), array("class"=>"reset_course_completion"));
            $mform->setDefault('reset_course_completion', 1);
            $mform->addHelpButton('reset_course_completion', 'reset_course_completion', 'block_course_rollover');
        }

        if (!in_array('delete_blog_associations', $general_resets)) {
            $mform->addElement('selectyesno', 'delete_blog_associations', get_string('delete_blog_associations', 'block_course_rollover'), array("class"=>"delete_blog_associations"));
            $mform->setDefault('delete_blog_associations', 1);
            $mform->addHelpButton('delete_blog_associations', 'delete_blog_associations', 'block_course_rollover');
        }

        if (!in_array('reset_gradebook_items', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_gradebook_items', get_string('reset_gradebook_items', 'block_course_rollover'), array("class"=>"reset_gradebook_items"));
            $mform->setDefault('reset_gradebook_items', 1);
            $mform->addHelpButton('reset_gradebook_items', 'reset_gradebook_items', 'block_course_rollover');
        }

        if (!in_array('reset_gradebook_grades', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_gradebook_grades', get_string('reset_gradebook_grades', 'block_course_rollover'), array("class"=>"reset_gradebook_grades"));
            $mform->setDefault('reset_gradebook_grades', 1);
            $mform->addHelpButton('reset_gradebook_grades', 'reset_gradebook_grades', 'block_course_rollover');
        }

        if (!in_array('reset_activity_grades', $general_resets)) {
            $mform->addElement('selectyesno', "reset_activity_grades", get_string('reset_activity_grades', 'block_course_rollover'), array("class"=>"reset_activity_grades"));
            $mform->setDefault("reset_activity_grades", 0);
            $mform->addHelpButton("reset_activity_grades", "reset_activity_grades", 'block_course_rollover');
        }

        if (!in_array('reset_groupings_remove', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_groupings_remove', get_string('reset_groupings_remove', 'block_course_rollover'), array("class"=>"reset_groupings_remove"));
            $mform->setDefault('reset_groupings_remove', 1);
            $mform->addHelpButton('reset_groupings_remove', 'reset_groupings_remove', 'block_course_rollover');
        }
        if (!in_array('reset_groupings_members', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_groupings_members', get_string('reset_groupings_members', 'block_course_rollover'), array("class"=>"reset_groupings_members"));
            $mform->setDefault('reset_groupings_members', 1);
            $mform->addHelpButton('reset_groupings_members', 'reset_groupings_members', 'block_course_rollover');
        }
        if (!in_array('reset_groups_remove', $general_resets)) {
            $mform->addElement('selectyesno', 'reset_groups_remove', get_string('reset_groups_remove', 'block_course_rollover'), array("class"=>"reset_groups_remove"));
            $mform->setDefault('reset_groups_remove', 1);
            $mform->addHelpButton('reset_groups_remove', 'reset_groups_remove', 'block_course_rollover');
        }
        if (!in_array('reset_assignments_remove', $general_resets)) {
            $mform->addElement('selectyesno', "reset_assignments_remove", get_string('reset_assignments_remove', 'block_course_rollover'), array("class"=>"reset_assignments_remove"));
            $mform->setDefault("reset_assignments_remove", 0);
            $mform->addHelpButton("reset_assignments_remove", "reset_assignments_remove", 'block_course_rollover');
        }
    }

    function category_reset_elements(&$mform, $course, $course_rollover_config, $course_reset_data = NULL){
        global $DB, $CFG;

        $wrap_class_name = '';
        $course_ids = array();

        if($course->id){
            if(isset($this->_customdata['edit_mode']) && ((
                        isset($course_reset_data->overridefullname) && $course_reset_data->overridefullname) ||
                    isset($course_reset_data->overrideshortname) && $course_reset_data->overrideshortname)){
                $mform->addElement('static','show_more', '', '<a data-id="course_names_wrap'.$course->id.'" class="course_names_toggle">Show Less...</a>');
                $mform->addElement('html','<div id="course_names_wrap'.$course->id.'" class="course_names_wrap">');
            } else {
                $mform->addElement('static','show_more', '', '<a data-id="course_names_wrap'.$course->id.'" class="course_names_toggle">Show More...</a>');
                $mform->addElement('html','<div id="course_names_wrap'.$course->id.'" class="course_names_wrap" style="display:none">');
            }

            $mform->addElement('static','wrap-label', '', '<b>Enter bespoke course full name and short name</b>');
            $elementgroup = array();
            $elementgroup[] = $mform->createElement('text', "fullname[$course->id]", '', 'maxlength="100" size="20"');
            $mform->setType("fullname[$course->id]",PARAM_TEXT);
            $elementgroup[] = $mform->createElement('checkbox', "overridefullname[$course->id]", '', "Enter Fullname");
            $mform->disabledIf("fullname[$course->id]","overridefullname[$course->id]",'notchecked');
            $mform->addGroup($elementgroup, "fullname_group[$course->id]", get_string('fullname', 'block_course_rollover'), ' ', false);
            $mform->addHelpButton("fullname_group[$course->id]", 'fullname', COURSE_ROLLOVER);

            $elementgroup = array();
            $elementgroup[] = $mform->createElement('text', "shortname[$course->id]", '', 'maxlength="100" size="20"');
            $mform->setType("shortname[$course->id]",PARAM_TEXT);
            $elementgroup[] = $mform->createElement('checkbox', "overrideshortname[$course->id]", '', "Enter Short Name");
            $mform->disabledIf("shortname[$course->id]","overrideshortname[$course->id]",'notchecked');
            $mform->addGroup($elementgroup, "shortname_group[$course->id]", get_string('shortname', 'block_course_rollover'), ' ', false);
            $mform->addHelpButton("shortname_group[$course->id]", 'shortname', COURSE_ROLLOVER);

            $mform->addElement('html','</div>');
            //add wrapper class
            $wrap_class_name = 'reset_elements';
        } else {
            $course_ids = array_keys($this->courses);
        }

        $general_resets = explode(',', $course_rollover_config->general_resets);

        //roles reset
        $mform->addElement('html','<fieldset class="clearfix collapsible collapsed" style="margin-left:50px">
            <legend class="ftoggler">'.get_string('header_roles', 'block_course_rollover').'</legend>
            <div class="fcontainer '.$wrap_class_name.'">'
        );
        $mform->addElement('advcheckbox',"overrideroles[$course->id][".VIEWER_ROLE."]",'','Tick the box to remove Viewers',array('group'=>1),array(0,1));
        $mform->addElement('html','</div></fieldset>');
        //general resets
        if(count($general_resets) < 13){
            $mform->addElement('html','<fieldset class="clearfix collapsible collapsed" style="margin-left:50px">
                <legend class="ftoggler">General</legend>
                <div class="fcontainer '.$wrap_class_name.'">'
            );
        }
        if (!in_array('reset_events', $general_resets)) {
            $mform->addElement('selectyesno', "reset_events[$course->id]", get_string('reset_events', 'block_course_rollover'), array("class"=>"reset_events"));
            $mform->setDefault("reset_events[$course->id]", 1);
            $mform->addHelpButton("reset_events[$course->id]", 'reset_events',  'block_course_rollover');
        }
        if (!in_array('reset_logs', $general_resets)) {
            $mform->addElement('selectyesno', "reset_logs[$course->id]", get_string('reset_logs', 'block_course_rollover'), array("class"=>"reset_logs"));
            $mform->setDefault("reset_logs[$course->id]", 1);
            $mform->addHelpButton("reset_logs[$course->id]", 'reset_logs', 'block_course_rollover');
        }
        if (!in_array('reset_notes', $general_resets)) {
            $mform->addElement('selectyesno', "reset_notes[$course->id]", get_string('reset_notes', 'block_course_rollover'), array("class"=>"reset_notes"));
            $mform->setDefault("reset_notes[$course->id]", 1);
            $mform->addHelpButton("reset_notes[$course->id]", "reset_notes", 'block_course_rollover');
        }
        if (!in_array('reset_comments', $general_resets)) {
            $mform->addElement('selectyesno', "reset_comments[$course->id]", get_string('reset_comments', 'block_course_rollover'), array("class"=>"reset_comments"));
            $mform->setDefault("reset_comments[$course->id]", 1);
            $mform->addHelpButton("reset_comments[$course->id]", "reset_comments", 'block_course_rollover');
        }

        if (!in_array('reset_course_completion', $general_resets)) {
            $mform->addElement('selectyesno', "reset_course_completion[$course->id]", get_string('reset_course_completion', 'block_course_rollover'), array("class"=>"reset_course_completion"));
            $mform->setDefault("reset_course_completion[$course->id]", 1);
            $mform->addHelpButton("reset_course_completion[$course->id]", "reset_course_completion", 'block_course_rollover');
        }

        if (!in_array('delete_blog_associations', $general_resets)) {
            $mform->addElement('selectyesno', "delete_blog_associations[$course->id]", get_string('delete_blog_associations', 'block_course_rollover'), array("class"=>"delete_blog_associations"));
            $mform->setDefault("delete_blog_associations[$course->id]", 1);
            $mform->addHelpButton("delete_blog_associations[$course->id]", "delete_blog_associations", 'block_course_rollover');
        }

        if (!in_array('reset_gradebook_items', $general_resets)) {
            $mform->addElement('selectyesno', "reset_gradebook_items[$course->id]", get_string('reset_gradebook_items', 'block_course_rollover'), array("class"=>"reset_gradebook_items"));
            $mform->setDefault("reset_gradebook_items[$course->id]", 1);
            $mform->addHelpButton("reset_gradebook_items[$course->id]", "reset_gradebook_items", 'block_course_rollover');
        }

        if (!in_array('reset_gradebook_grades', $general_resets)) {
            $mform->addElement('selectyesno', "reset_gradebook_grades[$course->id]", get_string('reset_gradebook_grades', 'block_course_rollover'), array("class"=>"reset_gradebook_grades"));
            $mform->setDefault("reset_gradebook_grades[$course->id]", 1);
            $mform->addHelpButton("reset_gradebook_grades[$course->id]", "reset_gradebook_grades", 'block_course_rollover');
        }

        if (!in_array('reset_activity_grades', $general_resets)) {
            $mform->addElement('selectyesno', "reset_activity_grades[$course->id]", get_string('reset_activity_grades', 'block_course_rollover'), array("class"=>"reset_activity_grades"));
            $mform->setDefault("reset_activity_grades[$course->id]", 0);
            $mform->addHelpButton("reset_activity_grades[$course->id]", "reset_activity_grades", 'block_course_rollover');
        }

        if (!in_array('reset_groupings_remove', $general_resets)) {
            $mform->addElement('selectyesno', "reset_groupings_remove[$course->id]", get_string('reset_groupings_remove', 'block_course_rollover'), array("class"=>"reset_groupings_remove"));
            $mform->setDefault("reset_groupings_remove[$course->id]", 1);
            $mform->addHelpButton("reset_groupings_remove[$course->id]", "reset_groupings_remove", 'block_course_rollover');
        }
        if (!in_array('reset_groupings_members', $general_resets)) {
            $mform->addElement('selectyesno', "reset_groupings_members[$course->id]", get_string('reset_groupings_members', 'block_course_rollover'), array("class"=>"reset_groupings_members"));
            $mform->setDefault("reset_groupings_members[$course->id]", 1);
            $mform->addHelpButton("reset_groupings_members[$course->id]", "reset_groupings_members", 'block_course_rollover');
        }
        if (!in_array('reset_groups_remove', $general_resets)) {
            $mform->addElement('selectyesno', "reset_groups_remove[$course->id]", get_string('reset_groups_remove', 'block_course_rollover'), array("class"=>"reset_groups_remove"));
            $mform->setDefault("reset_groups_remove[$course->id]", 1);
            $mform->addHelpButton("reset_groups_remove[$course->id]", "reset_groups_remove", 'block_course_rollover');
        }
        if (!in_array('reset_assignments_remove', $general_resets)) {
            $mform->addElement('selectyesno', "reset_assignments_remove[$course->id]", get_string('reset_assignments_remove', 'block_course_rollover'), array("class"=>"reset_assignments_remove"));
            $mform->setDefault("reset_assignments_remove[$course->id]", 0);
            $mform->addHelpButton("reset_assignments_remove[$course->id]", "reset_assignments_remove", 'block_course_rollover');
        }
        if(count($general_resets) < 13){
            $mform->addElement('html','</div></fieldset>');
        }

        foreach ($this->activity_resets as $modname => $elements) {
            if($course->id){
                if (!$DB->count_records($modname, array('course' => $course->id))) {
                    continue; // skip mods with no instances
                }
            } else {
                if (!$DB->count_records_select($modname, "course IN (".implode(",", $course_ids).")")) {
                    continue; // skip mods with no instances
                }
            }
            $mform->addElement('html','<fieldset class="clearfix collapsible collapsed" style="margin-left:50px">
                <legend class="ftoggler">'.get_string('modulenameplural',$modname).'</legend>
                <div class="fcontainer '.$wrap_class_name.'">'
            );
            foreach ($elements as $element) {
                $options = NULL;
                $course_ids = array_keys($this->courses);
                if(in_array($element->_type,array('select'))){
                    $options = array();
                    foreach ($element->_options as $key => $value) {
                        $options[$value['attr']['value']] = $value['text'];   
                    }
                    $select = $mform->addElement(
                        $element->_type,
                        $element->_attributes['name']."[$course->id]",
                        $element->_label,
                        $options,
                        array('class'=>$element->_attributes['name'])
                    );
                    if(isset($element->_attributes['multiple']) && $element->_attributes['multiple']=='multiple'){
                        $select->setMultiple(true);
                    }

                } else {
                    $mform->addElement(
                        $element->_type,
                        $element->_attributes['name']."[$course->id]",
                        $element->_label,
                        null,
                        array('class'=>$element->_attributes['name'])
                    );
                }
            }
            $mform->addElement('html','</div></fieldset>');
        }
        //set default values in form
        $setdefault = array();
        $unenrol_users = array();
        if ($course_reset_data) {
            $setdefault = $course_reset_data;
            $unenrol_users = $setdefault->unenrol_users;
            unset($setdefault->unenrol_users);
        } else {
            //setting what has been setting by the administrator as the default
            if (!empty($course_rollover_config)) {
                $setdefault = json_decode($course_rollover_config->activity_resets);
                $unenrol_users = explode(',', $course_rollover_config->unenrol_users);
            }
        }
        if(count($setdefault) > 0){
            foreach ($setdefault as $setting => $value) {
                $element = $setting."[".$course->id."]";
                if ($mform->elementExists($element) || in_array($element, array(
                        //TODO: These elements always exists in form but somehow mform->elementExists returns false.
                        "overridemodcode[$course->id]",
                        "fullname[$course->id]",
                        "overridefullname[$course->id]",
                        "shortname[$course->id]",
                        "overrideshortname[$course->id]",
                    ))) {
                    $mform->setDefault($element, $value);
                }
            }
        }
        if(in_array(VIEWER_ROLE,$unenrol_users)){
            $mform->setDefault('overrideroles['.$course->id.']['.VIEWER_ROLE.']',1);
        }
    }

    function initialize_activity_resets($course_rollover_config){
        global $CFG, $DB;
        if ($allmods = $DB->get_records('modules')) {
            require_once($CFG->dirroot.'/blocks/course_rollover/forms/activity_reset_form.php');
            $activity_resets = explode(',', $course_rollover_config->activity_resets);
            foreach ($allmods as $mod) {
                if (in_array($mod->name, $activity_resets)) {
                    continue; // skip mods with no instances
                }
                $modfile = $CFG->dirroot . "/mod/$mod->name/lib.php";
                $mod_reset_course_form_definition = $mod->name . '_reset_course_form_definition';
                $mod_reset__userdata = $mod->name . '_reset_userdata';
                if (file_exists($modfile)) {
                    include_once($modfile);
                    if (function_exists($mod_reset_course_form_definition)) {
                        $activity_reset_form = new activity_reset_form(null, array(
                            'modname'=>$mod->name,
                        ));
                        $activity_reset_elements = $activity_reset_form->get_elements();
                        $this->activity_resets[$mod->name] = $activity_reset_elements;
                    }
                }
            }
        }
    }
}