<?php  // $Id: lib.php, v1.0 2015/04/16  djhipps Exp $

/**
 * Library of functions and constants for module newmodule
 * 
 */

require_once($CFG->dirroot . '/mod/assign/locallib.php');


/*
 * Alters assignment settings menu
 * @param $setnav stdClass navigation
 * @param $context stdClass context
 */


function local_qmul_download_submissions_extend_settings_navigation($setnav, $context){
    global $CFG;

    if(!get_config('local_qmul_download_submissions', 'enable')){
        return;
    }

	$assignadminnavobj = $setnav->find('modulesettings',70);
	$contextlevel = $context->contextlevel;

    $capable = false;
    if(has_capability('mod/assign:grade', $context)){
        $capable = true;
    }

	//add link to assignment admin menu
 	if(($contextlevel == 70) && isset($assignadminnavobj->text) && ($assignadminnavobj->text == 'Assignment administration') && $capable){

        //$courseid = required_param('id', PARAM_INT);
        $assignmentinstanceid = $context->instanceid;


        $downloadurl = $CFG->wwwroot . '/local/qmul_download_submissions/download.php?id=' . $assignmentinstanceid;
        $link_text = get_config('local_qmul_download_submissions', 'label');

        $assignadminnavobj->add($link_text, new moodle_url($downloadurl));


        //remove the default download all sumbissions link from the assignement menu
        $assignadminnavalltypes = array_reverse($assignadminnavobj->find_all_of_type(70), true);



    }
}

/*
 *
 *
 *
 *
 */

class assignment_downloader extends assign{

    public function __construct($context, $cm, $course){
        parent::__construct($context, $cm, $course);
    }

    /*
     * @return bool
     *
     *
     */
    public function download_all_submissions_by_id(){
        global $CFG, $DB;
        require_once($CFG->libdir.'/filelib.php');
        //TODO: debug this code!! - it is not working as it should.

        $this->require_view_grades();

        $context = $this->get_context();

        //Load all users with submit
        $students = get_enrolled_users($context, "mod/assign:submit", null, 'u.*', null, null, null, $this->show_only_active_users());

        $this->submissionplugins = parent::get_submission_plugins();

        //Build a list of files to zip
        $filesforzipping = array();
        $fs = get_file_storage();

        $groupmode = groups_get_activity_groupmode($this->get_course_module());
        //All users
        $groupid = 0;
        $groupname = '';
        if($groupmode){
            $groupid = groups_get_activity_group($this->get_course_module(), true);
            $groupname = groups_get_group_name($groupid).'-';
        }

        $filename = clean_filename($this->get_course()->shortname . '-' .
                                   $this->get_instance()->name . '-' .
                                   $groupname.$this->get_course_module()->id . '.zip');

        // Get all the files for each student.
        foreach ($students as $student) {
            $userid = $student->id;

            if ((groups_is_member($groupid, $userid) or !$groupmode or !$groupid)) {
                // Get the plugins to add their own files to the zip.

                $submissiongroup = false;
                $groupname = '';
                if ($this->get_instance()->teamsubmission) {
                    $submission = $this->get_group_submission($userid, 0, false);
                    $submissiongroup = $this->get_submission_group($userid);
                    if ($submissiongroup) {
                        $groupname = $submissiongroup->name . '-';
                    } else {
                        $groupname = get_string('defaultteam', 'assign') . '-';
                    }
                } else {
                    $submission = $this->get_user_submission($userid, false);
                }


                //This bit of code writes the files names
                //It used to be displayed only if module is blind marked
                $prefix = str_replace('_', ' ', $groupname . get_string('username', 'local_qmul_download_submissions'));
                   // $prefix = clean_filename($prefix . '_' . $this->get_uniqueid_for_user($userid) . '_');
                $prefix = clean_filename($prefix . '_' . $student->username . '_'. get_string('studentid', 'local_qmul_download_submissions') . '_'.  $student->idnumber . '_' );


                if ($submission) {
                    foreach ($this->submissionplugins as $plugin) {
                        if ($plugin->is_enabled() && $plugin->is_visible()) {
                            $pluginfiles = $plugin->get_files($submission, $student);
                            foreach ($pluginfiles as $zipfilename => $file) {
                                $subtype = $plugin->get_subtype();
                                $type = $plugin->get_type();
                                $prefixedfilename = clean_filename($prefix .
                                    $subtype .
                                    '_' .
                                    $type .
                                    '_' .
                                    $zipfilename);
                                $filesforzipping[$prefixedfilename] = $file;
                            }
                        }
                    }
                }
            }
        }
        $result = '';
        if (count($filesforzipping) == 0) {
            $header = new assign_header($this->get_instance(),
                $this->get_context(),
                '',
                $this->get_course_module()->id,
                get_string('downloadall', 'assign'));
            $result .= $this->get_renderer()->render($header);
            $result .= $this->get_renderer()->notification(get_string('nosubmission', 'assign'));
            $url = new moodle_url('/mod/assign/view.php', array('id'=>$this->get_course_module()->id,
                'action'=>'grading'));
            $result .= $this->get_renderer()->continue_button($url);
            $result .= $this->view_footer();
        } else if ($zipfile = $this->pack_files($filesforzipping)) {
            \mod_assign\event\all_submissions_downloaded::create_from_assign($this)->trigger();
            // Send file and delete after sending.
            send_temp_file($zipfile, $filename);
            // We will not get here - send_temp_file calls exit.
        }
        return $result;

    }


}


function get_assignment_is_blind($assignmentid){
    global $DB;

    $sql = "SELECT blindmarking FROM {assign} WHERE id = (SELECT instance FROM {course_modules} WHERE id = ?)";

    $result =  $DB->get_record_sql($sql, array($assignmentid));

    return $result->blindmarking;
}


