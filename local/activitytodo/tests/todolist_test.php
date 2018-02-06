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
 * Test todolist updating
 *
 * @package   local_activitytodo
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_activitytodo\todo_item;

defined('MOODLE_INTERNAL') || die();

class local_activitytodo_todolist_test extends advanced_testcase {
    public function setUp() {
        $this->resetAfterTest();
    }

    public function test_add_items() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $course1 = $gen->create_course();
        $course2 = $gen->create_course();
        $gen->enrol_user($user->id, $course1->id, 'student');

        $page1 = $gen->create_module('page', ['name' => 'Page 1', 'course' => $course1->id]);
        $page2 = $gen->create_module('page', ['name' => 'Page 2', 'course' => $course1->id, 'visible' => 0]);
        $page3 = $gen->create_module('page', ['name' => 'Page 3', 'course' => $course2->id]);

        $todolist = new \local_activitytodo\todolist($user);
        $todolist->add($page1->cmid); // Try adding a valid activity from the user's course.
        try {
            $todolist->add($page2->cmid); // Try adding hidden activity.
            $this->fail('Expected exception not thrown');
        } catch (moodle_exception $e) {
            // Ignore the exception we were expecting.
        }
        try {
            $todolist->add($page3->cmid); // Try adding activity from a course the user is not enrolled on.
            $this->fail('Expected exception not thrown');
        } catch (moodle_exception $e) {
            // Ignore the exception we were expecting.
        }
        try {
            $todolist->add($page3->cmid + 2001); // Try adding a non-existent activity.
            $this->fail('Expected exception not thrown');
        } catch (moodle_exception $e) {
            // Ignore the exception we were expecting.
        }

