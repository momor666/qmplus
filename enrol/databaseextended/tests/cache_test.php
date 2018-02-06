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
 * Unit tests for the caching mechanism
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012 Matt Gibson {@link http://moodle.org/user/view.php?id=81450}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/enrol/databaseextended/lib.php');

/**
 * This tests to make sure we can have stuff in the DB to start with that the sync script will be able to make a flag for.
 */
class enrol_databaseextended_cache_test extends advanced_testcase {

    /**
     * Initialise the config variables for the database connection.
     */
    public function setUp() {

        global $CFG;

        $this->resetAfterTest();

        set_config('dbtype', $CFG->dbtype, 'enrol_databaseextended');
        set_config('dbhost', $CFG->dbhost, 'enrol_databaseextended');
        set_config('dbuser', $CFG->dbuser, 'enrol_databaseextended');
        set_config('dbpass', $CFG->dbpass, 'enrol_databaseextended');
        set_config('dbname', $CFG->dbname, 'enrol_databaseextended');
    }

    /**
     * Attempts to make sure we get a result from the DB when we have not primed the cache.
     */
    public function test_course_unprimed_cache_retrieval() {

        // Put a course into the DB.
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();

        $enrolplugin = new enrol_databaseextended_plugin();

        // Get the course table object without priming the cache.
        $coursetable = $enrolplugin->get_table_object('course');
        $coursetable->add_item_flag($course);

        $uniquekey = $coursetable->get_internal_unique_field_name();

        // Retrieve that course from the cache.
        $retrievedcourse = $coursetable->retrieve_from_cache($course->$uniquekey);

        $this->assertNotEmpty($retrievedcourse, 'No course retrieved from cache');
        $this->assertEquals($course->id, $retrievedcourse->id, 'Wrong course retrieved from cache');

    }

    /**
     * Attempts to make sure we get an empty result for hidden stuff from the DB when we have not primed the cache.
     */
    public function test_course_unprimed_cache_hidden_retrieval() {

        // Put a course into the DB.
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(array('visible' => 0));

        $enrolplugin = new enrol_databaseextended_plugin();

        // Get the course table object without priming the cache.
        $coursetable = $enrolplugin->get_table_object('course');
        $coursetable->add_item_flag($course);

        $uniquekey = $coursetable->get_internal_unique_field_name();

        // Retrieve that course from the cache.
        $retrievedcourse = $coursetable->retrieve_from_cache($course->$uniquekey);

        $this->assertEmpty($retrievedcourse, 'Course retrieved from cache when it shouldn\'t have been');
    }

}