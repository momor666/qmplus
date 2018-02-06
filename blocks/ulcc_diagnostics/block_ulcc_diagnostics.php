<?php

class block_ulcc_diagnostics extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_ulcc_diagnostics');
    }

    function get_content() {

       global $CFG, $OUTPUT,$DB;
       require_once($CFG->dirroot.'/lib/adodb/adodb.inc.php');

        if (empty($this->instance)) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $p= (core_plugin_manager::instance()->get_plugins());

        $extended_installed=isset($p['enrol']['databaseextended']);

        /// MDL-13252 Always get the course context or else the context may be incorrect in the user/index.php
        $currentcontext = $this->page->context;
		if (has_capability('moodle/site:config', context_system::instance())) {
				$this->content->items[] = '<a title="LDAP Diagnostics" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/views/ldap_connect.php"><img src="'.$OUTPUT->pix_url('i/user').'" class="icon" alt="" />&nbsp;LDAP Diagnostics</a>';
				$this->content->items[] = '<a title="Database Diagnostics" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/views/db_connect.php"><img src="'.$OUTPUT->pix_url('i/db').'" class="icon" alt="" />&nbsp;Database Diagnostics</a>';
                if($extended_installed)
                {
                   $this->content->items[] = '<a title="DB Extended Diagnostics" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/views/db_extended_connect.php"><img src="'.$OUTPUT->pix_url('i/db').'" class="icon" alt="" />&nbsp;Database-Ext Diagnostics</a>';
                }
				$this->content->items[] = '<a title="Upload Categories" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/actions/category_upload.php"><img src="'.$OUTPUT->pix_url('i/files').'" class="icon" alt="" />&nbsp;Upload Categories</a>';
				$this->content->items[] = '<a title="Upload Courses" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/actions/course_upload.php"><img src="'.$OUTPUT->pix_url('i/course').'" class="icon" alt="" />&nbsp;Upload Courses</a>';
				$this->content->items[] = '<a title="Upload Courses" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/actions/course_rollover.php"><img src="'.$OUTPUT->pix_url('i/course').'" class="icon" alt="" />&nbsp;Rollover Course IDs</a>';
				$this->content->items[] = '<a title="Upload Meta Courses" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/actions/meta_course_upload.php"><img src="'.$OUTPUT->pix_url('i/course').'" class="icon" alt="" />&nbsp;Upload Meta Courses</a>';
				$this->content->items[] = '<a title="Upload Mentors/Parents" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/actions/mentor_upload.php"><img src="'.$OUTPUT->pix_url('i/user').'" class="icon" alt="" />&nbsp;Upload Mentors/Parents</a>';
				$this->content->items[] = '<a title="Download Statistics" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/views/download_statistics.php"><img src="'.$OUTPUT->pix_url('i/files').'" class="icon" alt="" />&nbsp;Download Statistics</a>';
                $this->content->items[] = '<a title="Assigns readonlyuser  role to all site users except of  moodle-support  user" href="'.$CFG->wwwroot.'/blocks/ulcc_diagnostics/actions/readonly_site_mode.php"><img src="'.$OUTPUT->pix_url('i/user').'" class="icon" alt="" />&nbsp;Read-Only Site Mode Management</a>';
                return $this->content;
		}
    }

    function applicable_formats() {
        if (has_capability('moodle/site:config', context_system::instance())) {
            return array('all' => true);
        } else {
            return array('site' => true);
        }
    }

}


