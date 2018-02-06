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
 * Landing page support functions
 *
 * @package   block_landingpage
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_landingpage;

use coursecat;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

class landingpage {
    const FIELDNAME = 'landingpage';

    private $field = null;
    private $userschools = [];

    /**
     * @param bool $newinstance
     * @return landingpage
     */
    public static function instance($newinstance = false) {
        static $instance = null;
        if (!$instance || $newinstance) {
            $instance = new landingpage();
        }
        return $instance;
    }

    private function __construct() {
    }

    /**
     * Get the landing page for the current user.
     * @param object $user - normally the $USER global, including ->profile array
     * @param bool $ignorechoice - used on the 'choice' page to redirect (if needed) without infinite loop
     * @return moodle_url
     */
    public function get_landing_page($user, $ignorechoice = false, $forced = false) {
        if (!$forced && has_capability('block/landingpage:ignoreredirect', \context_system::instance(), null, false)) {
            return null;
        }

        // Get current configured page.
        if ($school = $this->get_saved_school($user)) {
            if (str_replace(' ', '', $school) !== '') {
                return $this->url_from_school($school);
            }
        }

        // If none configured, find out which school(s) they are in.
        $myschools = $this->get_user_schools($user->id);

        // If just one - redirect there.
        if (count($myschools) == 1) {
            reset($myschools);
            $school = key($myschools);
            if (str_replace(' ', '', $school) !== '') {
                return $this->url_from_school($school);
            }
        }

        // If more than one - redirect to choice page.
        if (count($myschools) > 1) {
            if ($ignorechoice) {
                return null;
            }
            return $this->get_choice_url();
        }

        // If none - redirect to default page.
        return $this->get_default_url();
    }

    public function get_choice_url() {
        return new moodle_url('/blocks/landingpage/choice.php');
    }

    public function get_default_url() {
        $default = trim(get_config('block_landingpage', 'default'));
        if (!$default) {
            return new moodle_url('/', array('redirect' => 0)); // If not configured, just send to the home page.
        }
        return new moodle_url($default);
    }

    /**
     * Convert a school into a URL to visit.
     * @param string $school
     * @return moodle_url
     */
    private function url_from_school($school) {
        return new moodle_url('/course/view.php', ['idnumber' => $school]);
    }

    private function get_profile_field() {
        global $DB;
        if ($this->field === null) {
            $this->field = $DB->get_record('user_info_field', ['shortname' => self::FIELDNAME]);
        }
        return $this->field;
    }

    public function get_saved_school($user) {
        if (!empty($user->profile[self::FIELDNAME])) {
            return $user->profile[self::FIELDNAME];
        }
        return null;
    }

    public function set_saved_school($user, $school) {
        global $DB;
        if (!$field = $this->get_profile_field()) {
            return;
        }
        if ($existing = $DB->get_record('user_info_data', ['fieldid' => $field->id, 'userid' => $user->id], 'id, data')) {
            $existing->data = $school;
            $DB->update_record('user_info_data', $existing);
        } else {
            $ins = (object)[
                'fieldid' => $field->id,
                'userid' => $user->id,
                'data' => $school,
            ];
            $DB->insert_record('user_info_data', $ins);
        }
        if (isset($user->profile)) {
            $user->profile[self::FIELDNAME] = $school;
        }
    }

    private function get_landingpage_idnumbers() {
        $field = $this->get_profile_field();
        $idnumbers = explode("\n", $field->param1);
        $idnumbers = array_map('trim', $idnumbers);
        return array_filter($idnumbers);
    }