        $items = $todolist->get_items();
        $this->assertCount(1, $items); // Only expect to find the activity from the enrolled course.
        /** @var todo_item $item1 */
        list($item1) = array_values($items);
        $this->assertEquals($course1->id, $item1->courseid);
        $this->assertEquals($page1->cmid, $item1->cmid);
        $this->assertEquals($user->id, $item1->userid);
        $this->assertEquals(1, $item1->sortorder);
    }

    public function test_add_assignment_due_date() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $course = $gen->create_course();
        $gen->enrol_user($user->id, $course->id, 'student');

        $page = $gen->create_module('page', ['name' => 'Page 1', 'course' => $course->id]);
        $due1 = time() + 10 * DAYSECS;
        cron_setup_user(); // Otherwise we have problems with updating the calendar with the duedate.
        $assign1 = $gen->create_module('assign', ['name' => 'Assign 1', 'course' => $course->id, 'duedate' => $due1]);
        $assign2 = $gen->create_module('assign', ['name' => 'Assign 2', 'course' => $course->id, 'duedate' => null]);

        $todolist = new local_activitytodo\todolist($user);
        $todolist->add($page->cmid);
        $todolist->add($assign1->cmid);
        $todolist->add($assign2->cmid);

        $items = $todolist->get_items();
        $this->assertCount(3, $items);
        /** @var todo_item $item1, $item2, $item3 */
        list($item1, $item2, $item3) = array_values($items);
        $this->assertEquals($assign2->cmid, $item1->cmid);
        $this->assertEquals(null, $item1->duedate);
        $this->assertEquals(0, $item1->lockduedate);

        $this->assertEquals($assign1->cmid, $item2->cmid);
        $this->assertEquals($due1, $item2->duedate);
        $this->assertEquals(1, $item2->lockduedate);

        $this->assertEquals($page->cmid, $item3->cmid);
        $this->assertEquals(null, $item3->duedate);
        $this->assertEquals(0, $item3->lockduedate);

        // Update the assignment due date and check it changes.
        $due2 = time() + 30 * DAYSECS;
        $modinfo = get_fast_modinfo($course->id);
        $cm = $modinfo->get_cm($item2->cmid)->get_course_module_record();
        list($cm, , , $modinfo, ) = get_moduleinfo_data($cm, $course);
        $modinfo->duedate = $due2;
        $cm->modname = $modinfo->modulename;
        update_moduleinfo($cm, $modinfo, $course);

        $items = $todolist->get_items();
        $this->assertCount(3, $items);
        /** @var todo_item $item1, $item2, $item3 */
        list($item1, $item2, $item3) = array_values($items);
        $this->assertEquals(null, $item1->duedate);
        $this->assertEquals(0, $item1->lockduedate);

        $this->assertEquals($due2, $item2->duedate);
        $this->assertEquals(1, $item2->lockduedate);

        $this->assertEquals(null, $item3->duedate);
        $this->assertEquals(0, $item3->lockduedate);

        // Remove the assignment due date and check it is cleared.
        list($cm, , , $modinfo, ) = get_moduleinfo_data($cm, $course);
        $modinfo->duedate = 0;
        update_moduleinfo($cm, $modinfo, $course);

        $items = $todolist->get_items();
        $this->assertCount(3, $items);
        /** @var todo_item $item1, $item2, $item3 */
        list($item1, $item2, $item3) = array_values($items);
        $this->assertEquals(null, $item1->duedate);
        $this->assertEquals(0, $item1->lockduedate);

        $this->assertEquals(null, $item2->duedate);
        $this->assertEquals(0, $item2->lockduedate);

        $this->assertEquals(null, $item3->duedate);
        $this->assertEquals(0, $item3->lockduedate);

        cron_setup_user('reset');
    }

    public function test_remove_items() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $course = $gen->create_course();
        $gen->enrol_user($user->id, $course->id, 'student');

        $page1 = $gen->create_module('page', ['name' => 'Page 1', 'course' => $course->id]);
        $page2 = $gen->create_module('page', ['name' => 'Page 2', 'course' => $course->id]);
        $page3 = $gen->create_module('page', ['name' => 'Page 3', 'course' => $course->id]);

        $todolist = new local_activitytodo\todolist($user);
        $todolist->add($page1->cmid);
        $todolist->add($page2->cmid);
        $todolist->add($page3->cmid);

        // Check all the items are in the list.
        $items = $todolist->get_items();
        $this->assertCount(3, $items);
        list($item1, $item2, $item3) = array_values($items);
        $this->assertEquals($page3->cmid, $item1->cmid); // Last item is at the top of the list.
        $this->assertEquals($page2->cmid, $item2->cmid);
        $this->assertEquals($page1->cmid, $item3->cmid);

        // Remove an item.
        $todolist->remove($page2->cmid);

        // Check the other items are still in the list.
        $items = $todolist->get_items();
        $this->assertCount(2, $items);
        list($item1, $item2) = array_values($items);
        $this->assertEquals($page3->cmid, $item1->cmid);
        $this->assertEquals($page1->cmid, $item2->cmid);

        // Remove an item not in the list - should not do anything.
        $todolist->remove($page2->cmid);
        $items = $todolist->get_items();
        $this->assertCount(2, $items);
        list($item1, $item2) = array_values($items);
        $this->assertEquals($page3->cmid, $item1->cmid);
        $this->assertEquals($page1->cmid, $item2->cmid);

        // Add item back into the list - should appear at the top of the list.
        $todolist->add($page2->cmid);
        $items = $todolist->get_items();
        $this->assertCount(3, $items);
        list($item1, $item2, $item3) = array_values($items);
        $this->assertEquals($page2->cmid, $item1->cmid);
        $this->assertEquals($page3->cmid, $item2->cmid);
        $this->assertEquals($page1->cmid, $item3->cmid);

        // Add a duplicate item to the list - nothing should happen.
        $todolist->add($page1->cmid);
        $items = $todolist->get_items();
        $this->assertCount(3, $items);
        list($item1, $item2, $item3) = array_values($items);
        $this->assertEquals($page2->cmid, $item1->cmid);
        $this->assertEquals($page3->cmid, $item2->cmid);
        $this->assertEquals($page1->cmid, $item3->cmid);

        // Remove all items from the list.
        $todolist->remove($page1->cmid);
        $todolist->remove($page2->cmid);
        $todolist->remove($page3->cmid);

        // Check the list is now empty.
        $items = $todolist->get_items();
        $this->assertEmpty($items);
    }

    public function test_sort_items() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $course = $gen->create_course();
        $gen->enrol_user($user->id, $course->id, 'student');

        $page1 = $gen->create_module('page', ['name' => 'Page 1', 'course' => $course->id]);
        $page2 = $gen->create_module('page', ['name' => 'Page 2', 'course' => $course->id]);
        $page3 = $gen->create_module('page', ['name' => 'Page 3', 'course' => $course->id]);
        $page4 = $gen->create_module('page', ['name' => 'Page 4', 'course' => $course->id]);

        $todolist = new local_activitytodo\todolist($user);
        $todolist->add($page1->cmid);
        $todolist->add($page2->cmid);
        $todolist->add($page3->cmid);
        $todolist->add($page4->cmid);

        // Check all the items are in the list.
        $items = $todolist->get_items();
        $this->assertCount(4, $items);
        list($item1, $item2, $item3, $item4) = array_values($items);
        $this->assertEquals($page4->cmid, $item1->cmid); // Last item is at the top of the list.
        $this->assertEquals($page3->cmid, $item2->cmid);
        $this->assertEquals($page2->cmid, $item3->cmid);
        $this->assertEquals($page1->cmid, $item4->cmid);

        // Update the sort order.
        $todolist->update_sortorder([$page2->cmid, $page1->cmid, $page4->cmid, $page3->cmid]);
        $items = $todolist->get_items();
        $this->assertCount(4, $items);
        list($item1, $item2, $item3, $item4) = array_values($items);
        $this->assertEquals($page2->cmid, $item1->cmid);
        $this->assertEquals($page1->cmid, $item2->cmid);
        $this->assertEquals($page4->cmid, $item3->cmid);
        $this->assertEquals($page3->cmid, $item4->cmid);

        // Partial update of the sort order - missing items appear at the start of the list (in
        // the order they previously appeared).
        $todolist->update_sortorder([$page1->cmid, $page2->cmid]);
        $items = $todolist->get_items();
        $this->assertCount(4, $items);
        list($item1, $item2, $item3, $item4) = array_values($items);
        $this->assertEquals($page4->cmid, $item1->cmid);
        $this->assertEquals($page3->cmid, $item2->cmid);
        $this->assertEquals($page1->cmid, $item3->cmid);
        $this->assertEquals($page2->cmid, $item4->cmid);
    }

    public function test_is_on_todolist() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $course = $gen->create_course();
        $gen->enrol_user($user->id, $course->id, 'student');

        $page1 = $gen->create_module('page', ['name' => 'Page 1', 'course' => $course->id]);
        $page2 = $gen->create_module('page', ['name' => 'Page 2', 'course' => $course->id]);
        $page3 = $gen->create_module('page', ['name' => 'Page 3', 'course' => $course->id]);
        $page4 = $gen->create_module('page', ['name' => 'Page 4', 'course' => $course->id]);

        $todolist = new local_activitytodo\todolist($user);
        $todolist->add($page1->cmid);
        $todolist->add($page4->cmid);

        $modinfo = get_fast_modinfo($course);
        $cm1 = $modinfo->get_cm($page1->cmid);
        $cm2 = $modinfo->get_cm($page2->cmid);
        $cm3 = $modinfo->get_cm($page3->cmid);
        $cm4 = $modinfo->get_cm($page4->cmid);

        // Check expected items are on the todolist.
        $this->assertTrue($todolist->is_on_todolist($cm1));
        $this->assertFalse($todolist->is_on_todolist($cm2));
        $this->assertFalse($todolist->is_on_todolist($cm3));
        $this->assertTrue($todolist->is_on_todolist($cm4));

        // Update the todolist and check it the expected items are on it.
        $todolist->add($page2->cmid);
        $todolist->remove($page4->cmid);

        $this->assertTrue($todolist->is_on_todolist($cm1));
        $this->assertTrue($todolist->is_on_todolist($cm2));
        $this->assertFalse($todolist->is_on_todolist($cm3));
        $this->assertFalse($todolist->is_on_todolist($cm4));
    }
}