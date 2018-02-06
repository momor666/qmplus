<?php
/**
* Block Course Reports
*
* @copyright &copy; 2011 University of London Computer Centre
* @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License

*/

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/course_reports:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_PROHIBIT
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/course_reports:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

);