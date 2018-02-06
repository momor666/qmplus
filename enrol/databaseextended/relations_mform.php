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
 * This form will present the relations as they eist in the database, allowing details to be changed
 * plus additions and deletions.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012 University of London Computer Centre {@link http://ulcc.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Makes the form which allows relations to be added, deleted and altered
 */
class enrol_databaseextended_relations_mform extends moodleform {

    /**
     * @var enrol_databaseextended_plugin Instance of the enrolment plugin to feed into the
     * singletons
     */
    protected $enrolmentplugin;

    /**
     * @var bool Flag set during save_data(), so that display() knows to add 'changes saved'
     */
    protected $datasaved = false;

    /**
     * @param enrol_databaseextended_plugin $enrolplugin
     */
    public function __construct($enrolplugin) {
        $this->enrolmentplugin = $enrolplugin;
        parent::__construct(); // Magic method calls the old-style constructor.
    }

    /**
     * Getter to check the state of the form. If not data was saved, we get false.
     * @return bool
     */
    public function data_was_saved() {
        return $this->datasaved;
    }

    /**
     * Makes the form that's used to specify the relations between the internal and external tables.
     *
     */
    protected function definition() {

        global $CFG;

        $mform =& $this->_form;

        // Get all the tables.
        $directory = $CFG->dirroot.'/enrol/databaseextended/tables';
        $textattributes = array('size'=> '20');

        // Note: we don't know how many tables or relations there will be, so we make it so that
        // all of the data comes in via an array so we can loop over it.

        foreach (glob($directory."/*.php") as $filename) {
            require_once($filename);
            $bits = explode('.', basename($filename));
            $tablename=array_shift($bits);
            /* @var enrol_databaseextended_table_base $tableobject */
            $tableobject = $this->enrolmentplugin->get_table_object($tablename);

            if (!$tableobject->sync_enabled()) {
                continue;
            }
            if ($tableobject->is_internal_one_to_one()) {
                continue;
            }

            $mform->addElement('header',
                               $tablename.'_header',
                               $tablename);

            if ($tableobject->check_config_vars_present()) {
                $message = get_string('configok', 'enrol_databaseextended');
                $attributes = array('class' => 'notifysuccess');
            } else {
                $message = get_string('configwrong', 'enrol_databaseextended');
                $attributes = array();
            }
            $mform->addElement('static', $tablename.'-status', get_string('status'),
                               $message, $attributes);

            // Name of external table.
            $fieldname = 'tables['.$tablename.'][externaltable]';
            $fieldtitle = 'External table name';
            $mform->addElement('text', $fieldname, $fieldtitle, $textattributes);
            $mform->setType($fieldname, PARAM_ALPHANUMEXT);

            $relations = $tableobject->get_mapped_relations(); // Unique will come first.
            foreach ($relations as $relation) {

                // Add a group so they are all on one line.
                $grouparray   = array();
                // Name of external field.

                $fieldname = 'tables['.$relation->maintable.']['.$relation->maintablefield.
                             '][externalfield]';
                $grouparray[] =& $mform->createElement('text', $fieldname, '', $textattributes);
                $mform->setType($fieldname, PARAM_ALPHANUMEXT);

                // Unique checkbox/radio
                // Should be a forced option for join table fields that have an internal relation
                // as you can't just have your own key for a join.
                $allowdelete = true;
                if ($tableobject instanceof enrol_databaseextended_join_table) {
                    // Unique fields are fixed. Don't allow changes and show unique checkboxes
                    // as disabled.
                    /* @var enrol_databaseextended_join_table $tableobject */
                    if ($tableobject->is_required_relation($relation)) {
                        // Ignored dummy field.
                        $fieldname = 'tables[' . $relation->maintable . '][' .
                            $relation->maintablefield . '][uniquefielddummy]';
                        $fieldtitle = get_string('unique', 'enrol_databaseextended');
                        $grouparray[] =& $mform->createElement('checkbox',
                                                               $fieldname,
                                                               null,
                                                               $fieldtitle,
                                                               array('disabled' => 'disabled'));

                        // Actual field - saves us doing the same check for relationtype during
                        // data processing.
                        $fieldname = 'tables[' . $relation->maintable . '][' .
                            $relation->maintablefield . '][uniquefield]';
                        $grouparray[] =& $mform->createElement('hidden', $fieldname, '1');
                        $mform->setType($fieldname, PARAM_INT);

                        $allowdelete = false;

                    }

                } else { // Single table.
                    // Allow the user to alter whether or not this is the unique key field.
                    // We can only have one, so we use radio buttons, but this is a bit awkward
                    // when we want a nice clean single-value-per-row for 'uniquefield'.
                    $fieldname = 'tables[' . $relation->maintable . '][uniquefield]';
                    $fieldtitle = get_string('unique', 'enrol_databaseextended');
                    $grouparray[] =& $mform->createElement('radio',
                                                           $fieldname,
                                                           null,
                                                           ' '.$fieldtitle,
                                                           $relation->id);
                    $mform->setType($fieldname, PARAM_INT);
                }

                // Delete checkbox.
                if ($allowdelete) {
                    $fieldname    = 'tables['.$relation->maintable.']['.$relation->maintablefield.
                    '][delete]';
                    $fieldtitle   = get_string('delete');
                    $grouparray[] =& $mform->createElement('checkbox', $fieldname,
                                                           null, $fieldtitle);
                }

                if ($relation->maintablefield == 'externalname') {
                    // Special case - field is not in this table.
                    $grouptitle = get_string('flagfield', 'enrol_databaseextended').
                                  " external field name";
                } else {
                    $grouptitle = "'".$relation->maintablefield."' external field name";
                }
                $groupname = $relation->maintable.'_'.$relation->maintablefield.'_group';
                $mform->addGroup($grouparray, $groupname,
                                 $grouptitle, array(' '), false);

                // We need the DB id of the relation to write quickly on submit.
                $fieldname = 'tables['.$tablename.']['.$relation->maintablefield.'][id]';
                $mform->addElement('hidden', $fieldname, $relation->id);
                $mform->setType($fieldname, PARAM_INT);
            }

            // Fields to add a new one.
            $newrelationarray = array();
            $fieldname =
                'tables['.$tablename.'][addrelation][internalfield]';
            $newrelationarray['internalfield'] =& $mform->createElement('text', $fieldname,
                                                                        '', $textattributes);
            $mform->setType($fieldname, PARAM_ALPHAEXT);

            $fieldname =
                'tables['.$tablename.'][addrelation][externalfield]';
            $newrelationarray['externalfield'] =& $mform->createElement('text', $fieldname,
                                                                        '', $textattributes);
            $mform->setType($fieldname, PARAM_ALPHAEXT);

            // If flags are in use, we can use the extra field in that table to have an external ID
            // which is not directly synced to a user-visible field.
            if ($tableobject->use_flags()) { // TODO only one allowed - check if it's already there.
                $fieldname = 'tables['.$tablename.'][addrelation][useflagfield]';
                $fieldtitle = get_string('flagfield', 'enrol_databaseextended');
                $newrelationarray[] =& $mform->createElement('checkbox',
                                                       $fieldname,
                                                       null,
                                                       $fieldtitle);
                $mform->disabledIf('tables['.$tablename.'][addrelation][internalfield]',
                                   'tables['.$tablename.'][addrelation][useflagfield]', 'checked');
            }

            $grouptitle = 'Add a new relation (internal & external fields)';
            $mform->addGroup($newrelationarray,
                             $tablename.'_newrelation_group',
                             $grouptitle, array(' '), false);

            // Now give the option to specify that the table should not do updates, just create and delete.
            $mform->addElement('advcheckbox', $tablename.'_disable_updates',
                               get_string('disableupdates', 'enrol_databaseextended'));
            $mform->addHelpButton($tablename.'_disable_updates', 'disableupdates', 'enrol_databaseextended');

            // Now let the user choose whether the sync should try to claim any existing stuff so that the sync will
            // manage it from now on (if the unique identifiers match), rather than creating duplicates.
            $mform->addElement('advcheckbox', $tablename.'_claim_existing',
                               get_string('claimexisting', 'enrol_databaseextended'));
            $mform->addHelpButton($tablename.'_claim_existing', 'claimexisting', 'enrol_databaseextended');
        }

        $this->add_action_buttons();

    }

    /**
     * Add the existing stuff from the relations to the form
     *
     */
    public function load_existing_data() {

        global $DB;

        $mform =& $this->_form;

        $relations = $DB->get_records('databaseextended_relations');

        $tables = array();

        foreach ($relations as $relation) {
            if ($relation->internal == ENROL_DATABASEEXTENDED_EXTERNAL) {

                $tables[] = $relation->maintable; // Collect so we can add per table config stuff in the loop lower down.

                $fieldname = 'tables['.$relation->maintable.']['.$relation->maintablefield.'][externalfield]';
                $mform->setDefault($fieldname, $relation->secondarytablefield);

                // The table will only need setting once, but seeing as it will always be the same
                // there's no harm doing it repeatedly.
                $fieldname = 'tables['.$relation->maintable.'][externaltable]';
                $mform->setDefault($fieldname, $relation->secondarytable);

                // Single tables use radio group, join use checkboxes. No problem setting defaults
                // that aren't used, so cover all bases and let the extra ones get ignored.
                $fieldname = 'tables[' . $relation->maintable . '][uniquefield]';
                if (!empty($relation->uniquefield)) {
                    $mform->setDefault($fieldname, $relation->id);
                }
                $fieldname = 'tables[' . $relation->maintable . '][' . $relation->maintablefield .
                    '][uniquefield]';
                $mform->setDefault($fieldname, (empty($relation->uniquefield) ? 0 : 1));
                // In case we are showing a disabled field to the user.
                $fieldname = 'tables[' . $relation->maintable . '][' . $relation->maintablefield .
                    '][uniquefielddummy]';
                $mform->setDefault($fieldname, (empty($relation->uniquefield) ? 0 : 1));
            }
        }

        // Load the settings.
        foreach ($tables as $table) {

            $settings = array(
                $table.'_disable_updates',
                $table.'_claim_existing'
            );

            foreach ($settings as $setting) {
                $configsetting = get_config('enrol_databaseextended', $setting);
                if ($configsetting !== false) {
                    $mform->setDefault($setting, $configsetting);
                }
            }
        }
    }

    /**
     * We need this to make sure the array is OK. Moodle's optional_param() only deals with single
     * variables.
     *
     * @param $data
     */
    private function sanitise_data_array(&$data) {

        foreach ($data as $name => &$datum) {
            if (is_array($datum)) {
                $this->sanitise_data_array($datum);
            } else {
                $datum = filter_var($datum, FILTER_SANITIZE_STRING);
                // TODO throw error if illegal characters are inputted.
            }
        }

    }

    /**
     * Saves the dynamic relations stuff.
     *
     * @param $data
     */
    public function save_data($data) {

        global $DB;

        $this->sanitise_data_array($data);

        // Should be just one big array of tables.
        foreach ($data->tables as $tablename => $table) {
            $tableobject = $this->enrolmentplugin->get_table_object($tablename);

            // Save config settings.
            $settings = array(
                $tablename.'_disable_updates',
                $tablename.'_claim_existing'
            );
            foreach ($settings as $setting) {
                set_config($setting, $data->$setting, 'enrol_databaseextended');
            }

            $internalfieldtoadd = !empty($table['addrelation']['internalfield']) ?
                $table['addrelation']['internalfield'] : false;
            if (empty($internalfieldtoadd) && !empty($table['addrelation']['useflagfield'])) {
                $internalfieldtoadd = 'externalname'; // In the flags table.
            }
            $externalfieldtoadd = !empty($table['addrelation']['externalfield']) ?
                $table['addrelation']['externalfield'] : false;

            // Add the new relation if it validates and if it's there.
            if (!empty($internalfieldtoadd) &&
                !empty($table['externaltable']) &&
                !empty($externalfieldtoadd)) {

                $columns = $DB->get_columns($tablename);

                if ($columns && array_key_exists($internalfieldtoadd, $columns)) {
                    $this->make_new_relation($tablename, $internalfieldtoadd,
                                             $table['externaltable'],
                                             $externalfieldtoadd);
                }
            }

            unset ($table['addrelation']);

            // Save the external table name. Only needs doing once, but all relations need updating.
            if (!empty($table['externaltable'])) {
                $sql    = "UPDATE {databaseextended_relations}
                              SET secondarytable = :newtable
                            WHERE maintable = :maintable
                              AND internal = :externalflag
                           ";
                $params = array('newtable'      => $table['externaltable'],
                                'maintable'     => $tablename,
                                'externalflag' => ENROL_DATABASEEXTENDED_EXTERNAL);
                $DB->execute($sql, $params);
                $this->datasaved = true;
            }

            unset($table['externaltable']); // Leaves only updates.

            // Delete and update any relations data we got about existing relations.
            $uniquefield = isset($table['uniquefield']) ? $table['uniquefield'] : false;
            foreach ($table as $name => $relation) {

                if ($name == 'uniquefield') {
                    continue;
                }

                $this->update_relation($relation, $tableobject, $uniquefield);
            }
        }
    }

    /**
     * Updates or deletes a relation
     *
     * @param $relation
     * @param $tableobject
     * @param $uniquefield
     * @return string
     */
    protected function update_relation($relation, $tableobject, $uniquefield) {

        global $DB;

        // Make the data into an object for the DB, which doesn't like arrays for inserts.
        $relationobject = new stdClass();
        $relationobject->id = $relation['id'];
        $relationobject->secondarytablefield = $relation['externalfield'];

        if ($tableobject instanceof enrol_databaseextended_single_table) {
            // Unique is via a per-table radio button, not per-relation.
            if ($uniquefield && $relation['id'] == $uniquefield) {
                $relationobject->uniquefield = 1;
            } else {
                $relationobject->uniquefield = 0;
            }
        } else {
            // Checkbox per row.
            $relationobject->uniquefield = empty($relation['uniquefield']) ? 0 : 1;
        }

        $tablename = 'databaseextended_relations';
        // Do we already have a DB record?
        if (!empty($relation['delete'])) {
            $DB->delete_records($tablename, array('id' => $relation['id']));
            $this->datasaved = true;
            return true;
        } else if (!empty($relation['id'])) {
            $DB->update_record($tablename, $relationobject);
            $this->datasaved = true;
            return true;
        }
        return false;
    }

    /**
     * Adds a new non-unique relation
     *
     * @param $internaltable
     * @param $internalfieldtoadd
     * @param $externaltable
     * @param $externalfieldtoadd
     * @return bool
     */
    protected function make_new_relation($internaltable, $internalfieldtoadd, $externaltable,
                                         $externalfieldtoadd) {
        global $DB;

        $newrelation = new stdClass();
        $newrelation->maintable = $internaltable;
        $newrelation->maintablefield = $internalfieldtoadd;
        $newrelation->secondarytable = $externaltable['externaltable'];
        $newrelation->secondarytablefield = $externalfieldtoadd;
        $newrelation->internal = ENROL_DATABASEEXTENDED_EXTERNAL;
        $newrelation->uniquefield = 0;
        $newrelation->id = $DB->insert_record('databaseextended_relations', $newrelation);

        $this->datasaved = true;

        return true;

    }

    /**
     * Custom validation function
     *
     * @param stdClass $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {

        global $DB;

        $errors = parent::validation($data, $files);

        // Validate that a field that we need to add has all the right stuff it'll need.
        foreach ($data['tables'] as $tablename => $table) {

            $internalfieldtoadd = !empty($table['addrelation']['internalfield']) ?
                $table['addrelation']['internalfield'] : false;
            if (empty($internalfieldtoadd) && !empty($table['addrelation']['useflagfield'])) {
                $internalfieldtoadd = 'externalname'; // In the flags table.
            }
            $externalfieldtoadd = !empty($table['addrelation']['externalfield']) ?
                $table['addrelation']['externalfield'] : false;
            $externaltable = $table['externaltable'];

            // Add the new relation if it validates and if it's there.
            if (!empty($internalfieldtoadd) &&
                !empty($table['externaltable']) &&
                !empty($externalfieldtoadd)
            ) {

                $internalcolumns = $DB->get_columns($tablename);
                if (!$internalcolumns || !array_key_exists($internalfieldtoadd, $internalcolumns)) {
                    // Error must be attached to the group, not the individual elements. Won't show up otherwise.
                    $errors[$tablename.'_newrelation_group'] = get_string('columnnotthere', 'enrol_databaseextended');
                }

            }

            // Now check that we have a column that matches this one in the external database.
            if (!empty($externaltable) && !$this->enrolmentplugin->external_table_exists($externaltable)) {
                $errors['tables['.$tablename.'][externaltable]'] = get_string('externaltablenotthere', 'enrol_databaseextended');
            }

            // Column is valid. check we don't already have it in another relation.

            $sql = "SELECT id
                      FROM {databaseextended_relations}
                     WHERE internal = :externalfield
                       AND maintablefield = :fieldname
                       AND maintable = :tablename
                     ";
            $params = array('externalfield' => ENROL_DATABASEEXTENDED_EXTERNAL,
                            'fieldname' => $internalfieldtoadd,
                            'tablename' => $tablename);
            $otherrelations = $DB->get_records_sql($sql, $params);

            if (!empty($otherrelations)) {
                // TODO we already have a mapping relation for this field. Set form error.
            }
        }

        return $errors;
    }


}
