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
 * Help with the fake settings
 *
 * @package   theme_qmul
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_qmul;

defined('MOODLE_INTERNAL') || die();

class settings_helper {
    /**
     * @return \admin_root
     */
    public static function get_fake_admin_root() {
        global $CFG, $DB; // $DB needed by some settings pages.

        require_once($CFG->libdir.'/adminlib.php');
        $ADMIN = new \admin_root(true);
        $ADMIN->add('root', new \admin_category('themes', get_string('themes')));
        require($CFG->dirroot.'/theme/qmul/settings.php');

        return $ADMIN;
    }

    public static function output_pages_list(\admin_category $category) {
        $out = '';
        foreach ($category->get_children() as $child) {
            if ($child instanceof \admin_category) {
                $out .= self::output_pages_list($child);
            } else if ($child instanceof \admin_settingpage) {
                $out .= self::output_settingpage_link($child);
            }
        }
        return $out;
    }

    /**
     * Adapted from admin/settings.php + admin_write_settings()
     * @param \admin_root $fakeadminroot
     */
    public static function process_settings(\admin_root $fakeadminroot) {
        global $CFG, $PAGE;

        if (($formdata = data_submitted()) && confirm_sesskey()) {
            $olddbsessions = !empty($CFG->dbsessions);
            $formdata = (array)$formdata;
            $adminroot = admin_get_root();

            $data = [];
            foreach ($formdata as $fullname => $value) {
                if (strpos($fullname, 's_') !== 0) {
                    continue; // not a config value
                }
                $data[$fullname] = $value;
            }
            $settings = admin_find_write_settings($fakeadminroot, $data);

            $count = 0;
            foreach ($settings as $fullname => $setting) {
                /** @var $setting \admin_setting */
                $original = $setting->get_setting();
                $error = $setting->write_setting($data[$fullname]);
                if ($error !== '') {
                    // Save errors into the real adminroot, as that is where the output code looks for them.
                    $adminroot->errors[$fullname] = (object)[
                        'data' => $data[$fullname],
                        'id' => $setting->get_id(),
                        'error' => $error,
                    ];
                } else {
                    $setting->write_setting_flags($data);
                }
                if ($setting->post_write_settings($original)) {
                    $count++;
                }
            }

            if ($olddbsessions != !empty($CFG->dbsessions)) {
                require_logout();
            }

            // Reload all admin settings.
            admin_get_root(true);

            // No changes + no errors => go back to the main 'fake settings' page.
            if (!$count && !$adminroot->errors) {
                $redir = new \moodle_url($PAGE->url);
                $redir->remove_params('section');
                redirect($redir);
            }

            return (bool)$count;
        }
        return false;
    }

    private static function output_settingpage_link(\admin_settingpage $extpage) {
        $url = new \moodle_url('/theme/qmul/fakesettings.php', ['section' => $extpage->name]);
        $link = \html_writer::link($url, $extpage->visiblename);
        return \html_writer::div($link);
    }
}