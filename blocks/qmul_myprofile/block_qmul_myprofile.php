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
 * Block displaying information about current logged-in user.
 *
 *
 * @package    block
 * @subpackage qmul_myprofile
 * @copyright  2010 Gerry G Hall
 * @author     Gerry G HAll <gerryghall@googlemail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Displays the current user's profile information.
 *
 * @copyright  2010 Remote-Learner.net
 * @author     Olav Jordan <olav.jordan@remote-learner.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_qmul_myprofile extends block_base
{

    /**
     * block initializations
     */
    public function init()
    {
        $this->title = get_string('pluginname', 'block_qmul_myprofile');
    }

    /**
     * instance specialisations (must have instance allow config true)
     *
     */
    function specialization()
    {
        $this->title = $this->get_title();
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content()
    {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!isloggedin() or isguestuser()) {
            return '';      // Never useful unless you are logged in as real users
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $renderer = $PAGE->get_renderer('block_qmul_myprofile');
        $course = $this->page->course;

        $this->content->text .= $renderer->render_data(
                fullname($USER), 'div', array('class' => 'fullname')
        );

        if (!isset($this->config->display_picture) || $this->config->display_picture == 1) {
            $this->content->text .= $renderer->render_picture();
        }

        if (!isset($this->config->display_country) || $this->config->display_country == 1) {
            $countries = get_string_manager()->get_list_of_countries();
            if (isset($countries[$USER->country])) {
                $this->content->text .= $renderer->render_label_data_element(
                        get_string('country'), $countries[$USER->country], array('class' => 'country')
                );
            }
        }

        if (!isset($this->config->display_city) || $this->config->display_city == 1) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('city'), format_string($USER->city), array('class' => 'city')
            );
        }

        if (!isset($this->config->display_email) || $this->config->display_email == 1) {
            $this->content->text .= $renderer->render_label_data_element(
                    null, obfuscate_mailto($USER->email), array('class' => 'email')
            );
        }

        if ($this->validate_display('icq')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('icq', 'block_qmul_myprofile'), s($USER->icq), array('class' => 'icq')
            );
        }

        if ($this->validate_display('skype')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('skype', 'block_qmul_myprofile'), s($USER->skype), array('class' => 'skype')
            );
        }

        if ($this->validate_display('yahoo')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('yahoo', 'block_qmul_myprofile'), s($USER->yahoo), array('class' => 'yahoo')
            );
        }

        if ($this->validate_display('aim')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('aim', 'block_qmul_myprofile'), s($USER->aim), array('class' => 'aim')
            );
        }

        if ($this->validate_display('msn')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('msn', 'block_qmul_myprofile'), s($USER->msn), array('class' => 'msn')
            );
        }

        if ($this->validate_display('phone1')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('phone'), s($USER->phone1), array('class' => 'phone1')
            );
        }

        if ($this->validate_display('phone2')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('phone'), s($USER->phone2), array('class' => 'phone2')
            );
        }

        if ($this->validate_display('institution')) {
            $this->content->text .= $renderer->render_label_data_element(
                    NULL, format_string($USER->institution), array('class' => 'institution')
            );
        }

        if ($this->validate_display('address')) {
            $this->content->text .= $renderer->render_label_data_element(
                    NULL, format_string($USER->address), array('class' => 'address')
            );
        }

        if ($this->validate_display('firstaccess')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('firstaccess'), userdate($USER->firstaccess), array('class' => 'firstaccess')
            );
        }

        if ($this->validate_display('lastaccess')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('lastaccess'), userdate($USER->lastaccess), array('class' => 'lastaccess')
            );
        }

        if ($this->validate_display('currentlogin')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('currentlogin', 'block_qmul_myprofile'), userdate($USER->currentlogin), array('class' => 'currentlogin')
            );
        }

        if ($this->validate_display('lastip')) {
            $this->content->text .= $renderer->render_label_data_element(
                    get_string('lastip', 'block_qmul_myprofile'), $USER->lastip, array('class' => 'lastip')
            );
        }

        if (!empty($this->config->display_smartlink)) {
            $this->content->text .= $renderer->render_smart_link();
        }
        
        return $this->content;
    }

    /**
     * Returns true if we should display the property $name, else returns false.
     * @param unknown_type $name
     */
    private function validate_display($name)
    {
        global $USER;
        
        $result = false;
        
        $property = "display_{$name}";
        
        // If the property name is mentioned in the configuration...
        if(!empty($this->config->$property)) {
            $display = $this->config->$property;
        
            if($display == 1) {
                // Does the $USER object possess this property.
                if(!empty($USER->$name)) {
                    $value = $USER->$name;
                    
                    // We display the property if its value is initialised.
                    $result = !empty($value);
                }
            }
        }
        
        return $result;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config()
    {
        return true;
    }

    /**
     * allow more than one instance of the block on a page
     *
     * @return boolean
     */
    public function instance_allow_multiple()
    {
        //allow more than one instance on a page
        return false;
    }

    /**
     * allow instances to have their own configuration
     *
     * @return boolean
     */
    function instance_allow_config()
    {
        //allow instances to have their own configuration
        return false;
    }

    /**
     * displays instance configuration form
     *
     * @return boolean
     */
    function instance_config_print()
    {
        return false;

        /*
          global $CFG;

          $form = new block_myprofile.phpConfigForm(null, array($this->config));
          $form->display();

          return true;
         */
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats()
    {
        return array('all' => true);
    }

    /**
     * post install configurations
     *
     */
    public function after_install()
    {

    }

    /**
     * post delete configurations
     *
     */
    public function before_delete()
    {

    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked()
    {
        return (!empty($this->config->title) && parent::instance_can_be_docked());
    }

    /**
     * undocumented function
     *
     * @return string the title of this block. the title can be either the pluginname in the language file or the title value
     * which is set in the settings for this block
     * @author Gerry G Hall
     */
    public function get_title()
    {
        $title = get_config('qmul_myprofile', 'title');
        if (empty($title)) {
            $title = get_string('pluginname', 'block_qmul_myprofile');
        }
        return $title;
    }

    /**
     * if global setting username is set that we hide the header
     *
     * @return void
     * @author Gerry G Hall
     */
    public function hide_header()
    {
        return (bool) get_config('qmul_myprofile', 'use_name');
    }

}
