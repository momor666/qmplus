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
 * Show all todolist items
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $PAGE, $USER;

$url = new moodle_url('/local/activitytodo');
$PAGE->set_url($url);
require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

$todolist = new \local_activitytodo\todolist($USER);
$items = $todolist->get_items(); // Does a capability check internally.
$reportitems = \local_activitytodo\report_todo_item::wrap_items($items);

/** @var local_activitytodo_renderer|core_renderer $output */
$output = $PAGE->get_renderer('local_activitytodo');

$title = get_string('viewtodo', 'local_activitytodo');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->requires->jquery_plugin('ui-css');

echo $output->header();
echo $output->heading($title);
echo $output->items($reportitems);
echo $output->footer();
