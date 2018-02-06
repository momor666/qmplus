<?php

/****************************************************************

File:       block/module_info/renderer.php

Purpose:    Class with collection of methods that
handle rendering of visual aspects of the block

 ****************************************************************/

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/module_info/lib.php');

class block_module_info_renderer extends plugin_renderer_base {

    /**
     * Array of configuration data
     *
     * @var unknown_type
     */
    private $data = null;

    /**
     * Initialize configuration data
     *
     * @return boolean
     */
    public function initialise($owner) {

        global $CFG, $COURSE;

        if(!empty($data)) {
            return false;
        }

        $this->data = new stdClass();

        $this->data->block_config = $owner->config;
        $this->data->context = $owner->context;

        $sits_core_information = get_core_information_from_sits($COURSE->idnumber);

        $this->data->module_code = $sits_core_information['module_code'];
        $this->data->module_level = $sits_core_information['module_level'];
        $this->data->module_semester = $sits_core_information['module_semester'];
        $this->data->module_credit = $sits_core_information['module_credit'];
        $this->data->module_convenor = $sits_core_information['module_convenor'];

        //get the semester name from the title itself
        if(empty($this->data->module_semester)){
            $after_hyphen = strchr($COURSE->idnumber, "-");
            if(!empty($after_hyphen) && strlen($after_hyphen) > 2) {
                $this->data->module_semester = substr($after_hyphen, 1, 1);
            }
        }

        // override values out of the interim MIS if necessary
        if(!empty($this->data->block_config->module_code) && !empty($this->data->block_config->module_code_override)) {
            $this->data->module_code = $this->data->block_config->module_code_override;
        }
        if(!empty($this->data->block_config->module_level) && !empty($this->data->block_config->module_level_override)) {
            $this->data->module_level = $this->data->block_config->module_level_override;
        }
        if(!empty($this->data->block_config->module_semester) && !empty($this->data->block_config->module_semester_override)) {
            $this->data->module_semester = $this->data->block_config->module_semester_override;
        }
        if(!empty($this->data->block_config->module_credit) && !empty($this->data->block_config->module_credit_override)) {
            $this->data->module_credit = $this->data->block_config->module_credit_override;
        }
        if(!empty($this->data->block_config->module_convenor) && !empty($this->data->block_config->module_convenor_override)) {
            $this->data->module_convenor = $this->data->block_config->module_convenor_override;
        }

        return true;
    }
    /**
     * Create output for the module info section
     *
     * @return html
     */
    public function get_moduleinfo_output() {

        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $result = '';

        if(isset($this->data->block_config->coreinfo_hide) && $this->data->block_config->coreinfo_hide){
            return $result;
        }

        // Now build HTML
        if (! empty ($this->data->module_code)) {
            $result .= html_writer::start_tag('p', array('class'=>'module_specific'));
            $result .= html_writer::tag('span', get_string( 'module_code', 'block_module_info' ).': ',
                array('class'=>'module_info_title'));
            $result .= html_writer::tag('strong', $this->data->module_code);
            $result .= html_writer::end_tag('p');
        }
        if (! empty ($this->data->module_level)) {
            $result .= html_writer::start_tag('p', array('class'=>'module_specific'));
            $result .= html_writer::tag('span', get_string( 'module_level', 'block_module_info' ).': ',
                array('class'=>'module_info_title'));
            $result .= html_writer::tag('strong', $this->data->module_level);
            $result .= html_writer::end_tag('p');
        }
        if (! empty ($this->data->module_credit)) {
            $result .= html_writer::start_tag('p', array('class'=>'module_specific'));
            $result .= html_writer::tag('span', get_string( 'module_credit', 'block_module_info' ).': ',
                array('class'=>'module_info_title'));
            $result .= html_writer::tag('strong', $this->data->module_credit);
            $result .= html_writer::end_tag('p');
        }
        if (! empty ($this->data->module_semester)) {
            $result .= html_writer::start_tag('p', array('class'=>'module_specific'));
            $result .= html_writer::tag('span', get_string( 'module_semester', 'block_module_info' ).': ',
                array('class'=>'module_info_title'));
            $result .= html_writer::tag('strong', $this->data->module_semester);
            $result .= html_writer::end_tag('p');
        }

        // If by this stage result is still empty then display a warning.
        if(empty($result)) {
            $result .= html_writer::tag('p', get_string( 'missing_module', 'block_module_info'), array('class'=>'missing_module'));
        }

        $coreinfo_title = (isset($this->data->block_config->coreinfo_title_override) && $this->data->block_config->coreinfo_title_override) ?
            $this->data->block_config->coreinfo_title : get_string('config_coreinfo_title_default', 'block_module_info');

        return mod_info_collapsible_region_start('core-info', 'modinfo-core-info', $coreinfo_title,
            'modinfo-core-info', true, true).$result.mod_info_collapsible_region_end(true);
    }

