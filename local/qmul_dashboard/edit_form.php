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
 * Form for generating view settings for dashboard
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once("$CFG->libdir/formslib.php");

/**
 * class for displaying edit form.
 *
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 */
class local_qmul_dashboard_edit_form extends moodleform
{

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition()
    {
        $mform =& $this->_form;

        $mform->addElement('html', '<h2>'.get_string('settingstitle', 'local_qmul_dashboard').'</h2>');
        $mform->addElement('html', '<p>'.get_string('settingsdesc', 'local_qmul_dashboard').'</p>');

        // Get Users Schools
        $mform->addElement('select', 'category', get_string('school', 'local_qmul_dashboard'),
            local_qmul_dashboard_getUserSchools(), array('id'=>'course')
        );

        $radioarray = array();
        $attributes = array();
        $radioarray[] =& $mform->createElement(
            'radio',
            'activities',
            '',
            get_string('activitiespanel', 'local_qmul_dashboard'),
            'activities_panel',
            $attributes
        );
        $radioarray[] =& $mform->createElement(
            'radio',
            'activities',
            '',
            get_string('activitieshistogram', 'local_qmul_dashboard'),
            'activities_histograms',
            $attributes
        );


        $mform->addGroup($radioarray, 'activities_summary',   get_string('activitiessummary', 'local_qmul_dashboard'), array(' '), false);
//        $mform->addElement('checkbox', 'facebook', get_string('facebook', 'local_qmul_dashboard'));
//        $mform->addHelpButton('facebook', 'facebookhelp', 'local_qmul_dashboard');

        // Get All Plugins
        // $plugininfo = core_plugin_manager::instance()->get_plugin_info('local_qmul_dashboard');

        //$pluginmanager = \core_plugin_manager::instance();
        //$enabled = $pluginmanager->get_enabled_plugins('local_qmul_dashboard');

        $mform->addElement('submit', 'submit', get_string('editbutton', 'local_qmul_dashboard'), array('id'=>'submit'));

    }
}
