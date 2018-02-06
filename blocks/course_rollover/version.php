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

/*File:       block/module_info/version.php

Purpose:    This file holds version information for the plugin,
            along with other advanced parameters like object field
            definitions that denote the version number of the block,
            and the minimum version of Moodle that must be
            installed in order to use this plugin.
		    These parameters are used during the plugin installation
			and upgrade process to make sure that the
			plugin is compatible with the given Moodle site, as well
			as spotting whether an upgrade is needed.

-****************************************************************/

/**
 * Course Template block version.
 *
 * @package      blocks
 * @subpackage   course_rollover
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct script access.
defined('MOODLE_INTERNAL') || die();

$plugin->version   	= 2017080301;                // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  	= 2011112900;                // Requires this Moodle version.
$plugin->component 	= 'block_course_rollover';   // Full name of the plugin (used for diagnostics).
$plugin->dependencies = array(
    'local_qmul_sync' => 2015020900,
);
