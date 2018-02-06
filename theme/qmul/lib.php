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
 * @copyright 2016 Andrew Davidson, Synergy Learning
 * @author    Andrew Davidson,  Based on Bootstrap by Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir. '/coursecatlib.php');

function qmul_bootstrap_grid($hassidepre, $hassidepost) {

    if ($hassidepre && $hassidepost) {
        $regions = array('content' => 'col-12 col-lg-9');
        $regions['post'] = 'col-12 col-lg-3';
    } else if ($hassidepre && !$hassidepost) {
        $regions = array('content' => 'col-12');
        $regions['post'] = 'empty';
    } else if (!$hassidepre && $hassidepost) {
        $regions = array('content' => 'col-12 col-lg-9');
        $regions['post'] = 'col-12 col-lg-3';
    } else if (!$hassidepre && !$hassidepost) {
        $regions = array('content' => 'col-12');
        $regions['post'] = 'empty';
    }
    return $regions;
}

function theme_qmul_remove_mymodules_cache(\core\event\user_enrolment_deleted $event) {
    $userid = $event->relateduserid;

    $cache = cache::make('theme_qmul', 'searchcourses');
    $mymodules = enrol_get_users_courses($userid, true);
    if ($mymodules === false) {
        $mymodules = [];
    }
    $cache->set('mymodules_'.$userid, $mymodules);

    $cache = cache::make('theme_qmul', 'backpackcourses');
    $courses = $cache->get($userid);
    if ($courses !== false) {
        if (isset($courses->allmodules[$event->courseid])) {
            unset($courses->allmodules[$event->courseid]);
            if ($courses === false) {
                $courses = null;
            }
            $cache->set($userid, $courses);
        }
    }
}

function theme_qmul_add_mymodules_cache(\core\event\user_enrolment_created $event) {
    global $OUTPUT, $CFG;

    $userid = $event->relateduserid;

    $cache = cache::make('theme_qmul', 'searchcourses');
    $mymodules = enrol_get_users_courses($userid, true);
    if ($mymodules === false) {
        $mymodules = [];
    }
    $cache->set('mymodules_'.$userid, $mymodules);

    $cache = cache::make('theme_qmul', 'backpackcourses');
    $courses = $cache->get($userid);
    if ($courses !== false) {
        if (!isset($course->allmodules[$event->courseid])) {
            $course = get_course($event->courseid);
            $context = context_course::instance($event->courseid);
            $hidden = empty($course->visible);
            $editable = has_capability('moodle/course:update', $context);
            $listcourse = new course_in_list($course);
            $url = '';
            try {
                foreach ($listcourse->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                }
            } catch (Exception $e) {
                $url = '';
            }
            if (!empty($url)) {
                $course->overviewfile = $url;
            }
            if (!$course->visible) {
                $course->invisible = true;
            }
            $courseurl = new moodle_url('/course/view.php', array('id'=>$course->id));
            $course->url = $courseurl;
            if ($hidden) {
                if ($editable) {
                    $course->warning = $OUTPUT->pix_icon('i/warning', '', '', array('class'=>'iconlarge'));
                    $course->warning .= get_string('hidden_course_teacher', 'theme_qmul');
                } else {
                    $course->warning = $OUTPUT->pix_icon('i/info', '', '', array('class'=>'iconlarge'));
                    $course->warning .= get_string('hidden_course_student', 'theme_qmul');
                }
            }
            $course->userid = $userid;
            if (isset($overviews[$course->id])) {
                $course->overview = true;
                $overview = $overviews[$course->id];
                $modoverviews = array();
                foreach (array_keys($overview) as $module) {
                    $modinfo = new stdClass();
                    $url = new moodle_url("/mod/$module/index.php", array('id' => $course->id));
                    $modulename = get_string('modulename', $module);
                    $modinfo->icontext = html_writer::link($url, $OUTPUT->pix_icon('icon', $modulename, 'mod_'.$module, array('class'=>'iconlarge')));
                    if (get_string_manager()->string_exists("activityoverview", $module)) {
                        $modinfo->icontext .= get_string("activityoverview", $module);
                    } else {
                        $modinfo->icontext .= get_string("activityoverview", 'block_course_overview', $modulename);
                    }
                    $modoverviews[] = $modinfo;
                }
                $course->overviews = $modoverviews;
            }
            $courses->allmodules[$course->id] = $course;
            if ($courses === false) {
                $courses = null;
            }
            $cache->set($userid, $courses);
        }
    }
}

