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
 * Database extended enrolment plugin.
 *
 * This plugin synchronises enrolment and roles with external database table.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012 University of London Computer Centre {@link http://ulcc.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/enrol/databaseextended/classes/one_to_one_table.class.php');

/**
 * Class that controls the sync for the enrol instances table. This has been separated from the courses table
 * primarily so we can keep the logic simple in the courses class.
 */
class enrol_databaseextended_enrol_table extends enrol_databaseextended_one_to_one_table {

    /**
     * @var bool Put the records in the DB in bulk
     */
    protected $bulkinsert = true;

    /**
     * The table joins to another with conditions sometimes e.g. only enrol instances of this type,
     * or only a particular context level
     *
     * @return string
     */
    protected function get_extra_sql() {
        $enrolname = $this->enrolmentplugin->get_name();
        return "AND this.enrol = '{$enrolname}' ";
    }

    /**
     * Override as we already have an array, not a recordset.
     *
     * @internal param $existingitems
     *
     * @return mixed
     */
    public function get_external_recordset() {

        /* @var enrol_databaseextended_array_adapter $arrayadapter */
        $arrayadapter = parent::get_external_recordset();

        // Need to add a courseid to each one.

        while (($item = $arrayadapter->FetchRow()) !== false) {
            // We need the row to have a courseid field, which is actually id since we
            // got it from the course table
            $item->courseid = $item->id; // Object passed by ref so update not needed.
        }

        $arrayadapter->reset();

        return $arrayadapter; // Already an array of objects.
    }

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     * @return void
     */
    protected function add_instance($data) {
        global $DB;

        // Note: $data is a record from the courses table.

        if ($data->id == SITEID) {
            // Site level course - ignore (only affects unit tests).
            return;
        }
        if ($DB->record_exists('enrol', array('enrol' => $this->enrolmentplugin->get_name(), 'courseid' => $data->id))) {
            // Don't add duplicate enrolment instance.
            return;
        }

        $data->courseid = $data->id;
        $data->enrol    = $this->enrolmentplugin->get_name();
        // This just needs a $course object with an id.
        $data->status = ENROL_INSTANCE_ENABLED;
        $data->id     = $this->enrolmentplugin->add_instance($data);
    }

    /**
     * Formats the bulk insert data prior to writing.
     *
     * @param stdClass|bool $row
     * @return void
     */
    public function bulk_insert($row = false) {
        global $DB;

        if (!$row) {
            parent::bulk_insert();
            return;
        }

        if ($row->id == SITEID) {
            // Site level course - ignore (only affects unit tests).
            return;
        }

        if ($DB->record_exists('enrol', array('enrol' => $this->enrolmentplugin->get_name(), 'courseid' => $row->courseid))) {
            // Don't add duplicate enrolment instance.
            return;
        }

        $data = new stdClass();

        $data->courseid = $row->courseid;
        $data->enrol = $this->enrolmentplugin->get_name();
        parent::bulk_insert($data);

    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $thingtodelete
     */
    protected function delete_instance($thingtodelete) {

        // TODO this ought to be controlled by a setting. If the course has been set to visible = 0
        // then we don't want to unenrol the students and lose all records of their presence.
        $this->enrolmentplugin->delete_instance($thingtodelete);
    }

    /**
     * Maybe some fields are different now.
     *
     * @param $incoming
     * @return bool|void
     */
    protected function update_instance($incoming) {

        // Re-enable if needs be.
        if ($incoming->status == ENROL_INSTANCE_DISABLED) {
            $incoming->status = ENROL_INSTANCE_ENABLED;
        }
        parent::update_instance($incoming);
    }

    /**
     * Overriding as we are using an internal table, so ids are already in place. no action needed.
     */
    public function substitute_for_moodle_ids($externalrow) {
        // Empty on purpose.
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * @return array
     */
    public function get_internal_existing_rows() {

        global $DB;

        $tablename = $this->get_internal_table_name();
        $enrolname = $this->enrolmentplugin->get_name();

        // TODO what about end dates?
        $sql = "SELECT e.courseid,
                       e.id,
                       e.status
                  FROM {{$tablename}} e
                 WHERE e.enrol = '{$enrolname}'
                   AND e.courseid != :siteid ";

        return $DB->get_records_sql($sql, array('siteid' => SITEID));
    }

    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {
        global $DB;
        $relations = array(
            // Internal relation to courses table.
            array('maintable'           => 'enrol',
                  'maintablefield'      => 'courseid',
                  'secondarytable'      => 'course',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE));

        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Unusually, the enrolment table isn't directly synced with the external DB. It is in a 1-1
     * relationship with the courses table, so we need to have a cache that keys the enrol ids
     * by course uniquekey.
     *
     * @param bool $onlystoreids
     *
     * @internal param bool $reset Will need to reload once stuff has been added
     *
     * @internal param array $mappedfields
     *
     * @return bool
     */
    public function populate_cache($onlystoreids = true) {

        global $DB;

        $this->empty_cache();

        $coursetable       = enrol_databaseextended_course_table::get_table_singleton();
        $courseuniquefield = $coursetable->get_internal_unique_field_name();

        // Enrolments don't have any unique mapping relations, so we go and get the key based on
        // the course table. Effectively, we treat it as though the.
        $internaltable = $this->get_internal_table_name();
        $enrolname     = $this->enrolmentplugin->get_name();

        // Courseid needed for delete.
        // TODO better way of managing 1-1 ids for deletions - the link will go first
        // TODO is this causing duplicates as the courses are hidden first?
        // For deletions, we want to leave enrolment instances in place for hidden courses (as
        // Moodle does internally) but delete any where the course itself has been deleted.
        $sql = "SELECT e.id,
                       e.status,
                       e.enrol,
                       c.id as courseid,
                       c.{$courseuniquefield}
                  FROM {{$internaltable}} e
            INNER JOIN {course} c
                    ON e.courseid = c.id
            INNER JOIN {databaseextended_flags} flags
                    ON c.id = flags.itemid AND flags.tablename = 'course'
                 WHERE e.enrol = '{$enrolname}'
                     ";
        $recordset = $DB->get_recordset_sql($sql);
        while ($recordset->valid()) {
            $row = $recordset->current();
            $this->add_to_cache($row, $row->$courseuniquefield);
            $recordset->next();
        }

        return true;
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'enrol';
    }

    /**
     * We never want to 'claim' existing enrolments because you're either enrolled or you're not depending on
     * what the external data specifies. Also, we currently don't need to worry about the change of state of
     * an enrolment - but note that when we do (say for enhanced reporting) then claiming and creating flags may become
     * necessary.
     *
     * Returning false will ensure that any new instances are added to the enrol table.
     *
     * @param stdClass $incomingrow
     * @return bool
     */
    protected function claim_existing_record($incomingrow) {
        return false;
    }

}