    /**
     * Create output for the teaching section
     *
     * @return html
     */
    public function get_teaching_output() {

        $result = '';

        if(isset($this->data->block_config->teaching_hide) && $this->data->block_config->teaching_hide){
            return $result;
        }

        $teaching_title = (isset($this->data->block_config->teaching_title_override) && $this->data->block_config->teaching_title_override) ?
            $this->data->block_config->teaching_title : get_string('config_teaching_title_default', 'block_module_info');

        $result .= mod_info_collapsible_region_start('teaching-heading', 'modinfo-viewlet-teaching', $teaching_title, 'modinfo-teaching', true, true);

        $result .= $this->get_module_convenor_output();

        $result .= mod_info_collapsible_region_end(true);

        return $result;
    }

    /*
     * create output for module convenor
     */
    public function get_module_convenor_output() {
        global $DB;
        $result = '';
        $module_convenors = array();
        //primary module convenor
        if (!empty($this->data->module_convenor)) {
            if($primary_module_convenor = get_user_information($this->data->module_convenor)){
                if(isset($this->data->block_config->module_convenor_heading)){
                    if($this->data->block_config->module_convenor_heading == 'custom_name' &&
                        isset($this->data->block_config->custom_module_convenor_heading) &&
                        !empty($this->data->block_config->custom_module_convenor_heading)){
                        $module_convenor_heading = $this->data->block_config->custom_module_convenor_heading;
                    } else {
                        $module_convenor_heading = get_string($this->data->block_config->module_convenor_heading,'block_module_info');
                    }
                } else {
                    $module_convenor_heading = get_string('module_convenor','block_module_info');
                }
                $display_settings = array(
                    'largeimage' => isset($this->data->block_config->module_convenor_display_largeimage)?
                        (bool)$this->data->block_config->module_convenor_display_largeimage : false,
                    'email' => isset($this->data->block_config->module_convenor_display_email)?
                        (bool)$this->data->block_config->module_convenor_display_email : true,
                    'url' => isset($this->data->block_config->module_convenor_display_url)?
                        (bool)$this->data->block_config->module_convenor_display_url : false,
                    'skype' => isset($this->data->block_config->module_convenor_display_skype)?
                        (bool)$this->data->block_config->module_convenor_display_skype : false,
                    'phone1' => isset($this->data->block_config->module_convenor_display_phone1)?
                        (bool)$this->data->block_config->module_convenor_display_phone1 : false,
                    'location' => isset($this->data->block_config->module_convenor_display_location)?
                        (bool)$this->data->block_config->module_convenor_display_location : true,
                    'officehours' => isset($this->data->block_config->module_convenor_display_officehours)?
                        (bool)$this->data->block_config->module_convenor_display_officehours : true,

                );
                $module_convenors[$module_convenor_heading]['users'][] = array(
                    'user' => $primary_module_convenor,
                    'display' => $display_settings
                );
            }
        }
        //additional module users
        if(isset($this->data->block_config->additional_user_heading) && isset($this->data->block_config->additional_user_staffs)){
            if(!empty($this->data->block_config->additional_user_heading) && !empty($this->data->block_config->additional_user_staffs)){
                foreach($this->data->block_config->additional_user_heading as $key => $user_heading){
                    if(!empty($user_id = $this->data->block_config->additional_user_staffs[$key])){
                        if($additional_user = $DB->get_record('user', array('id' => $user_id))){
                            //get display settings
                            $display_settings = array(
                                'largeimage' => isset($this->data->block_config->additional_user_display_largeimage[$key])?
                                    (bool)$this->data->block_config->additional_user_display_largeimage[$key] : false,
                                'email' => isset($this->data->block_config->additional_user_display_email[$key])?
                                    (bool)$this->data->block_config->additional_user_display_email[$key] : true,
                                'url' => isset($this->data->block_config->additional_user_display_url[$key])?
                                    (bool)$this->data->block_config->additional_user_display_url[$key] : false,
                                'skype' => isset($this->data->block_config->additional_user_display_skype[$key])?
                                    (bool)$this->data->block_config->additional_user_display_skype[$key] : false,
                                'phone1' => isset($this->data->block_config->additional_user_display_phone1[$key])?
                                    (bool)$this->data->block_config->additional_user_display_phone1[$key] : false,
                                'location' => isset($this->data->block_config->additional_user_display_location[$key])?
                                    (bool)$this->data->block_config->additional_user_display_location[$key] : true,
                                'officehours' => isset($this->data->block_config->additional_user_display_officehours[$key])?
                                    (bool)$this->data->block_config->additional_user_display_officehours[$key] : true,
                            );
                            $module_convenors[$user_heading]['users'][] = array(
                                'user' => $additional_user,
                                'display' => $display_settings
                            );
                        }
                    }
                }
            }
        }
        //start output
        $result .= $this->get_teaching_staff_information($module_convenors);
        return $result;
    }

