<?php
/****************************************************************

File:       block/module_info/edit_form.php

Purpose:    Class to define block instance configuration form

 ****************************************************************/

global $CFG;

require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->dirroot . '/blocks/module_info/lib.php');

class block_module_info_edit_form extends block_edit_form {

    private $file_manager_data = null;

    private $sits_data = array();

    protected function specific_definition($mform) {

        global $COURSE, $PAGE;

        $PAGE->requires->js_init_call('M.block_module_info.init_section_display');
        // Block title
        $block_title = array();
        $block_title[] = &$mform->createElement('advcheckbox', 'config_title_override', '', null, array('group'=>1));
        $mform->setDefault('config_title_override', false);
        $block_title[] = $mform->createElement('text', 'config_title', '');
        $mform->setDefault('config_title', get_string('module_info', 'block_module_info'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->disabledIf('config_title','config_title_override');
        $mform->addGroup($block_title, 'block_title', get_string('block_title', 'block_module_info'), array(' '), false);
        $mform->addHelpButton('block_title','block_title','block_module_info');

        /*START CORE INFO SETTINGS*/

        $mform->addElement('header', 'configheader', get_string('coreinfo_header', 'block_module_info'));

        //Start section settings wrapper
        $mform->addElement('html', '<div class="section-settings-wrap clearfix">');

        //Start title override
        $mform->addElement('html','<div class="title-override">');
        $section_coreinfo_title = array();
        $section_coreinfo_title[] = &$mform->createElement('advcheckbox', 'config_coreinfo_title_override', null, null, array('group'=>1));
        $mform->setDefault('config_coreinfo_title_override', false);
        $section_coreinfo_title[] = &$mform->createElement('text','config_coreinfo_title','');
        $mform->setDefault('config_coreinfo_title', get_string('config_coreinfo_title_default', 'block_module_info'));
        $mform->setType('config_coreinfo_title', PARAM_TEXT);
        $mform->disabledIf('config_coreinfo_title','config_coreinfo_title_override');
        $mform->addGroup($section_coreinfo_title, 'section_coreinfo', null, array(' '), false);
        $mform->addElement('html', '</div>');
        //End title override

        //Start section hide
        $mform->addElement('html','<div class="section-hide">');
        $mform->addElement('advcheckbox', 'config_coreinfo_hide', null, get_string('hide_section_block', 'block_module_info'), array('group'=>1));
        $mform->setDefault('config_coreinfo_hide', false);
        $mform->addElement('html', '</div>');
        //End section hide
        $mform->addElement('html', '</div>');
        //End section settings wrapper

        //CoreInfo Description
        $mform->addElement('html', '<div class="section-desc">');
        $mform->addElement('html', get_string('coreinfo_desc','block_module_info'));
        $mform->addElement('html', '</div>');

        $this->sits_data = $sits_core_information = get_core_information_from_sits($COURSE->idnumber);

        if(isset($this->block->config->module_code) && $this->block->config->module_code && !empty($sits_core_information['module_code']) && $sits_core_information['module_code'] != $this->block->config->module_code_override){
            $mform->addElement('html', get_string('coreinfo_data_mismatch', 'block_module_info'));
        }

        $module_code = array();
        $module_code[] = & $mform->createElement('advcheckbox', 'config_module_code', null, null, array('group'=>1));
        $mform->setDefault('config_module_code', false);
        $module_code[] = & $mform->createElement('text', 'config_module_code_override', get_string('config_module_code_override', 'block_module_info'));
        $mform->addGroup($module_code, 'module_code', get_string('config_module_code', 'block_module_info'), array(' '), false);
        $mform->setType('config_module_code_override', PARAM_TEXT);
        $mform->addHelpButton('module_code', 'module_code', 'block_module_info');
        $mform->disabledIf('config_module_code_override','config_module_code');
        $mform->setDefault('config_module_code_override', $sits_core_information['module_code']);

        if(isset($this->block->config->module_level) && $this->block->config->module_level && !empty($sits_core_information['module_level']) && $sits_core_information['module_level'] != $this->block->config->module_level_override){
            $mform->addElement('html', get_string('coreinfo_data_mismatch', 'block_module_info'));
        }

        $module_level = array();
        $module_level[] = & $mform->createElement('advcheckbox', 'config_module_level', null, null, array('group'=>1));
        $mform->setDefault('config_module_level', false);
        $module_level[] = & $mform->createElement('text', 'config_module_level_override', get_string('config_module_level_override', 'block_module_info'));
        $mform->addGroup($module_level, 'module_level', get_string('config_module_level', 'block_module_info'), array(' '), false);
        $mform->setType('config_module_level_override', PARAM_TEXT);
        $mform->addHelpButton('module_level', 'module_level', 'block_module_info');
        $mform->disabledIf('config_module_level_override','config_module_level');
        $mform->setDefault('config_module_level_override', $sits_core_information['module_level']);

        if(isset($this->block->config->module_credit) && $this->block->config->module_credit && !empty($sits_core_information['module_credit']) && $sits_core_information['module_credit'] != $this->block->config->module_credit_override){
            $mform->addElement('html', get_string('coreinfo_data_mismatch', 'block_module_info'));
        }

        $module_credit = array();
        $module_credit[] = & $mform->createElement('advcheckbox', 'config_module_credit', null, null, array('group'=>1));
        $mform->setDefault('config_module_credit', false);
        $module_credit[] = & $mform->createElement('text', 'config_module_credit_override', get_string('config_module_credit_override', 'block_module_info'));
        $mform->addGroup($module_credit, 'module_credit', get_string('config_module_credit', 'block_module_info'), array(' '), false);
        $mform->setType('config_module_credit_override', PARAM_TEXT);
        $mform->addHelpButton('module_credit', 'module_credit', 'block_module_info');
        $mform->disabledIf('config_module_credit_override','config_module_credit');
        $mform->setDefault('config_module_credit_override', $sits_core_information['module_credit']);

        if(isset($this->block->config->module_semester) && $this->block->config->module_semester && !empty($sits_core_information['module_semester']) && $sits_core_information['module_semester'] != $this->block->config->module_semester_override){
            $mform->addElement('html', get_string('coreinfo_data_mismatch', 'block_module_info'));
        }

        $module_semester = array();
        $module_semester[] = & $mform->createElement('advcheckbox', 'config_module_semester', null, null, array('group'=>1));
        $mform->setDefault('config_module_semester', false);
        $module_semester[] = & $mform->createElement('text', 'config_module_semester_override', get_string('config_module_semester_override', 'block_module_info'));
        $mform->addGroup($module_semester, 'module_semester', get_string('config_module_semester', 'block_module_info'), array(' '), false);
        $mform->setType('config_module_semester_override', PARAM_TEXT);
        $mform->addHelpButton('module_semester', 'module_semester', 'block_module_info');
        $mform->disabledIf('config_module_semester_override','config_module_semester');
        if($sits_core_information['module_semester']){
            $mform->setDefault('config_module_semester_override', $sits_core_information['module_semester']);
        } else {
            $after_hyphen = strchr($COURSE->idnumber, "-");
            if(!empty($after_hyphen) && strlen($after_hyphen) > 2) {
                $mform->setDefault('config_module_semester_override', substr($after_hyphen, 1, 1));
            }
        }

        /*END CORE INFO SETTINGS*/

        /*START TEACHING SETTINGS*/

        $mform->addElement('header', 'configheader', get_string('teaching_header', 'block_module_info'));

        //Start section settings wrapper
        $mform->addElement('html', '<div class="section-settings-wrap clearfix">');

        //Start title override
        $mform->addElement('html','<div class="title-override">');
        $section_teaching_title = array();
        $section_teaching_title[] = &$mform->createElement('advcheckbox', 'config_teaching_title_override', null, null, array('group'=>1));
        $mform->setDefault('config_teaching_title_override', false);
        $section_teaching_title[] = &$mform->createElement('text','config_teaching_title','');
        $mform->setDefault('config_teaching_title', get_string('config_teaching_title_default', 'block_module_info'));
        $mform->setType('config_teaching_title', PARAM_TEXT);
        $mform->disabledIf('config_teaching_title','config_teaching_title_override');
        $mform->addGroup($section_teaching_title, 'section_teaching', null, array(' '), false);
        $mform->addElement('html', '</div>');
        //End title override

        //Start section hide
        $mform->addElement('html','<div class="section-hide">');
        $mform->addElement('advcheckbox', 'config_teaching_hide', null, get_string('hide_section_block', 'block_module_info'), array('group'=>1));
        $mform->setDefault('config_teaching_hide', false);
        $mform->addElement('html', '</div>');
        //End section hide
        $mform->addElement('html', '</div>');
        //End section settings wrapper

        //Teaching Description
        $mform->addElement('html', '<div class="section-desc">');
        $mform->addElement('html', get_string('teaching_desc','block_module_info'));
        $mform->addElement('html', '</div>');

        //Teaching Primary Module Convenor
        $headings_options = array(
            'module_convenor' => get_string('module_convenor', 'block_module_info'),
            'module_organizer' => get_string('module_organizer', 'block_module_info'),
            'module_owner' => get_string('module_owner', 'block_module_info'),
            'convenor' => get_string('convenor', 'block_module_info'),
            'custom_name' => get_string('custom_name', 'block_module_info')
        );

        $mform->addElement('select', 'config_module_convenor_heading', get_string('config_module_convenor_heading', 'block_module_info'), $headings_options);
        $mform->addHelpButton('config_module_convenor_heading','config_module_convenor_heading','block_module_info');
        $mform->addElement('text', 'config_custom_module_convenor_heading', get_string('config_custom_module_convenor_heading', 'block_module_info'), array('class'=>'class_config_custom_module_convenor_heading'));
        $mform->setType('config_custom_module_convenor_heading', PARAM_TEXT);
        $mform->addHelpButton('config_custom_module_convenor_heading','config_custom_module_convenor_heading','block_module_info');
        $mform->disabledIf('config_custom_module_convenor_heading','config_module_convenor_heading','neq','custom_name');

        if(isset($this->block->config->module_convenor) && $this->block->config->module_convenor && !empty($sits_core_information['module_convenor']) && $sits_core_information['module_convenor'] != $this->block->config->module_convenor_override){
            if(empty(get_user_information($this->block->config->module_convenor_override))){
                $mform->addElement('html', get_string('module_convenor_mismatch_and_missing', 'block_module_info'));
            } else {
                $mform->addElement('html', get_string('module_convenor_mismatch', 'block_module_info'));
            }
        } else if(empty(get_user_information($sits_core_information['module_convenor']))){
            $mform->addElement('html', get_string('module_convenor_missing', 'block_module_info'));
        }

        //primary module convenor
        $module_convenor = array();
        $module_convenor[] = & $mform->createElement('advcheckbox', 'config_module_convenor', null, null, array('group'=>1));
        $mform->setDefault('config_module_convenor', false);
        $module_convenor[] = & $mform->createElement('text', 'config_module_convenor_override', get_string('config_module_convenor_override', 'block_module_info'));
        $mform->addGroup($module_convenor, 'module_convenor', get_string('config_module_convenor', 'block_module_info'), array(' '), false);
        $mform->setType('config_module_convenor_override', PARAM_TEXT);
        $mform->addHelpButton('module_convenor', 'module_convenor', 'block_module_info');
        $mform->disabledIf('config_module_convenor_override','config_module_convenor');
        $mform->setDefault('config_module_convenor_override', $sits_core_information['module_convenor']);

        //Additional module convenors
        $course_staffs = array(''=>'Not Selected');
        foreach(get_course_staffs($COURSE->id) as $user){
            $course_staffs[$user->id] = $user->name;
        }

        //Module convenor display settings
        $module_convenor_display = array();
        $module_convenor_display[] = & $mform->createElement('advcheckbox',"config_module_convenor_display_largeimage", null, get_string('largeimage', 'block_module_info'),array('group'=>2));
        $module_convenor_display[] = & $mform->createElement('advcheckbox','config_module_convenor_display_email',null, get_string('email'),array('group'=>2));
        $mform->setDefault('config_module_convenor_display_email',1);
        $module_convenor_display[] = & $mform->createElement('advcheckbox','config_module_convenor_display_url', null, get_string('url'),array('group'=>2));
        $module_convenor_display[] = & $mform->createElement('advcheckbox','config_module_convenor_display_skype', null, get_string('skypeid'),array('group'=>2));
        $module_convenor_display[] = & $mform->createElement('advcheckbox','config_module_convenor_display_phone1', null, get_string('phone1'),array('group'=>2));
        $module_convenor_display[] = & $mform->createElement('advcheckbox','config_module_convenor_display_location', null, get_string('location', 'block_module_info'),array('group'=>2));
        $mform->setDefault('config_module_convenor_display_location',1);
        $module_convenor_display[] = & $mform->createElement('advcheckbox','config_module_convenor_display_officehours', null, get_string('officehours', 'block_module_info'),array('group'=>2));
        $mform->setDefault('config_module_convenor_display_officehours',1);
        $mform->addGroup($module_convenor_display, 'module_convenor_display', get_string('module_convenor_display', 'block_module_info'), null, false);
        $mform->addHelpButton('module_convenor_display', 'module_convenor_display', 'block_module_info');

        //additional roles field
        $additional_user = array();
        $additional_user[] = $mform->createElement('header', 'additional-user', get_string('config_additional_user', 'block_module_info').' {no}');
        $additional_user[] = $mform->createElement('text', 'config_additional_user_heading', get_string('config_additional_user_heading', 'block_module_info'));
        $mform->setType('config_additional_user_heading', PARAM_TEXT);
        $additional_user[] = $mform->createElement('select', 'config_additional_user_staffs', get_string('config_additional_user_staffs', 'block_module_info'), $course_staffs);
        $additional_user[] = $mform->createElement('html', '<div class="display-settings-wrap">');
        $additional_user[] = & $mform->createElement('advcheckbox',"config_additional_user_display_largeimage", get_string('additional_user_display', 'block_module_info'), get_string('largeimage', 'block_module_info'),array('group'=>2));
        $additional_user[] = & $mform->createElement('advcheckbox','config_additional_user_display_email',null, get_string('email'),array('group'=>2));
        $additional_user[] = & $mform->createElement('advcheckbox','config_additional_user_display_url', null, get_string('url'),array('group'=>2));
        $additional_user[] = & $mform->createElement('advcheckbox','config_additional_user_display_skype', null, get_string('skypeid'),array('group'=>2));
        $additional_user[] = & $mform->createElement('advcheckbox','config_additional_user_display_phone1', null, get_string('phone1'),array('group'=>2));
        $additional_user[] = & $mform->createElement('advcheckbox','config_additional_user_display_location', null, get_string('location', 'block_module_info'),array('group'=>2));
        $additional_user[] = & $mform->createElement('advcheckbox','config_additional_user_display_officehours', null, get_string('officehours', 'block_module_info'),array('group'=>2));
        $additional_user[] = $mform->createElement('html', '</div>');
        $usersno = 1;

        if(!empty($this->block->config->additional_user_heading)) {
            $usersno = sizeof($this->block->config->additional_user_heading);
            $usersno += 1;
        }
        $repeateloptions = array();
        $repeateloptions['config_additional_user_heading']['helpbutton'] = array(
            'config_additional_user_heading', 'block_module_info'
        );
        $repeateloptions['config_additional_user_heading']['helpbutton'] = array(
            'config_additional_user_heading', 'block_module_info'
        );
        $repeateloptions['additional-user']['expanded'] = true;
        $repeateloptions['config_additional_user_display_largeimage']['helpbutton'] = array(
            'additional_user_display', 'block_module_info'
        );
        $repeateloptions['config_additional_user_display_email']['default'] = 1;
        $repeateloptions['config_additional_user_display_location']['default'] = 1;
        $repeateloptions['config_additional_user_display_officehours']['default'] = 1;

        $this->repeat_elements($additional_user, $usersno,
            $repeateloptions, 'role_repeats', 'option_role_add_fields', 1, get_string('config_additional_user_add_string', 'block_module_info'), false);

        /*END TEACHING SETTINGS*/

        /*START SCHEDULE SETTINGS*/

        $mform->addElement('header', 'configheader', get_string('schedule_header', 'block_module_info'));

        //Start section settings wrapper
        $mform->addElement('html', '<div class="section-settings-wrap  clearfix">');

        //Start title override
        $mform->addElement('html','<div class="title-override">');
        $section_schedule_title = array();
        $section_schedule_title[] = &$mform->createElement('advcheckbox', 'config_schedule_title_override', null, null, array('group'=>1));
        $mform->setDefault('config_schedule_title_override', false);
        $section_schedule_title[] = &$mform->createElement('text','config_schedule_title','');
        $mform->setDefault('config_schedule_title', get_string('config_schedule_title_default', 'block_module_info'));
        $mform->setType('config_schedule_title', PARAM_TEXT);
        $mform->disabledIf('config_schedule_title','config_schedule_title_override');
        $mform->addGroup($section_schedule_title, 'section_schedule', null, array(' '), false);
        $mform->addElement('html', '</div>');
        //End title override

        //Start section hide
        $mform->addElement('html','<div class="section-hide">');
        $mform->addElement('advcheckbox', 'config_schedule_hide', null, get_string('hide_section_block', 'block_module_info'), array('group'=>1));
        $mform->setDefault('config_schedule_hide', false);
        $mform->addElement('html', '</div>');
        //End section hide
        $mform->addElement('html', '</div>');
        //End section settings wrapper

        //Schedule Description
        $mform->addElement('html', '<div class="section-desc">');
        $mform->addElement('html', get_string('schedule_desc','block_module_info'));
        $mform->addElement('html', '</div>');

        $mform->addElement('advcheckbox', 'config_enable_personal_timetable_link', get_string('config_enable_personal_timetable_link', 'block_module_info'));

        $mform->setDefault('config_enable_personal_timetable_link', 1);
        $mform->addElement('advcheckbox', 'config_enable_module_timetable_link', get_string('config_enable_module_timetable_link', 'block_module_info'));
        $mform->setDefault('config_enable_module_timetable_link', 1);

        $mform->addElement('url', 'config_custom_timetable_url', get_string('config_custom_timetable_url', 'block_module_info'), array('size'=>'50'), array('usefilepicker'=>false));
        $mform->setType('config_custom_timetable_url', PARAM_TEXT);
        $mform->addHelpButton('config_custom_timetable_url', 'config_custom_timetable_url', 'block_module_info');
        $mform->addElement('text', 'config_custom_timetable_text', get_string('config_custom_timetable_text', 'block_module_info'), array('size'=>'30'));
        $mform->setType('config_custom_timetable_text', PARAM_TEXT);
        $mform->setDefault('config_custom_timetable_text', get_string('config_custom_timetable_text_default', 'block_module_info'));
        $mform->addHelpButton('config_custom_timetable_text', 'config_custom_timetable_text', 'block_module_info');

        $sessionarray = array();
        $sessionarray[] = $mform->createElement('header', 'additional-teaching', get_string('config_additional_session','block_module_info').' {no}');
        $sessionarray[] = $mform->createElement('text', 'config_additional_session_subheading', get_string('config_additional_session_subheading','block_module_info'));
        $mform->setType('config_additional_session_subheading', PARAM_TEXT);
        $sessionarray[] = $mform->createElement('text', 'config_additional_session_day', get_string('config_additional_session_day','block_module_info'));
        $mform->setType('config_additional_session_day', PARAM_TEXT);
        $sessionarray[] = $mform->createElement('text', 'config_additional_session_time', get_string('config_additional_session_time','block_module_info'));
        $mform->setType('config_additional_session_time', PARAM_TEXT);
        $sessionarray[] = $mform->createElement('text', 'config_additional_session_location', get_string('config_additional_session_location','block_module_info'));
        $mform->setType('config_additional_session_location', PARAM_TEXT);
        $sessionarray[] = $mform->createElement('hidden', 'additionalsessionid', 0);
        $mform->setType('additionalsessionid', PARAM_INT);

        $sessionno = 1;

        if(!empty($this->block->config->additional_session_subheading)) {
            $sessionno = sizeof($this->block->config->additional_session_subheading);
            $sessionno += 1;
        }

        // No settings options specified for now...
        $repeateloptions = array();
        $repeateloptions['additional-teaching']['expanded'] = true;
        $repeateloptions['config_additional_session_subheading']['helpbutton'] = array(
            'config_additional_session_subheading', 'block_module_info'
        );
        $repeateloptions['config_additional_session_day']['helpbutton'] = array(
            'config_additional_session_day', 'block_module_info'
        );
        $repeateloptions['config_additional_session_time']['helpbutton'] = array(
            'config_additional_session_time','block_module_info'
        );
        $repeateloptions['config_additional_session_location']['helpbutton'] = array(
            'config_additional_session_location','block_module_info'
        );

        $this->repeat_elements($sessionarray, $sessionno,
            $repeateloptions, 'session_repeats', 'option_add_fields', 1, get_string('config_additional_session_add_string', 'block_module_info'), false);
        /*END SCHEDULE SETTINGS*/

        /*START DOCUMENTS SETTINGS*/

        $mform->addElement('header', 'configheader', get_string('documents_header', 'block_module_info'));

        //Start section settings wrapper
        $mform->addElement('html', '<div class="section-settings-wrap clearfix">');

        //Start title override
        $mform->addElement('html','<div class="title-override">');
        $section_documents_title = array();
        $section_documents_title[] = &$mform->createElement('advcheckbox', 'config_documents_title_override', null, null, array('group'=>1));
        $mform->setDefault('config_documents_title_override', false);
        $section_documents_title[] = &$mform->createElement('text','config_documents_title','');
        $mform->setDefault('config_documents_title', get_string('config_documents_title_default', 'block_module_info'));
        $mform->setType('config_documents_title', PARAM_TEXT);
        $mform->disabledIf('config_documents_title','config_documents_title_override');
        $mform->addGroup($section_documents_title, 'section_documents', null, array(' '), false);
        $mform->addElement('html', '</div>');
        //End title override

        //Start section hide
        $mform->addElement('html','<div class="section-hide">');
        $mform->addElement('advcheckbox', 'config_documents_hide', null, get_string('hide_section_block', 'block_module_info'), array('group'=>1));
        $mform->setDefault('config_documents_hide', false);
        $mform->addElement('html', '</div>');
        //End section hide
        $mform->addElement('html', '</div>');
        //End section settings wrapper

        //Documents Description
        $mform->addElement('html', '<div class="section-desc">');
        $mform->addElement('html', get_string('documents_desc','block_module_info'));
        $mform->addElement('html', '</div>');

        $fileoptions = array('subdirs'=>0,
            'maxbytes'=>$COURSE->maxbytes,
            'accepted_types'=>'*',
            'return_types'=>FILE_INTERNAL);

        $this->file_manager_data = new stdClass();
        file_prepare_standard_filemanager($this->file_manager_data,
            'files',
            $fileoptions,
            $this->page->context,
            'block_module_info',
            'documents',
            $this->block->context->id);

        $mform->addElement('filemanager', 'files_filemanager', get_string('files'), null, $fileoptions);
        $mform->addHelpButton('files_filemanager', 'files_filemanager', 'block_module_info');



        /*END DOCUMENTS SETTINGS*/

        /*START OTHERINFO SETTINGS*/
        $mform->addElement('header', 'configheader', get_string('otherinfo_header', 'block_module_info'));

        //End section settings wrapper
        $mform->addElement('html', '<div class="section-settings-wrap clearfix">');

        //Start title override
        $mform->addElement('html','<div class="title-override">');
        $section_otherinfo_title = array();
        $section_otherinfo_title[] = &$mform->createElement('advcheckbox', 'config_otherinfo_title_override', null, null, array('group'=>1));
        $mform->setDefault('config_otherinfo_title_override', false);
        $section_otherinfo_title[] = &$mform->createElement('text','config_otherinfo_title','');
        $mform->setDefault('config_otherinfo_title', get_string('config_otherinfo_title_default', 'block_module_info'));
        $mform->setType('config_otherinfo_title', PARAM_TEXT);
        $mform->disabledIf('config_otherinfo_title','config_otherinfo_title_override');
        $mform->addGroup($section_otherinfo_title, 'section_otherinfo', null, array(' '), false);
        $mform->addElement('html', '</div>');
        //End title override

        //Start section hide
        $mform->addElement('html','<div class="section-hide">');
        $mform->addElement('advcheckbox', 'config_otherinfo_hide', null, get_string('hide_section_block', 'block_module_info'), array('group'=>1));
        $mform->setDefault('config_otherinfo_hide', true);
        $mform->addElement('html', '</div>');
        //End section hide
        $mform->addElement('html', '</div>');
        //End section settings wrapper

        //Other Info Description
        $mform->addElement('html', '<div class="section-desc">');
        $mform->addElement('html', get_string('otherinfo_desc','block_module_info'));
        $mform->addElement('html', '</div>');

        //other info text editor
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean'=>true, 'context'=>$this->block->context);
        $mform->addElement('editor', 'config_htmlcontent', get_string('config_htmlcontent', 'block_module_info'), null, $editoroptions)
            ->setValue(array('text'=>get_string('config_htmlcontent_default', 'block_module_info')));
        $mform->setDefault('config_htmlcontent',array('text'=>get_string('config_htmlcontent_default', 'block_module_info'), 'format'=>FORMAT_HTML));
        $mform->setType('config_htmlcontent', PARAM_RAW);
        /*END OTHERINFO SETTINGS*/

        /*START STAFFSEARCH SETTINGS*/
        $mform->addElement('header', 'configheader', get_string('staffsearch_header', 'block_module_info'));

        //End section settings wrapper
        $mform->addElement('html', '<div class="section-settings-wrap clearfix">');

        //Start section hide
        $mform->addElement('html','<div class="section-hide">');
        $mform->addElement('advcheckbox', 'config_staffsearch_hide', null, get_string('hide_section_block', 'block_module_info'), array('group'=>1));
        $mform->setDefault('config_staffsearch_hide', true);
        $mform->addElement('html', '</div>');
        //End section hide
        $mform->addElement('html', '</div>');
        //End section settings wrapper

        //Staff Search Description
        $mform->addElement('html', '<div class="section-desc">');
        $mform->addElement('html', get_string('staffsearch_desc','block_module_info'));
        $mform->addElement('html', '</div>');

        /*END STAFFSEARCH SETTINGS*/
    }

    /**
     * Handle submitted data
     *
     * Return submitted data if properly submitted
     * or returns NULL if validation fails
     * or if there is no submitted data
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        global $COURSE;

        $data = parent::get_data();
        //$data can be null when form is submitted with other buttons than submit like "Add 1 additional teaching session"
        if($this->_form->isSubmitted() && $data != NULL) {
            //revert sits values if override is unchecked
            if(!$data->config_module_code) {
                $data->config_module_code_override = $this->sits_data['module_code'];
            }
            if(!$data->config_module_level) {
                $data->config_module_level_override = $this->sits_data['module_level'];
            }
            if(!$data->config_module_semester) {
                if($module_semester = $this->sits_data['module_semester']){
                    $data->config_module_semester_override = $this->sits_data['module_semester'];
                } else {
                    $after_hyphen = strchr($COURSE->idnumber, "-");
                    if(!empty($after_hyphen) && strlen($after_hyphen) > 2) {
                        $data->config_module_semester_override = substr($after_hyphen, 1, 1);
                    } else {
                        $data->config_module_semester_override = null;
                    }
                }
            }
            if(!$data->config_module_credit) {
                $data->config_module_credit_override = $this->sits_data['module_credit'];
            }
            if(!$data->config_module_convenor) {
                $data->config_module_convenor_override = $this->sits_data['module_convenor'];
            }

            // Any empty additional teaching sessions need to be removed
            $names = $data->config_additional_session_subheading;

            foreach($names as $key=>$value) {
                if(strlen($value) == 0 || $value == NULL) {
                    unset($data->config_additional_session_subheading[$key]);
                    unset($data->config_additional_session_day[$key]);
                    unset($data->config_additional_session_time[$key]);
                    unset($data->config_additional_session_location[$key]);
                }
            }

            $data->config_additional_session_subheading = array_values($data->config_additional_session_subheading);
            $data->config_additional_session_day = array_values($data->config_additional_session_day);
            $data->config_additional_session_time = array_values($data->config_additional_session_time);
            $data->config_additional_session_location = array_values($data->config_additional_session_location);

            //Any empty additional users need to be removed
            $names = $data->config_additional_user_heading;

            foreach($names as $key=>$value) {
                if(strlen($value) == 0 || $value == NULL) {
                    unset($data->config_additional_user_heading[$key]);
                    unset($data->config_additional_user_staffs[$key]);
                }
            }
            $data->config_additional_user_heading = array_values($data->config_additional_user_heading);
            $data->config_additional_user_staffs = array_values($data->config_additional_user_staffs);

            $fileoptions = array('subdirs'=>1,
                'maxbytes'=>$COURSE->maxbytes,
                'accepted_types'=>'*',
                'return_types'=>FILE_INTERNAL);

            file_postupdate_standard_filemanager($data,
                'files',
                $fileoptions,
                $this->page->context,
                'block_module_info',
                'documents',
                $this->block->context->id);

            return $data;
        }
    }

    /**
     * Load in existing data as form defaults
     *
     * Note that usually new entry defaults are stored directly in form definition
     * This function is normally used to load in data where values already exist and data is being edited
     *
     * @param mixed $default_values object or array of default values
     *
     * @return void
     */
    public function set_data($default_values) {

        $default_values->files_filemanager = $this->file_manager_data->files_filemanager;
        parent::set_data($default_values);

    }

    public function validation($data, $files) {

        $errors = parent::validation($data, $files);

        if(strlen($data['config_title']) > 30) {
            $errors['block_title'] = get_string('block_title_error', 'block_module_info');
        }

        if(strlen($data['config_otherinfo_title']) > 50) {
            $errors['section_otherinfo'] = get_string('config_otherinfo_title_error', 'block_module_info');
        }

        return $errors;
    }
}