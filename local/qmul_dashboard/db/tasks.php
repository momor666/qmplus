<?php
/**
 * Tasks settings
 * reference https://docs.moodle.org/dev/Task_API
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_qmul_dashboard\task\regradeFinalGrades',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '*/6',
        'day' => '*',
        'month' => '*'
    )
);