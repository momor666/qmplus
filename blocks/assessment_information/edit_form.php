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
 * Version details.
 *
 * @package    blocks
 * @subpackage assessment_information
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_assessment_information_edit_form extends block_edit_form {
    function __construct($actionurl, $block, $page) {
        if(!is_array($block->config->subheadings_title)) {
            $block->config->subheadings_title = array($block->config->subheadings_title);
            $block->config->subheadings_background = array($block->config->subheadings_background);
            $block->config->subheadings_text = array($block->config->subheadings_text);
        }
        parent::__construct($actionurl, $block, $page);
    }
    protected function specific_definition($mform) {
        global $PAGE;

        //configure section titles
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text','config_assessment_header', get_string(
            'config_assessment_header', 'block_assessment_information'
        ));
        $mform->setType('config_assessment_header', PARAM_TEXT);
        $mform->addRule('config_assessment_header',null,'required',null,'client');
        $mform->setDefault('config_assessment_header',get_string(
            'default_assessment_header', 'block_assessment_information'
        ));

        $mform->addElement('text','config_assignment_header', get_string(
            'config_assignment_header', 'block_assessment_information'
        ));
        $mform->setType('config_assignment_header', PARAM_TEXT);
        $mform->addRule('config_assignment_header',null,'required',null,'client');
        $mform->setDefault('config_assignment_header', get_string(
            'default_assignment_header', 'block_assessment_information'
        ));

        $mform->addElement('editor', 'config_assignment_text',
            get_string('config_assignment_text','block_assessment_information'));
        $mform->setType('config_assignment_text', PARAM_TEXT);

        $mform->addElement('advcheckbox','config_enable_extra_section', get_string(
            'config_enable_extra_section', 'block_assessment_information'
        ));

        $mform->addElement('text','config_extra_section_header', get_string(
            'config_extra_section_header', 'block_assessment_information'
        ));
        $mform->setType('config_extra_section_header', PARAM_TEXT);
        $mform->setDefault('config_extra_section_header', get_string(
            'default_extra_section_header', 'block_assessment_information'
        ));
        $mform->disabledIf('config_extra_section_header','config_enable_extra_section',
            'notchecked');
        //configure subheadings

        //additional roles field
        $additional_subheadings = array();
        $additional_subheadings[] = $mform->createElement('header', 'config_subheadings', get_string('configure_subheadings', 'block_assessment_information').' {no}');
        $additional_subheadings[] = $mform->createElement('text', 'config_subheadings_title',
            get_string('config_subheadings_title', 'block_assessment_information'));
        $mform->setType('config_subheadings_title', PARAM_TEXT);
        //subheading title bg color
        $colors = array(''=>'None');

        $config = get_config('block_assessment_information');
        $allowedcolors = explode(';',$config->allowedcolors);
        foreach ($allowedcolors as $color) {
            $color = trim($color);
            if(preg_match('/^#[a-f0-9]{6}$/i', $color)){
                $colors[$color] = $color;
            }
        }
        $additional_subheadings[] = $mform->createElement('select', 'config_subheadings_background', get_string(
            'config_subheadings_background','block_assessment_information'),
            $colors
        );
        $mform->setType('config_subheadings_background', PARAM_TEXT);
        //subheadings text
        $additional_subheadings[] = $mform->createElement('editor', 'config_subheadings_text',
                get_string('config_subheadings_text','block_assessment_information'));
        $mform->setType('config_subheadings_text', PARAM_TEXT);
        $subheadingnos = 1;
        if(!empty($this->block->config->subheadings_title)) {
            $subheadingnos = sizeof($this->block->config->subheadings_title);
            $subheadingnos += 1;
        }
        $repeateloptions = array();
        $repeateloptions['config_subheadings']['expanded'] = true;
        $this->repeat_elements($additional_subheadings, $subheadingnos,
            $repeateloptions, 'subheading_repeats', 'option_subheading_add_fields', 1, get_string('config_additional_subheading_add_string', 'block_assessment_information'), false);
    }
    /**
     * Handle submitted data
     *
     * Return submitted data if properly submitted
     * or returns NULL if validation fails
     * or if there is no submitted data
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        global $COURSE;
        $data = parent::get_data();
        //$data can be null when form is submitted with other buttons than submit like "Add 1 additional teaching session"
        if($this->_form->isSubmitted() && $data != NULL) {
            // Any empty additional subheadings need to be removed
            $subheadings = $data->config_subheadings_title;

            foreach($subheadings as $key=>$value) {
                if(strlen($value) == 0 || $value == NULL) {
                    unset($data->config_subheadings_title[$key]);
                    unset($data->config_subheadings_background[$key]);
                    unset($data->config_subheadings_text[$key]);
                }
            }
            $data->config_subheadings_title = array_values($data->config_subheadings_title);
            $data->config_subheadings_background = array_values($data->config_subheadings_background);
            $data->config_subheadings_text = array_values($data->config_subheadings_text);
            return $data;
        }
    }
}