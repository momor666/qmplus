<?php

/****************************************************************

File:       local/physicsapp/course.php

Purpose:    Class to render courses in format suitable for
            the Physics Mobile App

****************************************************************/

// Moodle configuation
include('../../config.php');
require_once 'condition.php';
require_once $CFG->dirroot.'/course/lib.php';
require_once $CFG->dirroot.'/lib/htmlpurifier/HTMLPurifier.safe-includes.php';
require_once $CFG->dirroot.'/lib/htmlpurifier/HTMLPurifier.php';

// Extract parameters passed to this page
$id   = required_param('id', PARAM_INT);

global $PAGE;
$PAGE->set_url('/local/physicsapp/course.php', array('id' => $id)); // Defined here to avoid notices on errors etc

/**
 * Class that encapsulates the App renderer.
 *
 * @author Ian Wild
 *
 */
class app_course_renderer {

    var $courseid = -1;
    var $course = null;
    var $context = null;
    var $sections = null;
    var $initialised = false;
    var $tabs = '';
    var $content = '';

    var $mods = array();
    var $modnamesused = array();

    var $allowedmods = array();
    var $linkwarning = '';

    /**
     * Constructor. Passed the id of the course we are trying to render.
     *
     * @param unknown_type $id
     */
    function __construct($id) {
        $this->courseid = $id;

        global $DB, $COURSE;

        if($this->course = $DB->get_record('course', array('id'=>$this->courseid))) {
            // The course exists so check that it allows public access
            require_login($this->courseid,true,NULL,true,true);

            // Load contexts
            context_helper::preload_course($this->course->id);
//            preload_course_contexts($this->course->id);
            if (!$this->context = get_context_instance(CONTEXT_COURSE, $this->course->id)) {
                print_error('nocontext');
            }

            // Get module info
            $modinfo =& get_fast_modinfo($COURSE);
            $this->mods = $modinfo->get_cms();
            $this->modnamesused = $modinfo->get_used_module_names();

            foreach($this->mods as $modid=>$unused) {
                if (!isset($modinfo->cms[$modid])) {
                    rebuild_course_cache($this->course->id);
                    $modinfo =& get_fast_modinfo($COURSE);
                    debugging('Rebuilding course cache', DEBUG_DEVELOPER);
                    break;
                }
            }

            // Attempt to get course sections (i.e. topics)
            if (! $this->sections = $modinfo->get_section_info_all()) {   // No sections found
                // Double-check to be extra sure
                if (! $section = $DB->get_record('course_sections', array('course'=>$this->course->id, 'section'=>0))) {
                    $section->course = $this->course->id;   // Create a default section.
                    $section->section = 0;
                    $section->visible = 1;
                    $section->summaryformat = FORMAT_HTML;
                    $section->id = $DB->insert_record('course_sections', $section);
                }
                if (! $sections = $modinfo->get_section_info_all()) {      // Try again
                    // Abort execution and print an error.
                    print_error('cannotcreateorfindstructs', 'error');
                }
            }

            $this->allowedmods = explode(',', get_config('local_physicsapp', 'allowedmods'));
            $this->linkwarning = get_config('local_physicsapp', 'linkwarning');

            // If we got this far then all is well.
            $this->initialised = true;
        }
    }

    /*
     * Returns true if the renderer is initialised, else returns false.
     */
    public function is_initialised() {
        return $this->initialised;
    }

    public function get_app_course_head() {
        $result = '';

        $result .= html_writer::tag('title', $this->course->fullname);
        $result .= html_writer::empty_tag('meta', array('http-equiv'=>'Content-Type','content'=>'text/html; charset=utf-8'));

        return $result;
    }

