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
 * Grid Format - A topics based format that uses a grid of user selectable images to popup a light box of the section.
 *
 * @package    course/format
 * @subpackage grid
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/grid/renderer.php');
class theme_qmul_format_grid_renderer extends format_grid_renderer {

    /**
     * Constructor method, calls the parent constructor - MDL-21097
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->courseformat = course_get_format($page->course);
        $this->settings = $this->courseformat->get_settings();
        $this->topic0attop = $this->courseformat->get_summary_visibility($page->course->id)->showsummary == 1;

        $this->tcsettings = $this->courseformat->get_format_options();

        /* Since format_grid_renderer::section_edit_controls() only displays the 'Set current section' control when editing
           mode is on we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any
           other managing capability. */
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods
     * @param array $modnames
     * @param array $modnamesused
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $USER;
        if (!empty($USER->profile['accessible'])) {
            return parent::print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
        }

        global $PAGE;
        $coursecontext = context_course::instance($course->id);
        $editing = $PAGE->user_is_editing();
        $hascapvishidsect = has_capability('moodle/course:viewhiddensections', $coursecontext);

        if ($editing) {
            $streditsummary = get_string('editsummary');
            $urlpicedit = $this->output->pix_url('t/edit');
        } else {
            $urlpicedit = false;
            $streditsummary = '';
        }

        if (empty($this->tcsettings)) {
            $this->tcsettings = $this->courseformat->get_format_options();
        }

        $extratabnames = array('extratab1', 'extratab2', 'extratab3');
        $extratabs = array();
        if (isset($this->tcsettings['enable_assessmentinformation']) &&
            $this->tcsettings['enable_assessmentinformation'] == 1) {
            $tab = new stdClass();
            $tab->name = 'assessmentinformation';
            $tab->title = get_string('assessmentinformation', 'format_qmultc');
            $tab->content = qmul_format_get_assessmentinformation($this->tcsettings['content_assessmentinformation']);
            $extratabs[] = $tab;
        }

        foreach ($extratabnames as $extratabname) {
            if (isset($this->tcsettings["enable_{$extratabname}"]) &&
                $this->tcsettings["enable_{$extratabname}"] == 1) {
                $tab = new stdClass();
                $tab->name = $extratabname;
                $tab->title = format_text($this->tcsettings["title_{$extratabname}"]);
                $tab->content = format_text($this->tcsettings["content_{$extratabname}"]);
                $extratabs[] = $tab;
            }
        }


        // Add tab navigation
        echo html_writer::start_tag('ul', array('class'=>'qmultabs nav nav-tabs row'));
            echo html_writer::start_tag('li', array('class'=>'qmultabitem nav-item'));
            echo html_writer::tag('a', get_string('modulecontent', 'format_qmultc'), array('data-toggle'=>'tab', 'class'=>'qmultablink nav-link active modulecontentlink', 'href'=>'#modulecontent'));
            echo html_writer::end_tag('li');
            if (function_exists('theme_qmul_add_pin_tab')) {
                theme_qmul_add_pin_tab();
            }
            foreach ($extratabs as $extratab) {
                echo html_writer::start_tag('li', array('class'=>'qmultabitem nav-item'));
                echo html_writer::tag('a', $extratab->title, array('data-toggle'=>'tab', 'class'=>"nav-link qmultablink {$extratab->name}", 'href'=>"#{$extratab->name}"));
                echo html_writer::end_tag('li');
            }
        echo html_writer::end_tag('ul');


        echo html_writer::start_tag('div', array('class'=>'qmultabcontent tab-content row bg-white'));
        echo html_writer::start_tag('div', array('id'=>'modulecontent', 'class'=>'col-12 tab-pane qmultab modulecontent active'));

