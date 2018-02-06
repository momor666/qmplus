<?php
/**
 * Current course mappings info block.
 *
 * @package    block
 * @subpackage qmul_course_mappings
 * @copyright  2015 Queen Mary, University of London
 * @author     Phil Lello <phil@dunlop-lello.uk>
 */
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2015070300;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires = 2013111802;        // Requires this Moodle version
$plugin->component = 'block_qmul_course_mappings'; // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array(
    'local_qmul_sync' => 2015070100,
);