    /*
     * create output for teaching staffs with information
     */
    public function get_teaching_staff_information($module_convenors){
        global $CFG, $OUTPUT;

        require_once("{$CFG->dirroot}/user/profile/lib.php");

        $result = '';
        $i = 0;
        foreach($module_convenors as $user_heading => $convenors){
            //start heading or collapsible region depending on type
            $result .= html_writer::tag('h2', $user_heading, array('class'=>'convenor-heading'));
            foreach($convenors['users'] as $user) {
                $display_settings = $user['display'];
                $staff = $user['user'];
                //load profile for extra fields
                profile_load_data($staff);
                $result .= html_writer::start_div('', array('class' => 'staff-wrap clearfix'));
                //user picture
                if ($display_settings['largeimage']) {
                    $size = 100;
                    $staffimagewidth = 50;
                } else {
                    $size = 40;
                    $staffimagewidth = 25;
                }
                $result .= html_writer::start_div('', array('class' => 'staff-image', 'style' => 'max-width:' . $staffimagewidth . '%;float:left;'));
                $result .= $OUTPUT->user_picture($staff, array('size' => $size, 'class' => 'staff-profile-pic'));
                $result .= html_writer::end_div();
                //user info
                $result .= html_writer::start_div('', array('class' => 'staff-info', 'style' => 'max-width:' . (100 - $staffimagewidth) . '%;float:left;'));
                if ($display_settings['email']) {
                    //user name
                    $result .= html_writer::tag('div', fullname($staff, true), array('class' => 'staff-name'));
                    //email
                    $result .= html_writer::start_tag('div', array('class' => 'staff-email'));
                    $result .= obfuscate_mailto($staff->email, '');
                    $result .= html_writer::end_tag('div');
                } else {
                    //user name
                    $result .= html_writer::start_tag('div', array('class' => 'staff-name'));
                    $result .= obfuscate_mailto($staff->email, fullname($staff, true));
                    $result .= html_writer::end_tag('div');
                }
                //url
                if ($display_settings['url'] && $url = $staff->url) {
                    if (strpos($url, '://') === false) {
                        $url = 'http://' . $url;
                    }
                    $result .= html_writer::tag('div', html_writer::link(s($url), s($staff->url), array('target' => '_blank')), array('class' => 'staff-url'));
                }
                // Location
                if ($staff->profile_field_location && $display_settings['location']) {
                    $result .= html_writer::tag('div', get_string('location', 'block_module_info') . ': ' . s($staff->profile_field_location), array('class' => 'staff-location'));
                }
                //Office Hours
                if ($staff->profile_field_officehours && $display_settings['officehours']) {
                    $result .= html_writer::tag('div', get_string('officehours', 'block_module_info') . ': ' . s($staff->profile_field_officehours), array('class' => 'staff-officehours'));
                }
                //skype
                if ($display_settings['skype'] && $staff->skype) {
                    $skype_url = html_writer::link('skype:' . $staff->skype . '?call', s($staff->skype));
                    $result .= html_writer::tag('div', get_string('skypeid') . ': ' . $skype_url, array('class' => 'staff-skype'));
                }
                //phone
                if ($display_settings['phone1'] && $staff->phone1) {
                    $result .= html_writer::tag('div', get_string('phone1') . ': ' . s($staff->phone1), array('class' => 'staff-phone'));
                }
                $result .= html_writer::end_div();
                $result .= html_writer::end_div();
            }
        }
        return $result;
    }