        echo html_writer::start_tag('div', array('id' => 'gridmiddle-column'));
        echo $this->output->skip_link_target();

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
        if ($this->topic0attop) {
            $this->topic0attop = $this->make_block_topic0($course, $sections, $modinfo, $editing, $urlpicedit,
                    $streditsummary, false);
            // For the purpose of the grid shade box shown array topic 0 is not shown.
            $this->shadeboxshownarray[0] = 1;
        }
        echo html_writer::start_tag('div', array('id' => 'gridiconcontainer', 'role' => 'navigation',
            'aria-label' => get_string('gridimagecontainer', 'format_grid')));
                        $sectiontitleclass = 'icon_content';
        $gridiconsclass = 'gridicons';
        if ($this->settings['sectiontitleboxposition'] == 1) {
            $gridiconsclass .= ' content_inside';
        }
        echo html_writer::start_tag('ul', array('class' => $gridiconsclass));
        // Print all of the image containers.
        $this->make_block_icon_topics($coursecontext->id, $modinfo, $course, $editing, $hascapvishidsect, $urlpicedit);
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('div');

        $rtl = right_to_left();
        if (!(($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) && (!$editing))) {
            echo html_writer::start_tag('div', array('id' => 'gridshadebox'));
            echo html_writer::tag('div', '', array('id' => 'gridshadebox_overlay', 'style' => 'display: none;'));

            $gridshadeboxcontentclasses = array('hide_content');
            if (!$editing) {
                if ($this->settings['fitsectioncontainertowindow'] == 2) {
                    $gridshadeboxcontentclasses[] = 'fit_to_window';
                } else {
                    $gridshadeboxcontentclasses[] = 'absolute';
                }
            }

            echo html_writer::start_tag('div', array('id' => 'gridshadebox_content', 'class' => implode(' ',
                $gridshadeboxcontentclasses),
                'role' => 'region',
                'aria-label' => get_string('shadeboxcontent', 'format_grid')));

            $deviceextra = '';
            if (isset($this->portable)) {
                switch ($this->portable) {
                    case 1: // Mobile.
                        $deviceextra = ' gridshadebox_mobile';
                    break;
                    case 2: // Tablet.
                        $deviceextra = ' gridshadebox_tablet';
                    break;
                    default:
                    break;
                }
            }
            echo html_writer::tag('img', '', array('id' => 'gridshadebox_close', 'style' => 'display: none;',
                'class' => $deviceextra,
                'src' => $this->output->pix_url('close', 'format_grid'),
                'role' => 'link',
                'aria-label' => get_string('closeshadebox', 'format_grid')));

            // Only show the arrows if there is more than one box shown.
            if (($course->numsections > 1) || (($course->numsections == 1) && (!$this->topic0attop))) {
                if ($rtl) {
                    $previcon = 'right';
                    $nexticon = 'left';
                    $areadir = 'rtl';
                } else {
                    $previcon = 'left';
                    $nexticon = 'right';
                    $areadir = 'ltr';
                }
                $prev = html_writer::start_tag('div', array('id' => 'gridshadebox_previous',
                    'class' => 'gridshadebox_area gridshadebox_previous_area '.$areadir,
                    'style' => 'display: none;',
                    'role' => 'link',
                    'aria-label' => get_string('previoussection', 'format_grid')));
                $prev .= html_writer::tag('img', '', array('class' => 'gridshadebox_arrow gridshadebox_previous'.$deviceextra,
                    'src' => $this->output->pix_url('fa-arrow-circle-'.$previcon.'-w', 'format_grid')));
                $prev .= html_writer::end_tag('div');
                $next = html_writer::start_tag('div', array('id' => 'gridshadebox_next',
                    'class' => 'gridshadebox_area gridshadebox_next_area '.$areadir,
                    'style' => 'display: none;',
                    'role' => 'link',
                    'aria-label' => get_string('nextsection', 'format_grid')));
                $next .= html_writer::tag('img', '', array('class' => 'gridshadebox_arrow gridshadebox_next'.$deviceextra,
                    'src' => $this->output->pix_url('fa-arrow-circle-'.$nexticon.'-w', 'format_grid')));
                $next .= html_writer::end_tag('div');

                if ($rtl) {
                    echo $next.$prev;
                } else {
                    echo $prev.$next;
                }
            }

            echo $this->start_section_list();
            // If currently moving a file then show the current clipboard.
            $this->make_block_show_clipboard_if_file_moving($course);

            // Print Section 0 with general activities.
            if (!$this->topic0attop) {
                $this->make_block_topic0($course, $sections, $modinfo, $editing, $urlpicedit, $streditsummary, false);
            }

            // Now all the normal modules by topic.
            // Everything below uses "section" terminology - each "section" is a topic/module.
            $this->make_block_topics($course, $sections, $modinfo, $editing, $hascapvishidsect, $streditsummary,
                    $urlpicedit, false);

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::tag('div', '&nbsp;', array('class' => 'clearer'));
        }
        echo html_writer::end_tag('div');

