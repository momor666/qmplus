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
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012 University of London Computer Centre {@link http://ulcc.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/enrol/databaseextended/lib.php');

/**
 * Tests for course categories file to check whether course categories table has any inconsistent data
 */
class course_categories_test extends advanced_testcase {

    /**
     * Create external categories table
     */
    public function setUp() {

        global $DB, $CFG;

        $this->resetAfterTest(); // So the categories we add are wiped.

        // Make a DB with external data in it so we can test.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('extcategories');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null); // Must have sequential id.
        $table->add_field('category_id', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('parent', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // We need all the settings etc from the normal plugin so we can make the relations work.

        // Alter the relations so that they point to the tables we just made.
        // Note: this is relative to the way the relations are at install time. If the installed ones are altered, then this
        // will need to be altered too.

        $conditions = array('maintable' => 'course_categories',
            'internal'=> ENROL_DATABASEEXTENDED_EXTERNAL,
            'uniquefield'=> ENROL_DATABASEEXTENDED_UNIQUE);
        $row = $DB->get_record('databaseextended_relations', $conditions);
        $id = $row->id;
        // Update the value of foreign key.
        $idnumberrelation = new stdClass();
        $idnumberrelation->id = $id;
        $idnumberrelation->secondarytablefield = 'category_id';
        $DB->update_record('databaseextended_relations', $idnumberrelation);

        // Update name of secondarytable with PHPUnit prefix.
        $conditions = array('maintable' => 'course_categories',
            'internal' => ENROL_DATABASEEXTENDED_EXTERNAL);
        $rows = $DB->get_records('databaseextended_relations', $conditions);
        foreach ($rows as $row) {
            $idnumberrelation = new stdClass();
            $idnumberrelation->id = $row->id;
            $idnumberrelation->secondarytable = 'phpu_extcategories';
            $DB->update_record('databaseextended_relations', $idnumberrelation);
        }

        set_config('rootcategory', 'ROOT', 'enrol_databaseextended');
        set_config('dbtype', 'mysql', 'enrol_databaseextended');
        set_config('dbname', $CFG->dbname, 'enrol_databaseextended');
        set_config('dbuser', $CFG->dbuser, 'enrol_databaseextended');
        set_config('dbpass', $CFG->dbpass, 'enrol_databaseextended');

    }

    /**
     * Test to find out whether sanity_check function checks external course category table for inconsistent data
     */
    public function test_course_categories() {

        global $DB;

        // Make external categories with different parent ids matching the PK + one with ROOT.
        $firstexternalcategory = new stdClass();
        $firstexternalcategory->category_id = 'FIRST_ID_NUMBER';
        $firstexternalcategory->name = 'First Category';
        $firstexternalcategory->parent = 'ROOT';
        $DB->insert_record('extcategories', $firstexternalcategory);

        $secondexternalcategory = new stdClass();
        $secondexternalcategory->category_id = 'SECOND_ID_NUMBER';
        $secondexternalcategory->name = 'Second Category';
        $secondexternalcategory->parent = 'FIRST_ID_NUMBER';
        $DB->insert_record('extcategories', $secondexternalcategory);

        $thirdexternalcategory = new stdClass();
        $thirdexternalcategory->category_id = 'THIRD_ID_NUMBER';
        $thirdexternalcategory->name = 'Third Category';
        $thirdexternalcategory->parent = 'SECOND_ID_NUMBER';
        $DB->insert_record('extcategories', $thirdexternalcategory);

        $fourthexternalcategory  = new stdClass();
        $fourthexternalcategory->category_id = 'FOURTH_ID_NUMBER';
        $fourthexternalcategory->name = 'Fourth Category';
        $fourthexternalcategory->parent = 'THIRD_ID_NUMBER';
        $DB->insert_record('extcategories', $fourthexternalcategory );

        /* @var enrol_databaseextended_plugin $enrol */
        $enrol = enrol_get_plugin('databaseextended');
        $enrol->initialise_all_tables();

        $tablename = 'course_categories';
        $table = $enrol->get_table_object($tablename);

        $externalrecordset = $table->get_external_recordset();
        $this->assertNotEmpty($externalrecordset);

        // Assertion that parent id of course categories in the external table matches existent PK from that table.
        $test1 = $table->sanity_check($externalrecordset);
        $this->assertTrue($test1);

        // Add external category that doesn't have matching primary key.
        $fifthexternalcategory = new stdClass();
        $fifthexternalcategory->category_id = 'FIFTH_ID_NUMBER';
        $fifthexternalcategory->name = 'Fifth Category';
        $fifthexternalcategory->parent = 'SIXTH_ID_NUMBER';
        $DB->insert_record('extcategories', $fifthexternalcategory  );

        // Assertion that parent id of course categories in the external table does not match existent PK from that table.
        $test2 = $table->sanity_check($externalrecordset);
        $this->assertFalse($test2);
    }

    /**
     * Remove the extra table we made.
     */
    public function tearDown() {

        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('extcategories');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
    }
}