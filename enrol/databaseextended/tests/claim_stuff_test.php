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
 * @copyright  2012 Matt Gibson {@link http://moodle.org/user/view.php?id=81450}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/enrol/databaseextended/lib.php');

/**
 * This tests to make sure we can have stuff in the DB to start with that the sync script will be able to make a flag for.
 */
class enrol_databaseextended_claim_stuff_test extends advanced_testcase {

    /**
     * Make a simulated external database so we can test the stuff. Be aware that these tests may fail because the settings for the
     * relations' defaults have changed e.g. if the install methods have different names for the external tables.
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

        // This one was not there in the install before. Might need to be removed later.
        $idnumberrelation = new stdClass();
        $idnumberrelation->maintable = 'course_categories';
        $idnumberrelation->maintablefield = 'idnumber';
        $idnumberrelation->secondarytable = 'extcategories';
        $idnumberrelation->secondarytablefield = 'category_id';
        $idnumberrelation->internal = ENROL_DATABASEEXTENDED_EXTERNAL;
        $idnumberrelation->uniquefield = ENROL_DATABASEEXTENDED_UNIQUE;
        $DB->insert_record('databaseextended_relations', $idnumberrelation);

        // Default is for name to be unique, so we need to alter that.
        $conditions = array('secondarytable' => 'extcategories', 'secondarytablefield' => 'name');
        $DB->set_field('databaseextended_relations', 'uniquefield', ENROL_DATABASEEXTENDED_NON_UNIQUE, $conditions);

        $sql = "UPDATE {databaseextended_relations}
                   SET secondarytable = '{$DB->get_prefix()}extcategories'
                  WHERE secondarytable = 'extcategories'";
        $DB->execute($sql);

        set_config('rootcategory', 'ROOT', 'enrol_databaseextended');
        set_config('dbtype', 'mysql', 'enrol_databaseextended');
        set_config('dbname', $CFG->dbname, 'enrol_databaseextended');
        set_config('dbuser', $CFG->dbuser, 'enrol_databaseextended');
        set_config('dbpass', $CFG->dbpass, 'enrol_databaseextended');

    }

    /**
     * Find out whether the
     */
    public function test_claim_categories() {

        global $DB;

        // Make external categories.
        $firstexternalcategory = new stdClass();
        $firstexternalcategory->category_id = 'FIRST_ID_NUMBER';
        $firstexternalcategory->name = 'First category';
        $firstexternalcategory->parent = 'ROOT';
        $DB->insert_record('extcategories', $firstexternalcategory);
        $secondexternalcategory = new stdClass();
        $secondexternalcategory->category_id = 'SECOND_ID_NUMBER';
        $secondexternalcategory->name = 'Second category';
        $secondexternalcategory->parent = 'ROOT';
        $DB->insert_record('extcategories', $secondexternalcategory);

        // Make Moodle categories.
        $firstcategory = new stdClass();
        $firstcategory->name = 'First category';
        $firstcategory->idnumber = 'FIRST_ID_NUMBER';
        $firstcategory->parent = 0;
        $firstcategory->id = $DB->insert_record('course_categories', $firstcategory);
        $secondcategory = new stdClass();
        $secondcategory->name = 'Second category';
        $secondcategory->parent = 0;
        $secondcategory->idnumber = 'SECOND_ID_NUMBER';
        $secondcategory->id = $DB->insert_record('course_categories', $secondcategory);

        // Run sync.
        /* @var enrol_databaseextended_plugin $enrol */
        $enrol = enrol_get_plugin('databaseextended');

        $tables = array('course_categories');
        $enrol->speedy_sync($tables);

        // We should now find that there are flags for both categories.
        $params = array('tablename' => 'course_categories',
                        'itemid' => $firstcategory->id);
        $flagone = $DB->get_record('databaseextended_flags', $params);
        $this->assertNotEmpty($flagone);
        $params = array('tablename' => 'course_categories',
                        'itemid' => $secondcategory->id);
        $flagtwo = $DB->get_record('databaseextended_flags', $params);
        $this->assertNotEmpty($flagtwo);

        // We should also find that the idnumber has been added to the internal category that was missing it.
//        $alteredcategory = $DB->get_record('course_categories', array('id' => $firstcategory->id));
//        $this->assertEquals($firstcategory->id, $alteredcategory->id);
        $this->assertEquals('FIRST_ID_NUMBER', $flagone->externalname);

    }

    /**
     * Remove the extra table we made.
     */
    public function tearDown() {

        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('mis_categories');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

    }


}