<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 26/06/2017
 * Time: 10:40
 * QM+ Activities reporting plugin
 * Author: v.sotiras@qmul.ac.uk Vasileios Sotiras
 */

/**
 * Cron definition for this plugin is not required
 */
function local_qm_activities_cron(){
    /**
     * No need for this version to have background tasks
     */
    return true;
}

/**
 * @param \global_navigation $navigation
 *
 * Add a navigation link for the activities reporter
 */
// function local_qm_activities_extend_navigation(global_navigation $navigation ){
function local_qm_activities_extend_navigation( $navigation ){
    /**
     * Add a menu selection for accessing the reporting functioanlity
     */
    $nodeCalActivities = $navigation->add('Calendar Activities' , new moodle_url('/local/qm_activities/index.php',array()) , null, 'Calendar Activities' , 0 );
}

/**
 * @param $settingsnav
 * @param $context
 *
 * Add a settings navigation for an administration page
 */
function local_qm_activities_extend_settings_navigation($settingsnav , $context){
    /**
     * Add a settings menu selection to be able to configure this plugin
     */
}

define('mydebg', false);
if(mydebg == true ){
    ini_set('display_errors',1);
    error_reporting(E_ALL);
}
/**
 * @return string of locale default date format
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */


function local_qm_activities_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course){

    //add links to report category
    $url = new moodle_url('/local/qm_activities/index.php');
    $linktext = get_string('profilename', 'local_qm_activities');
    $node = new core_user\output\myprofile\node('reports', 'activities', $linktext, null, $url);
    $tree->add_node($node);

    return true;
}