function theme_qmul_update_allmodules_cache() {
    global $DB, $PAGE;

    $cache = cache::make('theme_qmul', 'allsearchcourses');
    $allmodules = $DB->get_records('course', null, '', 'id, fullname, visible');
    $PAGE->theme->allmodules = $allmodules;
    if ($allmodules === false) {
        $allmodules = [];
    }
    $cache->set('allmodules', $allmodules);
}

function theme_qmul_extend_navigation_user_settings($navigation, $user, $usercontext, $course, $coursecontext) {
    $url = new moodle_url('/theme/qmul/preferences.php');
    $themenode = navigation_node::create(get_string('displaypreferences', 'theme_qmul'), $url,
            navigation_node::TYPE_SETTING, null, 'theme', new pix_icon('i/settings', ''));

    $navigation->add_node($themenode);
}

function theme_qmul_get_recent_courses() {
    global $DB, $USER;

    $sql = "SELECT c.id, c.fullname, c.visible
            FROM {user_lastaccess} ula
            JOIN {course} c ON (ula.courseid = c.id)
            WHERE ula.userid = ?
            ORDER BY ula.timeaccess DESC
            LIMIT 6";
    $params = array($USER->id);
    $courses = $DB->get_records_sql($sql, $params);

    return $courses;
}

function theme_qmul_get_my_modules($fields = NULL, $sort = 'lastaccess DESC', $limit = 0) {
    global $DB, $USER;

    // Guest account does not have any courses
    if (isguestuser() or !isloggedin()) {
        return(array());
    }

    $basefields = array('id', 'category', 'sortorder',
                        'shortname', 'fullname', 'idnumber',
                        'startdate', 'visible',
                        'groupmode', 'groupmodeforce', 'cacherev');

    if (empty($fields)) {
        $fields = $basefields;
    } else if (is_string($fields)) {
        // turn the fields from a string to an array
        $fields = explode(',', $fields);
        $fields = array_map('trim', $fields);
        $fields = array_unique(array_merge($basefields, $fields));
    } else if (is_array($fields)) {
        $fields = array_unique(array_merge($basefields, $fields));
    } else {
        throw new coding_exception('Invalid $fileds parameter in enrol_get_my_courses()');
    }
    if (in_array('*', $fields)) {
        $fields = array('*');
    }

    $orderby = "";
    $sort    = trim($sort);
    if (!empty($sort)) {
        $rawsorts = explode(',', $sort);
        $sorts = array();
        foreach ($rawsorts as $rawsort) {
            $rawsort = trim($rawsort);
            if (strpos($rawsort, 'c.') === 0) {
                $rawsort = substr($rawsort, 2);
            }
            $sorts[] = trim($rawsort);
        }
        $sort = implode(',', $sorts);
        $orderby = "ORDER BY $sort";
    }

    $wheres = array("c.id <> :siteid");
    $params = array('siteid'=>SITEID);

    if (isset($USER->loginascontext) and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
        // list _only_ this course - anything else is asking for trouble...
        $wheres[] = "en.courseid = :loginas";
        $params['loginas'] = $USER->loginascontext->instanceid;
    }

    $coursefields = 'c.' .join(',c.', $fields);
    $ccselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)";
    $params['contextlevel'] = CONTEXT_COURSE;
    $wheres = implode(" AND ", $wheres);

    //note: we can not use DISTINCT + text fields due to Oracle and MS limitations, that is why we have the subselect there
    $sql = "SELECT $coursefields $ccselect, ul.timeaccess as lastaccess, cc.name as categoryname
              FROM {course} c
              JOIN (SELECT DISTINCT e.courseid
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                     WHERE ue.status = :active AND e.status = :enabled AND ue.timestart < :now1 AND (ue.timeend = 0 OR ue.timeend > :now2)
                   ) en ON (en.courseid = c.id)
                LEFT JOIN {user_lastaccess} ul ON (ul.courseid = c.id AND ul.userid = :userid2)
                JOIN {course_categories} cc ON c.category = cc.id
           $ccjoin
             WHERE $wheres
          $orderby";
    $params['userid']  = $USER->id;
    $params['userid2']  = $USER->id;
    $params['active']  = ENROL_USER_ACTIVE;
    $params['enabled'] = ENROL_INSTANCE_ENABLED;
    $params['now1']    = round(time(), -2); // improves db caching
    $params['now2']    = $params['now1'];

    $courses = $DB->get_records_sql($sql, $params, 0, $limit);

    // preload contexts and check visibility
    foreach ($courses as $id=>$course) {
        context_helper::preload_from_record($course);
        if (!$course->visible) {
            if (!$context = context_course::instance($id, IGNORE_MISSING)) {
                unset($courses[$id]);
                continue;
            }
            if (!has_capability('moodle/course:viewhiddencourses', $context)) {
                unset($courses[$id]);
                continue;
            }
        }
        $courses[$id] = $course;
    }

    //wow! Is that really all? :-D

    return $courses;
}

