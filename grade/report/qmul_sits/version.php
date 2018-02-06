<?php
/**
 * Version details for the qmul_sits report
 *
 * @package    gradereport_qmul_sits
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2015031900;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2013110500;        // Requires this Moodle version
$plugin->component = 'gradereport_qmul_sits'; // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array(
        'local_qmul_sync' => 2015031200
);
