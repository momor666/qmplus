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
 * Quiz settings
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/local/qmframework/lib.php');

if ($hassiteconfig) {
    $quizsettings = get_string('skillsauditsettings', 'local_qmframework');
    $settings = new admin_settingpage('local_qmframework_quizsettings', $quizsettings);

    // Get defined configuration.
    $config = (array) get_config('local_qmframework');

    // The QM course quizes.
    $qmquizzes = local_qmframework_quizzes();
    if (!empty($qmquizzes)) {
        $quizzes = [];
        $quizzes[null] = get_string('selectaquiz', 'local_qmframework');
        foreach ($qmquizzes as $quiz) {
            $quizzes[$quiz->id] = $quiz->name;
        }

        // The main quiz id.
        $settings->add(new admin_setting_configselect(
            'local_qmframework/local_qmframework_quizid',
            get_string('skillsaudit', 'local_qmframework'),
            get_string('skillsaudit_help', 'local_qmframework'),
            null,
            $quizzes
        ));
    }

    // The main quiz question categories.
    $maincategories = local_qmframework_quiz_categories();
    if (!empty($maincategories)) {
        $mains = [];
        $mains[null] = get_string('selectasubcategory', 'local_qmframework');
        foreach ($maincategories as $category) {
            $mains[$category->id] = $category->name;
        }

        // The maincategory ID.
        $maincategoryhelp = '';
        if (empty($config['local_qmframework_maincategory'])) {
            $maincategoryhelp .= get_string('maincategory_note', 'local_qmframework');
        }
        $settings->add(new admin_setting_configselect(
            'local_qmframework/local_qmframework_maincategory',
            get_string('maincategory', 'local_qmframework'),
            $maincategoryhelp,
            null,
            $mains
        ));
    }

    if (!empty($config['local_qmframework_maincategory'])) {
        $categories = local_qmframework_quiz_categories($config['local_qmframework_maincategory']);
        if (!empty($categories)) {
            $themes = [];
            $themes[null] = get_string('selectasubcategory', 'local_qmframework');
            foreach ($categories as $category) {
                $themes[$category->id] = $category->name;
            }

            for ($i = 1; $i <= 4; $i++) {
                $settings->add(new admin_setting_heading("theme{$i}heading", get_string("theme{$i}", 'local_qmframework'), null));

                if (!empty($config["local_qmframework_theme{$i}"])) {
                    $settings->add(new admin_setting_configselect(
                        "local_qmframework/local_qmframework_theme{$i}",
                        get_string('thememaincategory', 'local_qmframework'),
                        null,
                        null,
                        $themes
                    ));

                    // The theme category's children.
                    $subcategories = local_qmframework_quiz_categories($config["local_qmframework_theme{$i}"]);
                    if (!empty($subcategories)) {
                        $stages = [];
                        $stages[null] = get_string('selectasubcategory', 'local_qmframework');
                        foreach ($subcategories as $category) {
                            $stages[$category->id] = $category->name;
                        }

                        // Stages.
                        for ($j = 1; $j <= 4; $j++) {
                            $settings->add(new admin_setting_configselect(
                                "local_qmframework/local_qmframework_theme{$i}stage{$j}",
                                get_string("stage{$j}", 'local_qmframework'),
                                null,
                                null,
                                $stages
                            ));
                        }
                    }
                } else {
                    $settings->add(new admin_setting_configselect(
                        "local_qmframework/local_qmframework_theme{$i}",
                        get_string("thememaincategory", 'local_qmframework'),
                        get_string("thememaincaetgory_help", 'local_qmframework'),
                        null,
                        $themes
                    ));
                }
            }
        }
    }

    $ADMIN->add('local_qmframework', $settings);
}