    /**
     * Private function to get the HTML for the given section.
     *
     * @param unknown_type $course
     * @param unknown_type $section
     * @param unknown_type $mods
     * @param unknown_type $modnamesused
     * @param unknown_type $absolute
     * @param unknown_type $width
     * @param unknown_type $hidecompletion
     * @return string
     */
    private function get_section_HTML($course, $section, $mods, $modnamesused, $absolute=false, $width="100%", $hidecompletion=false) {
        global $CFG, $USER, $DB, $PAGE, $OUTPUT;

        static $initialised;

        static $groupings;
        static $modulenames;

        $result = '';

        if ($section->summary) {
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            $summarytext = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $section->id);
            $summaryformatoptions = new stdClass();
            $summaryformatoptions->noclean = true;
            $summaryformatoptions->overflowdiv = true;
            $result .= format_text($summarytext, $section->summaryformat, $summaryformatoptions);
        } else {
            $result .= '&nbsp;';
        }

//        $tl = textlib_get_instance();
//         $t1 = new core_text;

        $modinfo = get_fast_modinfo($course);
        $completioninfo = new completion_info($course);

        if (!empty($section->sequence)) {

            // Fix bug #5027, don't want style=\"width:$width\".
            $sectionmods = explode(",", $section->sequence);

            foreach ($sectionmods as $modnumber) {
                if (empty($mods[$modnumber])) {
                    continue;
                }

                /**
                 * @var cm_info
                 */
                $mod = $mods[$modnumber];

                if (isset($modinfo->cms[$modnumber])) {
                    // We can continue (because it will not be displayed at all)
                    // if:
                    // 1) The activity is not visible to users
                    // and
                    // 2a) The 'showavailability' option is not set (if that is set,
                    //     we need to display the activity so we can show
                    //     availability info)
                    // or
                    // 2b) The 'availableinfo' is empty, i.e. the activity was
                    //     hidden in a way that leaves no info, such as using the
                    //     eye icon.
                    if (!$modinfo->cms[$modnumber]->uservisible &&
                        (empty($modinfo->cms[$modnumber]->showavailability) ||
                          empty($modinfo->cms[$modnumber]->availableinfo))) {
                        // visibility shortcut
                        continue;
                    }
                } else {
                    if (!file_exists("$CFG->dirroot/mod/$mod->modname/lib.php")) {
                        // module not installed
                        continue;
                    }
                    if (!coursemodule_visible_for_user($mod) &&
                        empty($mod->showavailability)) {
                        // full visibility check
                        continue;
                    }
                }

                if (!isset($modulenames[$mod->modname])) {
                    $modulenames[$mod->modname] = get_string('modulename', $mod->modname);
                }
                $modulename = $modulenames[$mod->modname];

                // In some cases the activity is visible to user, but it is
                // dimmed. This is done if viewhiddenactivities is true and if:
                // 1. the activity is not visible, or
                // 2. the activity has dates set which do not include current, or
                // 3. the activity has any other conditions set (regardless of whether
                //    current user meets them)
                $canviewhidden = has_capability(
                    'moodle/course:viewhiddenactivities',
                    get_context_instance(CONTEXT_MODULE, $mod->id));
                $accessiblebutdim = false;
                if ($canviewhidden) {
                    $accessiblebutdim = !$mod->visible;
                    if (!empty($CFG->enableavailability)) {
                        $accessiblebutdim = $accessiblebutdim ||
                            $mod->availablefrom > time() ||
                            ($mod->availableuntil && $mod->availableuntil < time()) ||
                            count($mod->conditionsgrade) > 0 ||
                            count($mod->conditionscompletion) > 0;
                    }
                }

                $result .= html_writer::start_tag('div', array());




                $content = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));
                $instancename = $mod->get_formatted_name();


