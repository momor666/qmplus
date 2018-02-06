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
 * Theme functions.
 *
 * @package    theme_bloom
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post process the CSS tree.
 *
 * @param string $tree The CSS tree.
 * @param theme_config $theme The theme config object.
 */
function theme_bloom_css_tree_post_processor($tree, $theme) {
    $prefixer = new theme_bloom\autoprefixer($tree);
    $prefixer->prefix();
}

/**
 * Get the SCSS file to include.
 *
 * @param theme_config $theme The theme config object.
 * @return string The name of the file without 'scss'.
 */
// function theme_bloom_get_scss_file($theme) {
//     $preset = !empty($theme->settings->preset) ? $theme->settings->preset : 'default';
//     return 'preset-' . $preset;
// }

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_bloom_get_extra_scss($theme) {
    $content = '';
    $imageurl = $theme->setting_file_url('backgroundimage', 'backgroundimage');

    if (!empty($imageurl)) {
        $content .= "body.path-login { ";
        $content .= "background-image: linear-gradient(rgba(50, 84, 140, 0.5),rgba(50, 84, 140, 0.5)),";
        $content .= "url('$imageurl');";
        $content .= " }";
    }

    return $content;

    // /return !empty($theme->settings->scss) ? $theme->settings->scss : '';
}


/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return array
 */
function theme_bloom_get_scss_variables($theme) {
    global $CFG;

    $scss = '';
    $configurable = [
        //Config key => [variableName, ...].
        'primary' => ['brand-secondary'],
        'secondary' => ['brand-primary'],
        'accentcolor' => ['bloom-accent-color'],
        'tab1' => ['bloom-courses-color'],
        'tab2' => ['bloom-assignments-color'],
        'tab3' => ['bloom-user-color'],
        'footer' => ['gray-dark'],
        'blocksgray' => ['blocks-gray'],
        'blocksblue' => ['blocks-blue'],
        'btntextcolor' => ['btn-text-color'],
        'headertextcolor' => ['bloom-header-text-color']
    ];

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        array_map(function($target) use (&$scss, $value) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }, (array) $targets);
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}


