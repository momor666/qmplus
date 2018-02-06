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

require_once($CFG->dirroot."/course/lib.php");
require_once($CFG->dirroot.'/enrol/databaseextended/classes/single_table.class.php');

/**
 * Implements the speedy sync algorithm for courses table. Really just a rearrangement of the
 * standard 2.0 core stuff.
 */
class enrol_databaseextended_course_table extends enrol_databaseextended_single_table {

    /**
     * @var string The external table that has the courses we want to sync with
     */
    protected $externaltable;

    /**
     * @var string Speedy or efficient
     */
    protected $tabletype = 'speedy';

    /**
     * @var array items that need to be added to the database.
     */
    public $itemstoadd;

    /**
     * @var array Fields we must have in the external data
     */
    protected $mandatoryfields = array('shortname',
                                       'fullname');

    /**
     * Gets all of the rows of the internal table that we want to sync against. Should be keyed by
     * unique id to match on.
     *
     * Not used except for when one-to-one tables need to have this data instead of external dat
     * @todo make 1-1 tables use the cache instead
     *
     * @throws coding_exception
     * @return array
     */
    public function get_internal_existing_rows() {

        global $DB;

        // We want all courses, along with whether they have a databaseextended enrolment instance.
        // assumes we have no duplicates.
        // TODO use flags to avoid duplicates.

        $uniquefield = $this->get_internal_unique_field_name();
        $tablename   = $this->get_internal_table_name();
        if (!is_string($uniquefield)) {
           $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_MAJOR);
            throw new coding_exception('Must have single unique field for courses table sync');
        }
        $enrolmentname = $this->enrolmentplugin->get_name();
        $sql           = "
                SELECT c.{$uniquefield},
                       c.id,
                       c.visible,
                       e.id AS enrolid
                  FROM {course} c
             LEFT JOIN {enrol} e
                    ON (c.id = e.courseid
                        AND e.enrol = '{$enrolmentname}')
                  WHERE c.{$uniquefield} IS NOT NULL
                    AND c.{$uniquefield} != ''
                    AND c.id != :siteid ";

