<?php
/**
 * Tasks settings
 * reference https://docs.moodle.org/dev/Task_API
 *
 * @package    local
 * @subpackage qmul_facebook
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'report_qmplus\task\newFiles',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '12',
        'day' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'report_qmplus\task\newSettings',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '13',
        'day' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'report_qmplus\task\newBlacklisted',
        'blocking' => 0,
        'minute' => '1',
        'hour' => '7',
        'day' => '*',
        'month' => '*'
    )
);