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

require_once($CFG->dirroot.'/enrol/databaseextended/classes/join_table.class.php');

/**
 * Manages the sync for the user_enrolments table.
 */
class enrol_databaseextended_user_enrolments_table extends enrol_databaseextended_join_table {

    /**
     * @var bool Allow bulk inserts
     */
    protected $bulkinsert = true;

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param \stdClass $data
     * @throws coding_exception
     * @return \stdClass
     */
    protected function add_instance($data) {

        global $DB;

        // We need the courseid but it's not part of the record from outside as we swap course
        // identifier for enrolid.

        if ($data->courseid == SITEID) {
            throw new coding_exception('invalid attempt to enrol into frontpage course!');
        }

        $name = $this->enrolmentplugin->get_name();

        $data->status       = ENROL_USER_ACTIVE;
        $data->timestart    = isset($data->timestart) ? $data->timestart : time();
        $data->timeend      = isset($data->timeend) ? $data->timeend : 0;
        $data->modifierid   = 0;
        $data->timecreated  = time();
        $data->timemodified = time();
        $data->id           = $DB->insert_record('user_enrolments', $data);

        // Add extra info and trigger event.
        $data->enrol = $name;

        return $data;
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param stdClass $thingtodelete
     */
    protected function delete_instance($thingtodelete) {

        global $DB;

        // TODO Do we want to unenrol by suspension or by deletion? Need a setting.
        $unenroleaction = $this->enrolmentplugin->get_config('unenrolaction');

        // Do we unassign roles here or leave it for the later table sync of role assignments?
        // If there is a default role, it may need un assigning.
        // TODO always sync the roles table even if no settings? (using default role).

        // Needs enrol, courseid and row id from enrolments.
        switch ($unenroleaction) {

            case ENROL_EXT_REMOVED_UNENROL: // Unenrol user from course.
                $instance = $DB->get_record('enrol', array('id' => $thingtodelete->enrolid));
                $this->enrolmentplugin->unenrol_user($instance, $thingtodelete->userid);
                break;

            case ENROL_EXT_REMOVED_KEEP: // Keep user enrolled. Default.
                // Do nothing.
                break;

            case ENROL_EXT_REMOVED_SUSPENDNOROLES: // Disable course enrolment and remove roles
                // Role assignments are taken care of by the
                // enrol_databaseextended_role_assignments_table class.

                // Deliberate fall-through.

            case ENROL_EXT_REMOVED_SUSPEND: // Disable course enrolment
                // Just flag it.
                $DB->set_field('user_enrolments', 'status', ENROL_USER_SUSPENDED,
                               array('enrolid' => $thingtodelete->enrolid,
                                    'userid' => $thingtodelete->userid));
                break;

        }
    }

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * This is effectively a join table, so the unique key is two fields (user-enrolmentinstanceid)
     * not one. This means we use a multi dimensional array for fast searching.
     *
     * Complication: the table has enrolid, not course id so we need to translate this.
     *
     * @return array
     */
    public function get_internal_existing_rows() {
        global $DB;

        // TODO use flags to avoid duplicates.
        $coursetable     = $this->enrolmentplugin->get_table_object('course');
        $courseuniquekey = $coursetable->get_internal_unique_field_name();
        $tablename       = $this->get_internal_table_name();
        $enrolname       = $this->enrolmentplugin->get_name();

        $sql = "SELECT ue.id,
                               ue.enrolid,
                               ue.userid,
                               e.courseid,
                               c.{$courseuniquekey}
                          FROM {{$tablename}} ue
                    INNER JOIN {enrol} e
                            ON e.id = ue.enrolid
                    INNER JOIN {course} c
                            ON c.id = e.courseid
                         WHERE e.enrol = '{$enrolname}'";

        return $DB->get_records_sql($sql);
    }

    /**
     * @static
     * Called by the install procedure and by the unit test code to add the initial relations to
     * the DB
     */
    public static function install() {
        global $DB;

        $relations = array(

            array('maintable'           => 'user_enrolments',
                  'maintablefield'      => 'userid',
                  'secondarytable'      => 'user',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'user_enrolments',
                  'maintablefield'      => 'userid',
                  'secondarytable'      => 'extenrolments',
                // Works for unit tests. Probably not a good default.
                  'secondarytablefield' => 'user',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'user_enrolments',
                  'maintablefield'      => 'enrolid',
                  'secondarytable'      => 'enrol',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            // TODO what problems will this cause?
            // This is not a real relation and depends on some other hardcoded stuff to take account
            // of the need to translate coursename into enrolid.
            array('maintable'           => 'user_enrolments',
                  'maintablefield'      => 'enrolid',
                  'secondarytable'      => 'extenrolments',
                  'secondarytablefield' => 'course',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE)

        );
        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Adds the courseid as we really need it to trigger events later
     *
     * @param $externalrow
     */
    protected function substitute_for_moodle_ids($externalrow) {

        $courseidentifier = $externalrow->enrolid;

        parent::substitute_for_moodle_ids($externalrow);

        $coursetablename = $this->enrolmentplugin->get_table_classname('course');
        $coursetable     = $coursetablename::get_table_singleton();
        // Is the cache primed? Relations won't give us this.
        if (!$coursetable->cache_is_primed() && !$this->issingleuser) {
            $coursetable->populate_cache();
        }
        $courseid = $coursetable->retrieve_from_cache($courseidentifier);

        $externalrow->courseid = $courseid;
    }

    /**
     * Some tables, rather than being deleted, are marked as invisible. If so, we need to add a
     * condition to the SQL used to populate the cache so that those ones are ignored.
     *
     * @return array
     */
    protected function get_sql_not_deleted_conditions() {
        // Default is to get all records.
        return array('status' => ENROL_USER_ACTIVE);
    }

    /**
     * We need the course id for the events trigger too.
     *
     * @return array
     */
    public function get_linked_internal_table_names() {

        return array_merge(parent::get_linked_internal_table_names(), array('course'));
    }

    /**
     * SQL to fill the cache
     *
     * @param string $fields
     * @return string
     */
    public function get_populate_cache_sql($fields = '*') {

        $table = $this->get_internal_table_name();

        // Only enrolments for a databaseextended enrol instance.
        $sql = "
            SELECT {$fields}
              FROM {{$table}} this
        INNER JOIN {enrol} enrol
                ON this.enrolid = enrol.id
             WHERE enrol.enrol = 'databaseextended'
        ";

        return $sql;
    }

    /**
     * Preformats the data for bulk insert
     *
     * @param bool|stdClass $row
     * @return mixed
     * @throws coding_exception
     */
    public function bulk_insert($row = false) {

        if (!$row) {
            parent::bulk_insert();
            return;
        }

        if ($row->courseid == SITEID) {
            throw new coding_exception('invalid attempt to enrol into frontpage course!');
        }

        $data = new stdClass();

        $data->userid       = $row->userid;
        $data->enrolid      = $row->enrolid;
        $data->status       = ENROL_USER_ACTIVE;
        $data->timestart    = isset($data->timestart) ? $data->timestart : 0;
        $data->timeend      = isset($data->timeend) ? $data->timeend : 0;
        $data->modifierid   = 0;
        $data->timecreated  = time();
        $data->timemodified = time();

        parent::bulk_insert($data);

    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'user_enrolments';
    }

    /**
     * Some tables hide things instead of deleting them. This un-hides them if they reappear in the
     * external DB.
     *
     * @param stdClass $hiddenrow
     * @return bool
     */
    protected function reanimate($hiddenrow) {

        global $DB;

        if (empty($hiddenrow)) {
            return false;
        }

        $hiddenrow->status = ENROL_USER_ACTIVE;
        $result = $DB->update_record('user_enrolments', $hiddenrow);

        return true;
    }
    /**
     * We get the status field in cache
     *
     * @return array
     */
    public function get_extra_fields() {
        return array('this.status');
    }

    /**
     * This determines whether we will be trying to get existing records and claimm them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
    	return $this->get_config('user_enrolments_claim_existing', true);
    }

    /**
     * This will try to find an existing row with the same key field as the incoming row. If one's there, it'll get
     * claimed by way of having a flag attached.
     *
     * @param stdClass $incomingrow
     * @return bool
     */
    protected function claim_existing_record($incomingrow) {
        global $DB;

        if (!$this->should_try_to_claim_existing_items()) {
            return false;
        }

        $uniquekey = $this->get_internal_unique_field_array();

        $params = array();
        foreach ($uniquekey as $keyfield) {
            $params[$keyfield] = $incomingrow->$keyfield;
        }

        // Is there a record and a flag?
        try {
            $existingrecord = $this->get_existing_internal_row($incomingrow);
        } catch (Exception $e) {
            echo 'Found duplicate records whilst claiming existing (join table): ' . $this->get_internal_table_name() .
                ' ' . implode(',', $params);
            return true;
        }

        if ($existingrecord) {
            $incomingrow->id = $existingrecord->id;


            $existingrecord->enrol = 'databaseextended';
            $DB->update_record($this->get_internal_table_name(), $existingrecord);

            // We need to use the cache to find parent ids on subsequent passes.
            $incomingrow->notfordeletion = true;
            $this->add_to_cache($incomingrow);

            return true;
        }

        return false;
    }

}

