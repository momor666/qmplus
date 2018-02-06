<?php

// This file is part of Moodle - http://moodle.org/

/**
 * local_qmul_system_role_sync plugin version specification.
 * Lib functions (for cron) to automatically assign JP Students role on the system context
 *
 * @package    local
 * @subpackage qmul_system_role_sync
 * @copyright  Greg Pasciak/QMUL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$plugin->version = 2016011300;
$plugin->requires = 2010112400;
$plugin->cron = 86400;  //once a daytudents system enrolment (CLI)';
$plugin->component = 'local_qmul_system_role_sync';
