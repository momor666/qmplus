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
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_qmul
 * @copyright 2016 Andrew Davidson, Synergy Learning
 * @author    Andrew Davidson,  Based on Bootstrap by Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function theme_qmul_medicine_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $theme = theme_config::load('qmul');
        theme_qmul_store_in_localcache($filearea, $args, $options);
        exit;
    } else {
        send_file_not_found();
    }
}

function theme_qmul_medicine_page_init(moodle_page $page) {
    $parentsettings = get_config('theme_qmul');
    $page->theme->settings = $parentsettings;
    theme_qmul_page_init($page);
}