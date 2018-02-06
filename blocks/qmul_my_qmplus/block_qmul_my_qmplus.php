<?php


include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->dirroot . '/blocks/qmplus_course_overview/locallib.php');

class block_qmul_my_qmplus extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_qmul_my_qmplus');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $PAGE,$CFG, $USER, $DB, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        //$icon  = '<img src="' . $OUTPUT->pix_url('i/course') . '" class="icon" alt="" />';
        $icon  = $OUTPUT->pix_url('i/course');

        /**
         * IMPORTANT!
         * This is the jquery library that is being used for rendering the list
         */
        $PAGE->requires->jquery_plugin('ddslick','block_qmul_my_qmplus');


        $adminseesall = true;
        if (isset($CFG->block_qmul_my_qmplus_adminview)) {
            if ( $CFG->block_qmul_my_qmplus_adminview == 'own'){
                $adminseesall = false;
            }
        }

        $output = '';


        /*
           Enabled admin users to view modules instead of categories as well.

             if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser() and

                    !(has_capability('moodle/course:update', context_system::instance()) and $adminseesall)) {

        */
        if (empty($CFG->disablemycourses) and isloggedin() and !isguestuser()) {
            // Just print My Courses
            // As this is producing navigation sort order should default to $CFG->navsortmycoursessort instead
            // of using the default.
            if (!empty($CFG->navsortmycoursessort)) {
                $sortorder = 'visible DESC, ' . $CFG->navsortmycoursessort . ' ASC';
            } else {
                $sortorder = 'visible DESC, sortorder ASC';
            }

            /**
             *  Need to get sorted courses from course overview block if exist
             *  else check for enrolled courses
             */
            $coursesFounded = false;
            list($courses, $sitecourses, $totalcourses) = block_qmplus_course_overview_get_sorted_courses(true);
            if (!empty($courses)) {
                $coursesFounded = true;
            }
            elseif ($courses = enrol_get_my_courses(NULL, $sortorder)) {
                $coursesFounded = true;
            }


            if ($coursesFounded) {

                $PAGE->requires->yui_module('moodle-block_qmul_my_qmplus-ddslick','M.block_qmul_my_qmplus.init_ddslick',
                    array('selectname' => get_string('selecttitle', 'block_qmul_my_qmplus')));

                foreach ($courses as $course) {
                    $courseTitle = format_string(get_course_display_name_for_list($course));
                    $courseHasCode = explode(" - ", $courseTitle);
                    $courseDesk = '';
                    $hiddenAttr = '';

                    if(!$course->visible){ // Add hidden attributes that are pulled from qmul.custom.ddsclick.js

                        $hiddenAttr = 'data-hidden="true" data-hiddenstyle="dd-hidden"
                            data-hiddentitle="[Not available]"';

                        $coursecontext = context_course::instance($course->id);
                        if (has_capability('moodle/course:update', $coursecontext)) {
                            $hiddenAttr .= ' data-canedit="true"';
                        }

                    }

                    if(count($courseHasCode)>2){
                        $courseName = $courseHasCode[1];
                        unset($courseHasCode[1]);
                        $courseDesk = 'data-description="';

                        /*foreach($courseHasCode as $val){
                            $courseDesk .= $val.' ';
                        }*/

                        $courseDesk .= $courseHasCode[0]; // Only want to display leading code

                        $courseDesk .= '"';
                    }else if(count($courseHasCode)==2){
                        /* Cant decide if a code is leading or a date follows
                          I am assuming that a date follows
                        */
                        $courseName = $courseHasCode[0];
                        unset($courseHasCode[0]);

                        $courseDesk = 'data-description="';

                        /*foreach($courseHasCode as $val){
                            $courseDesk .= $val.' ';
                        }*/
                        $courseDesk .= $courseHasCode[0]; // Only want to display leading code

                        $courseDesk .= '"';
                    }
                    else{
                        $courseName = $courseTitle;
                        //$courseDesk .= '&nbsp;';
                    }

                    //$courseDesk .= '"';

                    $output.= '<option value="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'" data-imagesrc="'.$icon.'"
                    '.$courseDesk.' '.$hiddenAttr.'>'.$courseName.'</option>';

                }
                //url(/theme/image.php/qmul/theme/1428665271/landing/default_link_arrow)
                //$this->title = get_string('mycourses');
                $this->title = '<div class="qmul_my_qmplus_block_img">
                    <a class="qmul_my_qmplus_block_title" href="'.$CFG->wwwroot.'/my/">' . get_string('blocktitle', 'block_qmul_my_qmplus') . '</a>
                    </div>';

                /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance())
                    || empty($CFG->block_qmul_my_qmplus_hideallcourseslink)) {
                    $this->content->footer = "<a href=\"$CFG->wwwroot/course/index.php\">"
                        .get_string("allcourseslink", 'block_qmul_my_qmplus')."</a>";
                }
            }

            $this->get_remote_courses();

            if ($output!='') { // make sure we don't return an empty list

                $this->content->items[]= '<div id="myModulesWrapper">
                                            <select id="myModulesList"  name="myModulesList">'.$output .'</select></div>';
                return $this->content;
            }
        }

        $categories = coursecat::get(0)->get_children();  // Parent = 0   ie top-level categories only
        if ($categories) {   //Check we have categories

            $PAGE->requires->yui_module('moodle-block_qmul_my_qmplus-ddslick','M.block_qmul_my_qmplus.init_ddslick',
                array('selectname' => get_string('selectcategory', 'block_qmul_my_qmplus')));

            if (count($categories) > 1 || (count($categories) == 1 && $DB->count_records('course') > 200)) {
                // Just print top level category links
                foreach ($categories as $category) {
                    $categoryname = $category->get_formatted_name();
                    $output.= '<option value="'.$CFG->wwwroot.'/course/index.php?categoryid='.$category->id.'" data-imagesrc="'.$icon.'">'
                        .$categoryname.'</option>';

                }
                /// If we can update any course of the view all isn't hidden, show the view all courses link
                if (has_capability('moodle/course:update', context_system::instance())
                    || empty($CFG->block_qmul_my_qmplus_hideallcourseslink)) {
                    $this->content->footer .= "<a href=\"$CFG->wwwroot/course/index.php\">"
                        .get_string('allcourseslink', 'block_qmul_my_qmplus').'</a> ';
                }
                //$this->title = get_string('categories');

                $this->title = get_string('blockcategorytitle', 'block_qmul_my_qmplus');

            } else {                          // Just print course names of single category
                $category = array_shift($categories);
                $courses = get_courses($category->id);

                if ($courses) {
                    foreach ($courses as $course) {
                        $output.= '<option value="'.$course->id.'" data-imagesrc="'.$icon.'"
                    data-description="Description with Facebook">'
                            .format_string(
                                get_course_display_name_for_list($course),
                                true,
                                array('context' => context_course::instance($course->id))
                            ).'</option>';
                    }
                    /// If we can update any course of the view all isn't hidden, show the view all courses link
                    if (has_capability('moodle/course:update', context_system::instance())
                        || empty($CFG->block_qmul_my_qmplus_hideallcourseslink)) {
                        $this->content->footer .=
                            "<a href=\"$CFG->wwwroot/course/index.php\">"
                            .get_string('allcourseslink', 'block_qmul_my_qmplus').'</a>';
                    }
                    $this->get_remote_courses();

                } else {

                    $this->content->icons[] = '';
                    $this->content->items[] = get_string('nocoursesyet');
                    if (has_capability('moodle/course:create', context_coursecat::instance($category->id))) {
                        $this->content->footer =
                            '<a href="'.$CFG->wwwroot.'/course/edit.php?category='.$category->id.'">'
                            .get_string("addnewcourse").'</a> ...';
                    }

                    $this->get_remote_courses();
                }
                //$this->title = get_string('courses');
                $this->title = '<a class="qmul_my_qmplus_block_title" href="'.$CFG->wwwroot.'/my/">' . get_string('blocktitle', 'block_qmul_my_qmplus') . '</a>';
            }
        }

        $this->content->items[]= '<div id="myModulesWrapper"><select id="myModulesList"  name="myModulesList">'
            .$output .'</select></div>';

        return $this->content;
    }

    function get_remote_courses() {
        global $CFG, $USER, $OUTPUT;

        if (!is_enabled_auth('mnet')) {
            // no need to query anything remote related
            return;
        }

        $icon = '<img src="'.$OUTPUT->pix_url('i/mnethost') . '" class="icon" alt="" />';

        // shortcut - the rest is only for logged in users!
        if (!isloggedin() || isguestuser()) {
            return false;
        }

        if ($courses = get_my_remotecourses()) {
            $this->content->items[] = get_string('remotecourses','mnet');
            $this->content->icons[] = '';
            foreach ($courses as $course) {
                $this->content->items[]="<a title=\"" . format_string($course->shortname, true) . "\" ".
                    "href=\"{$CFG->wwwroot}/auth/mnet/jump.php?hostid={"
                    ."$course->hostid}&amp;wantsurl=/course/view.php?id={$course->remoteid}\">"
                    .$icon. format_string(get_course_display_name_for_list($course)) . "</a>";
            }
            // if we listed courses, we are done
            return true;
        }

        if ($hosts = get_my_remotehosts()) {
            $this->content->items[] = get_string('remotehosts', 'mnet');
            $this->content->icons[] = '';
            foreach($USER->mnet_foreign_host_array as $somehost) {
                $this->content->items[] = $somehost['count'].get_string('courseson','mnet').'<a title="'
                    .$somehost['name'].'" href="'.$somehost['url'].'">'.$icon.$somehost['name'].'</a>';
            }
            // if we listed hosts, done
            return true;
        }

        return false;
    }

    /**
     * Returns the role that best describes the course list block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }
}