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

namespace theme_bloom\output;

use coding_exception;
use html_writer;
use tabobject;
use tabtree;
use custom_menu_item;
use custom_menu;
use block_contents;
use navigation_node;
use action_link;
use stdClass;
use moodle_url;
use preferences_groups;
use action_menu;
use help_icon;
use single_button;
use single_select;
use paging_bar;
use url_select;
use context_course;
use pix_icon;
use context_system;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_bloom
 * @copyright  2012 Bas Brands, www.basbrands.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_renderer extends \core_renderer {

    /** @var custom_menu_item language The language menu if created */
    protected $language = null;

    /**
     * Outputs the opening section of a box.
     *
     * @param string $classes A space-separated list of CSS classes
     * @param string $id An optional ID
     * @param array $attributes An array of other attributes to give the box.
     * @return string the HTML to output.
     */
    // public function box_start($classes = 'generalbox', $id = null, $attributes = array()) {
    //     if (is_array($classes)) {
    //         $classes = implode(' ', $classes);
    //     }
    //     return parent::box_start($classes . ' p-y-1', $id, $attributes);
    // }

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     */
    public function full_header() {
        $html = html_writer::start_tag('header', array('id' => 'page-header', 'class' => 'row'));
        $html .= html_writer::start_div('col-xs-12 p-t-1 p-b-1');
        //$html .= $this->context_header();
        $html .= html_writer::start_div('clearfix', array('id' => 'page-navbar'));
        if(isloggedin()){
            $html .= html_writer::tag('div', $this->navbar(), array('class' => 'breadcrumb-nav'));
            $html .= html_writer::div($this->page_heading_button(), 'breadcrumb-button');
            $html .= html_writer::link('#', 'Full screen', array('class' => 'hide-nav btn btn-secondary'));
            $html .= html_writer::end_div();
        }
        $html .= html_writer::tag('div', $this->course_header(), array('id' => 'course-header'));
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('header');
        return $html;
    }

    /**
     * The standard tags that should be included in the <head> tag
     * including a meta description for the front page
     *
     * @return string HTML fragment.
     */
    public function standard_head_html() {
        global $SITE, $PAGE;

        $output = parent::standard_head_html();
        if ($PAGE->pagelayout == 'frontpage') {
            $summary = s(strip_tags(format_text($SITE->summary, FORMAT_HTML)));
            if (!empty($summary)) {
                $output .= "<meta name=\"description\" content=\"$summary\" />\n";
            }
        }

        return $output;
    }

    /*
     * This renders the navbar.
     * Uses bootstrap compatible html.
     */
    public function navbar() {
        return $this->render_from_template('core/navbar', $this->page->navbar);
    }

    /**
     * We don't like these...
     *
     */
    public function edit_button(moodle_url $url) {
        return '';
    }

    /**
     * Override to inject the logo.
     *
     * @param array $headerinfo The header info.
     * @param int $headinglevel What level the 'h' tag will be.
     * @return string HTML for the header bar.
     */
    public function context_header($headerinfo = null, $headinglevel = 1) {
        global $SITE;

        if ($this->should_display_main_logo($headinglevel)) {
            $sitename = format_string($SITE->fullname, true, array('context' => context_course::instance(SITEID)));
            return html_writer::div(html_writer::empty_tag('img', [
                'src' => $this->get_logo_url(null, 75), 'alt' => $sitename]), 'logo');
        }

        return parent::context_header($headerinfo, $headinglevel);
    }

    /**
     * Get the compact logo URL.
     *
     * @return string
     */
    public function get_compact_logo_url($maxwidth = 100, $maxheight = 100) {
        return parent::get_compact_logo_url(null, 35);
    }

    /**
     * Whether we should display the main logo.
     *
     * @return bool
     */
    public function should_display_main_logo($headinglevel = 1) {
        global $PAGE;

        // Only render the logo if we're on the front page or login page and the we have a logo.
        $logo = $this->get_logo_url();
        if ($headinglevel == 1 && !empty($logo)) {
            if ($PAGE->pagelayout == 'frontpage' || $PAGE->pagelayout == 'login') {
                return true;
            }
        }

        return false;
    }
    /**
     * Whether we should display the logo in the navbar.
     *
     * We will when there are no main logos, and we have compact logo.
     *
     * @return bool
     */
    public function should_display_navbar_logo() {
        $logo = $this->get_compact_logo_url();
        return !empty($logo) && !$this->should_display_main_logo();
    }

    /*
     * Overriding the custom_menu function ensures the custom menu is
     * always shown, even if no menu items are configured in the global
     * theme settings page.

        shows dashboad and calendar links when they are set true
        shows custom links in the setting
     */
     public function custom_menu($custommenuitems = '') {
        global $CFG;
        $path = $CFG->wwwroot;
        $optional = " ";

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }

        if(isloggedin()){
            if (isset($this->page->theme->settings->dashboard) && !empty($this->page->theme->settings->dashboard)){
                $optional .= get_string('header_dashboard', 'theme_bloom')."|" .$path ."/my" ."\r\n";
            }
            if (isset($this->page->theme->settings->calendar) && !empty($this->page->theme->settings->calendar)){
                $optional .= get_string('header_calendar', 'theme_bloom') ."|" .$path ."/calendar/view.php?view=month" ."\r\n";
            }
            if (isset($this->page->theme->settings->mycourse) && !empty($this->page->theme->settings->mycourse)){

                $courses = enrol_get_my_courses();
                if(!empty($courses)){
                    $optional .=  get_string('header_mycourse', 'theme_bloom') ."|" ."\r\n";
                    foreach ($courses as $key => $course) {
                        $coursefullname = format_string(get_course_display_name_for_list($course), true, $course->id);
                        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
                        $optional .=  "-" . $coursefullname . "|" . $courseurl ."\r\n" ;
                    }
                    $optional .= "\r\n";
                }
            }
            $custommenuitems = $optional .$custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }

    /**
     * We want to show the custom menus as a list of links in the footer on small screens.
     * Just return the menu object exported so we can render it differently.
     */
    public function custom_menu_flat() {
        global $CFG;
        $custommenuitems = '';

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) {
            $custommenuitems = $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $custommenu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        return $custommenu->export_for_template($this);
    }


    /*
     * This renders the bootstrap top menu.
     *
     * This renderer is needed to enable the Bootstrap style navigation.
     */
    protected function render_custom_menu(custom_menu $menu) {
        global $CFG;

        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if (!$menu->has_children() && !$haslangmenu) {
            return '';
        }

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $menu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('core/custom_menu_item', $context);
        }

        return $content;
    }

    /**
     * This code renders the navbar button to control the display of the custom menu
     * on smaller screens.
     *
     * Do not display the button if the menu is empty.
     *
     * @return string HTML fragment
     */
    public function navbar_button() {
        global $CFG;

        if (empty($CFG->custommenuitems) && $this->lang_menu() == '') {
            return '';
        }

        $iconbar = html_writer::tag('span', '', array('class' => 'icon-bar'));
        $button = html_writer::tag('a', $iconbar . "\n" . $iconbar. "\n" . $iconbar, array(
            'class'       => 'btn btn-navbar',
            'data-toggle' => 'collapse',
            'data-target' => '.nav-collapse'
        ));
        return $button;
    }

    /**
     * Renders tabtree
     *
     * @param tabtree $tabtree
     * @return string
     */
    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }
        $data = $tabtree->export_for_template($this);
        return $this->render_from_template('core/tabtree', $data);
    }

    /**
     * Renders tabobject (part of tabtree)
     *
     * This function is called from {@link core_renderer::render_tabtree()}
     * and also it calls itself when printing the $tabobject subtree recursively.
     *
     * @param tabobject $tabobject
     * @return string HTML fragment
     */
    protected function render_tabobject(tabobject $tab) {
        throw new coding_exception('Tab objects should not be directly rendered.');
    }

    /**
     * Prints a nice side block with an optional header.
     *
     * @param block_contents $bc HTML for the content
     * @param string $region the region the block is appearing in.
     * @return string the HTML to be output.
     */
    public function block(block_contents $bc, $region) {
        $bc = clone($bc); // Avoid messing up the object passed in.
        if (empty($bc->blockinstanceid) || !strip_tags($bc->title)) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        }

        $id = !empty($bc->attributes['id']) ? $bc->attributes['id'] : uniqid('block-');
        $context = new stdClass();
        $context->skipid = $bc->skipid;
        $context->blockinstanceid = $bc->blockinstanceid;
        $context->dockable = $bc->dockable;
        $context->id = $id;
        $context->hidden = $bc->collapsible == block_contents::HIDDEN;
        $context->skiptitle = strip_tags($bc->title);
        $context->showskiplink = !empty($context->skiptitle);
        $context->arialabel = $bc->arialabel;
        $context->ariarole = !empty($bc->attributes['role']) ? $bc->attributes['role'] : 'complementary';
        $context->type = $bc->attributes['data-block'];
        $context->title = $bc->title;
        $context->content = $bc->content;
        $context->annotation = $bc->annotation;
        $context->footer = $bc->footer;
        $context->hascontrols = !empty($bc->controls);
        if ($context->hascontrols) {
            $context->controls = $this->block_controls($bc->controls, $id);
        }

        return $this->render_from_template('core/block', $context);
    }

    /**
     * Returns the CSS classes to apply to the body tag.
     *
     * @since Moodle 2.5.1 2.6
     * @param array $additionalclasses Any additional classes to apply.
     * @return string
     */
    public function body_css_classes(array $additionalclasses = array()) {
        return $this->page->bodyclasses . ' ' . implode(' ', $additionalclasses);
    }

    /**
     * Renders preferences groups.
     *
     * @param  preferences_groups $renderable The renderable
     * @return string The output.
     */
    public function render_preferences_groups(preferences_groups $renderable) {
        return $this->render_from_template('core/preferences_groups', $renderable);
    }


    /**
     * Implementation of user image rendering.
     *
     * @param help_icon $helpicon A help icon instance
     * @return string HTML fragment
     */
    protected function render_help_icon(help_icon $helpicon) {
        $context = $helpicon->export_for_template($this);
        return $this->render_from_template('core/help_icon', $context);
    }

    /**
     * Renders a single button widget.
     *
     * This will return HTML to display a form containing a single button.
     *
     * @param single_button $button
     * @return string HTML fragment
     */
    protected function render_single_button(single_button $button) {
        return $this->render_from_template('core/single_button', $button->export_for_template($this));
    }

    /**
     * Renders a single select.
     *
     * @param single_select $select The object.
     * @return string HTML
     */
    protected function render_single_select(single_select $select) {
        return $this->render_from_template('core/single_select', $select->export_for_template($this));
    }

    /**
     * Renders a paging bar.
     *
     * @param paging_bar $pagingbar The object.
     * @return string HTML
     */
    protected function render_paging_bar(paging_bar $pagingbar) {
        // Any more than 10 is not usable and causes wierd wrapping of the pagination in this theme.
        $pagingbar->maxdisplay = 10;
        return $this->render_from_template('core/paging_bar', $pagingbar->export_for_template($this));
    }

    /**
     * Renders a url select.
     *
     * @param url_select $select The object.
     * @return string HTML
     */
    protected function render_url_select(url_select $select) {
        return $this->render_from_template('core/url_select', $select->export_for_template($this));
    }

    /**
     * Renders a pix_icon widget and returns the HTML to display it.
     *
     * @param pix_icon $icon
     * @return string HTML fragment
     */
    protected function render_pix_icon(pix_icon $icon) {
        $data = $icon->export_for_template($this);
        foreach ($data['attributes'] as $key => $item) {
            $name = $item['name'];
            $value = $item['value'];
            if ($name == 'class') {
                $data['extraclasses'] = $value;
                unset($data['attributes'][$key]);
                $data['attributes'] = array_values($data['attributes']);
                break;
            }
        }
        return $this->render_from_template('core/pix_icon', $data);
    }

    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $CFG, $PAGE, $SITE;

        $context = $form->export_for_template($this);

        // Override because rendering is not supported in template yet.
        $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_main_url();
        if ($url == "") {
            $url = $CFG->wwwroot .'/theme/'.  $PAGE->theme->name .'/pix/logo-main-default.png';
        }
        $context->logourl = $url;
        $context->sitename = format_string($SITE->fullname, true, array('context' => context_course::instance(SITEID)));

        return $this->render_from_template('core/login', $context);
    }

    /**
     * Render the login signup form into a nice template for the theme.
     *
     * @param mform $form
     * @return string
     */
    public function render_login_signup_form($form) {
        global $SITE, $CFG, $PAGE;

        $context = $form->export_for_template($this);
        $url = $this->get_logo_main_url();
        if ($url =="") {
            $url = $CFG->wwwroot .'/theme/'.  $PAGE->theme->name .'/pix/logo-main-default.png';
        }
        $context['logourl'] = $url;
        $context['sitename'] = format_string($SITE->fullname, true, array('context' => context_course::instance(SITEID)));

        return $this->render_from_template('core/signup_form_layout', $context);
    }

    /**
     * This is an optional menu that can be added to a layout by a theme. It contains the
     * menu for the course administration, only on the course main page.
     *
     * @return string
     */
    public function context_header_settings_menu() {
        $context = $this->page->context;
        $menu = new action_menu();
        if ($context->contextlevel == CONTEXT_COURSE) {
            // Get the course admin node from the settings navigation.
            $items = $this->page->navbar->get_items();
            $node = end($items);
            $settingsnode = false;
            if (!empty($node) && $node->key === 'home') {
                $settingsnode = $this->page->settingsnav->find('frontpage', navigation_node::TYPE_SETTING);
                if ($settingsnode) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                    // We only add a list to the full settings menu if we didn't include every node in the short menu.
                    if ($skipped) {
                        $text = get_string('morenavigationlinks');
                        $url = new moodle_url('/course/admin.php', array('courseid' => $this->page->course->id));
                        $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                        $menu->add_secondary_action($link);
                    }
                }
            } else if (!empty($node) &&
                ($node->type == navigation_node::TYPE_COURSE || $node->type == navigation_node::TYPE_SECTION)) {
                $settingsnode = $this->page->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
                if ($settingsnode) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                    // We only add a list to the full settings menu if we didn't include every node in the short menu.
                    if ($skipped) {
                        $text = get_string('morenavigationlinks');
                        $url = new moodle_url('/course/admin.php', array('courseid' => $this->page->course->id));
                        $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                        $menu->add_secondary_action($link);
                    }
                }
            }
        } else if ($context->contextlevel == CONTEXT_USER) {
            $items = $this->page->navbar->get_items();
            $node = end($items);
            if (!empty($node) && ($node->key === 'myprofile')) {
                // Get the course admin node from the settings navigation.
                $node = $this->page->settingsnav->find('useraccount', navigation_node::TYPE_CONTAINER);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }
            }
        }
        return $this->render($menu);
    }

    /**
     * This is an optional menu that can be added to a layout by a theme. It contains the
     * menu for the most specific thing from the settings block. E.g. Module administration.
     *
     * @return string
     */
    public function region_main_settings_menu() {
        $context = $this->page->context;
        $menu = new action_menu();

        if ($context->contextlevel == CONTEXT_MODULE) {

            $this->page->navigation->initialise();
            $node = $this->page->navigation->find_active_node();
            $buildmenu = false;
            // If the settings menu has been forced then show the menu.
            if ($this->page->is_settings_menu_forced()) {
                $buildmenu = true;
            } else if (!empty($node) && ($node->type == navigation_node::TYPE_ACTIVITY ||
                    $node->type == navigation_node::TYPE_RESOURCE)) {

                $items = $this->page->navbar->get_items();
                $navbarnode = end($items);
                // We only want to show the menu on the first page of the activity. This means
                // the breadcrumb has no additional nodes.
                if ($navbarnode && ($navbarnode->key == $node->key && $navbarnode->type == $node->type)) {
                    $buildmenu = true;
                }
            }
            if ($buildmenu) {
                // Get the course admin node from the settings navigation.
                $node = $this->page->settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }
            }
        } else {
            $items = $this->page->navbar->get_items();
            $navbarnode = end($items);

            if ($navbarnode && ($navbarnode->key == 'participants')) {
                $node = $this->page->settingsnav->find('users', navigation_node::TYPE_CONTAINER);
                if ($node) {
                    // Build an action menu based on the visible nodes from this navigation tree.
                    $this->build_action_menu_from_navigation($menu, $node);
                }

            }
        }
        return $this->render($menu);
    }

    /**
     * Take a node in the nav tree and make an action menu out of it.
     * The links are injected in the action menu.
     *
     * @param action_menu $menu
     * @param navigation_node $node
     * @param boolean $indent
     * @param boolean $onlytopleafnodes
     * @return boolean nodesskipped - True if nodes were skipped in building the menu
     */
    private function build_action_menu_from_navigation(action_menu $menu,
                                                       navigation_node $node,
                                                       $indent = false,
                                                       $onlytopleafnodes = false) {
        $skipped = false;
        // Build an action menu based on the visible nodes from this navigation tree.
        foreach ($node->children as $menuitem) {
            if ($menuitem->display) {
                if ($onlytopleafnodes && $menuitem->children->count()) {
                    $skipped = true;
                    continue;
                }
                if ($menuitem->action) {
                    $text = $menuitem->text;
                    if ($menuitem->action instanceof action_link) {
                        $link = $menuitem->action;
                    } else {
                        $link = new action_link($menuitem->action, $menuitem->text, null, null, $menuitem->icon);
                    }
                    if ($indent) {
                        $link->add_class('m-l-1');
                    }
                } else {
                    if ($onlytopleafnodes) {
                        $skipped = true;
                        continue;
                    }
                    $link = $menuitem->text;
                }
                $menu->add_secondary_action($link);
                $skipped = $skipped || $this->build_action_menu_from_navigation($menu, $menuitem, true);
            }
        }
        return $skipped;
    }

    /**
     * Return the site's compact logo URL, if any.
     *
     * @param int $maxwidth The maximum width, or null when the maximum width does not matter.
     * @param int $maxheight The maximum height, or null when the maximum height does not matter.
     * @return moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 100) {
        global $CFG, $PAGE;
        $logo = $this->page->theme->setting_file_url('logo', 'logo');

        if($logo == ""){
            $logo = $CFG->wwwroot .'/theme/'.  $PAGE->theme->name .'/pix/logo-default.svg';
        }

        return $logo;
    }
    
    /**
     * Return the site's compact logo URL, if any.
     *
     * @param int $maxwidth The maximum width, or null when the maximum width does not matter.
     * @param int $maxheight The maximum height, or null when the maximum height does not matter.
     * @return moodle_url|false
     */
    public function get_logocompact_url($maxwidth = null, $maxheight = 100) {
        global $CFG, $PAGE;
        $logo = $this->page->theme->setting_file_url('logocompact', 'logocompact');

        if($logo == ""){
            $logo = $CFG->wwwroot .'/theme/'.  $PAGE->theme->name .'/pix/logo-sml-default.svg';
        }

        return $logo;
    }

     /**
     * Return the site's compact logo URL, if any.
     *
     * @param int $maxwidth The maximum width, or null when the maximum width does not matter.
     * @param int $maxheight The maximum height, or null when the maximum height does not matter.
     * @return moodle_url|false
     */
    public function get_logo_main_url($maxwidth = null, $maxheight = 100) {
        $logo = $this->page->theme->setting_file_url('logomain', 'logomain');

        return $logo;
    }

    /**
     * logout button for the drawer
     *
     */
    public function logout_url(){
        global $CFG;
        $url = $CFG->wwwroot ."/login/logout.php?sesskey=" . sesskey();
        $logout = "";
        if(isloggedin()) {
            $logout .= "<a href=" . $url . " class='nav-item nav-link btn-logout'>Logout</a>";
        }
        return $logout;
    }

    /**
     * Return the search form for navbar
     *
     * not used any more because I've combined with global search below
     */
