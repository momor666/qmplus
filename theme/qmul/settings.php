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
 * This is built using the Clean template to allow for new theme's using
 * Moodle's new Bootstrap theme engine
 *
 *
 * @package   theme_qmul
 * @copyright 2016 Andrew Davidson, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$settings = null;

defined('MOODLE_INTERNAL') || die;

if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}

    require($CFG->dirroot.'/theme/qmul/settings/generic.php');
    require($CFG->dirroot.'/theme/qmul/settings/frontpagecontent.php');
    require($CFG->dirroot.'/theme/qmul/settings/alerts.php');
    require($CFG->dirroot.'/theme/qmul/settings/analytics.php');
    require($CFG->dirroot.'/theme/qmul/settings/news.php');

$ADMIN->add('theme_qmul', new admin_externalpage('theme_qmul_docs', get_string('documentation', 'theme_qmul'),
        $CFG->wwwroot.'/theme/qmul/docs'));