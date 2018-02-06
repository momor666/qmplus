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
 * Local qmframework main library functionality file
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/setuplib.php');
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Get list of all the mahara MNet hosts.
 *
 * @return array
 */
function local_qmframework_mahara_mnet_hosts() {
    global $DB;

    $sql = "SELECT h.id, h.name as host_name, h.wwwroot as url
              FROM {mnet_host} h
              JOIN {mnet_application} a ON h.applicationid = a.id
             WHERE h.deleted <> 1 AND a.name = ? ";

    return $DB->get_records_sql($sql, ['mahara']);
}

/**
 * Get mahara MNet host details as per the saved configuration.
 *
 * @param  integer $id MNET host ID
 * @return object
 */
function local_qmframework_mahara_mnet_host($id) {
    global $DB;

    return $DB->get_record_select('mnet_host', 'id = :host', ['host' => $id], '*', MUST_EXIST);
}

/**
 * Get moodle user details required to sync the account to
 * the QM Model Framework Mahara host.
 *
 * @param  integer $id user ID
 * @return object
 */
function local_qmframework_user_details($id) {
    global $DB;

    $select = 'id AS userid, firstname, lastname, username, email';
    return $DB->get_record_select('user', 'id = :user', ['user' => $id], $select);
}

/**
 * Get moodle group details.
 *
 * @param  integer $id group ID
 * @return object
 */
function local_qmframework_group_details($id) {
    global $DB;

    $select = 'id, name, description, idnumber, courseid';
    $group = $DB->get_record_select('groups', 'id = :group', ['group' => $id], $select);
    if ($group) {
        if (empty($group->idnumber) || !$group->idnumber) {
            $idnumber = str_replace(' ', '', strtolower($group->name));
            $idnumber = preg_replace('/[^a-zA-Z]/', '', $idnumber);
            $group->idnumber = $idnumber;
            try {
                groups_update_group($group);
            } catch (\Exception $e) {
                throw new \invalid_parameter_exception('Could not update Moodle Group ID Number: ' . $e->getMessage());
            }
        }
        $group->idnumber = strtolower($group->idnumber); // Ensure the ID number is always lowercase.
    }

    return $group;
}

/**
 * Get moodle groups_members id.
 * @param integer $userid user ID
 * @param integer $groupid group ID
 */
function local_qmframework_membership_id($userid, $groupid) {
    global $DB;

    $params = ['groupid' => $groupid, 'userid' => $userid];
    $condition = 'groupid = :groupid AND userid = :userid';
    return (int) $DB->get_field_select('groups_members', 'id', $condition, $params);
}

/**
 * Check if user has a valid enrolment within the configured
 * course context.
 *
 * @param  integer $enrolmentid enrolment ID
 * @param  integer $userid user ID
 * @return boolean
 */
function local_qmframework_check_user_enrolment($enrolmentid, $userid) {
    global $DB;

    $sql = "SELECT e.roleid
        FROM {user_enrolments} ue
        INNER JOIN {enrol} e ON e.id = ue.enrolid
        WHERE ue.id = :enrolmentid AND ue.userid = :userid";
    $params = ['enrolmentid' => $enrolmentid, 'userid' => $userid];
    $roleid = (int) $DB->get_field_sql($sql, $params);
    $settings = local_qmframework_course_and_roles_settings();
    if ($roleid === (int) $settings['student'] || $roleid === (int) $settings['tutor']) {
        $ok = true;
    } else {
        $ok = false;
    }
    return $ok;
}

/**
 * Return a group's valid "Student" or "Tutor" members.
 *
 * @param  integer $groupid group ID
 * @return array
 */
function local_qmframework_group_valid_members($groupid) {
    global $DB;

    $sql = "SELECT gm.id AS memberid, u.id AS userid, u.firstname, u.lastname, u.username, u.email,
        (CASE  WHEN ra.roleid = :studentid THEN 'member' ELSE 'tutor' END) AS role
        FROM {groups_members} gm
        INNER JOIN {user} u ON gm.userid = u.id
        INNER JOIN {role_assignments} ra ON ra.userid = u.id
        WHERE gm.groupid = :groupid AND ra.contextid = :contextid AND (ra.roleid = :student OR ra.roleid = :tutor)";
    $settings = local_qmframework_course_and_roles_settings();
    $student = (int) $settings['student'];
    $tutor = (int) $settings['tutor'];
    $context = \context_course::instance($settings['course']);
    $params = array(
        'studentid' => $student,
        'contextid' => $context->id,
        'groupid' => $groupid,
        'student' => $student,
        'tutor' => $tutor
    );
    $members = $DB->get_records_sql($sql, $params);
    return $members;
}