    /**
     * Create output for the session info section
     *
     * @return html
     */
    public function get_sessioninfo_output() {

        $result = '';

        if(isset($this->data->block_config->schedule_hide) && $this->data->block_config->schedule_hide){
            return $result;
        }

        // Display section heading
        $schedule_title = (isset($this->data->block_config->schedule_title_override) && $this->data->block_config->schedule_title_override) ?
            $this->data->block_config->schedule_title : get_string('config_schedule_title_default', 'block_module_info');
        $result .= mod_info_collapsible_region_start('schedule-heading', 'modinfo-viewlet-schedule', $schedule_title, 'modinfo-schedule', true, true);
        $result .= html_writer::start_tag('div', array('id'=>'schedule-pane'));

        // First check to see if there is any session information

        if (! empty($this->data->block_config->additional_session_subheading) || ! empty($this->data->block_config->enable_personal_timetable_link)|| ! empty($this->data->block_config->enable_module_timetable_link) || !empty($this->data->block_config->custom_timetable_url)) {

            $result .= html_writer::start_tag('div', array('id' => 'schedule'));

            // Only display personal timetable link if user is logged in
            if(!isguestuser()) {
                if($this->data->block_config->enable_personal_timetable_link == true) {
                    $result .= $this->get_personal_timetable_html();
                }
            } else {
                $result .= html_writer::tag('div', get_string('login_to_view_timetable', 'block_module_info'));
            }

            // Module timetable link
            if($this->data->block_config->enable_module_timetable_link == true) {
                $result .= $this->get_module_timetable_html();
            }

            // Display custom timetable link if URL is specified
            if(!empty($this->data->block_config->custom_timetable_url)) {
                $result .= $this->get_custom_timetable_html();
            }

            // Display each session
            foreach($this->data->block_config->additional_session_subheading as $key=>$value) {
                // Session title:
                $result .= html_writer::tag('h2', s($value), array('class'=>'session-heading'));

                // Formatted session details:
                $a = new stdClass();
                $a->day = $this->data->block_config->additional_session_day[$key];
                $a->time = $this->data->block_config->additional_session_time[$key];
                $a->location = $this->data->block_config->additional_session_location[$key];
                $result .= html_writer::tag('div', get_string('session_details', 'block_module_info', $a), array('class'=>'session-details'));
            }

            $result .= html_writer::end_tag('div');

        } else {
            $result .= $this->output->box(get_string('nosessionsavailable', 'block_module_info'));
        }

        $result .= html_writer::end_tag('div');
        $result .= mod_info_collapsible_region_end(true);

        return $result;
    }