function theme_qmul_process_css($css, $theme) {
    global $CFG;
    $themewww = $CFG->wwwroot."/theme";
    $remove = array('http:', 'https:');
    foreach($remove as $r) {
        if(strpos($themewww, $r) === 0) {
            $themewww = str_replace($r, '', $themewww);
        }
    }
    $tag = '[[fontsdir]]';
    $css = str_replace($tag, $themewww.'/qmul/fonts/', $css);

    $customcss = $theme->settings->customcss;
    $css = $css . $customcss;

    $tag = "fill='#";
    $css = str_replace($tag, "fill='%23", $css);

    return $css;
}

function theme_qmul_socialicons(){
    global $PAGE;

    // Social Settings
    $hasfacebook    = (empty($PAGE->theme->settings->facebook)) ? false : $PAGE->theme->settings->facebook;
    $hastwitter     = (empty($PAGE->theme->settings->twitter)) ? false : $PAGE->theme->settings->twitter;
    $hasgoogleplus  = (empty($PAGE->theme->settings->googleplus)) ? false : $PAGE->theme->settings->googleplus;
    $haslinkedin    = (empty($PAGE->theme->settings->linkedin)) ? false : $PAGE->theme->settings->linkedin;
    $hasyoutube     = (empty($PAGE->theme->settings->youtube)) ? false : $PAGE->theme->settings->youtube;
    $hasflickr      = (empty($PAGE->theme->settings->flickr)) ? false : $PAGE->theme->settings->flickr;
    $haspinterest   = (empty($PAGE->theme->settings->pinterest)) ? false : $PAGE->theme->settings->pinterest;
    $hasinstagram   = (empty($PAGE->theme->settings->instagram)) ? false : $PAGE->theme->settings->instagram;
    $hasskype       = (empty($PAGE->theme->settings->skype)) ? false : $PAGE->theme->settings->skype;
    $hassocialnetworks = ($hasfacebook || $hastwitter || $hasgoogleplus || $hasflickr || $hasinstagram || $haslinkedin || $haspinterest || $hasskype || $haslinkedin || $hasyoutube ) ? true : false;

    $output = '';
    if ($hassocialnetworks) {
        $output = html_writer::start_tag('div', array('class'=>'socialicons col-sm-6'));
        $output .= html_writer::start_tag('ul', array('class'=>'socials unstyled'));
            if ($hasgoogleplus) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-google-plus'));
                $anchor = html_writer::link($hasgoogleplus, $iconclass, array('class'=>'googleplus', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($hastwitter) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-twitter'));
                $anchor = html_writer::link($hastwitter, $iconclass, array('class'=>'twitter', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($hasfacebook) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-facebook'));
                $anchor = html_writer::link($hasfacebook, $iconclass,  array('class'=>'facebook', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($haslinkedin) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-linked-in'));
                $anchor = html_writer::link($haslinkedin, $iconclass,  array('class'=>'linkedin', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($hasyoutube) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-youtube'));
                $anchor = html_writer::link($hasyoutube, $iconclass,  array('class'=>'youtube', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($hasflickr) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-flickr'));
                $anchor = html_writer::link($hasflickr, $iconclass,  array('class'=>'flickr', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($haspinterest) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-pinterest'));
                $anchor = html_writer::link($haspinterest, $iconclass,  array('class'=>'pinterest', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($hasinstagram) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-instagram'));
                $anchor = html_writer::link($hasinstagram, $iconclass,  array('class'=>'instagram', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
            if ($hasskype) {
                $iconclass = html_writer::tag('i', '', array('class'=>'glyphicon social social-skype icon-light'));
                $anchor = html_writer::link($hasskype, $iconclass,  array('class'=>'skype', 'target'=>'_blank'));
                $output .= html_writer::tag('li', $anchor);
            }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('div');
    }
    return $output;
}

function theme_qmul_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $theme = theme_config::load('qmul');
        theme_qmul_store_in_localcache($filearea, $args, $options);
        exit;
    } else {
        send_file_not_found();
    }
}

function theme_qmul_store_in_localcache($filearea, $args, $options) {
    global $CFG;
    $filename = $args[1];
    $candidate = $CFG->localcachedir.'/theme_qmul/'.$filename;
    if (file_exists($candidate)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        header("Content-type:   ".finfo_file($finfo, $candidate));
        finfo_close($finfo);
        echo file_get_contents($candidate);
        return true;
    } else {
        require_once("$CFG->libdir/filelib.php");

        $syscontext = context_system::instance();
        $component = 'theme_qmul';

        if (!file_exists(dirname($candidate))) {
            @mkdir(dirname($candidate), $CFG->directorypermissions, true);
        }

        $revision = array_shift($args);
        if ($revision < 0) {
            $lifetime = 0;
        } else {
            $lifetime = 60*60*24*60;
            // By default, theme files must be cache-able by both browsers and proxies.
            if (!array_key_exists('cacheability', $options)) {
                $options['cacheability'] = 'public';
            }
        }

        $fs = get_file_storage();
        $relativepath = implode('/', $args);

        $fullpath = "/{$syscontext->id}/{$component}/{$filearea}/0/{$relativepath}";
        $fullpath = rtrim($fullpath, '/');
        $file = $fs->get_file_by_hash(sha1($fullpath));
        if ($file) {
            $contents = $file->get_content();
        } else {
            send_file_not_found();
        }
        if ($fp = fopen($candidate.'.tmp', 'xb')) {
            fwrite($fp, $contents);
            fclose($fp);
            rename($candidate.'.tmp', $candidate);
            @chmod($candidate, $CFG->filepermissions);
            @unlink($candidate.'.tmp'); // just in case anything fails
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        header("Content-type:   ".finfo_file($finfo, $candidate));
        finfo_close($finfo);
        echo file_get_contents($candidate);
        return true;
    }
}

function theme_qmul_page_init(moodle_page $page) {
    global $CFG, $DB, $USER;

    if ($page->pagelayout == 'mydashboard') {
        $redir = \block_landingpage\landingpage::instance()->get_landing_page($USER);
        if ($redir) {
            redirect($redir);
        }
    }

    theme_qmul_load_search_courses();

    $page->requires->js_call_amd('theme_qmul/loader', 'init');
    $page->requires->js_call_amd('theme_qmul/scaffolding', 'init');
    $page->requires->js_call_amd('theme_qmul/ieplaceholders', 'init');
    $page->requires->js_call_amd('theme_qmul/activitychooser', 'init');
    $page->requires->js_call_amd('theme_qmul/blockicons', 'init');
    $page->requires->js_call_amd('theme_qmul/gradebook', 'init');
    $page->requires->js_call_amd('theme_qmul/alerts', 'init');
    $page->requires->js_call_amd('theme_qmul/questionflag', 'init');
    $page->requires->js_call_amd('theme_qmul/fileinputs', 'init');
    $page->requires->js_call_amd('theme_qmul/popover', 'init');
    $page->requires->js_call_amd('theme_qmul/replaceicons', 'init');
    $page->requires->js_call_amd('theme_qmul/ticker', 'init');
    $page->requires->js_call_amd('theme_qmul/backpackmodules', 'init');
    $page->requires->js_call_amd('theme_qmul/zoom', 'init');
    $drawermenus = (empty($page->theme->settings->drawermenus)) ? false : $page->theme->settings->drawermenus;
    $stickytables = (empty($page->theme->settings->stickytables)) ? false : $page->theme->settings->stickytables;

    $page->requires->js_call_amd('theme_qmul/drawermenu', 'init');

    if ($drawermenus) {
        if ($page->pagetype != 'mod-assign-grader') {
            $page->add_body_class('drawermenus');
        }
    }
    if ($stickytables) {
        $page->requires->js_call_amd('theme_qmul/stickytables', 'init');
    }

    if ($page->pagetype == 'mod-scorm-player') {
        if ($page->theme->settings->fullscreenscorm) {
            $page->add_body_class('fullscreenscorm');
        }
    }

    $page->requires->js_call_amd('theme_qmul/tableoverflow', 'init');

    if ($page->course->id != SITEID) {
        $coursevars = (object) [
            'id' => $page->course->id,
            'shortname' => $page->course->shortname,
            'contextid' => $page->context->id
        ];
        $initvars = array($coursevars, get_max_upload_file_size($CFG->maxbytes));
        $page->requires->string_for_js('loading', 'theme_qmul');
        $page->requires->js_call_amd('theme_qmul/courseimage', 'init', $initvars);
    }

    $page->requires->js_call_amd('theme_qmul/forumicons', 'init');
    $page->requires->string_for_js('edit', 'forum');
    $page->requires->string_for_js('delete', 'forum');
    $page->requires->string_for_js('reply', 'forum');
    $page->requires->string_for_js('parent', 'forum');
    $page->requires->string_for_js('permalink', 'forum');
    $page->requires->string_for_js('prune', 'forum');


    $page->requires->string_for_js('pinthismodule', 'theme_qmul');
    $page->requires->string_for_js('unpinthismodule', 'theme_qmul');

    $page->requires->strings_for_js(array(
        'close',
        'error:courseimageexceedsmaxbytes',
        'error:courseimageresolutionlow',
        'loading',
    ), 'theme_qmul');

    return true;
}

function theme_qmul_load_search_courses() {
    global $DB, $PAGE, $SESSION, $USER;

    $cache = cache::make('theme_qmul', 'searchcourses');
    $allcache = cache::make('theme_qmul', 'allsearchcourses');
    $mymodules = $cache->get('mymodules_'.$USER->id);
    $allmodules = $allcache->get('allmodules');
    if (empty($mymodules) || isset($SESSION->justloggedin)) {
        if (isloggedin() && !isguestuser()) {
            $mymodules = enrol_get_my_courses();
        }
    }

    if (empty($allmodules)) {
        $allmodules = $DB->get_records('course', null, '', 'id, fullname, visible');
    }

    $PAGE->theme->mymodules = $mymodules;
    $PAGE->theme->allmodules = $allmodules;

    if ($mymodules === false) {
        $mymodules = [];
    }
    if ($allmodules === false) {
        $allmodules = [];
    }
    $cache->set('mymodules_'.$USER->id, $mymodules);
    $allcache->set('allmodules', $allmodules);
}

function theme_qmul_truncate_html($text, $length = 150, $ending = '...', $exact = false, $considerHtml = true) {
    if ($considerHtml) {
        // if the plain text is shorter than the maximum length, return the whole text
        if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        // splits all html-tags to scanable lines
        preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
        $total_length = strlen($ending);
        $open_tags = array();
        $truncate = '';
        foreach ($lines as $line_matchings) {
            // if there is any html-tag in this line, handle it and add it (uncounted) to the output
            if (!empty($line_matchings[1])) {
                // if it's an "empty element" with or without xhtml-conform closing slash
                if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                    // do nothing
                // if tag is a closing tag
                } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                    // delete tag from $open_tags list
                    $pos = array_search($tag_matchings[1], $open_tags);
                    if ($pos !== false) {
                    unset($open_tags[$pos]);
                    }
                // if tag is an opening tag
                } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                    // add tag to the beginning of $open_tags list
                    array_unshift($open_tags, strtolower($tag_matchings[1]));
                }
                // add html-tag to $truncate'd text
                $truncate .= $line_matchings[1];
            }
            // calculate the length of the plain text part of the line; handle entities as one character
            $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
            if ($total_length+$content_length> $length) {
                // the number of characters which are left
                $left = $length - $total_length;
                $entities_length = 0;
                // search for html entities
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                    // calculate the real length of all entities in the legal range
                    foreach ($entities[0] as $entity) {
                        if ($entity[1]+1-$entities_length <= $left) {
                            $left--;
                            $entities_length += strlen($entity[0]);
                        } else {
                            // no more characters left
                            break;
                        }
                    }
                }
                $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
                // maximum lenght is reached, so get off the loop
                break;
            } else {
                $truncate .= $line_matchings[2];
                $total_length += $content_length;
            }
            // if the maximum length is reached, get off the loop
            if($total_length>= $length) {
                break;
            }
        }
    } else {
        if (strlen($text) <= $length) {
            return $text;
        } else {
            $truncate = substr($text, 0, $length - strlen($ending));
        }
    }
    // if the words shouldn't be cut in the middle...
    if (!$exact) {
        // ...search the last occurance of a space...
        $spacepos = strrpos($truncate, ' ');
        if (isset($spacepos)) {
            // ...and cut the text in this position
            $truncate = substr($truncate, 0, $spacepos);
        }
    }
    // add the defined ending to the text
    $truncate .= $ending;
    if($considerHtml) {
        // close all unclosed html-tags
        foreach ($open_tags as $tag) {
            $truncate .= '</' . $tag . '>';
        }
    }
    return $truncate;
}

function theme_qmul_add_pin_tab() {
    global $COURSE, $USER;

    $preferences = get_user_preferences();
    $pref = "theme_qmul_pincourse_{$COURSE->id}";
    $class = '';
    $str = get_string('pinthismodule', 'theme_qmul');
    $state = 0;
    $icon = '<svg class="pinicon unpinned" xmlns="http://www.w3.org/2000/svg" width="16" height="22" viewBox="0 0 16 22">
          <path fill-rule="evenodd" d="M18.7719196,148.610912 L18.7719196,143.226296 C18.7719196,143.114116 18.7358622,143.02197 18.6637465,142.949854 C18.5916308,142.877738 18.4994842,142.841681 18.3873042,142.841681 C18.2751241,142.841681 18.1829776,142.877738 18.1108619,142.949854 C18.0387461,143.02197 18.0026888,143.114116 18.0026888,143.226296 L18.0026888,148.610912 C18.0026888,148.723092 18.0387461,148.815238 18.1108619,148.887354 C18.1829776,148.95947 18.2751241,148.995527 18.3873042,148.995527 C18.4994842,148.995527 18.5916308,148.95947 18.6637465,148.887354 C18.7358622,148.815238 18.7719196,148.723092 18.7719196,148.610912 Z M26.8488427,152.841681 C26.8488427,153.050015 26.7727216,153.230302 26.6204773,153.382546 C26.4682329,153.534791 26.2879463,153.610912 26.0796119,153.610912 L20.9233619,153.610912 L20.3103811,159.4162 C20.2943554,159.512355 20.2522885,159.594485 20.1841792,159.662595 C20.1160699,159.730704 20.0339393,159.764758 19.937785,159.764758 L19.9257657,159.764758 C19.7094185,159.764758 19.5812146,159.656586 19.5411503,159.440239 L18.6276888,153.610912 L13.7719196,153.610912 C13.5635852,153.610912 13.3832985,153.534791 13.2310542,153.382546 C13.0788098,153.230302 13.0026888,153.050015 13.0026888,152.841681 C13.0026888,151.856099 13.3171889,150.968688 13.9461984,150.179421 C14.575208,149.390155 15.2863387,148.995527 16.0796119,148.995527 L16.0796119,142.841681 C15.6629431,142.841681 15.3023698,142.689439 14.9978811,142.38495 C14.6933924,142.080462 14.5411503,141.719888 14.5411503,141.30322 C14.5411503,140.886551 14.6933924,140.525977 14.9978811,140.221489 C15.3023698,139.917 15.6629431,139.764758 16.0796119,139.764758 L23.7719196,139.764758 C24.1885883,139.764758 24.5491616,139.917 24.8536503,140.221489 C25.158139,140.525977 25.3103811,140.886551 25.3103811,141.30322 C25.3103811,141.719888 25.158139,142.080462 24.8536503,142.38495 C24.5491616,142.689439 24.1885883,142.841681 23.7719196,142.841681 L23.7719196,148.995527 C24.5651928,148.995527 25.2763235,149.390155 25.905333,150.179421 C26.5343426,150.968688 26.8488427,151.856099 26.8488427,152.841681 Z" transform="rotate(20 405.244 46.737)"/>
        </svg>';
    $icon .= '<svg class="pinicon pinned" xmlns="http://www.w3.org/2000/svg" width="14" height="20" viewBox="0 0 14 20">
          <path fill-rule="evenodd" d="M18.7692308,78.8461538 L18.7692308,73.4615385 C18.7692308,73.3493584 18.7331734,73.2572119 18.6610577,73.1850962 C18.5889419,73.1129804 18.4967954,73.0769231 18.3846154,73.0769231 C18.2724353,73.0769231 18.1802888,73.1129804 18.1081731,73.1850962 C18.0360573,73.2572119 18,73.3493584 18,73.4615385 L18,78.8461538 C18,78.9583339 18.0360573,79.0504804 18.1081731,79.1225962 C18.1802888,79.1947119 18.2724353,79.2307692 18.3846154,79.2307692 C18.4967954,79.2307692 18.5889419,79.1947119 18.6610577,79.1225962 C18.7331734,79.0504804 18.7692308,78.9583339 18.7692308,78.8461538 Z M26.8461538,83.0769231 C26.8461538,83.2852575 26.7700328,83.4655441 26.6177885,83.6177885 C26.4655441,83.7700328 26.2852575,83.8461538 26.0769231,83.8461538 L20.9206731,83.8461538 L20.3076923,89.6514423 C20.2916666,89.7475966 20.2495997,89.8297272 20.1814904,89.8978365 C20.1133811,89.9659459 20.0312505,90 19.9350962,90 L19.9230769,90 C19.7067297,90 19.5785258,89.891828 19.5384615,89.6754808 L18.625,83.8461538 L13.7692308,83.8461538 C13.5608964,83.8461538 13.3806097,83.7700328 13.2283654,83.6177885 C13.076121,83.4655441 13,83.2852575 13,83.0769231 C13,82.0913412 13.3145001,81.2039302 13.9435096,80.4146635 C14.5725192,79.6253967 15.2836499,79.2307692 16.0769231,79.2307692 L16.0769231,73.0769231 C15.6602543,73.0769231 15.299681,72.924681 14.9951923,72.6201923 C14.6907036,72.3157036 14.5384615,71.9551303 14.5384615,71.5384615 C14.5384615,71.1217928 14.6907036,70.7612195 14.9951923,70.4567308 C15.299681,70.1522421 15.6602543,70 16.0769231,70 L23.7692308,70 C24.1858995,70 24.5464728,70.1522421 24.8509615,70.4567308 C25.1554502,70.7612195 25.3076923,71.1217928 25.3076923,71.5384615 C25.3076923,71.9551303 25.1554502,72.3157036 24.8509615,72.6201923 C24.5464728,72.924681 24.1858995,73.0769231 23.7692308,73.0769231 L23.7692308,79.2307692 C24.562504,79.2307692 25.2736347,79.6253967 25.9026442,80.4146635 C26.5316538,81.2039302 26.8461538,82.0913412 26.8461538,83.0769231 Z" transform="translate(-13 -70)"/>
        </svg>';
    if (isset($preferences[$pref]) && $preferences[$pref] == 1) {
        $class = ' pinned';
        $str = get_string('unpinthismodule', 'theme_qmul');
        $state = 1;
    }

    echo html_writer::start_tag('li', array('class'=>'qmultabitem nav-item pincoursetab'));
    echo html_writer::tag('a', $icon,
        array(
            'data-toggle'=>'tooltip',
            'class'=>'qmultablink nav-link pincourse'.$class,
            'title'=>$str,
            'href'=>'javascript://void(0)',
            'data-userid'=>$USER->id,
            'data-courseid'=>$COURSE->id,
            'data-state'=>$state
        )
    );
    echo html_writer::end_tag('li');
}
























