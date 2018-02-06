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
 *
 * @package    local
 * @subpackage qmcw_coversheet
 * @copyright  2017 Queen Mary University of London
 * @author     Damian Hippisley <d.j.hippisley@qmul.ac.uk>
 * @author     Vasileos Sotiras <v.sotiras@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/navigationlib.php');
require_once( __DIR__ . '/locallib.php');

function local_qmcw_coversheet_extend_settings_navigation(settings_navigation $navigation, context $context) {

    if(!has_capability('local/qmcw_coversheet:scan', $context)){
        return false;
    }



    $label = get_string('adminscanform', 'local_qmcw_coversheet');
    $action = new moodle_url('/local/qmcw_coversheet/scan.php');

    if($rootnode = $navigation->find('root', navigation_node::TYPE_ROOTNODE)){
        $rootnode->add( $label , $action, navigation_node::NODETYPE_LEAF,
            get_string('shorttxtmenunode', 'local_qmcw_coversheet'),
            get_string('scanformkey', 'local_qmcw_coversheet'));
    }

    if($rootnode = $navigation->find('modulesettings', navigation_node::TYPE_SETTING)){
        $rootnode->add( $label , $action, navigation_node::NODETYPE_LEAF,
            get_string('shorttxtmenunode', 'local_qmcw_coversheet'),
            get_string('scanformkey', 'local_qmcw_coversheet'));
    }


}

/**
 * @param \core_user\output\myprofile\tree $tree
 * @param $user
 * @param $iscurrentuser
 * @param $course
 * @return bool
 */
function local_qmcw_coversheet_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course){

    if(!$iscurrentuser){
        return false;
    }

    if(!local_qmcw_coversheet_is_admin_or_courseadmin($user->id)){
        return false;
    }

    //add category for this module
    $title = get_string('userprofilecategory', 'local_qmcw_coversheet');
    $category = new core_user\output\myprofile\category('cwmanagement', $title);
    $tree->add_category($category);

    //add links to category
    $url = new moodle_url('/local/qmcw_coversheet/scan.php');
    $linktext = get_string('userprofilelinktext', 'local_qmcw_coversheet');
    $node = new core_user\output\myprofile\node('cwmanagement', 'scan', $linktext, null, $url);
    $tree->add_node($node);

    return true;

}