    /**
     * Get a list of all course categories that contain school landing pages.
     * @return coursecat[] school (idnumber) => category, sorted with highest 'depth' values first
     */
    private function get_school_categories() {
        global $DB, $CFG;
        require_once($CFG->libdir.'/coursecatlib.php');
        $idnumbers = $this->get_landingpage_idnumbers();
        if (!$idnumbers) {
            return [];
        }
        list($csql, $params) = $DB->get_in_or_equal($idnumbers, SQL_PARAMS_NAMED);
        $select = "idnumber $csql";
        $catids = $DB->get_records_select_menu('course', $select, $params, '', 'idnumber, category');

        $cats = \coursecat::get_many($catids);
        $ret = [];
        foreach ($catids as $idnumber => $catid) {
            if (isset($cats[$catid])) {
                $ret[$idnumber] = $cats[$catid];
            }
        }

        // Sort, so highest 'depth' values come first in the list.
        uasort($ret, function (coursecat $a, coursecat $b) {
            if ($a->depth > $b->depth) {
                return -1;
            }
            if ($a->depth < $b->depth) {
                return 1;
            }
            return 0;
        });
        return $ret;
    }

    /**
     * Get a list of all categories within which the user is enrolled in courses.
     * @param int $userid
     * @return coursecat[]
     */
    private function get_user_categories($userid) {
        global $CFG;
        require_once($CFG->libdir.'/coursecatlib.php');
        $courses = enrol_get_all_users_courses($userid);
        if (!$courses) {
            return [];
        }
        $catids = [];
        foreach ($courses as $course) {
            $catids[$course->category] = $course->category;
        }
        return \coursecat::get_many($catids);
    }

    /**
     * @param coursecat $cat
     * @param coursecat[] $schoolcats school => category
     * @return string|null school id
     */
    private function get_school_from_category(coursecat $cat, $schoolcats) {
        $parentids = $cat->get_parents();
        array_push($parentids, $cat->id); // Include current category in the search.
        $parentids = array_reverse($parentids);
        foreach ($parentids as $parentid) {
            foreach ($schoolcats as $school => $schoolcat) {
                if ($schoolcat->id == $parentid) {
                    return $school;
                }
            }
        }
        return null;
    }

    /**
     * @param string[] $schools idnumber => idnumber
     * @return string[] idnumber => display name
     */
    private function add_school_names($schools) {
        global $DB;
        if (!$schools) {
            return [];
        }
        list($csql, $params) = $DB->get_in_or_equal(array_keys($schools));
        return $DB->get_records_select_menu('course', "idnumber $csql", $params, 'shortname', 'idnumber, shortname');
    }

    private function get_user_schools_internal($userid) {
        $schoolcats = $this->get_school_categories();
        $usercats = $this->get_user_categories($userid);

        if (!$schoolcats || !$usercats) {
            return [];
        }

        $userschools = [];
        foreach ($usercats as $cat) {
            // Look for the first matching school category - they are sorted by depth so first match will
            // be the furthest down the category tree.
            if ($school = $this->get_school_from_category($cat, $schoolcats)) {
                $userschools[$school] = $school;
            }
        }

        // Sort returned values alphabetically.
        uasort($userschools, function ($a, $b) use ($schoolcats) {
            $sorta = $schoolcats[$a]->sortorder;
            $sortb = $schoolcats[$b]->sortorder;
            if ($sorta < $sortb) {
                return -1;
            }
            if ($sorta > $sortb) {
                return 1;
            }
            return 0;
        });

        return $this->add_school_names($userschools);
    }

    /**
     * @param int $userid
     * @return string[] list of schools (course idnumbers) the user is enrolled in
     */
    public function get_user_schools($userid) {
        if (!array_key_exists($userid, $this->userschools)) {
            $this->userschools[$userid] = $this->get_user_schools_internal($userid);
        }
        return $this->userschools[$userid];
    }

    /**
     * @return string[] list of all available schools ($idnumber => $idnumber)
     */
    public function get_all_schools() {
        $schoolcats = $this->get_school_categories();
        $ret = array_keys($schoolcats);
        $ret = array_combine($ret, $ret);

        uasort($ret, function ($a, $b) use ($schoolcats) {
            $sorta = $schoolcats[$a]->sortorder;
            $sortb = $schoolcats[$b]->sortorder;
            if ($sorta < $sortb) {
                return -1;
            }
            if ($sorta > $sortb) {
                return 1;
            }
            return 0;
        });
        return $this->add_school_names($ret);
    }
}