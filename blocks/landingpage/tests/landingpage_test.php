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
 * Test the calculation of the landing page
 *
 * @package   block_landingpage
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_landingpage\landingpage;

defined('MOODLE_INTERNAL') || die();

class block_landingpage_testcase extends advanced_testcase {
    private $school1cat;
    private $school1acat;
    private $school1bcat;
    private $school11cat;
    private $school11acat;
    private $school2cat;
    private $school2acat;
    private $misccat;

    public function setUp() {
        global $DB;

        // Create category hierarchy:
        // school1 (with course SCHOOL1)
        // - school1_a
        // - school1_b
        // - subschool1.1 (with course SCHOOL1.1)
        // - - subschool1.1_a
        // school2 (with course SCHOOL2)
        // - school2_a
        // Misc

        $this->school1cat = $this->getDataGenerator()->create_category(['name' => 'school1',
                                                                        'parent' => 0]);
        $this->school1acat = $this->getDataGenerator()->create_category(['name' => 'school1_a',
                                                                         'parent' => $this->school1cat->id]);
        $this->school1bcat = $this->getDataGenerator()->create_category(['name' => 'school1_b',
                                                                         'parent' => $this->school1cat->id]);
        $this->school11cat = $this->getDataGenerator()->create_category(['name' => 'school1.1',
                                                                         'parent' => $this->school1cat->id]);
        $this->school11acat = $this->getDataGenerator()->create_category(['name' => 'school1.1_a',
                                                                          'parent' => $this->school11cat->id]);
        $this->school2cat = $this->getDataGenerator()->create_category(['name' => 'school2',
                                                                        'parent' => 0]);
        $this->school2acat = $this->getDataGenerator()->create_category(['name' => 'school2_a',
                                                                         'parent' => $this->school2cat->id]);
        $this->misccat = $this->getDataGenerator()->create_category(['name' => 'Misc',
                                                                     'parent' => 0]);

        // Create landing page courses.
        $school1landing = $this->getDataGenerator()->create_course(['category' => $this->school1cat->id,
                                                                    'idnumber' => 'SCHOOL1',
                                                                    'shortname' => 'School 1']);
        $school11landing = $this->getDataGenerator()->create_course(['category' => $this->school11cat->id,
                                                                     'idnumber' => 'SCHOOL1.1',
                                                                     'shortname' => 'School 1.1']);
        $school2landing = $this->getDataGenerator()->create_course(['category' => $this->school2cat->id,
                                                                    'idnumber' => 'SCHOOL2',
                                                                    'shortname' => 'School 2']);

        // Update the user profile field.
        $schools = ['SCHOOL1', 'SCHOOL1.1', 'SCHOOL2', 'SCHOOL5'];
        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => landingpage::FIELDNAME], MUST_EXIST);
        $DB->set_field('user_info_field', 'param1', implode("\n", $schools), ['id' => $fieldid]);

        $this->resetAfterTest();
    }

    public function test_no_schools() {
        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        // User not enrolled in any courses.
        $schools = landingpage::instance(true)->get_user_schools($user->id);
        $this->assertEmpty($schools);

        // User enrolled in a course not in any schools.
        $course = $this->getDataGenerator()->create_course(['category' => $this->misccat->id]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $schools = landingpage::instance(true)->get_user_schools($user->id);
        $this->assertEmpty($schools);
    }

    public function test_single_school() {
        // Create 3 users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Enrol each one in a course within a different school.
        $course1 = $this->getDataGenerator()->create_course(['category' => $this->school1bcat->id]);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $this->school11acat->id]);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);
        $course3 = $this->getDataGenerator()->create_course(['category' => $this->school2cat->id]);
        $this->getDataGenerator()->enrol_user($user3->id, $course3->id);

        // Check each is within the expected school.
        $schools = landingpage::instance(true)->get_user_schools($user1->id);
        $this->assertCount(1, $schools);
        list($school) = array_keys($schools);
        $this->assertEquals('SCHOOL1', $school);
        list($schoolname) = array_values($schools);
        $this->assertEquals('School 1', $schoolname);

        $schools = landingpage::instance()->get_user_schools($user2->id);
        $this->assertCount(1, $schools);
        list($school) = array_keys($schools);
        $this->assertEquals('SCHOOL1.1', $school);
        list($schoolname) = array_values($schools);
        $this->assertEquals('School 1.1', $schoolname);

        $schools = landingpage::instance()->get_user_schools($user3->id);
        $this->assertCount(1, $schools);
        list($school) = array_keys($schools);
        $this->assertEquals('SCHOOL2', $school);
        list($schoolname) = array_values($schools);
        $this->assertEquals('School 2', $schoolname);
    }

    public function test_multiple_schools() {
        // Create 3 users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // User 1 is enrolled in multiple courses in the same school.
        $course1 = $this->getDataGenerator()->create_course(['category' => $this->school1acat->id]);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $this->school1bcat->id]);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $course3 = $this->getDataGenerator()->create_course(['category' => $this->school1cat->id]);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id);

        $schools = landingpage::instance(true)->get_user_schools($user1->id);
        $this->assertCount(1, $schools);
        list($school) = array_keys($schools);
        $this->assertEquals('SCHOOL1', $school);
        list($schoolname) = array_values($schools);
        $this->assertEquals('School 1', $schoolname);

        // User 2 is enrolled in courses in school 1 + 1.1.
        $course1 = $this->getDataGenerator()->create_course(['category' => $this->school1bcat->id]);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $this->school11acat->id]);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);

        $schools = landingpage::instance(true)->get_user_schools($user2->id);
        $this->assertCount(2, $schools);
        list($school, $school2) = array_keys($schools);
        $this->assertEquals('SCHOOL1', $school);
        $this->assertEquals('SCHOOL1.1', $school2);
        list($schoolname1, $schoolname2) = array_values($schools);
        $this->assertEquals('School 1', $schoolname1);
        $this->assertEquals('School 1.1', $schoolname2);

        // User 3 is enrolled in courses in school 1.1 + 2.
        $course1 = $this->getDataGenerator()->create_course(['category' => $this->school11acat->id]);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $this->school2acat->id]);
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);

        $schools = landingpage::instance(true)->get_user_schools($user3->id);
        $this->assertCount(2, $schools);
        list($school, $school2) = array_keys($schools);
        $this->assertEquals('SCHOOL1.1', $school);
        $this->assertEquals('SCHOOL2', $school2);
        list($schoolname1, $schoolname2) = array_values($schools);
        $this->assertEquals('School 1.1', $schoolname1);
        $this->assertEquals('School 2', $schoolname2);
    }
}