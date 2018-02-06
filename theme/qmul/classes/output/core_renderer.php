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

defined('MOODLE_INTERNAL') || die();

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_qmul
 * @copyright  2016 Andrew Davidson, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class theme_qmul_core_renderer extends core_renderer {

    public function mform_element($element, $required, $advanced, $error, $ingroup) {
        $templatename = 'core_form/element-' . $element->getType();
        if ($ingroup) {
            $templatename .= "-inline";
        }
        try {
            // We call this to generate a file not found exception if there is no template.
            // We don't want to call export_for_template if there is no template.
            core\output\mustache_template_finder::get_template_filepath($templatename);

            if ($element instanceof templatable) {
                $elementcontext = $element->export_for_template($this);

                $helpbutton = '';
                if (method_exists($element, 'getHelpButton')) {
                    $helpbutton = $element->getHelpButton();
                }
                $label = $element->getLabel();
                $text = '';
                if (method_exists($element, 'getText')) {
                    // There currently exists code that adds a form element with an empty label.
                    // If this is the case then set the label to the description.
                    if (empty($label)) {
                        $label = $element->getText();
                    } else {
                        $text = $element->getText();
                    }
                }

                // Fix checkboxes with embedded help icons
                if ($templatename == 'core_form/element-checkbox-inline') {
                    $dom = new DOMDocument();
                    $dom->loadHTML($label);

                    $xpath = new DOMXpath($dom);
                    $result = $xpath->query('//span[@class="helpbutton"]');

                    if ($result->length > 0) {
                        $node = $result->item(0);
                        $helpbutton = $node->ownerDocument->saveHTML($node);
                        $node->parentNode->removeChild($node);
                        $label = strip_tags($dom->saveHTML($dom));
                    }
                }

                $context = array(
                    'element' => $elementcontext,
                    'label' => $label,
                    'text' => $text,
                    'required' => $required,
                    'advanced' => $advanced,
                    'helpbutton' => $helpbutton,
                    'error' => $error
                );
                return $this->render_from_template($templatename, $context);
            }
        } catch (Exception $e) {
            // No template for this element.
            return false;
        }
    }

    public function notification($message, $classes = 'notifyproblem') {
        $message = clean_text($message);

        if ($classes == 'notifyproblem') {
            return html_writer::div($message, 'alert alert-danger');
        }
        if ($classes == 'notifywarning') {
            return html_writer::div($message, 'alert alert-warning');
        }
        if ($classes == 'notifysuccess') {
            return html_writer::div($message, 'alert alert-success');
        }
        if ($classes == 'notifymessage') {
            return html_writer::div($message, 'alert alert-info');
        }
        if ($classes == 'redirectmessage') {
            return html_writer::div($message, 'alert alert-block alert-info');
        }
        if ($classes == 'notifytiny') {
            // Not an appropriate semantic alert class!
            return $this->debug_listing($message);
        }
        return html_writer::div($message, $classes);
    }

    public function box_start($classes = 'generalbox', $id = null, $attributes = array()) {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }
        return parent::box_start($classes . ' py-1', $id, $attributes);
    }

    public function qmul_modules() {
        global $CFG, $USER, $SESSION;
        require_once($CFG->libdir.'/coursecatlib.php');

        $cache = cache::make('theme_qmul', 'backpackcourses');
        $courses = $cache->get($USER->id);
        if ($courses !== false || isset($SESSION->justloggedin)) {
            if ($courses->pinnedmodules != false) {
                $courses->pinnedmodules = array_values($courses->pinnedmodules);
            }
            if ($courses->allmodules != false) {
                $courses->allmodules = array_values($courses->allmodules);
            }
            if ($courses->mymodules != false) {
                $courses->mymodules = array_values($courses->mymodules);
            }
            if (count($courses->allmodules) > count($courses->mymodules)) {
                $courses->moremodules = true;
            }
            $courses->userid = $USER->id;
            $courses->allmoduleslink = new moodle_url('/course/index.php');
            $courses->currentloaded = count($courses->mymodules);
            return $this->render_from_template('theme_qmul/backpack_modules', $courses);
        }

        $content = '';

        $limit = $this->page->theme->settings->mymoduleslimit;

        $preferences = get_user_preferences();
        $allmodules = theme_qmul_get_my_modules(NULL, 'lastaccess DESC, fullname ASC');

        $mymodules = array_slice($allmodules, 0, $limit);

        $overviews = array();
        if ($mods = get_plugin_list_with_function('mod', 'print_overview')) {
            if (defined('MAX_MODINFO_CACHE_SIZE') && MAX_MODINFO_CACHE_SIZE > 0 && count($mymodules) > MAX_MODINFO_CACHE_SIZE) {
                $batches = array_chunk($mymodules, MAX_MODINFO_CACHE_SIZE, true);
            } else {
                $batches = array($mymodules);
            }
            foreach ($batches as $courses) {
                foreach ($mods as $fname) {
                    $fname($courses, $overviews);
                }
            }
        }

        $output = new stdClass();
        $output->pinnedmodules = array();
        $output->allmodules = array();
        $output->mymodules = array();

        foreach ($mymodules as $key => $course) {
            $output->mymodules[$course->id] = $course;
        }

        foreach ($allmodules as $key => $course) {
            $context = context_course::instance($course->id);
            $hidden = empty($course->visible);
            $editable = has_capability('moodle/course:update', $context);
            $pref = "theme_qmul_pincourse_{$course->id}";
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
                    $course->warning = $this->pix_icon('i/warning', '', '', array('class'=>'iconlarge'));
                    $course->warning .= get_string('hidden_course_teacher', 'theme_qmul');
                } else {
                    $course->warning = $this->pix_icon('i/info', '', '', array('class'=>'iconlarge'));
                    $course->warning .= get_string('hidden_course_student', 'theme_qmul');
                }
            }
            $course->userid = $USER->id;
            if (isset($overviews[$course->id])) {
                $course->overview = true;
                $overview = $overviews[$course->id];
                $modoverviews = array();
                foreach (array_keys($overview) as $module) {
                    $modinfo = new stdClass();
                    $url = new moodle_url("/mod/$module/index.php", array('id' => $course->id));
                    $modulename = get_string('modulename', $module);
                    $modinfo->icontext = html_writer::link($url, $this->pix_icon('icon', $modulename, 'mod_'.$module, array('class'=>'iconlarge')));
                    if (get_string_manager()->string_exists("activityoverview", $module)) {
                        $modinfo->icontext .= get_string("activityoverview", $module);
                    } else {
                        $modinfo->icontext .= get_string("activityoverview", 'block_course_overview', $modulename);
                    }
                    $modoverviews[] = $modinfo;
                }
                $course->overviews = $modoverviews;
            }
            if (isset($preferences[$pref]) && $preferences[$pref] == 1) {
                $course->pinned = true;
                $output->pinnedmodules[$course->id] = $course;
            }
            $output->allmodules[$course->id] = $course;
        }

        if (empty($output->pinnedmodules)) {
            $output->pinnedmodules = false;
        }
        if (empty($output->allmodules)) {
            $output->allmodules = false;
        }
        if (empty($output->mymodules)) {
            $output->mymodules = false;
        }
        if ($output === false) {
            $output = null;
        }
        $cache->set($USER->id, $output);

        if (!empty($output->pinnedmodules)) {
            $output->pinnedmodules = array_values($output->pinnedmodules);
        }
        if (!empty($output->allmodules)) {
            $output->allmodules = array_values($output->allmodules);
        }
        if (!empty($output->mymodules)) {
            $output->mymodules = array_values($output->mymodules);
        }

        if (count($output->allmodules) > count($output->mymodules)) {
            $output->moremodules = true;
        }
        $output->userid = $USER->id;
        $output->currentloaded = $limit;

        $output->allmoduleslink = new moodle_url('/course/index.php');

        return $this->render_from_template('theme_qmul/backpack_modules', $output);
    }

    public function render_news_ticker() {
        global $CFG, $COURSE, $PAGE, $USER, $DB;
        require_once($CFG->libdir.'/simplepie/moodle_simplepie.php');
        if (!isset($PAGE->theme->settings->enablesitenews)) {
            return '';
        }
        if ($PAGE->theme->settings->enablesitenews == false) {
            return '';
        }
        $field = $DB->get_record('user_info_field', array('shortname'=>'landingpage'));
        $list = empty($field->param1) ? [] : explode("\n", $field->param1);
        $facultylanding = array();
        foreach ($list as $faculty) {
            $faculty = str_replace('-', '_', trim($faculty));
            $varname = $faculty.'_landingid';
            if (isset($PAGE->theme->settings->$varname)) {
                $facultylanding[] = $PAGE->theme->settings->$varname;
            }
        }
        $sql = "SELECT ud.data FROM {user_info_data} ud
                JOIN {user_info_field} uf ON ud.fieldid = uf.id
                WHERE uf.shortname = :landingpage AND ud.userid = :uid";
        $userfaculty = $DB->get_field_sql($sql, ['landingpage' => 'landingpage', 'uid' => $USER->id]);
        $content = '';
        if ($PAGE->pagelayout == 'frontpage' ||
            $PAGE->pagelayout == 'mydashboard' ||
            in_array($COURSE->id, $facultylanding)) {
            $sitenewsfeed = $PAGE->theme->settings->sitenewsrssfeed;
            $sitenewsfeed = new moodle_simplepie($sitenewsfeed);
            $siteitems = $sitenewsfeed->get_items(0, 2);
            $itsservicefeed = $PAGE->theme->settings->itsservicerssfeed;
            $itsservicefeed = new moodle_simplepie($itsservicefeed);
            $itsitems = $itsservicefeed->get_items(0, 2);
            $field = $DB->get_record('user_info_field', array('shortname'=>'landingpage'));
            $content .= html_writer::start_tag('div', array('class'=>'news-ticker-container'));
            $content .= '<style>.news-ticker:before {content: "'.get_string('news', 'theme_qmul').'";</style>';
            $content .= html_writer::start_tag('div', array('class'=>'news-ticker'));
                $content .= html_writer::start_tag('div', array('class'=>'inner-wrap'));
                foreach ($siteitems as $item) {
                    $content .= $this->print_rss_item($item);
                }
                foreach ($itsitems as $item) {
                    $content .= $this->print_rss_item($item);
                }
                if (!empty($userfaculty)) {
                    $userfaculty = str_replace('-', '_', trim($userfaculty));
                    $varname = $userfaculty.'_rssfeed';
                    if (isset($PAGE->theme->settings->$varname)) {
                        $facultyfeed = $PAGE->theme->settings->$varname;
                        $facultyfeed = new moodle_simplepie($facultyfeed);
                        $facultyitems = $facultyfeed->get_items(0, 2);
                        foreach ($facultyitems as $item) {
                            $content .= $this->print_rss_item($item);
                        }
                    }
                }
                $content .= html_writer::end_tag('div');
            $content .= html_writer::end_tag('div');
            $controls = html_writer::tag('div', '', array('class'=>'news-ticker-next'));
            $controls .= html_writer::tag('div', '', array('class'=>'news-ticker-prev'));
            $controls .= html_writer::tag('div', '', array('class'=>'news-ticker-pause'));
            $content .= html_writer::tag('div', $controls, array('class'=>'news-ticker-controls'));
            $content .= html_writer::end_tag('div');
        }
        return $content;
    }

    private function print_rss_item($item) {
        $link = $item->get_link();
        $title = $item->get_title();
        $date = $item->get_date('U');
        if (empty($title)) {
            // no title present, use portion of description
            $description = $item->get_description();
            $title = core_text::substr(strip_tags($description), 0, 20) . '...';
        } else {
            $title = break_up_long_words($title, 30);
        }
        if ($date > strtotime('48 hours ago')) {
            $title = html_writer::tag('div', get_string('new', 'theme_qmul'), array('class'=>'newitem')).$title;
        }
        if (empty($link)) {
            $link = $item->get_id();
        } else {
            try {
                // URLs in our RSS cache will be escaped (correctly as theyre store in XML)
                // html_writer::link() will re-escape them. To prevent double escaping unescape here.
                // This can by done using htmlspecialchars_decode() but moodle_url also has that effect.
                $link = new moodle_url($link);
            } catch (moodle_exception $e) {
                // Catching the exception to prevent the whole site to crash in case of malformed RSS feed
                $link = '';
            }
        }
        $r = html_writer::start_tag('div', array('class'=>'list'));
            $r.= html_writer::start_tag('div',array('class'=>'link'));
                $r.= html_writer::link($link, $title, array('onclick'=>'this.target="_blank"'));
            $r.= html_writer::end_tag('div');;
        $r.= html_writer::end_tag('div');
        return $r;
    }

    public function course_search() {
        global $CFG;

        $output = '';
        $output .= html_writer::start_tag('form', array('method'=>'GET', 'action'=>$CFG->wwwroot.'/course/search.php'));
        $output .= html_writer::start_tag('div', array('class'=>'input-group'));
        $output .= html_writer::empty_tag('input', array('class'=>'form-control m-0', 'type'=>'text', 'name'=>'search', 'placeholder'=>get_string('searchcourses')));
        $output .= html_writer::start_tag('span', array('class'=>'input-group-btn'));
        $output .= html_writer::tag('button', '<i class="glyphicon glyphicon-search"></i>', array('type'=>'button', 'class'=>'btn btn-secondary'));
        $output .= html_writer::end_tag('span');
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        return $output;
    }

    public function confirm($message, $continue, $cancel) {
        if ($continue instanceof single_button) {
            // ok
        } else if (is_string($continue)) {
            $continue = new single_button(new moodle_url($continue), get_string('continue'), 'post');
        } else if ($continue instanceof moodle_url) {
            $continue = new single_button($continue, get_string('continue'), 'post');
        } else {
            throw new coding_exception('The continue param to $OUTPUT->confirm() must be either a URL (string/moodle_url) or a single_button instance.');
        }

        if ($cancel instanceof single_button) {
            // ok
        } else if (is_string($cancel)) {
            $cancel = new single_button(new moodle_url($cancel), get_string('cancel'), 'get');
        } else if ($cancel instanceof moodle_url) {
            $cancel = new single_button($cancel, get_string('cancel'), 'get');
        } else {
            throw new coding_exception('The cancel param to $OUTPUT->confirm() must be either a URL (string/moodle_url) or a single_button instance.');
        }

        $output = $this->box_start('generalbox', 'notice');
        $output .= html_writer::tag('p', $message);
        $output .= html_writer::tag('div', $this->render($continue) . $this->render($cancel), array('class' => 'buttons yesno'));
        $output .= $this->box_end();
        return $output;
    }

    private function debug_listing($message) {
        $message = str_replace('<ul style', '<ul class="list-unstyled" style', $message);
        return html_writer::tag('pre', $message, array('class' => 'alert alert-info'));
    }

    public function synergyblocks($region, $classes = array(), $tag = 'aside') {
        $classes = (array)$classes;
        $classes[] = 'block-region';
        $attributes = array(
            'id' => 'block-region-'.preg_replace('#[^a-zA-Z0-9_\-]+#', '-', $region),
            'class' => join(' ', $classes),
            'data-blockregion' => $region,
            'data-droptarget' => '1'
        );
        $content = '';
        if ($this->page->blocks->region_has_content($region, $this)) {
            $content = $this->blocks_for_region($region);
        }
        return html_writer::tag($tag, $content, $attributes);
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

        if ($region == 'side-pre') {
            $context->initial = $this->get_block_initial($bc);
            $context->initialtitle = strip_tags($context->title);
            $context->collapsed = true;
            if ($bc->blockinstanceid == 0) {
                $context->collapsed = false;
            }
            return $this->render_from_template('theme_qmul/drawer_block', $context);
        }

        return $this->render_from_template('core/block', $context);
    }

    private function get_block_initial($bc) {
        $title = explode(' ', strip_tags($bc->title));
        if (count($title) == 1) {
            return substr($bc->title, 0, 2);
        }
        if (count($title) > 4) {
            $title = array_slice($title, 0, 4);
        }
        if (count($title) == 4) {
            $break = "<br />";
            array_splice( $title, 2, 0, $break );
        }
        $initial = '';
        foreach ($title as $word) {
            if ($word == '<br />') {
                $initial .= $word;
                continue;
            }
            $initial .= strtoupper($word[0]);
        }

        return $initial;
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

    public function single_button($url, $label, $method='post', array $options=null) {
        if (!($url instanceof moodle_url)) {
            $url = new moodle_url($url);
        }

        if ($label == get_string('blockseditoff')
                || $label == get_string('turneditingoff')
                || $label == get_string('updatemymoodleoff')) {
            $label = get_string('on', 'theme_qmul');
            $options['state'] = 'on';
        } else if($label == get_string('blocksediton')
                || $label == get_string('turneditingon')
                || $label == get_string('updatemymoodleon')) {
            $label = get_string('off', 'theme_qmul');
            $options['state'] = 'off';
        }

        $button = new single_button($url, $label, $method);
        foreach ((array)$options as $key=>$value) {
            $button->$key = $value;
        }

        if ($label == get_string('on', 'theme_qmul')
                || $label == get_string('off', 'theme_qmul')) {
            return $this->render_edit_button($button);
        }
        if (!$options['large']) {
            $button->small = true;
        }


        return $this->render($button);
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
        $data = $button->export_for_template($this);
        if (isset($button->small)) {
            $data->small = true;
        }
        if ($button->label == get_string('blockseditoff')
                || $button->label == get_string('turneditingoff')
                || $button->label == get_string('updatemymoodleoff')) {
            $button->label = get_string('on', 'theme_qmul');
            $button->state = 'on';
            return $this->render_edit_button($button);
        } else if ($button->label == get_string('blocksediton')
                || $button->label == get_string('turneditingon')
                || $button->label == get_string('updatemymoodleon')) {
            $button->label = get_string('off', 'theme_qmul');
            $button->state = 'off';
            return $this->render_edit_button($button);
        }
        return $this->render_from_template('core/single_button', $data);
    }

    protected function render_edit_button(single_button $button) {
        $data = $button->export_for_template($this);
        $data->state = $button->state;
        return $this->render_from_template('theme_qmul/edit_button', $data);
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
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $SITE;

        $context = $form->export_for_template($this);

        // Override because rendering is not supported in template yet.
        $context->cookieshelpiconformatted = $this->help_icon('cookiesenabled');
        $context->errorformatted = $this->error_text($context->error);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context->logourl = $url;
        $context->sitename = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);

        return $this->render_from_template('core/login', $context);
    }

    /**
     * Render the login signup form into a nice template for the theme.
     *
     * @param mform $form
     * @return string
     */
    public function render_login_signup_form($form) {
        global $SITE;

        $context = $form->export_for_template($this);
        $url = $this->get_logo_url();
        if ($url) {
            $url = $url->out(false);
        }
        $context['logourl'] = $url;
        $context['sitename'] = format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID), "escape" => false]);

        return $this->render_from_template('core/signup_form_layout', $context);
    }

    /**
     * Return the standard string that says whether you are logged in (and switched
     * roles/logged in as another user).
     * @param bool $withlinks if false, then don't include any links in the HTML produced.
     * If not set, the default is the nologinlinks option from the theme config.php file,
     * and if that is not set, then links are included.
     * @return string HTML fragment.
     */
    public function login_info($withlinks = null) {
        global $USER, $CFG, $DB, $SESSION;

        if (during_initial_install()) {
            return '';
        }

        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        $loginpage = $this->is_login_page();
        $loginurl = get_login_url();

        if (!isloggedin()) {
            return '';
        }

        $extra = '';
        $class = '';
        $userpreference = get_user_preferences('theme_qmul_logindashboard');
        if (is_null($userpreference) || $userpreference == 1) {
            if (isset($SESSION->justloggedin)) {
                $class = ' hidden';
                $extra = 'style="display: inline;"';
            }
        }

        $loggedinas = '<a class="sr-only sr-only-focusable" href="#page">Skip to page content</a><a href="javascript:void(0)" class="backpackbutton'.$class.'"><svg width="30px" height="30px" viewBox="0 0 59 59" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><title>'.get_string('flydowndashboard', 'theme_qmul').'</title><g fill="#FFFFFF"><rect x="0" y="0" width="26.55" height="32.45"></rect><rect x="32.45" y="26.55" width="26.55" height="32.45"></rect><rect x="32.45" y="0" width="26.55" height="20.65"></rect><rect x="0" y="38.35" width="26.55" height="20.65"></rect></g></g></svg></a>';
        $loggedinas .= '<a href="javascript:void(0)" class="closebutton" '.$extra.'><svg version="1.2" preserveAspectRatio="none" viewBox="0 0 24 24"><g><path xmlns:default="http://www.w3.org/2000/svg" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" style="fill: rgb(245, 245, 245);"></path></g></svg>'.get_string('close', 'theme_qmul').'</a>';

        $loggedinas = '<div class="logininfo">'.$loggedinas.'</div>';
        return $loggedinas;
    }

    public function user_info() {
        global $CFG, $DB, $SESSION, $USER;
        $content = '';
        $substring = '';

        if (isloggedin()) {

            $course = $this->page->course;
            if (\core\session\manager::is_loggedinas()) {
                $realuser = \core\session\manager::get_realuser();
                $fullname = fullname($realuser, true);
                $loginastitle = get_string('loginas');
                $substring = "<a href=\"$CFG->wwwroot/course/loginas.php?id=$course->id&amp;sesskey=".sesskey()."\"";
                $substring .= "title =\"".$loginastitle."\">$fullname</a>";
            }

            $loginpage = $this->is_login_page();
            $loginurl = get_login_url();

            $context = context_course::instance($course->id);

            $username = fullname($USER, true);
            if (is_role_switched($course->id)) { // Has switched roles
                $rolename = '';
                if ($role = $DB->get_record('role', array('id'=>$USER->access['rsw'][$context->path]))) {
                    $rolename = role_get_name($role, $context);
                }
                $substring = $rolename;
                $url = new moodle_url('/course/switchrole.php', array('id'=>$course->id,'sesskey'=>sesskey(), 'switchrole'=>0, 'returnurl'=>$this->page->url->out_as_local_url(false)));
                $substring .= ' ('.html_writer::tag('a', get_string('switchrolereturn'), array('href' => $url)).')';
            }

            if (isset($SESSION->justloggedin)) {
                unset($SESSION->justloggedin);
                if (!empty($CFG->displayloginfailures)) {
                    if (!isguestuser()) {
                        // Include this file only when required.
                        require_once($CFG->dirroot . '/user/lib.php');
                        if ($count = user_count_login_failures($USER)) {
                            $a = new stdClass();
                            $a->attempts = $count;
                            $substring .= get_string('failedloginattempts', '', $a);
                            if (file_exists("$CFG->dirroot/report/log/index.php") and has_capability('report/log:view', context_system::instance())) {
                                $substring .= ' ('.html_writer::link(new moodle_url('/report/log/index.php', array('chooselog' => 1,
                                        'id' => 0 , 'modid' => 'site_errors')), get_string('logs')).')';
                            }
                        }
                    }
                }
            }

            $prefurl = new moodle_url('/user/preferences.php');
            $preficon = html_writer::tag('i', '', array('class'=>'glyphicon glyphicon-cogwheel'));
            $preflink = html_writer::link($prefurl, $preficon.get_string('preferences'), array('class'=>'preferenceslink text-white'));

            $content = html_writer::tag('div', $this->user_picture($USER, array('size'=>'50', 'link'=>true)), array('class'=>'avatar'));
            $content .= html_writer::start_tag('div', array('class'=>'text-white info ml-1'));
            $content .= html_writer::tag('div', $username, array('class'=>'username'));
            $content .= html_writer::tag('div', $substring, array('class'=>'substring'));
            $content .= html_writer::tag('div', $preflink, array('class'=>'preferences'));
            $content .= html_writer::end_tag('div');
        }

        return $content;
    }

    /**
     * Construct a user menu, returning HTML that can be echoed out by a
     * layout file.
     *
     * @param stdClass $user A user object, usually $USER.
     * @param bool $withlinks true if a dropdown should be built.
     * @return string HTML fragment.
     */
    public function user_menu($user = null, $withlinks = null) {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        if (is_null($user)) {
            $user = $USER;
        }

        // Note: this behaviour is intended to match that of core_renderer::login_info,
        // but should not be considered to be good practice; layout options are
        // intended to be theme-specific. Please don't copy this snippet anywhere else.
        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        // Add a class for when $withlinks is false.
        $usermenuclasses = 'usermenu';
        if (!$withlinks) {
            $usermenuclasses .= ' withoutlinks';
        }

        $returnstr = "";

        // If during initial install, return the empty return string.
        if (during_initial_install()) {
            return $returnstr;
        }

        $loginpage = $this->is_login_page();
        $loginurl = get_login_url();
        // If not logged in, show the typical not-logged-in string.
        if (!isloggedin()) {
            $returnstr = '';
            if (!$loginpage) {
                $returnstr = "";
                $returnstr .= " <a class='btn-primary btn loginbtn' href=\"$loginurl\">" . get_string('login') . '</a>';
            }
            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login mr-1'
                ),
                $usermenuclasses
            );

        }

        // If logged in as a guest user, show a string to that effect.
        if (isguestuser()) {
            $returnstr = get_string('loggedinasguest');
            if (!$loginpage && $withlinks) {
                $returnstr .= " (<a href=\"$loginurl\">".get_string('login').'</a>)';
            }

            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );
        }

        // Get some navigation opts.
        $opts = user_get_user_navigation_info($user, $this->page, array('avatarsize'=>36));

        $divider = new stdClass();
        $divider->itemtype = 'divider';

        unset($opts->navitems[0]);

        $logout = null;
        $switchrole = null;

        $check = array_pop($opts->navitems);
        if ($check->pix == 'a/logout') {
            $logout = $check;
        } else if ($check->pix == 'i/switchrole') {
            $logout = array_pop($opts->navitems);
            $switchrole = $check;
        }

        // QM Model check
        $dashboardlink = local_qmframework\qmframework::get_dashboardlink();
        $adviseeslink = local_qmframework\qmframework::get_adviseeslinks();
        if ($adviseeslink != false) {
            $qmmodel = new stdClass();
            $qmmodel->itemtype = 'link';
            $qmmodel->title = get_string('skillsreview', 'theme_qmul');
            $qmmodel->pix = null;
            $qmmodel->children = $adviseeslink;
            $opts->navitems[] = $qmmodel;
        } else  if ($dashboardlink != false) {
            $qmmodel = new stdClass();
            $qmmodel->itemtype = 'link';
            $qmmodel->url = $dashboardlink;
            $qmmodel->title = get_string('skillsreview', 'theme_qmul');
            $qmmodel->pix = null;
            $opts->navitems[] = $qmmodel;
        }

        if ($switchrole) {
            $opts->navitems[] = $switchrole;
        }

        $opts->navitems[] = $logout;


        $content = '';
        foreach ($opts->navitems as $item) {
            switch ($item->itemtype) {
                case 'divider':
                    $content .= html_writer::empty_tag('hr');
                    break;
                case 'invalid':
                    break;
                case 'link':
                    $pix = null;
                    if (isset($item->pix) && !empty($item->pix)) {
                        $pix = new pix_icon($item->pix, $item->title, null, array('class' => 'iconsmall'));
                    } else if (isset($item->imgsrc) && !empty($item->imgsrc)) {
                        $item->title = html_writer::img(
                            $item->imgsrc,
                            $item->title,
                            array('class' => 'iconsmall')
                        ) . $item->title;
                    }

                    $extraclass = '';
                    if ($item->pix == 'a/logout') {
                        $extraclass = ' logout';
                    }

                    // QM Model children
                    if (isset($item->children)) {
                        $title = html_writer::tag('span', $item->title, array('class'=>'title dropdown-toggle', 'data-toggle'=>'dropdown'));
                        $children = '';
                        foreach ($item->children as $child) {
                            $children .= html_writer::link($child['link'], $child['name'], array('class'=>'dropdown-item'));
                        }
                        $title .= html_writer::tag('ul', $children, array('class'=>'dropdown-menu'));
                        $content .= html_writer::tag('div', $title, array('class'=>'dropdown menu-link text-white'.$extraclass));
                    } else {
                        $title = html_writer::tag('span', $item->title, array('class'=>'title'));
                        $content .= html_writer::link($item->url, $title, array('class'=>'menu-link text-white'.$extraclass));
                    }
                    break;
            }
        }

        $content = html_writer::tag('div', $content, array('class'=>"usermenu"));

        return $content;
    }

    /**
     * Internal implementation of user image rendering.
     *
     * @param user_picture $userpicture
     * @return string
     */
    protected function render_user_picture(user_picture $userpicture) {
        global $CFG, $DB;

        $user = $userpicture->user;

        if ($userpicture->alttext) {
            if (!empty($user->imagealt)) {
                $alt = $user->imagealt;
            } else {
                $alt = get_string('pictureof', '', fullname($user));
            }
        } else {
            $alt = '';
        }


        if (empty($userpicture->size)) {
            $size = 85;
        } else if ($userpicture->size === true or $userpicture->size == 1) {
            $size = 100;
        } else {
            $size = $userpicture->size;
        }

        if ($size == 35) {
            $size = 85;
        }
        $userpicture->size = $size;


        $class = $userpicture->class;

        $src = $userpicture->get_url($this->page, $this);

        $attributes = array('src'=>$src, 'alt'=>$alt, 'title'=>$alt, 'class'=>$class, 'width'=>$size, 'height'=>$size);
        if (!$userpicture->visibletoscreenreaders) {
            $attributes['role'] = 'presentation';
        }

        // get the image html output fisrt
        $output = html_writer::empty_tag('img', $attributes);

        // then wrap it in link if needed
        if (!$userpicture->link) {
            return $output;
        }

        if (empty($userpicture->courseid)) {
            $courseid = $this->page->course->id;
        } else {
            $courseid = $userpicture->courseid;
        }

        if ($courseid == SITEID) {
            $url = new moodle_url('/user/profile.php', array('id' => $user->id));
        } else {
            $url = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $courseid));
        }

        $attributes = array('href'=>$url);
        if (!$userpicture->visibletoscreenreaders) {
            $attributes['tabindex'] = '-1';
            $attributes['aria-hidden'] = 'true';
        }

        if ($userpicture->popup) {
            $id = html_writer::random_id('userpicture');
            $attributes['id'] = $id;
            $this->add_action_handler(new popup_action('click', $url), $id);
        }

        return html_writer::tag('a', $output, $attributes);
    }

    public function navbar() {
        $items = $this->page->navbar->get_items();
        $firstcat = true;
        $sitecontext = context_system::instance();
        foreach ($items as $key => $item) {
            if ($item->key === 'mycourses') {
                unset($items[$key]);
                continue;
            }
            if (!has_capability('theme/qmul:seelongbreadcrumb', $sitecontext)) {
                if ($item->type == 11 || $item->type == 10) {
                    if ($firstcat) {
                        $firstcat = false;
                    } else {
                        unset($items[$key]);
                        continue;
                    }
                }
            }
            if ($item->key === 'myhome') {
                $items[$key]->isdashboard = true;
            }
            $items[$key]->titletext = $item->get_title();
            $items[$key]->text = $item->text;
        }

        $context = new stdClass();
        $context->items = array_values($items);

        if (empty($items)) {
            return '';
        }


        return $this->render_from_template('core/navbar', $context);
    }

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

    public function custom_menu($custommenuitems = '') {
        // The custom menu is always shown, even if no menu items
        // are configured in the global theme settings page.
        global $CFG;

        if (empty($custommenuitems) && !empty($CFG->custommenuitems)) { // MDL-45507.
            $custommenuitems = $CFG->custommenuitems;
        }
        $custommenu = new custom_menu($custommenuitems, current_language());
        return $this->render_custom_menu($custommenu);
    }

    protected function render_custom_menu(custom_menu $menu) {
        /*
        * This code replaces adds the current enrolled
        * courses to the custommenu.
        */

        if (!$menu->get_children()) {
            return '';
        }

        $hasdisplaymycourses = (empty($this->page->theme->settings->displaymycourses)) ? false : $this->page->theme->settings->displaymycourses;
        if (isloggedin() && $hasdisplaymycourses) {
            $mycoursetitle = $this->page->theme->settings->mycoursetitle;
            if ($mycoursetitle == 'module') {
                $branchlabel = '<i class="glyphicon glyphicon-briefcase"></i>'.get_string('mymodules', 'theme_qmul');
                $branchtitle = get_string('mymodules', 'theme_qmul');
            } else if ($mycoursetitle == 'unit') {
                $branchlabel = '<i class="glyphicon glyphicon-briefcase"></i>'.get_string('myunits', 'theme_qmul');
                $branchtitle = get_string('myunits', 'theme_qmul');
            } else if ($mycoursetitle == 'class') {
                $branchlabel = '<i class="glyphicon glyphicon-briefcase"></i>'.get_string('myclasses', 'theme_qmul');
                $branchtitle = get_string('myclasses', 'theme_qmul');
            } else {
                $branchlabel = '<i class="glyphicon glyphicon-briefcase"></i>'.get_string('mycourses', 'theme_qmul');
                $branchtitle = get_string('mycourses', 'theme_qmul');
            }
            $branchurl   = new moodle_url('/my/index.php');
            $branchsort  = 10000;

            $branch = $menu->add($branchlabel, $branchurl, $branchtitle, $branchsort);
            if ($courses = enrol_get_my_courses(NULL, 'fullname ASC')) {
                foreach ($courses as $course) {
                    if ($course->visible){
                        $branch->add(format_string($course->fullname), new moodle_url('/course/view.php?id='.$course->id), format_string($course->shortname));
                    }
                }
            } else {
                $branch->add('<em>'.get_string('noenrolments', 'theme_qmul').'</em>',new moodle_url('/'),get_string('noenrolments', 'theme_qmul'));
            }
        }

        $content = '';
        foreach ($menu->get_children() as $item) {
            $context = $item->export_for_template($this);
            $content .= $this->render_from_template('core/custom_menu_item', $context);
        }

        return  html_writer::tag('nav', $content, array('id'=>'custommenu', 'class'=>'custommenu nav flex-row justify-content-center', 'aria-multiselectable'=>'true'));
    }

    public function user_stats($courses) {
        global $CFG, $DB, $USER;

        $stats = new stdClass();

        $stats->enrolled = count($courses);

        $completed = 0;
        foreach ($courses as $course) {
            $course = new course_in_list($course);
            $completion = new completion_info($course);
            if ($completion->is_course_complete($USER->id)) {
                $completed++;
            }
        }

        $stats->completed = $completed;

        $content = $this->render_from_template('theme_qmul/userstats', $stats);

        return html_writer::tag('div', $content, array('class'=>'userstats row mb-1'));
    }

    public function dashboard_courses($courses) {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/course/renderer.php');

        if (!empty($USER->currentcourseaccess)) {
            $lastaccess = max($USER->currentcourseaccess);
            $lastcourseid = array_keys($USER->currentcourseaccess, max($USER->currentcourseaccess));
        } elseif (!empty($USER->lastcourseaccess)) {
            $lastaccess = max($USER->lastcourseaccess);
            $lastcourseid = array_keys($USER->lastcourseaccess, max($USER->lastcourseaccess));
        } else {
            return '';
        }
        $lastcourseid = reset($lastcourseid);

        if (empty($lastcourseid)) {
            return '';
        }

        $content = '';
        $i = 1;
        if (isset($course[$lastcourseid])) {
            $lastcourse = $courses[$lastcourseid];
        } else {
            $lastcourse = $DB->get_record('course', array('id'=>$lastcourseid));
        }
        unset($courses[$lastcourseid]);
        $content .= html_writer::start_tag('div', array('class'=>'card-deck col-12'));
        include_once($CFG->dirroot.'/lib/coursecatlib.php');
        $course = new course_in_list($lastcourse);
        $completion = new completion_info($course);
        $courseinfo = new stdClass();
        $courseinfo->name = $course->fullname;
        $courselink = new moodle_url('/course/view.php', array('id'=>$course->id));
        $courseinfo->url = $courselink->out();
        $chelper = new coursecat_helper();
        $shortsummary = theme_qmul_truncate_html($chelper->get_course_formatted_summary($course));
        $courseinfo->summary = format_text($shortsummary, $course->summaryformat);
        list($progressbar, $activityprogress) = $this->completionbar($course);
        $courseinfo->progressbar = $progressbar;
        $courseinfo->activityprogress = $activityprogress;
        $url = '';
        try {
            foreach ($course->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                        '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                        $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
            }
        } catch(Exception $e) {
            $url = '';
        }
        $courseinfo->hasimage = false;
        if (!empty($url)) {
            $courseinfo->hasimage = true;
        }
        $courseinfo->image = $url;
        $courseinfo->lastaccess = date('d/m/y', $lastaccess);

        if ($course->has_course_contacts() && $this->page->theme->settings->showteacherimages == true) {
            $teachers = array();
            foreach ($course->get_course_contacts() as $userid => $coursecontact) {
                $fields = \user_picture::fields();
                $sql = "SELECT $fields FROM {user} WHERE id = $userid";
                $user = $DB->get_record_sql($sql);

                $image = new user_picture($user);
                $teacher = new stdClass();
                $teacher->src = $image->get_url($this->page, $this)->out();
                $teacher->name = $user->firstname.' '.$user->lastname;

                $teachers[] = $teacher;
            }
            $courseinfo->teachers = $teachers;
        }

        $content .= $this->render_from_template('theme_qmul/lastcourse', $courseinfo);
        $content .= html_writer::end_tag('div');
        $content .= html_writer::start_tag('div', array('class'=>'card-deck col-12'));
        foreach ($courses as $course) {
            $course = new course_in_list($course);
            $completion = new completion_info($course);

            $courseinfo = new stdClass();
            $courseinfo->name = $course->fullname;
            $courselink = new moodle_url('/course/view.php', array('id'=>$course->id));
            $courseinfo->url = $courselink->out();
            $chelper = new coursecat_helper();
            $shortsummary = theme_qmul_truncate_html($chelper->get_course_formatted_summary($course));
            $courseinfo->summary = format_text($shortsummary, $course->summaryformat);
            list($progressbar, $activityprogress) = $this->completionbar($course);
            $courseinfo->progressbar = $progressbar;
            $courseinfo->activityprogress = $activityprogress;
            if ($i > 1 && ($i % 2 == 1)) {
                $content .= html_writer::end_tag('div');
                $content .= html_writer::start_tag('div', array('class'=>'card-deck col-12'));
            }
            $i++;

            $url = '';
            try {
                foreach ($course->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                }
            } catch (Exception $e) {
                $url = '';
            }
            $courseinfo->hasimage = false;
            if (!empty($url)) {
                $courseinfo->hasimage = true;
            }
            $courseinfo->image = $url;

            if ($course->has_course_contacts() && $this->page->theme->settings->showteacherimages == true) {
                $teachers = array();

                foreach ($course->get_course_contacts() as $userid => $coursecontact) {
                    $fields = \user_picture::fields();
                    $sql = "SELECT $fields FROM {user} WHERE id = $userid";
                    $user = $DB->get_record_sql($sql);

                    $image = new user_picture($user);
                    $teacher = new stdClass();
                    $teacher->src = $image->get_url($this->page, $this)->out();
                    $teacher->name = $user->firstname.' '.$user->lastname;

                    $teachers[] = $teacher;
                }
                $courseinfo->teachers = $teachers;
            }

            $content .= $this->render_from_template('theme_qmul/coursebox', $courseinfo);
        }
        $content .= html_writer::end_tag('div');
        return html_writer::tag('div', $content, array('class'=>'mycourses clearfix row'));
    }

    protected function completionbar($course) {
        global $CFG, $USER;
        require_once($CFG->libdir.'/completionlib.php');

        $info = new completion_info($course);
        $completions = $info->get_completions($USER->id);

        // Check if this course has any criteria.
        if (empty($completions)) {
            return array('', '');
        }

        $progressbar = '';
        $activityinfo = '';
        // Check this user is enroled.
        if ($info->is_tracked_user($USER->id)) {
            // For aggregating activity completion.
            $activities = array();
            $activities_complete = 0;

            // Loop through course criteria.
            foreach ($completions as $completion) {
                $criteria = $completion->get_criteria();
                $complete = $completion->is_complete();

                if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                    $activities[$criteria->moduleinstance] = $complete;
                    if ($complete) {
                        $activities_complete++;
                    }
                }
            }

            // Aggregate activities.
            if (!empty($activities)) {
                $per = floor(100 * ($activities_complete / count($activities)));
                $progressbar = html_writer::start_tag('div', array('class'=>'progressinfo clearfix'));
                $progressbar .= html_writer::tag('div', get_string('progress', 'tool_lp'), array('class'=>'float-left progresstitle'));
                $progressbar .= html_writer::tag('div', $per.'%', array('class'=>'float-right'));
                $progressbar .= html_writer::end_tag('div');
                $bar = html_writer::tag('div', '', array('class'=>'progress-bar-animated progress-bar bg-success', 'aria-valuemin'=>0, 'aria-valuemax'=>100, 'aria-valuenow'=>$per, 'style'=>"width: $per%"));
                $progressbar .= html_writer::tag('div', $bar, array('class'=>'progress'));

                $activity = new stdClass();
                $activity->total = count($activities);
                $activity->complete = $activities_complete;
                $activityinfo = get_string('activityoutof', 'theme_qmul', $activity);
            }
        }
        return array($progressbar, $activityinfo);
    }

    protected function render_context_header(context_header $contextheader) {
        global $COURSE;

        // All the html stuff goes here.
        $html = html_writer::start_div('page-context-header');
        $html .= html_writer::start_div('container-fluid py-12 clearfix');
        $image = '';
        $headings = '';

        if ($COURSE->format == 'landingpage') {
            $button = $this->page_heading_button();
            if (!empty($button)) {
                $html .= html_writer::div($this->page_heading_button(), 'breadcrumb-button');
                $html .= html_writer::end_div();
                $html .= html_writer::end_div();
                return $html;
            } else {
                return '';
            }
        }

        // Image data.
        if (isset($contextheader->imagedata)) {
            // Header specific image.
            $image .= html_writer::div($contextheader->imagedata, 'page-header-image d-inline-block mr-1');
        }

        // Headings.
        if (isset($contextheader->heading)) {
            $headings = $this->heading($contextheader->heading, $contextheader->headinglevel, 'd-inline-block py-12 m-0');
        } else {
            $headings = $this->heading($this->page->heading, $contextheader->headinglevel, 'd-inline-block py-12 m-0');
        }

        if (!$image && !$headings) {
            return '';
        }

        $html .= html_writer::tag('div', $image.$headings, array('class' => 'page-header-headings d-inline-block mb-sm-0 mb-1 mr-1'));

        // Buttons.
        if (isset($contextheader->additionalbuttons)) {
            $html .= html_writer::start_div('btn-group header-button-group mb-sm-0 mb-1');
            foreach ($contextheader->additionalbuttons as $button) {
                $button['linkattributes']['class'] .= ' btn-secondary mr-1';
                if (!isset($button->page)) {
                    // Include js for messaging.
                    if ($button['buttontype'] === 'togglecontact') {
                        \core_message\helper::togglecontact_requirejs();
                    }
                    $image = $this->pix_icon($button['formattedimage'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                } else {
                    $image = html_writer::empty_tag('img', array(
                        'src' => $button['formattedimage'],
                        'role' => 'presentation'
                    ));
                }
                $html .= html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes']);
            }
            $html .= html_writer::end_div();
        }

        $html .= html_writer::div($this->page_heading_button(), 'breadcrumb-button py-12');

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        return $html;
    }

    public function page_heading($tag = 'h1') {
        global $COURSE;
        $heading = html_writer::tag($tag, $this->page->heading, array('class'=>'pageheading'));
        if ($COURSE->id != SITEID) {
            $heading = html_writer::tag('div', $heading, array('id'=>'course-header'));
            $path = parse_url($this->page->url->out_as_local_url())['path'];
            $courseviewpage = ($path === '/course/view.php');
            if ($courseviewpage) {
                $heading .= $this->cover_image_selector();
            }
        }
        return $heading;
    }

    protected function render_tabtree(tabtree $tabtree) {
        if (empty($tabtree->subtree)) {
            return '';
        }
        $data = $tabtree->export_for_template($this);
        return $this->render_from_template('core/tabtree', $data);
    }


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
        $oldclass = '';
        if (isset($icon->attributes['class'])) {
            $oldclass = $icon->attributes['class'];
        }
        if ($replaceicon = self::replace_moodle_icon($icon->pix, $oldclass)) {
            $data['hasglyphicon'] = true;
            $data['glyphicon'] = $replaceicon;
        } else {
            $data['hasglyphicon'] = false;
            $data['glyphicon'] = false;
        }
        return $this->render_from_template('core/pix_icon', $data);
    }

    public static function replace_moodle_icon($icon, $oldclass = false, $alt = false) {
        $icons = array(
            'add'                => 'plus',
            'adv'                => 'asterisk',
            'book'               => 'book',
            'chapter'            => 'file',
            'docs'               => 'info-sign',
            'help'               => 'question-sign',
            'generate'           => 'gift',
            'a/search'           => 'search',
            'a/logout'           => 'log-out',
            'i/assignroles'      => 'user-add',
            'i/backup'           => 'cloud-download',
            'i/badge'            => 'certificate',
            'i/calc'             => 'calculator',
            'i/calendar'         => 'calendar',
            'i/checkpermissions' => 'user-lock',
            'i/cohort'           => 'group',
            'i/competencies'     => 'tree-structure',
            'i/course'           => 'share-alt',
            'i/db'               => 'database',
            'i/delete'           => 'remove',
            'i/down'             => 'arrow-down',
            'i/dragdrop'         => 'move',
            'i/dropdown'         => 'chevron-down',
            'i/edit'             => 'pencil',
            'i/enrolusers'       => 'user-add',
            'i/export'           => 'inbox-out',
            'i/files'            => 'folder-open',
            'i/user'             => 'folder-open',
            'i/filter'           => 'filter',
            'i/folder'           => 'folder-open',
            'i/grades'           => 'table',
            'i/group'            => 'group',
            'i/groupevent'       => 'group',
            'i/groupn'           => 'group',
            'i/groupv'           => 'group',
            'i/groups'           => 'group',
            'i/hide'             => 'eye-open',
            'i/import'           => 'upload',
            'i/info'             => 'info-sign',
            'i/item'             => 'stop',
            'i/loading'          => 'refresh glyphicon-spin spinner',
            'i/loading_small'    => 'refresh glyphicon-spin spinner',
            'i/manual_item'      => 'edit',
            'i/marker'           => 'lightbulb',
            'i/marked'           => 'lightbulb',
            'i%2Fmarker'         => 'lightbulb',
            'i%2Fmarked'         => 'lightbulb',
            'i/move_2d'          => 'resize-vertical',
            'i/navigationitem'   => 'stop',
            'i/outcomes'         => 'pie-chart',
            'i/permissions'      => 'keys',
            'i/preview'          => 'search',
            'i/publish'          => 'globe-af',
            'i/reload'           => 'refresh',
            'i/report'           => 'list-alt',
            'i/repository'       => 'database',
            'i/restore'          => 'cloud-upload',
            'i/return'           => 'repeat',
            'i/roles'            => 'user',
            'i/scales'           => 'charts',
            'i/scheduled'        => 'clock',
            'i/search'           => 'search',
            'i/self'             => 'check',
            'i%2Fself'           => 'check',
            'i/settings'         => 'cogwheels',
            'i/site-event'       => 'calendar',
            'i/show'             => 'eye-close',
            'i/switchrole'       => 'user-asterisk',
            'i/two-way'          => 'transfer',
            'i/up'               => 'arrow-up',
            'i/notifications'    => 'bell',
            'i/user'             => 'user',
            'i/userevent'        => 'user',
            'i/users'            => 'parents',
            'i/warning'          => 'warning-sign',
            'i/withsubcat'       => 'flowchart',
            't/add'              => 'plus',
            't/addcontact'       => 'plus',
            't/approve'          => 'ok',
            't/award'            => 'cup',
            't/assignroles'      => 'user-add',
            't/block_to_dock'    => 'circle-arrow-left',
            't/check'            => 'ok',
            't/cohort'           => 'family',
            't/collapsed'        => 'chevron-right',
            't/copy'             => 'copy',
            't/delete'           => 'remove',
            't/dock_to_block'    => 'circle-arrow-right',
            't/down'             => 'arrow-down',
            't/download'         => 'cloud-download',
            't/edit'             => 'edit',
            't/editstring'       => 'pencil',
            't/edit_menu'        => 'cogwheels',
            't/enrolusers'       => 'user-add',
            't/email'            => 'envelope',
            't/expanded'         => 'chevron-down',
            't/export'           => 'file-export',
            't/grades'           => 'list-alt',
            't/groupn'           => 'group',
            't/groups'           => 'group',
            't/groupv'           => 'group',
            't/hide'             => 'eye-open',
            't/left'             => 'arrow-left',
            't/lock'             => 'unlock',
            't/message'          => 'chat',
            't/move'             => 'resize-vertical',
            't/passwordunmask-edit' => 'pencil',
            't/passwordunmask-reveal' => 'eye-open',
            't/preview'          => 'search',
            't/right'            => 'arrow-right',
            't/show'             => 'eye-close',
            't/sort'             => 'sorting',
            't/sort_asc'         => 'sort-by-attributes',
            't/sort_desc'        => 'sort-by-attributes flipv',
            't/switch_minus'     => 'minus-sign',
            't/switch_plus'      => 'plus-sign',
            'i/switch_minus'     => 'minus-sign',
            'i/switch_plus'      => 'plus-sign',
            't/preferences'      => 'cogwheel',
            't/unblock'          => 'arrow-up',
            't/unlock'           => 'lock',
            't/unlocked'         => 'unlock',
            't/up'               => 'arrow-up',
            't/markasread'       => 'check',
            't/viewdetails'      => 'search',
            'y/loading'          => 'refresh animated spin',
            'add_todo'           => 'bookmark',
            'add_note'           => 'file-plus',
            'edit_note'          => 'file',

        );
        if (array_key_exists($icon, $icons)) {
            return "<i class=\"glyphicon glyphicon-$icons[$icon] icon $oldclass\" title=\"$alt\"></i>";
        } else {
            return false;
        }
    }

    public function render_qmul_user_action_menu(qmul_user_action_menu $menu) {
        $menu->initialise_js($this->page);

        $output = html_writer::start_tag('div', $menu->attributes);
        $output .= html_writer::start_tag('ul', $menu->attributesprimary);
        foreach ($menu->get_primary_actions($this) as $action) {
            if ($action instanceof renderable) {
                $content = $this->render($action);
            } else {
                $content = $action;
            }
            $output .= html_writer::tag('li', $content, array('role' => 'presentation'));
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::start_tag('ul', $menu->attributessecondary);
        foreach ($menu->get_secondary_actions() as $action) {
            if ($action instanceof renderable) {
                $content = $this->render($action);
            } else {
                $content = $action;
            }
            $output .= html_writer::tag('li', $content, array('role' => 'presentation'));
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function cover_image_selector() {
        global $PAGE;
        if (has_capability('moodle/course:changesummary', $PAGE->context)) {
            $vars = ['accepttypes' => $this->supported_courseimage_typesstr()];
            return $this->render_from_template('theme_qmul/cover_image_selector', $vars);
        }
        return null;
    }

    protected static function supported_courseimage_types() {
        global $CFG;
        $extsstr = strtolower($CFG->courseoverviewfilesext);

        // Supported file extensions.
        $extensions = explode(',', str_replace('.', '', $extsstr));
        array_walk($extensions, function($s) {trim($s); });
        // Filter out any extensions that might be in the config but not image extensions.
        $imgextensions = ['jpg', 'png', 'gif', 'svg', 'webp'];
        return array_intersect ($extensions, $imgextensions);
    }

    protected static function supported_courseimage_typesstr() {
        $supportedexts = self::supported_courseimage_types();
        $extsstr = '';
        $typemaps = [
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'png'  => 'image/png',
            'svg'  => 'image/svg'
        ];
        foreach ($supportedexts as $ext) {
            if (in_array($ext, $supportedexts) && isset($typemaps[$ext])) {
                $extsstr .= $extsstr == '' ? '' : ',';
                $extsstr .= $typemaps[$ext];
            }
        }
        return $extsstr;
    }

    public function course_news() {
        global $COURSE, $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/course/format/qmultopics/locallib.php');

        $course = $COURSE;
        $coursecontext = context_course::instance($course->id);

        $streditsummary = get_string('editsummary');

        $output = '';

        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $coursecontext)) {
            $image = html_writer::empty_tag('img', array('src'=>$this->pix_url('t/edit'), 'alt'=>$streditsummary, 'class'=>'iconsmall edit'));
            $url = new moodle_url('/course/format/qmultopics/newssettings.php', array('course'=>$course->id));
            $link = html_writer::link($url, $image, array('title'=>get_string('editnewssettings', 'format_qmultopics')));
            $output .= $link;
        }
        if ($newssettings = $DB->get_record('format_qmultopics_news', array('courseid' => $course->id))) {
            if ($newssettings->displaynews || $newssettings->usestatictext) {
                if ($newssettings->usestatictext) {
                    $newstext = $newssettings->statictext;
                } else {
                    $newstext = format_qmultopics_getnews($course);
                }
                $output .= html_writer::tag('div', $newstext, array('class'=>'news_text'));
            }
        }

        return $output;
    }
}

class qmul_user_action_menu extends action_menu implements renderable {

    /**
     * Constructs the action menu with the given items.
     *
     * @param array $actions An array of actions.
     */
    public function __construct(array $actions = array()) {
        static $initialised = 0;
        $this->instance = $initialised;
        $initialised++;

        $this->attributes = array(
            'id' => 'action-menu-'.$this->instance,
            'class' => 'moodle-actionmenu nav navbar-nav navbar-right user-menu',
            'data-enhance' => 'moodle-core-actionmenu'
        );
        $this->attributesprimary = array(
            'id' => 'action-menu-'.$this->instance.'-menubar',
            'class' => 'menubar',
            'role' => 'menubar'
        );
        $this->attributessecondary = array(
            'id' => 'action-menu-'.$this->instance.'-menu',
            'class' => 'menu',
            'data-rel' => 'menu-content',
            'aria-labelledby' => 'action-menu-toggle-'.$this->instance,
            'role' => 'menu'
        );
        $this->set_alignment(self::TR, self::BR);
        foreach ($actions as $action) {
            $this->add($action);
        }
    }
}