/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_bloom_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM and ($filearea === 'logo' || $filearea === 'logocompact' || $filearea === 'backgroundimage' || $filearea === 'logomain' || $filearea === 'favicon' )) {
        $theme = theme_config::load('bloom');
        // By default, theme files must be cache-able by both browsers and proxies.
        // if (!array_key_exists('cacheability', $options)) {
        //     $options['cacheability'] = 'public';
        // }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

function theme_bloom_page_init(moodle_page $page) {

    $scripts = array(
        'setZoom.js' //this has to be loaded before anything
    );

    $page->requires->jquery();
    foreach ($scripts as $script)  {
        $page->requires->js("/theme/bloom/javascript/{$script}", true);
    }
}



/*** override for core calendar **/
/** Kaori  **/
/**
 * Formats a filter control element.
 *
 * @param moodle_url $url of the filter
 * @param int $type constant defining the type filter
 * @return string html content of the element
 */
function theme_bloom_calendar_filter_controls_element(moodle_url $url, $type) {
   
    global $OUTPUT;
    $showhide = 'show';

    switch ($type) {
        case CALENDAR_EVENT_GLOBAL:
            $typeforhumans = 'global';
            $class = 'calendar_event_global';
            break;
        case CALENDAR_EVENT_COURSE:
            $typeforhumans = 'course';
            $class = 'calendar_event_course';
            break;
        case CALENDAR_EVENT_GROUP:
            $typeforhumans = 'groups';
            $class = 'calendar_event_group';
            break;
        case CALENDAR_EVENT_USER:
            $typeforhumans = 'user';
            $class = 'calendar_event_user';
            break;
    }
    if (calendar_show_event_type($type)) {
        $showhide = 'hide';
        $icon = $OUTPUT->pix_icon($showhide, get_string($showhide),'block_calendar_agenda');
        $str = get_string($showhide.$typeforhumans.'events', 'calendar');
    } else {
        $icon = $OUTPUT->pix_icon($showhide, get_string($showhide),'block_calendar_agenda');
        $str = get_string($showhide.$typeforhumans.'events', 'calendar');
    }
    $content = html_writer::start_tag('li', array('class' => 'calendar_event'));
    $content .= html_writer::start_tag('a', array('href' => $url, 'rel' => 'nofollow'));
    $content .= html_writer::tag('span', $icon, array('class' => $class . ' ' .$showhide .'-event'));
    $content .= html_writer::tag('span', $str, array('class' => 'eventname'));
    $content .= html_writer::end_tag('a');
    $content .= html_writer::end_tag('li');
    return $content;
}

function theme_bloom_calendar_filter_controls(moodle_url $returnurl) {
    global $CFG, $USER, $OUTPUT;

    $groupevents = true;
    $id = optional_param( 'id',0,PARAM_INT );
    $seturl = new moodle_url('/calendar/set.php', array('return' => base64_encode($returnurl->out_as_local_url(false)), 'sesskey'=>sesskey()));
    $content = html_writer::start_tag('ul');

    $seturl->param('var', 'showglobal');
    $content .= theme_bloom_calendar_filter_controls_element($seturl, CALENDAR_EVENT_GLOBAL);

    $seturl->param('var', 'showcourses');
    $content .= theme_bloom_calendar_filter_controls_element($seturl, CALENDAR_EVENT_COURSE);

    if (isloggedin() && !isguestuser()) {
        if ($groupevents) {
            // This course MIGHT have group events defined, so show the filter
            $seturl->param('var', 'showgroups');
            $content .= theme_bloom_calendar_filter_controls_element($seturl, CALENDAR_EVENT_GROUP);
        } else {
            // This course CANNOT have group events, so lose the filter
        }
        $seturl->param('var', 'showuser');
        $content .= theme_bloom_calendar_filter_controls_element($seturl, CALENDAR_EVENT_USER);
    }
    $content .= html_writer::end_tag('ul');

    return $content;
}

/** add export links in the side bar **/
/** Kaori  **/
function theme_bloom_add_export_fake_block($course) { 
    global $CFG, $OUTPUT, $DB, $USER;
   
   $content = '';

     if (!empty($CFG->enablecalendarexport)) {
            $content .= $OUTPUT->single_button(new moodle_url('export.php', array('course'=>$course->id)), get_string('exportcalendar', 'calendar'));
            if (calendar_user_can_add_event($course)) {
                $content .= $OUTPUT->single_button(new moodle_url('/calendar/managesubscriptions.php', array('course'=>$course->id)), get_string('managesubscriptions', 'calendar'));
            }
            if (isloggedin()) {
                $authtoken = sha1($USER->id . $DB->get_field('user', 'password', array('id' => $USER->id)) . $CFG->calendar_exportsalt);
                $link = new moodle_url(
                    '/calendar/export_execute.php',
                    array('preset_what'=>'all', 'preset_time' => 'recentupcoming', 'userid' => $USER->id, 'authtoken'=>$authtoken)
                );
                $content .= html_writer::tag('a', 'iCal',
                    array('href' => $link, 'title' => get_string('quickdownloadcalendar', 'calendar'), 'class' => 'ical-link'));
            }
        }
    return $content;
}

/**
 * Parses CSS before it is cached.
 *
 * This function can make alterations and replace patterns within the CSS.
 *
 * @param string $css The CSS
 * @param theme_config $theme The theme config object.
 * @return string The parsed CSS The parsed CSS.
 */
function theme_bloom_process_css($css, $theme) {
    global $OUTPUT;

    // Set custom CSS.
    if (!empty($theme->settings->customcss)) {
        $customcss = $theme->settings->customcss;
    } else {
        $customcss = null;
    }
    $css = theme_bloom_set_customcss($css, $customcss);

    return $css;
}

/**
 * Adds any custom CSS to the CSS before it is cached.
 *
 * @param string $css The original CSS.
 * @param string $customcss The custom CSS to add.
 * @return string The CSS which now contains our custom CSS.
 */
function theme_bloom_set_customcss($css, $customcss) {
    $tag = '[[setting:customcss]]';
    $replacement = $customcss;
    if (is_null($replacement)) {
        $replacement = '';
    }

    $css = str_replace($tag, $replacement, $css);

    return $css;
}