/**
 * Check if user has the required "Student" or "Tutor" role
 * the configured QM Model Framework course context.
 *
 * @param  integer $contextid context ID
 * @param  integer $userid user ID
 * @return boolean
 */
function local_qmframework_user_is_student_or_tutor($contextid, $userid) {
    global $DB;

    $params = ['contextid' => $contextid, 'userid' => $userid];
    $condition = 'contextid = :contextid AND userid = :userid';
    $roleid = (int) $DB->get_field_select('role_assignments', 'roleid', $condition, $params);
    $settings = local_qmframework_course_and_roles_settings();
    if ($roleid === (int) $settings['student']) {
        $ok = 'member';
    } else if ($roleid === (int) $settings['tutor']) {
        $ok = 'tutor';
    } else {
        $ok = false;
    }
    return $ok;
}

/**
 * Check if group is in the personal tutor grouping.
 *
 * @param  integer $id group ID
 * @param  integer $id grouping ID
 * @return boolean
 */
function local_qmframework_group_in_tutor_grouping($groupid, $groupingid) {
    global $DB;

    $ok = false;
    $params = ['groupid' => $groupid, 'groupingid' => $groupingid];
    $condition = 'groupid = :groupid AND groupingid = :groupingid';
    $found = (int) $DB->get_field_select('groupings_groups', 'groupingid', $condition, $params);
    if ($found === (int) $groupingid) {
        $ok = true;
    }
    return $ok;
}

/**
 * Generate a random password string to be used as the Mahara
 * user account default.
 *
 * @return string
 */