        echo html_writer::end_tag('div');

        foreach ($extratabs as $extratab) {
            echo html_writer::start_tag('div', array('id'=>$extratab->name, 'class'=>'tab-pane col-12 '.$extratab->name));
            echo html_writer::tag('div', $extratab->content, array('class'=>'p-1'));
            echo html_writer::end_tag('div');
        }
        echo html_writer::end_tag('div');

        $sectionredirect = null;
        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            // Get the redirect URL prefix for keyboard control with the 'Show one section per page' layout.
            $sectionredirect = $this->courseformat->get_view_url(null)->out(true);
        }

        // Initialise the shade box functionality:...
        $PAGE->requires->js_init_call('M.format_grid.init', array(
            $PAGE->user_is_editing(),
            $sectionredirect,
            $course->numsections,
            json_encode($this->shadeboxshownarray)));
        // Initialise the key control functionality...
        $PAGE->requires->yui_module('moodle-format_grid-gridkeys', 'M.format_grid.gridkeys.init',
            array(array('editing' => $PAGE->user_is_editing(), 'rtl' => $rtl)), null, true);
    }

        // Grid format specific code.
    /**
     * Makes section zero.
     */
    private function make_block_topic0($course, $sections, $modinfo, $editing, $urlpicedit, $streditsummary,
            $onsectionpage) {
        $section = 0;
        if (!array_key_exists($section, $sections)) {
            return false;
        }

        $thissection = $modinfo->get_section_info($section);
        if (!is_object($thissection)) {
            return false;
        }

        if ($this->topic0attop) {
            echo html_writer::start_tag('ul', array('class' => 'gtopics-0'));
        }

        $sectionname = $this->courseformat->get_section_name($thissection);
        echo html_writer::start_tag('li', array(
            'id' => 'section-0',
            'class' => 'section main' . ($this->topic0attop ? '' : ' grid_section hide_section'),
            'role' => 'region',
            'aria-label' => $sectionname)
        );

        echo html_writer::start_tag('div', array('class' => 'content'));

        echo html_writer::start_tag('div', array('class' => 'summary'));

        echo $this->format_summary_text($thissection);

        if ($editing) {
            echo html_writer::link(
                            new moodle_url('editsection.php', array('id' => $thissection->id)),
                                html_writer::empty_tag('img', array('src' => $urlpicedit,
                                                                     'alt' => $streditsummary,
                                                                     'class' => 'iconsmall edit')),
                                                        array('title' => $streditsummary));
        }
        echo html_writer::end_tag('div');

        echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);

        if ($editing) {
            echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0, 0);

            if ($this->topic0attop) {
                $strhidesummary = get_string('hide_summary', 'format_grid');
                $strhidesummaryalt = get_string('hide_summary_alt', 'format_grid');

                echo html_writer::link(
                        $this->courseformat->grid_moodle_url('mod_summary.php', array(
                            'sesskey' => sesskey(),
                            'course' => $course->id,
                            'showsummary' => 0)), html_writer::empty_tag('img', array(
                            'src' => $this->output->pix_url('into_grid', 'format_grid'),
                            'alt' => $strhidesummaryalt)) . '&nbsp;' . $strhidesummary, array('title' => $strhidesummaryalt));
            }
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('li');

        if ($this->topic0attop) {
            echo html_writer::end_tag('ul');
        }
        return true;
    }

    /**
     * Makes the grid image containers.
     */
    private function make_block_icon_topics($contextid, $modinfo, $course, $editing, $hascapvishidsect,
            $urlpicedit) {
        global $CFG;

        if ($this->settings['newactivity'] == 2) {
            $currentlanguage = current_language();
            if (!file_exists("$CFG->dirroot/course/format/grid/pix/new_activity_".$currentlanguage.".png")) {
                $currentlanguage = 'en';
            }
            $urlpicnewactivity = $this->output->pix_url('new_activity_'.$currentlanguage, 'format_grid');

            // Get all the section information about which items should be marked with the NEW picture.
            $sectionupdated = $this->new_activity($course);
        }

        // Get the section images for the course.
        $sectionimages = $this->courseformat->get_images($course->id);

        // CONTRIB-4099:...
        $gridimagepath = $this->courseformat->get_image_path();

        if ($course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
            $singlepageurl = $this->courseformat->get_view_url(null)->out(true);
        }

        if ($this->settings['showsectiontitlesummary'] == 2) {
            global $PAGE;
            $PAGE->requires->js_call_amd('format_grid/tooltip', 'init', array());
        }

        // Start at 1 to skip the summary block or include the summary block if it's in the grid display.
        for ($section = $this->topic0attop ? 1 : 0; $section <= $course->numsections; $section++) {
            $thissection = $modinfo->get_section_info($section);

            // Check if section is visible to user.
            $sectionvisible = ($thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo)));
            $showsection = $hascapvishidsect || $sectionvisible;

            // If we should grey it out, flag that here.  Justin 2016/05/14.
            $sectionunavailable = !$thissection->available;
            $greyouthidden = $this->settings['greyouthidden'] == 2;
            $sectiongreyedout = $sectionunavailable && !$hascapvishidsect && $greyouthidden;

            if ($showsection || $sectiongreyedout) {
                // We now know the value for the grid shade box shown array.
                $this->shadeboxshownarray[$section] = 2;

                /* Roles info on based on: http://www.w3.org/TR/wai-aria/roles.
                   Looked into the 'grid' role but that requires 'row' before 'gridcell' and there are none as the grid
                   is responsive, so as the container is a 'navigation' then need to look into converting the containing
                   'div' to a 'nav' tag (www.w3.org/TR/2010/WD-html5-20100624/sections.html#the-nav-element) when I'm
                   that all browsers support it against the browser requirements of Moodle. */
                $liattributes = array(
                    'role' => 'region',
                    'aria-labelledby' => 'gridsectionname-'.$thissection->section
                ); // NOTE: When implement not show the section title then need an 'aria-label' here with the section title.
                if ($this->courseformat->is_section_current($section)) {
                    $liattributes['class'] = 'currenticon';
                }
                echo html_writer::start_tag('li', $liattributes);

                // Ensure the record exists.
                if (($sectionimages === false) || (!array_key_exists($thissection->id, $sectionimages))) {
                    // Method get_image has 'repair' functionality for when there are issues with the data.
                    $sectionimage = $this->courseformat->get_image($course->id, $thissection->id);
                } else {
                    $sectionimage = $sectionimages[$thissection->id];
                }

                // If the image is set then check that displayedimageindex is greater than 0 otherwise create the displayed image.
                // This is a catch-all for existing courses.
                if (isset($sectionimage->image) && ($sectionimage->displayedimageindex < 1)) {
                    // Set up the displayed image:...
                    $sectionimage->newimage = $sectionimage->image;
                    $sectionimage = $this->courseformat->setup_displayed_image($sectionimage, $contextid,
                        $this->settings);
                }

                $sectionname = $this->courseformat->get_section_name($thissection);
                $sectiontitleattribues = array();
                if ($this->settings['hidesectiontitle'] == 1) {
                    $displaysectionname = $sectionname;
                } else {
                    $displaysectionname = '';
                    $sectiontitleattribues['aria-label'] = $sectionname;
                }
                if ($this->settings['sectiontitlegridlengthmaxoption'] != 0) {
                    $sectionnamelen = core_text::strlen($displaysectionname);
                    if ($sectionnamelen !== false) {
                        if ($sectionnamelen > $this->settings['sectiontitlegridlengthmaxoption']) {
                            $displaysectionname = core_text::substr($displaysectionname, 0, $this->settings['sectiontitlegridlengthmaxoption']).'...';
                        }
                    }
                }
                $sectiontitleclass = 'icon_content';
                if ($this->settings['sectiontitleboxposition'] == 1) {
                    // Only bother if there is a section name to show.
                    $canshow = false;
                    $sectionnamelen = core_text::strlen($displaysectionname);
                    if (($sectionnamelen !== false) && ($sectionnamelen > 0)) {
                        if ($sectionnamelen == 1) {
                            if ($displaysectionname[0] != ' ') {
                                $canshow = true;
                            }
                        } else {
                            $canshow = true;
                        }
                    }
                    if ($canshow) {
                        $sectiontitleclass .= ' content_inside';
                        if ($this->settings['sectiontitleboxinsideposition'] == 2) {
                            $sectiontitleclass .= ' middle';
                        } else if ($this->settings['sectiontitleboxinsideposition'] == 3) {
                            $sectiontitleclass .= ' bottom';
                        }
                    }
                }

                $sectiontitleattribues['id'] = 'gridsectionname-'.$thissection->section;
                $sectiontitleattribues['class'] = $sectiontitleclass;
                if ($this->settings['showsectiontitlesummary'] == 2) {
                    $summary = strip_tags($thissection->summary);
                    if (core_text::strlen($summary) > 0) {
                        $sectiontitleattribues['title'] = strip_tags($thissection->summary);
                        $sectiontitleattribues['data-toggle'] = 'gridtooltip';
                        $sectiontitleattribues['data-placement'] = $this->courseformat->get_set_show_section_title_summary_position();
                    }
                }

                if ($course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
                    echo html_writer::start_tag('a', array(
                        'href' => '#section-'.$thissection->section,
                        'id' => 'gridsection-'.$thissection->section,
                        'class' => 'gridicon_link',
                        'role' => 'link'));

                    echo html_writer::tag('div', $displaysectionname, $sectiontitleattribues);

                    if (($this->settings['newactivity'] == 2) && (isset($sectionupdated[$thissection->id]))) {
                        // The section has been updated since the user last visited this course, add NEW label.
                        echo html_writer::empty_tag('img', array(
                            'class' => 'new_activity',
                            'src' => $urlpicnewactivity,
                            'alt' => ''));
                    }

                    $imageclass = 'image_holder';
                    if ($sectiongreyedout) {
                        $imageclass .= ' inaccessible';
                    }
                    echo html_writer::start_tag('div', array('class' => $imageclass));

                    echo $this->output_section_image($section, $sectionname, $sectionimage, $contextid, $thissection, $gridimagepath);

                    echo html_writer::end_tag('div');
                    echo html_writer::end_tag('a');

                    if ($editing) {
                        $this->make_block_icon_topics_editing($thissection, $contextid, $urlpicedit, $course, $section);
                    }
                    echo html_writer::end_tag('li');
                } else {
                    $content = html_writer::tag('div', $displaysectionname, $sectiontitleattribues);

                    if (($this->settings['newactivity'] == 2) && (isset($sectionupdated[$thissection->id]))) {
                        $content .= html_writer::empty_tag('img', array(
                                    'class' => 'new_activity',
                                    'src' => $urlpicnewactivity,
                                    'alt' => ''));
                    }

                    // Grey out code: Justin 2016/05/14.
                    $imageclass = 'image_holder';
                    if ($sectiongreyedout) {
                        $imageclass .= ' inaccessible';
                    }
                    $content .= html_writer::start_tag('div', array('class' => $imageclass));

                    $content .= $this->output_section_image($section, $sectionname, $sectionimage, $contextid, $thissection, $gridimagepath);

                    $content .= html_writer::end_tag('div');

                    if ($editing) {
                        // Section greyed out by Justin 2016/05/14.
                        if (!$sectiongreyedout) {
                            echo html_writer::link($singlepageurl.'#section-'.$thissection->section, $content, array(
                                'id' => 'gridsection-'.$thissection->section,
                                'class' => 'gridicon_link',
                                'role' => 'link'));
                        } else {
                            // Need an enclosing 'span' for IE.
                            echo html_writer::tag('span', $content);
                        }
                        $this->make_block_icon_topics_editing($thissection, $contextid, $urlpicedit, $course, $section);
                    } else {
                        if (!$sectiongreyedout) {
                            echo html_writer::link($singlepageurl.'&section='.$thissection->section, $content, array(
                                'id' => 'gridsection-'.$thissection->section,
                                'class' => 'gridicon_link',
                                'role' => 'link'));
                        } else {
                            // Need an enclosing 'span' for IE.
                            echo html_writer::tag('span', $content);
                        }
                    }
                    echo html_writer::end_tag('li');
                }
            } else {
                // We now know the value for the grid shade box shown array.
                $this->shadeboxshownarray[$section] = 1;
            }
        }
    }

    private function make_block_icon_topics_editing($thissection, $contextid, $urlpicedit, $course, $section) {
        global $USER;

        $streditimage = get_string('editimage', 'format_grid');
        $streditimagealt = get_string('editimage_alt', 'format_grid');

        echo html_writer::link(
            $this->courseformat->grid_moodle_url('editimage.php', array(
                'sectionid' => $thissection->id,
                'contextid' => $contextid,
                'userid' => $USER->id,
                'role' => 'link',
                'aria-label' => $streditimagealt)
            ),
            html_writer::empty_tag('img', array(
                'src' => $urlpicedit,
                'alt' => $streditimagealt,
                'role' => 'img',
                'aria-label' => $streditimagealt)).'&nbsp;'.$streditimage,
            array('title' => $streditimagealt)
        );

        if ($section == 0) {
            $strdisplaysummary = get_string('display_summary', 'format_grid');
            $strdisplaysummaryalt = get_string('display_summary_alt', 'format_grid');

            echo html_writer::empty_tag('br') . html_writer::link(
                $this->courseformat->grid_moodle_url('mod_summary.php', array(
                    'sesskey' => sesskey(),
                    'course' => $course->id,
                    'showsummary' => 1,
                    'role' => 'link',
                    'aria-label' => $strdisplaysummaryalt)
                ),
                html_writer::empty_tag('img', array(
                    'src' => $this->output->pix_url('out_of_grid', 'format_grid'),
                    'alt' => $strdisplaysummaryalt,
                    'role' => 'img',
                    'aria-label' => $strdisplaysummaryalt)) . '&nbsp;' . $strdisplaysummary,
                    array('title' => $strdisplaysummaryalt)
            );
        }
    }

    /**
     * If currently moving a file then show the current clipboard.
     */
    private function make_block_show_clipboard_if_file_moving($course) {
        global $USER;

        if (is_object($course) && ismoving($course->id)) {
            $strcancel = get_string('cancel');

            $stractivityclipboard = clean_param(format_string(
                            get_string('activityclipboard', '', $USER->activitycopyname)), PARAM_NOTAGS);
            $stractivityclipboard .= '&nbsp;&nbsp;('
                    . html_writer::link(new moodle_url('/mod.php', array(
                        'cancelcopy' => 'true',
                        'sesskey' => sesskey())), $strcancel);

            echo html_writer::tag('li', $stractivityclipboard, array('class' => 'clipboard'));
        }
    }

    /**
     * Makes the list of sections to show.
     */
    private function make_block_topics($course, $sections, $modinfo, $editing, $hascapvishidsect, $streditsummary,
            $urlpicedit, $onsectionpage) {
        $coursecontext = context_course::instance($course->id);
        unset($sections[0]);
        for ($section = 1; $section <= $course->numsections; $section++) {
            $thissection = $modinfo->get_section_info($section);

            if (!$hascapvishidsect && !$thissection->visible && $course->hiddensections) {
                unset($sections[$section]);
                continue;
            }

            $sectionstyle = 'section main';
            if (!$thissection->visible) {
                $sectionstyle .= ' hidden';
            }
            if ($this->courseformat->is_section_current($section)) {
                $sectionstyle .= ' current';
            }
            $sectionstyle .= ' row grid_section hide_section';

            $sectionname = $this->courseformat->get_section_name($thissection);
            if ($editing) {
                $title = $this->section_title($thissection, $course);
            } else {
                $title = $sectionname;
            }
            echo html_writer::start_tag('li', array(
                'id' => 'section-' . $section,
                'class' => $sectionstyle,
                'role' => 'region',
                'aria-label' => $sectionname)
            );

            $class = 'col-md-12';
            if ($editing) {
                // Note, 'left side' is BEFORE content.
                $leftcontent = $this->section_left_content($thissection, $course, $onsectionpage);
                // Note, 'right side' is BEFORE content.
                $rightcontent = $this->section_right_content($thissection, $course, $onsectionpage);
                echo html_writer::tag('div', $leftcontent.$rightcontent, array('class' => 'left right side col-md-1 push-md-11'));
                $class = 'col-md-11 pull-md-1';
            }

            echo html_writer::start_tag('div', array('class' => 'content '.$class));
            if ($hascapvishidsect || ($thissection->visible && $thissection->available)) {
                // If visible.
                echo $this->output->heading($title, 3, 'sectionname');

                echo html_writer::start_tag('div', array('class' => 'summary'));

                echo $this->format_summary_text($thissection);

                if ($editing) {
                    echo html_writer::link(
                            new moodle_url('editsection.php', array('id' => $thissection->id)),
                            html_writer::empty_tag('img', array('src' => $urlpicedit, 'alt' => $streditsummary,
                                'class' => 'iconsmall edit')), array('title' => $streditsummary));
                }
                echo html_writer::end_tag('div');

                echo $this->section_availability_message($thissection, has_capability('moodle/course:viewhiddensections',
                        $coursecontext));

                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            } else {
                echo html_writer::tag('h2', $this->get_title($thissection));
                echo html_writer::tag('p', get_string('hidden_topic', 'format_grid'));

                echo $this->section_availability_message($thissection, has_capability('moodle/course:viewhiddensections',
                        $coursecontext));
            }

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('li');

            unset($sections[$section]);
        }

        if ($editing) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php', array('courseid' => $course->id,
                'increase' => true,
                'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon . get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php', array('courseid' => $course->id,
                    'increase' => false,
                    'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon . get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }
    }

    /**
     * Attempts to return a 40 character title for the section image container.
     * If section names are set, they are used. Otherwise it scans
     * the summary for what looks like the first line.
     */
    private function get_title($section) {
        $title = is_object($section) && isset($section->name) &&
                is_string($section->name) ? trim($section->name) : '';

        if (!empty($title)) {
            // Apply filters and clean tags.
            $title = trim(format_string($section->name, true));
        }

        if (empty($title)) {
            $title = trim(format_text($section->summary));

            // Finds first header content. If it is not found, then try to find the first paragraph.
            foreach (array('h[1-6]', 'p') as $tag) {
                if (preg_match('#<(' . $tag . ')\b[^>]*>(?P<text>.*?)</\1>#si', $title, $m)) {
                    if (!$this->is_empty_text($m['text'])) {
                        $title = $m['text'];
                        break;
                    }
                }
            }
            $title = trim(clean_param($title, PARAM_NOTAGS));
        }

        if (strlen($title) > 40) {
            $title = $this->text_limit($title, 40);
        }

        return $title;
    }

    /**
     * Cuts long texts up to certain length without breaking words.
     */
    private function text_limit($text, $length, $replacer = '...') {
        if (strlen($text) > $length) {
            $text = wordwrap($text, $length, "\n", true);
            $pos = strpos($text, "\n");
            if ($pos === false) {
                $pos = $length;
            }
            $text = trim(substr($text, 0, $pos)) . $replacer;
        }
        return $text;
    }

    /**
     * Checks whether there has been new activity.
     */
    private function new_activity($course) {
        global $CFG, $USER, $DB;

        $sectionsedited = array();
        if (isset($USER->lastcourseaccess[$course->id])) {
            $course->lastaccess = $USER->lastcourseaccess[$course->id];
        } else {
            $course->lastaccess = 0;
        }

        $sql = "SELECT id, section FROM {$CFG->prefix}course_modules " .
                "WHERE course = :courseid AND added > :lastaccess";

        $params = array(
            'courseid' => $course->id,
            'lastaccess' => $course->lastaccess);

        $activity = $DB->get_records_sql($sql, $params);
        foreach ($activity as $record) {
            $sectionsedited[$record->section] = true;
        }

        return $sectionsedited;
    }

}
