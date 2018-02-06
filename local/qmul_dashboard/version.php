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
 * @subpackage qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$plugin->version  = 2017021400;
$plugin->requires = 2010112400;
$plugin->maturity = MATURITY_STABLE;
$plugin->component = 'local_qmul_dashboard'; // Full name of the plugin (used for diagnostics)

/*
 * The dependencies are used for building custom view permissions based on course categories (schools).
 * The plugins must be present but do not need to be enabled
 */
$plugin->dependencies = array(
    'plagiarism_turnitin' => ANY_VERSION
 );