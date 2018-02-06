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
 * Created On 6 Sep 2012
 * @package   course_backup.php
 * @copyright 2012 ghxx2574 gerryghall@googlemail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Create a course backup via CLI
 * this is an entry point that allows us to have a alternative config.php 
 * @package    local
 * @subpackage course_backup7day
 * @copyright  2006 Gerry G Hall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('QMULPCB', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require 'course_backup.php';