                //Accessibility: for files get description via icon, this is very ugly hack!
                $altname = '';
                $altname = $mod->modfullname;
                if (!empty($customicon)) {
                    $archetype = plugin_supports('mod', $mod->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
                    if ($archetype == MOD_ARCHETYPE_RESOURCE) {
                        $mimetype = mimeinfo_from_icon('type', $customicon);
                        $altname = get_mimetype_description($mimetype);
                    }
                }
                // Avoid unnecessary duplication: if e.g. a forum name already
                // includes the word forum (or Forum, etc) then it is unhelpful
                // to include that in the accessible description that is added.

                //debug
                $truth = core_text::strtolower($instancename);
                $truth = core_text::strtolower($altname);


                if (false !== strpos(core_text::strtolower($instancename),
                        core_text::strtolower($altname))) {
                    $altname = '';
                }
                // File type after name, for alphabetic lists (screen reader).
                if ($altname) {
                    $altname = get_accesshide(' '.$altname);
                }

                // We may be displaying this just in order to show information
                // about visibility, without the actual link
                $contentpart = '';
                if ($mod->uservisible) {
                    // Do we display a link or a warning?
                    if(!in_array($mod->modname, $this->allowedmods)) {
                        if ($url = $mod->get_url()) {

                            // Display link text followed by the link warning
                            $result .= '<div>' . $instancename . $altname . $this->linkwarning . '</div>';

                            // If specified, display extra content after link
                            if ($content) {
                                $contentpart = '<div>' . $content . '</div>';
                            }
                        } else {
                            // No link, so display only content
                            $contentpart = '<div ' . $mod->extra . '>' . $content . '</div>';
                        }

                    } else {
                        // Nope - in this case the link is fully working for user
                        $linkclasses = '';
                        $textclasses = '';
                        if ($accessiblebutdim) {
                            $linkclasses .= ' dimmed';
                            $textclasses .= ' dimmed_text';
                            $accesstext = get_string('hiddenfromstudents').': ';
                        } else {
                            $accesstext = '';
                        }
                        if ($linkclasses) {
                            $linkcss = 'class="' . trim($linkclasses) . '" ';
                        } else {
                            $linkcss = '';
                        }
                        if ($textclasses) {
                            $textcss = 'class="' . trim($textclasses) . '" ';
                        } else {
                            $textcss = '';
                        }

                        // Get on-click attribute value if specified
                        $onclick = $mod->get_on_click();
                        if ($onclick) {
                           // Parse out the URL
                           $elements = explode('\'', $onclick);
                           if(isset($elements[1])) { // Due to the way the string is constructed it will always be the element at index 1
                               $onclick = $elements[1];
                           }
                        }

                        if ($url = $mod->get_url()) {
                            // Display link itself
                            $result .= '<a ' . $linkcss . $mod->extra . $onclick .
                                    ' href="' . $url . '"><img src="' . $mod->get_icon_url() .
                                    '" class="activityicon" alt="' .
                                    $modulename . '" /> ' . $accesstext .
                                    $instancename . $altname . '</a>';

                            // If specified, display extra content after link
                            if ($content) {
                                $contentpart = '<div class="' . trim('contentafterlink' . $textclasses) .
                                        '">' . $content . '</div>';
                            }
                        } else {
                            // No link, so display only content
                            $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                                    $accesstext . $content . '</div>';
                        }
                    }

                    if (!empty($mod->groupingid) && has_capability('moodle/course:managegroups', get_context_instance(CONTEXT_COURSE, $course->id))) {
                        if (!isset($groupings)) {
                            $groupings = groups_get_all_groupings($course->id);
                        }
                        $result .= " (".format_string($groupings[$mod->groupingid]->name).')';
                    }
                } else {
                    $textclasses = $extraclasses;
                    $textclasses .= ' dimmed_text';
                    if ($textclasses) {
                        $textcss = 'class="' . trim($textclasses) . '" ';
                    } else {
                        $textcss = '';
                    }
                    $accesstext = get_string('notavailableyet', 'condition') . ': ';

                    if ($url = $mod->get_url()) {
                        // Display greyed-out text of link
                        $result .= '<div ' . $textcss . $mod->extra .
                                ' >' . '<img src="' . $mod->get_icon_url() .
                                '" class="activityicon" alt="' .
                                $modulename .
                                '" /> '. $instancename . $altname .
                                '</div>';

                        // Do not display content after link when it is greyed out like this.
                    } else {
                        // No link, so display only content (also greyed)
                        $contentpart = '<div ' . $textcss . $mod->extra . '>' .
                                $accesstext . $content . '</div>';
                    }
                }

                // Module can put text after the link (e.g. forum unread)
                $result .= $mod->get_after_link();

                // If there is content but NO link (eg label), then display the
                // content here (BEFORE any icons). In this case cons must be
                // displayed after the content so that it makes more sense visually
                // and for accessibility reasons, e.g. if you have a one-line label
                // it should work similarly (at least in terms of ordering) to an
                // activity.
                if (empty($url)) {
                    $result .= $contentpart;
                }

                // Completion
                $completion = $hidecompletion
                    ? COMPLETION_TRACKING_NONE
                    : $completioninfo->is_enabled($mod);
                if ($completion!=COMPLETION_TRACKING_NONE && isloggedin() &&
                    !isguestuser() && $mod->uservisible) {
                    $completiondata = $completioninfo->get_data($mod,true);
                    $completionicon = '';
                    if ($completion==COMPLETION_TRACKING_MANUAL) {
                        switch($completiondata->completionstate) {
                            case COMPLETION_INCOMPLETE:
                                $completionicon = 'manual-n'; break;
                            case COMPLETION_COMPLETE:
                                $completionicon = 'manual-y'; break;
                        }
                    } else { // Automatic
                        switch($completiondata->completionstate) {
                            case COMPLETION_INCOMPLETE:
                                $completionicon = 'auto-n'; break;
                            case COMPLETION_COMPLETE:
                                $completionicon = 'auto-y'; break;
                            case COMPLETION_COMPLETE_PASS:
                                $completionicon = 'auto-pass'; break;
                            case COMPLETION_COMPLETE_FAIL:
                                $completionicon = 'auto-fail'; break;
                        }
                    }
                    if ($completionicon) {
                        $imgsrc = $OUTPUT->pix_url('i/completion-'.$completionicon);
                        $imgalt = s(get_string('completion-alt-'.$completionicon, 'completion'));
                        if ($completion == COMPLETION_TRACKING_MANUAL) {
                            $imgtitle = s(get_string('completion-title-'.$completionicon, 'completion'));
                            $newstate =
                                $completiondata->completionstate==COMPLETION_COMPLETE
                                ? COMPLETION_INCOMPLETE
                                : COMPLETION_COMPLETE;
                            // In manual mode the icon is a toggle form...

                            // If this completion state is used by the
                            // conditional activities system, we need to turn
                            // off the JS.
                            if (!empty($CFG->enableavailability) &&
                                condition_info::completion_value_used_as_condition($course, $mod)) {
                                $extraclass = ' preventjs';
                            } else {
                                $extraclass = '';
                            }
                            $result .= "
    <form class='togglecompletion$extraclass' method='post' action='".$CFG->wwwroot."/course/togglecompletion.php'><div>
    <input type='hidden' name='id' value='{$mod->id}' />
    <input type='hidden' name='sesskey' value='".sesskey()."' />
    <input type='hidden' name='completionstate' value='$newstate' />
    <input type='image' src='$imgsrc' alt='$imgalt' title='$imgtitle' />
    </div></form>";
                        } else {
                            // In auto mode the icon is just an image
                            $result .= "<img src='$imgsrc' alt='$imgalt' title='$imgalt' />";
                        }
                    }
                }

                // If there is content AND a link, then display the content here
                // (AFTER any icons). Otherwise it was displayed before
                if (!empty($url)) {
                    $result .= $contentpart;
                }



                // Show availability information (for someone who isn't allowed to
                // see the activity itself, or for staff)
                if (!$mod->uservisible) {
                    $result .= '<div class="availabilityinfo">'.$mod->availableinfo.'</div>';
                } else if ($canviewhidden && !empty($CFG->enableavailability)) {
                    $fullinfo = $mod->availableinfo;
//                    $ci = new condition_info($mod);
//                    $fullinfo = $ci->get_full_information();
                    if($fullinfo) {
                        $result .= '<div class="availabilityinfo">'.get_string($mod->showavailability
                            ? 'userrestriction_visible'
                            : 'userrestriction_hidden','condition',
                            $fullinfo).'</div>';
                    }
                }

                $result .= html_writer::end_tag('div')."\n";
            }

        }

        //if (!empty($section->sequence)) {
        //    $result .= "</ul><!--class='section'-->\n\n";
        //}

        return $result;
    }

    /*
     * Returns the HTML for the given course in a format suitable for the app.
     */
    public function get_app_course_body() {
        $this->tabs = '';
        $this->content = '';

        if($this->initialised) {
            $section = get_config('local_physicsapp', 'displaytopiczero')?0:1;
            $sectionmenu = array();
            $course = $this->course;
            $sections = $this->sections;
            $numsections = count($sections);
	    global $DB;

            $this->tabs .= '<ul><!--DoNotDeleteThis_tabs_begin-->';
            $this->content .= '<!--DoNotDeleteThis_content_begin-->';

            while ($section < $numsections) {

                if (!empty($sections[$section])) {
                    $thissection = $sections[$section];

                } /*else {
                    $thissection = new stdClass;
                    $thissection->course  = $course->id;   // Create a new section structure
                    $thissection->section = $section;
                    $thissection->name    = null;
                    $thissection->summary  = '';
                    $thissection->summaryformat = FORMAT_HTML;
                    $thissection->visible  = 1;
                    $thissection->id = $DB->insert_record('course_sections', $thissection);
                }*/

                $showsection = (has_capability('moodle/course:viewhiddensections', $this->context) or $thissection->visible or !$course->hiddensections);

                if (!empty($displaysection) and $displaysection != $section) {  // Check this topic is visible
                    if ($showsection) {
                        $sectionmenu[$section] = get_section_name($course, $thissection);
                    }
                    $section++;
                    continue;
                }

                if (!$thissection->visible) {
                    $section++;
                    continue;
                }

                if (!$thissection->name) {
                    $section++;
                    continue;
                }

                if ($showsection) {
                    $sectionname = $section;
                    if(!is_null($thissection->name)) {
                        $sectionname = $thissection->name;
                    }

                     $this->tabs .= '<li><a href="#tab'.$section.'">'.format_string($sectionname, true).'</a></li>';

                     $sectionHTML = $this->get_section_HTML($course, $thissection, $this->mods, $this->modnamesused);

                     // The section HTML needs to be purified...
                     $config = HTMLPurifier_Config::createDefault();
                     // Remove all span tags as they cause trouble
                     $config->set('HTML.ForbiddenElements', 'span');
                     $config->set('HTML.ForbiddenAttributes', 'class');
                     // Note that caching really needs to be enabled!
                     $config->set('Cache.DefinitionImpl', null);

                     $purifier = new HTMLPurifier($config);
                     $clean_html = $purifier->purify( $sectionHTML );

                     $this->content .= '<div id="tab'.$section.'">'.$clean_html.'</div>';
                }

                unset($sections[$section]);
                $section++;
            }

             $this->tabs .= '<!--DoNotDeleteThis_tabs_end--></ul>';
             $this->content .= '<!--DoNotDeleteThis_content_end-->';
        }

        $result = $this->tabs.$this->content;

        $result = html_writer::tag('body', $result);

        return $result;
    }

    public function get_test_page_HTML() {
        $result = '<div id="tabs">
                    	<ul><!--DoNotDeleteThis_tabs_begin-->
                    		<li><a href="#general">General</a></li>
                    		<li><a href="#aims">Aims and Objectives</a></li>
                    		<li><a href="#syllabus">Syllabus</a></li>
                    		<li><a href="#coursework">Coursework</a></li>
                    		<li><a href="#tools">Computing and Tools</a></li>
                    		<li><a href="#schedule">Schedule</a></li>
                    		<li><a href="#deadlines">Deadlines</a></li>
                    		<li><a href="#marking">Marking Scheme</a></li>
                    		<li><a href="#experiments">Experiments</a></li>
                    		<li><a href="#homework">Homework</a></li>
                    		<li><a href="#lectureNotes">Lecture Notes</a></li>
                    		<!--DoNotDeleteThis_tabs_end--></ul>
                    	<!--DoNotDeleteThis_content_begin-->
                    	<div id="general">
                    		<h3>Welcome to the COURSE NAME (PHY-NNN) Home Page</h3>
                    		<p>General information paragraph</p>
                    		<p>General information paragraph</p>
                    		<p>General information paragraph</p>
                    	</div>
                    	<div id="aims">
                    		<h3>Learning Outcomes</h3>
                    		<p>paragraph text</p>
                    		<h3>Exercises</h3>
                    		<p>paragraph text</p>
                    	</div>
                    	<div id="syllabus">
                    		<h3>Syllabus</h3>
                    		<p>paragraph text syllabus</p>
                    	</div>
                    	<div id="coursework">
                    		<h3>Coursework</h3>
                    		<p>paragraph text coursework</p>
                    	</div>
                    	<div id="tools">
                    		<h3>Tools and Computing</h3>
                    		<p>paragraph text tools and computing</p>
                    	</div>
                    	<div id="schedule">
                    		<table id="people" width="100%">
                    			<thead>
                    				<tr>
                    					<th>&nbsp;Week&nbsp;</th>
                    					<th>Dates</th>
                    					<th>A1</th>
                    					<th>A2</th>
                    					<th>A4</th>
                    					<th>A4</th>
                    				</tr>
                    				<tr class="even" id="playlist">
                    					<td>&nbsp;</td>
                    					<td>&nbsp;</td>
                    					<td style="text-align: center; ">Monday</td>
                    					<td style="text-align: center; ">Tuesday</td>
                    					<td style="text-align: center; ">Thursday</td>
                    					<td style="text-align: center; ">Friday</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>1</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>2</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>3</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>4</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>5</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>6</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="even" id="playlist">
                    					<td colspan="6" style="text-align: center; ">Reading week</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>8</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>9</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>10</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>11</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    				<tr class="odd" id="playlist">
                    					<td>12</td>
                    					<td style="white-space: nowrap; ">&nbsp;</td>
                    					<td colspan="4" rowspan="1">input data</td>
                    				</tr>
                    			</thead>
                    			<tbody></tbody>
                    		</table>
                    		<p>&nbsp;</p>
                    	</div>
                    	<div id="deadlines">
                    		<h3>Deadlines</h3>
                    		<p>paragraph text deadlines</p>
                    	</div>
                    	<div id="marking">
                    		<h3>Marking</h3>
                    		<p>paragraph text marking</p>
                    	</div>
                    	<div id="experiments">
                    		<h3>Experiments</h3>
                    		<p>paragraph text experiments</p>
                    	</div>
                    	<div id="homework">
                    		<h3>Homework</h3>
                    		<p>paragraph text homework</p>
                    	</div>
                    	<div id="lectureNotes">
                    		<h3>Lecture notes</h3>
                    		<p>paragraph text lecture notes</p>
                    	</div>
	                <!--DoNotDeleteThis_content_end-->
	            </div>';

        return $result;
    }
}

global $DB;

// Create new instance of 'app_course_renderer' object.
$renderer = new app_course_renderer($id);

// Initialise the renderer
if($renderer->is_initialised()) {
    // Get the HTML for a test page.
    //$test_page = $renderer->get_test_page_HTML();
    $head = $renderer->get_app_course_head();
    $body = $renderer->get_app_course_body();

    // Wrap head and body in an HTML tag.
    $content = html_writer::tag('html', $head.$body, array('dir'=>'ltr', 'lang'=>'en', 'xml:lang'=>'en', 'xmlns'=>'http://www.w3.org/1999/xhtml'));

    // Render page.
    echo $content;
} else {
    // Display an error
    print_error('failedtoinitialise', 'local_physicsapp', '', $id);

}



?>