    /**
     * Create the personal timetable link
     *
     * @return html
     */
    private function get_personal_timetable_html() {

        global $USER;

        $result = '';

        $config = get_config('block_module_info');
        $params = array();

        $linkstring = get_string('default_personal_smart_link', 'block_module_info');

        if (strlen($USER->idnumber) == 9) {
            $params['objectclass'] = 'student+set';
            $linkstring = get_string('student_personal_smart_link', 'block_module_info');
        } elseif (strlen($USER->idnumber) == 6) {
            $params['objectclass'] = 'staff';
            $linkstring = get_string('staff_personal_smart_link', 'block_module_info');
        }

        $params['week'] = (empty($config->week)) ? '' : $config->week;
        $params['day'] =  (empty($config->day)) ? '' : $config->day;
        $params['period'] =  (empty($config->period)) ? '' : $config->period;
        $params['identifier'] = $USER->idnumber;
        $params['style'] = isset($config->style) ? $config->style : '';
        $params['template'] = isset($config->template) ? $config->template : '';

        $baseurl = isset($config->baseurl) ? $config->baseurl : '';
        $result = html_writer::link(new moodle_url($baseurl, $params), $linkstring, array('target' => '_BLANK'));
        $result = html_writer::tag('div', $result, array('class'=>'smart-link'));

        return $result;
    }

    /**
     * Create the module timetable link
     *
     * @return html
     */
    private function get_module_timetable_html() {

        global $USER, $COURSE;

        $result = '';

        $config = get_config('block_module_info');
        $params = array();
        $params['objectclass'] = 'module';
        $params['week'] = (empty($config->week)) ? '' :$config->week;
        $params['day'] =  (empty($config->day)) ? '' : $config->day;
        $params['period'] =  (empty($config->period)) ? '' : $config->period;
        $params['identifier'] = $COURSE->idnumber;
        $params['style'] = isset($config->style) ? $config->style : '';
        $params['template'] = isset($config->template) ? $config->template : '';

        $linkstring = get_string('default_module_smart_link', 'block_module_info');

        $baseurl = isset($config->baseurl) ? $config->baseurl : '';
        $result = html_writer::link(new moodle_url($baseurl, $params), $linkstring, array('target' => '_BLANK'));
        $result = html_writer::tag('div', $result, array('class'=>'smart-link'));

        return $result;
    }

    /**
     * Create custom timetable link
     *
     * @return html
     */
    private function get_custom_timetable_html() {

        $result = '';

        $linkstring = get_string('config_custom_timetable_text_default', 'block_module_info');

        if(!empty($this->data->block_config->custom_timetable_text)) {
            $linkstring = $this->data->block_config->custom_timetable_text;
        }

        $html = html_writer::start_tag('div', array('class' => 'custom-timetable'));

        $result = html_writer::link(new moodle_url($this->data->block_config->custom_timetable_url), $linkstring, array('target' => '_BLANK'));
        $result = html_writer::tag('div', $result, array('class'=>'smart-link'));

        return $result;
    }

    /**
     * Returns a collapsible document tree. Documents are associated with this module - specified by the course owner.
     *
     * @return html
     */
    public function get_documentinfo_output() {

        $result = '';

        if(isset($this->data->block_config->documents_hide) && $this->data->block_config->documents_hide){
            return $result;
        }

        // Get the stored files
        $fs = get_file_storage();
        $dir = $fs->get_area_tree($this->page->context->id, 'block_module_info', 'documents', $this->data->context->id);

        $has_files = !(empty($dir['subdirs']) && empty($dir['files']));

        if ($has_files) {
            $result = html_writer::start_tag('div', array('id'=>'documents'));

            $documents_title = (isset($this->data->block_config->documents_title_override) && $this->data->block_config->documents_title_override) ?
                $this->data->block_config->documents_title : get_string('config_documents_title_default', 'block_module_info');

            $result .= mod_info_collapsible_region_start('documents-heading', 'modinfo-viewlet-documents', $documents_title, 'modinfo-documents', true, true);

            $result .= html_writer::start_tag('div', array('id'=>'documents-pane'));

            $htmlid = 'document_tree_'.uniqid();
            $this->page->requires->js_init_call('M.block_module_info.init', array(false, $htmlid));
            $result .= '<div id="'.$htmlid.'">';
            $result .= $this->htmllize_document_tree($this->page->context, $this->data->context, $dir);
            $result .= '</div>';

            $result .= html_writer::end_tag('div');
            $result .= html_writer::end_tag('div');

            $result .= mod_info_collapsible_region_end(true);
        }

        return $result;
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     *
     * @param unknown_type $context
     * @param unknown_type $block_context
     * @param unknown_type $dir
     *
     * @return html
     */
    protected function htmllize_document_tree($context, $block_context, $dir) {

        global $CFG;

        $result = '';

        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result .= '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon("f/folder", $subdir['dirname'], 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.s($subdir['dirname']).'</div> '.$this->htmllize_document_tree($context, $block_context, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            $url = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename(),
                true);
            $icon = mimeinfo("icon", $filename);
            $image = $this->output->pix_icon("f/$icon", $filename, 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.html_writer::link($url, $image.$filename).'</div></li>';
        }
        $result .= '</ul>';

        return $result;
    }