//    public function show_course_search_form() {
//        global $PAGE;
//        $search = '';
//
//        if(isloggedin()){
//            $renderer = $PAGE->get_renderer('core', 'course');
//            $search = $renderer->course_search_form('', 'navbar');
//        }
//
//        return $search;
//    }

    /**
     * Returns a search box.
     *
     * @param  string $id     The search box wrapper div id, defaults to an autogenerated one.
     * @return string         HTML with the search form hidden by default.
     */
    public function search_box($id = false) {
        global $CFG;

        if ($id == false) {
            $id = uniqid();
        } else {
            // Needs to be cleaned, we use it for the input id.
            $id = clean_param($id, PARAM_ALPHANUMEXT);
        }

        // I don't like the core js, I've written my own : Kaori
       // $this->page->requires->js_call_amd('core/search-input', 'init', array($id));

        $searchicon = html_writer::tag('div', $this->pix_icon('a/search', get_string('search', 'search'), 'moodle'),
            array('role' => 'button', 'tabindex' => 0));

        $placeholder = get_string('course_search', 'theme_bloom');

        if(empty($CFG->enableglobalsearch) || !has_capability('moodle/search:query', context_system::instance())) {
            $formattrs = array('class' => 'search-input-form', 'action' => $CFG->wwwroot . '/course/search.php');
            $search = 'search';
        }else{
            $formattrs = array('class' => 'search-input-form', 'action' => $CFG->wwwroot . '/search/index.php');
            $search = 'q';
            $placeholder = get_string('global_search', 'theme_bloom');
        }

        $inputattrs = array('type' => 'text', 'name' => $search , 'placeholder' => $placeholder,
            'size' => 13, 'tabindex' => -1, 'id' => 'id_q_' . $id);

        $contents = html_writer::tag('label', get_string('enteryoursearchquery', 'search'),
                array('for' => 'id_q_' . $id, 'class' => 'accesshide')) . html_writer::tag('input', '', $inputattrs);
        $searchinput = html_writer::tag('form', $contents, $formattrs);

        return html_writer::tag('div', $searchicon . $searchinput, array('class' => 'search-input-wrapper-custom', 'id' => $id));
    }

     /**
     * favicon link
     *
     */
    public function get_favicon(){
        global $CFG, $PAGE;
        $favicon = $this->page->theme->setting_file_url('favicon', 'favicon');

        if($favicon == ""){
            $favicon = $CFG->wwwroot .'/theme/'.  $PAGE->theme->name .'/pix/favicon.ico';
        }

        return $favicon;
    }

     /**
     * It's a quick fix to show the course title but it should be re-written for all the page titles
     *
     */
    public function show_course_title(){
        $html = $this->context_header(); 
        return $html;
    }

    /***
        Show cog for admin
    ***/
    public function show_cog(){
        global $CFG;
        $html ='';

        if (!(!has_capability('moodle/site:config', context_system::instance()) || isguestuser())) {
            $path = $CFG->wwwroot  . "/admin/search.php";

            $icon =  html_writer::tag('div', $this->pix_icon('t/gotoadmin', get_string('goto_admin', 'theme_bloom'), 'moodle'),
                array('role' => 'button'));

            $cog =  html_writer::link($path, $icon);

            $html .= html_writer::tag('div', $cog, array('class'=>'cog-container'));
        }
        
        return $html;
    }


    public function show_footnotes() {
        global $CFG;

        $content = "";
       
        if (!empty($this->page->theme->settings->footnote)) { 
            $content = $this->page->theme->settings->footnote;
        }

        return $content;
    }

    public function show_socialmedialinks(){
        
        $socialmedia = "";
        $content = "";
        $socialmedia = "";
        
        if(!empty($this->page->theme->settings->facebook)){
            $facebook = $this->page->theme->settings->facebook;
            $socialmedia .= "<li><a href='" .$facebook . "' title='" .get_string('facebook', 'theme_bloom') ."' target='_blank'><i class='fa fa-facebook' aria-hidden='true'></i>"  . "</a></li>";
        }       
        if(!empty($this->page->theme->settings->twitter)){
            $twitter = $this->page->theme->settings->twitter;
            $socialmedia .= "<li><a href='" .$twitter . "' title='" .get_string('twitter', 'theme_bloom') ."' target='_blank'><i class='fa fa-twitter' aria-hidden='true'></i>"  . "</a></li>";
        }  
        if(!empty($this->page->theme->settings->linkedin)){
            $linkedin = $this->page->theme->settings->linkedin;
            $socialmedia .= "<li><a href='" .$linkedin . "' title='" .get_string('linkedin', 'theme_bloom') ."' target='_blank'><i class='fa fa-linkedin' aria-hidden='true'></i>"  . "</a></li>";
        }  
        if(!empty($this->page->theme->settings->youtube)){
            $youtube = $this->page->theme->settings->youtube;
            $socialmedia .= "<li><a href='" .$youtube . "' title='" .get_string('youtube', 'theme_bloom') ."' target='_blank'><i class='fa fa-youtube' aria-hidden='true'></i>"  . "</a></li>";
        }  
        if(!empty($this->page->theme->settings->google)){
            $google = $this->page->theme->settings->google;
            $socialmedia .= "<li><a href='" .$google . "' title='" .get_string('google', 'theme_bloom') ."' target='_blank'><i class='fa fa-google-plus' aria-hidden='true'></i>"  . "</a></li>";
        }  
        if(!empty($this->page->theme->settings->flickr)){
            $flickr = $this->page->theme->settings->flickr;
            $socialmedia .= "<li><a href='" .$flickr . "' title='" .get_string('flickr', 'theme_bloom') ."' target='_blank'><i class='fa fa-flickr' aria-hidden='true'></i>"  . "</a></li>";
        }  
        if(!empty($this->page->theme->settings->pinterest)){
            $pinterest = $this->page->theme->settings->pinterest;
            $socialmedia .= "<li><a href='" .$pinterest . "' title='" .get_string('pinterest', 'theme_bloom') ."' target='_blank'><i class='fa fa-pinterest' aria-hidden='true'></i>"  . "</a></li>";
        }  
        if(!empty($this->page->theme->settings->instagram)){
            $instagram = $this->page->theme->settings->instagram;
            $socialmedia .= "<li><a href='" .$instagram . "' title='" .get_string('instagram', 'theme_bloom') ."' target='_blank'><i class='fa fa-instagram' aria-hidden='true'></i>"  . "</a></li>";
        }  
        if(!empty($this->page->theme->settings->soundcloud)){
            $soundcloud = $this->page->theme->settings->soundcloud;
            $socialmedia .= "<li><a href='" .$soundcloud . "' title='" .get_string('soundcloud', 'theme_bloom') ."' target='_blank'><i class='fa fa-soundcloud' aria-hidden='true'></i>"  . "</a></li>";
        }  
        
        if($socialmedia != ""){
            $content .= "<h4 class='card-title'>".get_string('socialmediatitle', 'theme_bloom')."</h4><div class='card-text'><ul class='socialmedia'>" . $socialmedia ."</ul></div>";
        }

        return $content;
    }

     public function footer_links() {
        global $CFG;
        $path = $CFG->wwwroot;
        $custommenuitems = '';       
           
        if (isset($this->page->theme->settings->footerlinks) && !empty($this->page->theme->settings->footerlinks)){
            $custommenuitems .= $this->page->theme->settings->footerlinks ."\r\n";
        }
       
        $custommenu = new custom_menu($custommenuitems, current_language());
        $langs = get_string_manager()->get_list_of_translations();
        $haslangmenu = $this->lang_menu() != '';

        if ($haslangmenu) {
            $strlang = get_string('language');
            $currentlang = current_language();
            if (isset($langs[$currentlang])) {
                $currentlang = $langs[$currentlang];
            } else {
                $currentlang = $strlang;
            }
            $this->language = $custommenu->add($currentlang, new moodle_url('#'), $strlang, 10000);
            foreach ($langs as $langtype => $langname) {
                $this->language->add($langname, new moodle_url($this->page->url, array('lang' => $langtype)), $langname);
            }
        }

        return $custommenu->export_for_template($this);
    }

    //for login page
    //there was no cancel button
    public function go_back_home(){
        global $PAGE, $CFG;
        $path = $CFG->wwwroot;
        $content = "";

        if($PAGE->bodyid == "page-login-forgot_password"){
            $content .= "<a class='btn btn-secondary' href='" .$path ."'>Cancel</a>";
        }

        return $content;
    }
}
