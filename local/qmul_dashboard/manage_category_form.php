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
 * Form for generating Gradesplus histogram visibilty settings
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2016 Queen Mary University of London
 * @author     Damian Hippisley  <d.j.hippisley@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once("$CFG->libdir/formslib.php");
require_once($CFG->libdir.'/coursecatlib.php');


/**
 * class for displaying edit form.
 *
 * @copyright  2016 Queen Mary University of London
 * @author     Damian Hippisley  <d.j.hippisley@qmul.ac.uk>
 */
class local_qmul_dashboard_manage_category_form extends moodleform
{

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition()
    {
        $mform =& $this->_form;
        
        $mform->addElement('html', '<h2>'.get_string('manageSettingsHeaderTitle', 'local_qmul_dashboard').'</h2>');
        $mform->addElement('html', '<p class="managesettingsheaderdesc">'.get_string('manageSettingsHeaderDesc', 'local_qmul_dashboard').'</p>');    

        
        $categories = coursecat::make_categories_list('moodle/category:manage');

        // Get Users Schools
        $mform->addElement('select', 'category', get_string('school', 'local_qmul_dashboard'),
            $categories, array('id'=>'course')
        );

        $mform->addElement('hidden', 'categoryid', $this->_customdata['catid']);
        $mform->setType('categoryid', PARAM_INT);                   //Set type of element


        $mform->addElement('submit', 'submit', get_string('manageSettingsHideButton', 'local_qmul_dashboard'), array('id'=>'submit'));

    }
}