        return $DB->get_records_sql($sql, array('siteid' => SITEID));
    }

    /**
     * Makes a new entry in the internal table, corresponding to an external row
     *
     * @param $data
     *
     * @return object
     */
    protected function add_instance($data) {

        global $DB;

        // We don't want to fix the course sortorder every time because it's so expensive.
        // Only do it every 500, then once at the end.
        static $numbercreated = 0;
        static $templatecourse;

        $enrolmentname = $this->enrolmentplugin->get_name();

        // Cache to avoid repeated DB grind.
        if (!isset($templatecourse)) {
            $templateshortname = $this->enrolmentplugin->get_config('templatecourse');
            if (!empty($templateshortname)) {
                $templatecourse = $DB->get_record('course', array('shortname' => $templateshortname));
            } else {
                $templatecourse = false;
            }
        }

        // TODO - this is slow and should be already taken care of for the primary external.
        // key field, so we just need to use the cache to check if we have a dupe of the other one.
        // Check if the shortname already exist.
        // This will happen if we have dupes in the external data.
        if (!empty($data->shortname)) {
            // Whitespace in external table on one row and not another will lead to dupes here.
            $existingcourse = $DB->get_record('course', array('shortname' => $data->shortname));
            if ($existingcourse) {
                if ($this->should_try_to_claim_existing_items()) {
                    if (isset($data->externalname)) {
                        // Don't lose this if it's there!
                        $existingcourse->externalname = $data->externalname;
                    }
                    // Claim it as our own!
                    $this->add_item_flag($existingcourse);
                    return true;
                } else if (!$DB->get_record('enrol', array('enrol' => $enrolmentname, 'courseid' => $existingcourse->id))) {
                    $plugin = enrol_get_plugin($enrolmentname);
                    $plugin->add_instance($existingcourse);
                    return true;
                } else {
                   $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_WARNING);
                   echo 'Shortname "'.$data->shortname.'" already taken';
                   return true;
                }
            }
        }

        // Check if the id number already exist.
        if (!empty($data->idnumber)) {
            $existingcourse = $DB->get_record('course', array('idnumber' => $data->idnumber));
            if ($existingcourse) {
                if ($this->should_try_to_claim_existing_items()) {
                    if (isset($data->externalname)) {
                        // Don't lose this if it's there!
                        $existingcourse->externalname = $data->externalname;
                    }
                    // Claim it as our own!
                    $this->add_item_flag($existingcourse);
                    return true;
                } else if (!$DB->get_record('enrol', array('enrol' => $enrolmentname, 'courseid' => $existingcourse->id))) {
                    $plugin = enrol_get_plugin($enrolmentname);
                    $plugin->add_instance($existingcourse);
                    return true;
                } else {
                   $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_WARNING);
                   echo 'ID number '.$data->idnumber.' already taken';
                   return true;
                }
            }
        }

        if (empty($data->category) || !is_int($data->category)) {
            $data->category = $this->enrolmentplugin->get_config('defaultcategory', 1);
        }

        if ($templatecourse) {
            unset($templatecourse->id);
            unset($templatecourse->shortname);
            unset($templatecourse->fullname);
            unset($templatecourse->idnumber);
            foreach ($templatecourse as $key => $value) {
                if (!isset($data->$key)) {
                    $data->$key = $value;
                }
            }
        } else {
            $courseconfig = get_config('moodlecourse');
            $data->format         = $courseconfig->format;
            $data->numsections    = $courseconfig->numsections;
            $data->hiddensections = $courseconfig->hiddensections;
            $data->newsitems      = $courseconfig->newsitems;
            $data->showgrades     = $courseconfig->showgrades;
            $data->showreports    = $courseconfig->showreports;
            $data->maxbytes       = $courseconfig->maxbytes;
            $data->groupmode      = $courseconfig->groupmode;
            $data->groupmodeforce = $courseconfig->groupmodeforce;
            //removed visible as it overrides visible if it is mapped
            //$data->visible        = $courseconfig->visible;
        }

        $data->timecreated  = time();
        $data->timemodified = $data->timecreated;

        // Place at beginning of any category.
        $data->sortorder = 0;

        if (!isset($data->visible)) {
        	// Whether or not a new course is visible depends on a global configuration setting
        	$data->visible = ($this->enrolmentplugin->get_config('newcourseisvisible'));
        }
        $data->visibleold = $data->visible;

        $newcourseid = $DB->insert_record('course', $data);
        $context     = context_course::instance($newcourseid, MUST_EXIST);

        // Needed to add all the other defaults.
        $newcourse = $DB->get_record('course', array('id'=> $newcourseid));
        if (isset($data->externalname)) {
            $newcourse->externalname = $data->externalname;
        }

        // Setup the blocks.
        blocks_add_default_course_blocks($newcourse);

        $section                = new stdClass();
        $section->course        = $newcourse->id; // Create a default section.
        $section->section       = 0;
        $section->summaryformat = FORMAT_HTML;
        $DB->insert_record('course_sections', $section);

        // TODO is every 500 too frequent?
        // TODO need this after the last one too.
        if ($numbercreated !== 0 && (($numbercreated % 500) == 0)) {
            // Very expensive, so not every time.
            fix_course_sortorder();
        }

        // Mew context created - better mark it as dirty.
        $context->mark_dirty();

        // Save any custom role names.
        // save_local_role_names($newcourse->id, (array)$data);

        // Tell all the enrolment plugins.
        // TODO is this really needed? Usually it allows a new instance to be created for
        // each course e.g. manual.
        enrol_course_updated(true, $newcourse, $data);

        // TODO should this be logged?
        // add_to_log(SITEID, 'course', 'new', 'view.php?id='.$newcourse->id,
        //           $data->fullname.' (ID '.$newcourse->id.')');

        // Trigger events
        // TODO re-enable
        // events_trigger('course_created', $newcourse);

        $this->add_item_flag($newcourse);

