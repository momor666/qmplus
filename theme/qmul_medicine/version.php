<?php
// This file is part of The Bootstrap Moodle theme
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
 * Theme version info
 *
 * @package    theme_qmul
 * @copyright 2016 Andrew Davidson, Synergy Learning
 * @author    Andrew Davidson,  Based on Bootstrap by Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2017042801;
$plugin->requires  = 2016120501;
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 2017042800;
$plugin->component = 'theme_qmul_medicine';
$plugin->dependencies = array('theme_qmul' => 2017042801, 'format_qmultopics'=>2017051900);