    /**
     * Create output for the session info section
     *
     * @return html
     */

    public function get_otherinfo_output(){

        $result = '';

        if(!isset($this->data->block_config->otherinfo_hide) || $this->data->block_config->otherinfo_hide){
            return $result;
        }

        $filteropt = new stdClass;
        $filteropt->overflowdiv = true;

        // fancy html allowed only on course, category and system blocks.
        if ($this->content_is_trusted()) {
            $filteropt->noclean = true;
        }

        if(isset($this->data->block_config->htmlcontent) && !empty($this->data->block_config->htmlcontent['text'] && !empty($this->data->block_config->htmlcontent['format']))){
            $otherinfo_title = (isset($this->data->block_config->otherinfo_title_override) && $this->data->block_config->otherinfo_title_override) ?
                $this->data->block_config->otherinfo_title : get_string('config_otherinfo_title_default', 'block_module_info');

            $result .= mod_info_collapsible_region_start('otherinfo-html-heading', 'modinfo-viewlet-otherinfo', $otherinfo_title, 'modinfo-otherinfo', true, true);
                // rewrite url
            $this->data->block_config->htmlcontent['text'] = file_rewrite_pluginfile_urls($this->data->block_config->htmlcontent['text'],
                    'pluginfile.php', $this->page->context->id, 'block_module_info', 'content', NULL);
                $result .= format_text($this->data->block_config->htmlcontent['text'], $this->data->block_config->htmlcontent['format'], $filteropt);

            $result .= mod_info_collapsible_region_end(true);
        }

        return $result;

    }

    /*
     * Create Output for staff directory
     *
     * @return html
     */

    public function get_staff_directory_output() {
        $result = '';

        if(!isset($this->data->block_config->staffsearch_hide) || $this->data->block_config->staffsearch_hide){
            return $result;
        }

        global $CFG;
        require_once 'forms/staff_search_form.php';
        require_once($CFG->libdir . '/formslib.php');

        $result .= mod_info_collapsible_region_start('staffdir-html-heading', 'modinfo-viewlet-staffdir', get_string('staffsearch_header','block_module_info'), 'modinfo-staffdir', true, true);

        $target_url = new moodle_url('http://www.dir.qmul.ac.uk/search');
        $staff_search_form = new staff_search_form($target_url->__toString(), null, 'get', '_blank');
        $result .= $staff_search_form->render();

        $result .= mod_info_collapsible_region_end(true);
        return $result;
    }

    /**
     * Check if it safe to enable fancy html editor
     *
     * @return boolean
     */
    function content_is_trusted() {

        global $SCRIPT;

        if (!$context = context::instance_by_id($this->page->context->id)) {
            return false;
        }

        //find out if this block is on the profile page
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // this is exception - page is completely private, nobody else may see content there
                // that is why we allow JS here
                return true;
            } else {
                // no JS on public personal pages, it would be a big security issue
                return false;
            }
        }

        return true;
    }
}