//        $event=\core\event\course_created::create(array('context'=>context_course::instance($newcourse->id),
//                                                        'objectid'=>$newcourseid));
        $event = \core\event\course_created::create(array(
                                                        'objectid' => $newcourseid,
                                                        'context' => context_course::instance($newcourseid),
                                                        'other' => array('shortname' => $newcourse->shortname,
                                                                         'fullname' => $newcourse->fullname)
                                                    ));

        $event->trigger();

        $numbercreated++;

        return $newcourse;
    }

    /**
     * Delete a row in the internal table that corresponds to an external row
     *
     * @param $course
     */
    protected function delete_instance($course) {
       return;
        global $DB;
        // Set to hidden.
        $course->visible = 0;
        $DB->update_record($this->get_internal_table_name(), $course);

        $event = \core\event\course_deleted::create(array(
                                                        'objectid' => $course->id,
                                                        'context' => $context,
                                                        'other' => array(
                                                            'shortname' => $course->shortname,
                                                            'fullname' => $course->fullname,
                                                            'idnumber' => $course->idnumber
                                                        )
                                                    ));

        $event->trigger();

    }

    /**
     * Checks whether a particular record needs updating by comparing each of the mapped
     * fields of each object. If necessary, the incoming object is mapped to the database.
     *
     * @param $externalrow
     * @param $mappedfieldnames
     * @param $internalrow
     */
    protected function do_update_if_necessary($externalrow, $mappedfieldnames, $internalrow) {
        parent::do_update_if_necessary($externalrow, $mappedfieldnames, $internalrow);
    }
    
    /**
     * @param $incoming
     *
     * @internal param $existing
     * @return bool
     */
    protected function update_instance($incoming) {
    	if(false and $this->enrolmentplugin->get_config('makecoursevisiblewhentouched')) { // Unhide if necessary.
        	$incoming->visible = 1;
    	}
        parent::update_instance($incoming);
    }

    /**
     * Adds the relations that this table has to the database
     */
    public static function install() {
        global $DB;

        $relations = array(
            // This relation links the courses table to the categories table. A foreign key.
            array('maintable'           => 'course',
                  'maintablefield'      => 'category',
                  'secondarytable'      => 'course_categories',
                  'secondarytablefield' => 'id',
                  'internal'            => ENROL_DATABASEEXTENDED_INTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'course',
                  'maintablefield'      => 'shortname',
                  'secondarytable'      => 'extcourses',
                // Works for unitests. Probably no a good default.
                  'secondarytablefield' => 'extshortname',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_UNIQUE),
            array('maintable'           => 'course',
                  'maintablefield'      => 'fullname',
                  'secondarytable'      => 'extcourses',
                  'secondarytablefield' => 'extfullname',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_NON_UNIQUE),
            array('maintable'           => 'course',
                  'maintablefield'      => 'category',
                  'secondarytable'      => 'extcourses',
                  'secondarytablefield' => 'category',
                  'internal'            => ENROL_DATABASEEXTENDED_EXTERNAL,
                  'uniquefield'              => ENROL_DATABASEEXTENDED_NON_UNIQUE)
        );
        foreach ($relations as $relation) {
            $DB->insert_record('databaseextended_relations', $relation);
        }
    }

    /**
     * Deleted courses are set to hidden, so we exclude any that are not visible when populating the
     * cache.
     *
     * @return array
     */
    protected function get_sql_not_deleted_conditions() {
        $result = array();
        if(!$this->enrolmentplugin->get_config('allowcoursevisibilitymapping', 0)) {
            $result = array('visible' => 1);
        }
        return $result;
    }

    /**
     * @return array
     */
    protected function get_extra_fields() {
        return array_keys($this->get_sql_not_deleted_conditions());
    }
        /**
     * Called once all the courses have been added
     */
    protected function post_add_hook() {
        echo 'Fixing course sortorder...'.$this->enrolmentplugin->get_line_end();
        flush();
        fix_course_sortorder();
        context_helper::build_all_paths(); // TODO should this be force = true?
    }

    /**
     * Tells us to add flags on creation and only delete items that have one
     * @return bool
     */
    public function use_flags() {
        return true;
    }

    /**
     * @return string
     */
    public function get_internal_table_name() {
        return 'course';
    }

    /**
     * Adds the default category if necessary
     *
     * @param stdClass $externalrow
     */
    protected function substitute_for_moodle_ids($externalrow) {

        try {
            parent::substitute_for_moodle_ids($externalrow);
        }
        catch (cache_exception_dbx $idnotfoundexception) {
            $errorcode = $idnotfoundexception->getCode();
            $internaltablename = $idnotfoundexception->getInternalTableName();
            // If the exception is caused by a missing category id, we fix that by using the default category.
            // This missing category will be missing in the mapping.
            if($errorcode == cache_exception_dbx::CACHE_MISSING_ROW &&
                (strcmp($internaltablename, 'course_categories' == 0)) ) {
                $externalrow->category = $this->enrolmentplugin->get_config('defaultcategory', 1);
            }
            else
            {
               $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_MAJOR);
            }
        }
        catch (Exception $idnotfoundexception) {
             // TODO: catch a general exception
           $this->errorStatus(enrol_databaseextended_plugin::ENROL_DATABASEEXTENDED_STATUS_MAJOR);
        }
    }

    /**
     * This determines whether we will be trying to get existing records and claim them for the sync script to manage, or
     * whether we just leave them alone.
     *
     * @return bool
     */
    protected function should_try_to_claim_existing_items() {
    	return $this->get_config('course_claim_existing', true);
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

        return true;

        if (!$this->enrolmentplugin->get_config('makecoursevisiblewhentouched')) {
            return true;
        }

        $hiddenrow->visible = 1;
        return $DB->update_record($this->get_internal_table_name(), $hiddenrow);

    }

    public function retrieve_from_cache($externalkey, $tablename = false, $findhidden = true)
    {
       return parent::retrieve_from_cache($externalkey, $tablename, $findhidden);
    }

    protected function retrieve_directly_from_table($externalkey, $findhidden = true)
    {
       return parent::retrieve_directly_from_table($externalkey,$findhidden);
    }
}
