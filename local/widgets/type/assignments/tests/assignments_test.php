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
 * Test assignment retrieval
 *
 * @package   widgettype_assignments
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class widgettype_assignments_test extends advanced_testcase {
    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_assignments_list() {
        global $DB;

        $gen = $this->getDataGenerator();

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $course1 = $gen->create_course(); // Both users enrolled in this course (as students).
        $course2 = $gen->create_course(); // Only user2 enrolled in this course.

        $gen->enrol_user($user1->id, $course1->id, 'student');
        $gen->enrol_user($user2->id, $course1->id, 'student');
        $gen->enrol_user($user2->id, $course2->id, 'student');

        cron_setup_user(); // Otherwise we get errors about adding calendar events.

        // Assignment that should appear in the list (user2 has a submission, but not user 1).
        $due1 = time() + 5 * DAYSECS;
        $assign1 = $gen->create_module('assign', ['name' => 'Assign 1', 'course' => $course1->id, 'duedate' => $due1]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign1->id,
            'userid' => $user2->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);

        // Page should be ignored (as it is not an assignment).
        $page = $gen->create_module('page', ['name' => 'Page 1', 'course' => $course1->id]);

        // Assignment should be ignored, as it is in a course user1 is not enrolled in.
        $due2 = time() + 8 * DAYSECS;
        $assign2 = $gen->create_module('assign', ['name' => 'Assign 2', 'course' => $course2->id, 'duedate' => $due2]);

        // Assignment that should appear in the list (user1 + 2 have a submission).
        $due3 = time() + 6 * DAYSECS;
        $assign3 = $gen->create_module('assign', ['name' => 'Assign 3', 'course' => $course1->id, 'duedate' => $due3]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign3->id,
            'userid' => $user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign3->id,
            'userid' => $user2->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'draft',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);

        // Assignment that should appear in the list (user has a draft submission).
        $due4 = time() + 7 * DAYSECS;
        $assign4 = $gen->create_module('assign', ['name' => 'Assign 4', 'course' => $course1->id, 'duedate' => $due4]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign4->id,
            'userid' => $user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'draft',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);

        // Assignment that should be ignored, as it is submitted and the due date has gone.
        $due5 = time() - 5 * DAYSECS;
        $assign5 = $gen->create_module('assign', ['name' => 'Assign 5', 'course' => $course1->id, 'duedate' => $due5]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign5->id,
            'userid' => $user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);

        // Assignment that should be included, as it is NOT submitted (no record), the due date has gone.
        $due6 = time() - 5 * DAYSECS;
        $assign6 = $gen->create_module('assign', ['name' => 'Assign 6', 'course' => $course1->id, 'duedate' => $due6]);

        // Assignment that should be included, as it is NOT submitted ('new' record), the due date has gone.
        $due7 = time() - 4 * DAYSECS;
        $assign7 = $gen->create_module('assign', ['name' => 'Assign 7', 'course' => $course1->id, 'duedate' => $due7]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign7->id,
            'userid' => $user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'new', // New submission
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);

        // Assignment that should be ignored, as it has no due date.
        $due8 = 0;
        $assign8 = $gen->create_module('assign', ['name' => 'Assign 8', 'course' => $course1->id, 'duedate' => $due8]);

        // Make sure only the latest submission is picked up, for an assignment.
        $due9 = time() + 10 * DAYSECS;
        $assign9 = $gen->create_module('assign', ['name' => 'Assign 9', 'course' => $course1->id, 'duedate' => $due9]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign9->id,
            'userid' => $user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 0,
        ]);
        $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign9->id,
            'userid' => $user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'draft',
            'groupid' => 0,
            'attemptnumber' => 1,
            'latest' => 1,
        ]);

        // Make sure hidden assignments do not appear in the list.
        $due10 = time() + 5 * DAYSECS;
        $assign10 = $gen->create_module('assign', ['name' => 'Assign 10', 'course' => $course1->id, 'duedate' => $due10,
                                                   'visible' => 0]);

        // Make sure assignments that are not yet 'duesoon' have the right status.
        $due11 = time() + 30 * DAYSECS;
        $assign11 = $gen->create_module('assign', ['name' => 'Assign 11', 'course' => $course1->id, 'duedate' => $due11]);

        cron_setup_user('reset');

        // Get the assignment list.
        $widget = new \widgettype_assignments\assignments(false);
        $assignments = $widget->get_assignments($user1->id, 10); // Get up to 10 latest assignments.

        $this->assertCount(7, $assignments);
        list($item1, $item2, $item3, $item4, $item5, $item6, $item7) = $assignments;

        $this->assertEquals($assign6->name, $item1->name);
        $this->assertEquals($assign7->name, $item2->name);
        $this->assertEquals($assign1->name, $item3->name);
        $this->assertEquals($assign3->name, $item4->name);
        $this->assertEquals($assign4->name, $item5->name);
        $this->assertEquals($assign9->name, $item6->name);
        $this->assertEquals($assign11->name, $item7->name);

        $this->assertEquals('overdue', $item1->statusclass);
        $this->assertEquals('overdue', $item2->statusclass);
        $this->assertEquals('duesoon', $item3->statusclass);
        $this->assertEquals('submitted', $item4->statusclass);
        $this->assertEquals('draft', $item5->statusclass);
        $this->assertEquals('draft', $item6->statusclass);
        $this->assertEquals('due', $item7->statusclass);
    }
}