function local_qmframework_random_password() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = [];
    $length = strlen($alphabet) - 1;
    for ($i = 0; $i < 32; $i++) {
        $n = rand(0, $length);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

/**
 * Get plugin connection settings.
 *
 * @return array
 */
function local_qmframework_get_connection_settings() {
    // Get defined configuration.
    $config = (array) get_config('local_qmframework');
    if (empty($config)) {
        // Log configs missing.
        $missingplugin = get_string('missing_pluginsettings', 'local_qmframework');
        throw new \invalid_parameter_exception($missingplugin);
    } else {
        $settings = [];
        if (empty($config['local_qmframework_host'])) {
            $missinghost = get_string('missing_hostsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinghost);
        }
        if (empty($config['local_qmframework_institution'])) {
            $missinginstitution = get_string('missing_institutionsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinginstitution);
        }
        if (empty($config['local_qmframework_authinstitution'])) {
            $missingauthinstitution = get_string('missing_authinstitutionsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingauthinstitution);
        }
        if (empty($config['local_qmframework_wstoken'])) {
            $missingtoken = get_string('missing_tokensetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingtoken);
        }
        if (empty($config['local_qmframework_authwstoken'])) {
            $missingauthtoken = get_string('missing_authtokensetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingauthtoken);
        }
        $settings['host'] = $config['local_qmframework_host'];
        $settings['institution'] = $config['local_qmframework_institution'];
        $settings['authinstitution'] = $config['local_qmframework_authinstitution'];
        $settings['token'] = $config['local_qmframework_wstoken'];
        $settings['authtoken'] = $config['local_qmframework_authwstoken'];
        return $settings;
    }
}

/**
 * Check if plugin is enabled.
 *
 * @return boolean
 */
function local_qmframework_is_plugin_enabled() {
    // Get defined configuration.
    $config = (array) get_config('local_qmframework');
    if (empty($config)) {
        // Log configs missing.
        $missingplugin = get_string('missing_pluginsettings', 'local_qmframework');
        throw new \invalid_parameter_exception($missingplugin);
    } else {
        if (!isset($config['local_qmframework_enabled'])) {
            $missingenabled = get_string('missing_enabledsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingenabled);
        }
        return (bool) $config['local_qmframework_enabled'];
    }
}

/**
 * Get plugin course and roles settings.
 *
 * @return array
 */
function local_qmframework_course_and_roles_settings() {
    // Get defined configuration.
    $config = (array) get_config('local_qmframework');
    if (empty($config)) {
        // Log configs missing.
        $missingplugin = get_string('missing_pluginsettings', 'local_qmframework');
        throw new \invalid_parameter_exception($missingplugin);
    } else {
        $settings = [];
        if (empty($config['local_qmframework_course'])) {
            $missingcourse = get_string('missing_coursesetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingcourse);
        }

        if (empty($config['local_qmframework_student'])) {
            $missingstudent = get_string('missing_studentsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingstudent);
        }
        if (empty($config['local_qmframework_tutor'])) {
            $missingtutor = get_string('missing_tutorsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingtutor);
        }
        $settings['course'] = $config['local_qmframework_course'];
        $settings['student'] = $config['local_qmframework_student'];
        $settings['tutor'] = $config['local_qmframework_tutor'];
        return $settings;
    }
}

/**
 * Get plugin course and roles settings.
 *
 * @return array
 */
function local_qmframework_course_and_group_settings() {
    // Get defined configuration.
    $config = (array) get_config('local_qmframework');
    if (empty($config)) {
        // Log configs missing.
        $missingplugin = get_string('missing_pluginsettings', 'local_qmframework');
        throw new \invalid_parameter_exception($missingplugin);
    } else {
        $settings = [];
        if (empty($config['local_qmframework_course'])) {
            $missingcourse = get_string('missing_coursesetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingcourse);
        }

        if (empty($config['local_qmframework_grouping'])) {
            $missinggrouping = get_string('missing_groupingsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinggrouping);
        }
        if (empty($config['local_qmframework_groupadmin'])) {
            $missingadmin = get_string('missing_adminsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingadmin);
        }
        $settings['course'] = $config['local_qmframework_course'];
        $settings['grouping'] = $config['local_qmframework_grouping'];
        $settings['groupadmin'] = $config['local_qmframework_groupadmin'];
        return $settings;
    }
}

/**
 * Get plugin course and quiz settings.
 *
 * @return array
 */
function local_qmframework_course_and_quiz_settings() {
    // Get defined configuration.
    $config = (array) get_config('local_qmframework');
    if (empty($config)) {
        // Log configs missing.
        $missingplugin = get_string('missing_pluginsettings', 'local_qmframework');
        throw new \invalid_parameter_exception($missingplugin);
    } else {
        $settings = [];
        if (empty($config['local_qmframework_course'])) {
            $missingcourse = get_string('missing_coursesetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingcourse);
        }

        if (empty($config['local_qmframework_quizid'])) {
            $missinggrouping = get_string('missing_quizidsetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinggrouping);
        }
        $settings['course'] = $config['local_qmframework_course'];
        $settings['quizid'] = $config['local_qmframework_quizid'];
        return $settings;
    }
}

/**
 * Get plugin main questions category, themes an their stages settings.
 *
 * @return array
 */
function local_qmframework_category_themes_stages_settings() {
    // Get defined configuration.
    $config = (array) get_config('local_qmframework');
    if (empty($config)) {
        // Log configs missing.
        $missingplugin = get_string('missing_pluginsettings', 'local_qmframework');
        throw new \invalid_parameter_exception($missingplugin);
    } else {
        $settings = [];
        if (empty($config['local_qmframework_maincategory'])) {
            $missingcourse = get_string('missing_maincategorysetting', 'local_qmframework');
            throw new \invalid_parameter_exception($missingcourse);
        }

        if (empty($config['local_qmframework_theme1'])) {
            $missinggrouping = get_string('missing_theme1setting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinggrouping);

            if (empty($config['local_qmframework_theme1stage1'])) {
                $missinggrouping = get_string('missing_theme1stage1setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme1stage2'])) {
                $missinggrouping = get_string('missing_theme1stage2setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme1stage3'])) {
                $missinggrouping = get_string('missing_theme1stage3setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme1stage4'])) {
                $missinggrouping = get_string('missing_theme1stage4setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
        }

        if (empty($config['local_qmframework_theme2'])) {
            $missinggrouping = get_string('missing_theme2setting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinggrouping);

            if (empty($config['local_qmframework_theme2stage1'])) {
                $missinggrouping = get_string('missing_theme2stage1setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme2stage2'])) {
                $missinggrouping = get_string('missing_theme2stage2setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme2stage3'])) {
                $missinggrouping = get_string('missing_theme2stage3setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme2stage4'])) {
                $missinggrouping = get_string('missing_theme2stage4setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
        }

        if (empty($config['local_qmframework_theme3'])) {
            $missinggrouping = get_string('missing_theme3setting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinggrouping);

            if (empty($config['local_qmframework_theme3stage1'])) {
                $missinggrouping = get_string('missing_theme3stage1setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme3stage2'])) {
                $missinggrouping = get_string('missing_theme3stage2setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme3stage3'])) {
                $missinggrouping = get_string('missing_theme3stage3setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme3stage4'])) {
                $missinggrouping = get_string('missing_theme3stage4setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
        }

        if (empty($config['local_qmframework_theme4'])) {
            $missinggrouping = get_string('missing_theme4setting', 'local_qmframework');
            throw new \invalid_parameter_exception($missinggrouping);

            if (empty($config['local_qmframework_theme4stage1'])) {
                $missinggrouping = get_string('missing_theme4stage1setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme4stage2'])) {
                $missinggrouping = get_string('missing_theme4stage2setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme4stage3'])) {
                $missinggrouping = get_string('missing_theme4stage3setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
            if (empty($config['local_qmframework_theme4stage4'])) {
                $missinggrouping = get_string('missing_theme4stage4setting', 'local_qmframework');
                throw new \invalid_parameter_exception($missinggrouping);
            }
        }

        $settings['maincategory'] = $config['local_qmframework_maincategory'];
        $settings['theme1'] = $config['local_qmframework_theme1'];
        $settings['theme1stage1'] = $config['local_qmframework_theme1stage1'];
        $settings['theme1stage2'] = $config['local_qmframework_theme1stage2'];
        $settings['theme1stage3'] = $config['local_qmframework_theme1stage3'];
        $settings['theme1stage4'] = $config['local_qmframework_theme1stage4'];
        $settings['theme2'] = $config['local_qmframework_theme2'];
        $settings['theme2stage1'] = $config['local_qmframework_theme2stage1'];
        $settings['theme2stage2'] = $config['local_qmframework_theme2stage2'];
        $settings['theme2stage3'] = $config['local_qmframework_theme2stage3'];
        $settings['theme2stage4'] = $config['local_qmframework_theme2stage4'];
        $settings['theme3'] = $config['local_qmframework_theme3'];
        $settings['theme3stage1'] = $config['local_qmframework_theme3stage1'];
        $settings['theme3stage2'] = $config['local_qmframework_theme3stage2'];
        $settings['theme3stage3'] = $config['local_qmframework_theme3stage3'];
        $settings['theme3stage4'] = $config['local_qmframework_theme3stage4'];
        $settings['theme4'] = $config['local_qmframework_theme4'];
        $settings['theme4stage1'] = $config['local_qmframework_theme4stage1'];
        $settings['theme4stage2'] = $config['local_qmframework_theme4stage2'];
        $settings['theme4stage3'] = $config['local_qmframework_theme4stage3'];
        $settings['theme4stage4'] = $config['local_qmframework_theme4stage4'];
        return $settings;
    }
}

/**
 * Return a list of questions IDs
 *
 * @param  integer $category questions category ID
 * @return array
 */
function local_qmframework_get_stage_questions($category) {
    global $DB;

    $params = ['category' => $category];
    $condition = 'category = :category';
    $list = $DB->get_fieldset_select('question', 'id', $condition, $params);
    return $list;
}

/**
 * Return an array containing information about the theme
 * stage categories and their ordering
 *
 * @return array
 */
function local_qmframework_theme_data() {
    global $DB;

    $config = local_qmframework_category_themes_stages_settings();
    $data = [];
    for ($number = 1; $number <= 4; $number++) {
        if ($theme = $config['theme' . $number]) {
            if ($name = $DB->get_field('question_categories', 'name', array('id' => $theme))) {
                $data[$theme] = [];
                $data[$theme]['name']      = $name;
                $data[$theme]['tag']       = $name;
                $data[$theme]['stages'][1] = $config['theme' . $number . 'stage1'];
                $data[$theme]['stages'][2] = $config['theme' . $number . 'stage2'];
                $data[$theme]['stages'][3] = $config['theme' . $number . 'stage3'];
                $data[$theme]['stages'][4] = $config['theme' . $number . 'stage4'];
            }
        }
    }

    return $data;
}

/**
 * Calculate the grades and achieved stage for each QM Model theme
 * from a users single quiz attempt
 *
 * Assumptions:
 * - the quiz is graded with simple numerical values
 * - the quiz is separated into sections with headings
 *   which denote the QM Model Themes
 * - each section is split into separate stages by four categories
 * - mastered = grade 8 or above per stage
 *
 * @param  object $attempt the current attempt object
 * @param  array $themedata the theme data
 * @return array
 */
function local_qmframework_quiz_attempt_score($attempt, $themedata) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
    require_once($CFG->dirroot . '/mod/quiz/accessmanager.php');
    require_once($CFG->dirroot . '/question/engine/lib.php');

    // Sort out the stage keys from the theme
    // configuration data so that we can quickly
    // estabilsh which parent theme the question
    // answered category belongs to.
    $stagekeys = [];
    foreach ($themedata as $theme => $data) {
        foreach ($data['stages'] as $stage) {
            if (!isset($stagekeys[$stage])) {
                $stagekeys[$stage] = $theme;
            }
        }
    }

    // Fetch the quiz objects and structure required for core mod/quiz utilities.
    $quiz       = quiz_access_manager::load_quiz_and_settings($attempt->quiz);
    $course     = $DB->get_record('course', array('id' => $quiz->course));
    $cm         = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false);
    $quizobj    = new quiz($quiz, $cm, $course);
    $attemptobj = new quiz_attempt($attempt, $quiz, $cm, $course);
    $structure  = $quizobj->get_structure();
    $quba       = question_engine::load_questions_usage_by_activity($attempt->uniqueid);

    // Calculate the totals per question category - using the structures
    // section and slots to fetch the correct mark per question.
    $totals = [];
    foreach ($structure->get_sections() as $section) {
        $slots = $structure->get_slots_in_section($section->id);
        foreach ($slots as $slot) {
            $question = $quba->get_question($slot);
            if ($parent = $stagekeys[$question->category]) {
                if (!isset($totals[$question->category])) {
                    $totals[$question->category] = 0;
                }
                $totals[$question->category] += $attemptobj->get_question_mark($slot);
            }
        }
    }

    // The achieved stage is the one for which the student
    // calculated 8 or above and is the latest in the stage
    // ordering list.
    foreach ($themedata as $id => $data) {
        $highestscore = 0;
        $higheststage = 0;
        foreach ($data['stages'] as $stage => $category) {
            if (isset($totals[$category])) {
                $score = $totals[$category];
                if ($score > 0 && $score >= $highestscore && $score >= 8) {
                    $higheststage = $stage;
                    $highestscore = $score;
                }
            }
        }
        $themedata[$id]['score'] = $highestscore;
        $themedata[$id]['stage'] = $higheststage;
        $themedata[$id]['mastered'] = ($higheststage == 4) ? 1 : 0;
        unset($themedata[$id]['stages']);
    }

    return $themedata;
}

/**
 * Prepare quiz attempt data for export.
 *
 * @param  integer $userid the user's ID.
 * @param  integer $attemptid the ID of the attempt to export.
 * @return array
 */
function local_qmframework_attempt_data($userid, $attemptid) {
    global $DB;

    $info = [];
    $themedata = local_qmframework_theme_data();
    if ($attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid, 'userid' => $userid])) {
        $data = local_qmframework_quiz_attempt_score($attempt, $themedata);
        $url  = new moodle_url('/mod/quiz/review.php', array('attempt' => $attempt->id));
        $info['url']       = $url->out(true);
        $info['data']      = $data;
        $info['started']   = $attempt->timestart;
        $info['submitted'] = $attempt->timefinish;
        $info['order']     = $attempt->attempt; // The order to display attempts.
    }

    return $info;
}

/**
 * Get the QM course quizzes.
 * @return array
 */
function local_qmframework_quizzes() {
    global $DB;

    if ($courseid = get_config('local_qmframework', 'local_qmframework_course')) {
        return $DB->get_records('quiz', array('course' => $courseid), '', 'id, name');
    }

    return [];
}

/**
 * Get the QM course questions categories.
 * @param string $catid the ID of the main category
 * @return array
 */
function local_qmframework_quiz_categories($catid=0) {
    global $DB;

    if ($courseid = get_config('local_qmframework', 'local_qmframework_course')) {
        $context  = context_course::instance($courseid);
        $params   = ['parent' => $catid, 'contextid' => $context->id];
        return $DB->get_records('question_categories', $params, '', 'id, name');
    }

    return [];
}
