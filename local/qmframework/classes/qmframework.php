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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.gig.

/**
 * Local qmframework class
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Maria Sorica <maria.sorica@catalyst-eu.net>
 */

namespace local_qmframework;
defined('MOODLE_INTERNAL') || die();

use moodle_url;

require_once($CFG->dirroot . '/local/qmframework/lib.php');

class qmframework {

    /**
     * Return user's Mahara dashboard link if it exist.
     *
     * The user must have the student role within the configured QM Framework
     * course for a dashboard link to exist in the 'local_qmframework_links' table.
     *
     * @return boolean
     */
    public static function get_dashboardlink() {
        global $DB, $USER;
        $settings = local_qmframework_get_connection_settings();
        $host     = local_qmframework_mahara_mnet_host($settings['host']);

        $record = $DB->get_record_sql(
            'SELECT *
               FROM {local_qmframework_links}
              WHERE userid = ? AND groupid = 0', [$USER->id]);

        if ($record) {

            // If the URL contains the dashboard host use a jump URL.
            if (strpos($record->link, $host->wwwroot) !== false) {
                $link = str_replace($host->wwwroot, '', $record->link);
                return new moodle_url('/auth/mnet/jump.php', array('hostid' => $host->id, 'wantsurl'=> $link));
            }

            return $record->link;
        }

        return false;
    }

    /**
     * Add a dashboard link for a given user
     *
     * @param integer $userid the user to add the link for
     * @param string  $link the dashboard URL string
     */
    public static function add_dashboardlink($userid, $link) {
        global $DB;

        $record = new \stdClass;
        $record->userid  = $userid;
        $record->groupid = 0;

        // If there is more than one record delete all to avoid duplicates.
        if ($DB->count_records('local_qmframework_links', (array) $record) > 1) {
            $DB->delete_records('local_qmframework_links', (array) $record);
        }

        // Insert if doesn't exist, update otherwise.
        if (!$record->id = $DB->get_field('local_qmframework_links', 'id', (array) $record)) {
            $record->link = $link;
            $DB->insert_record('local_qmframework_links', $record);
        } else {
            $record->link = $link;
            $DB->update_record('local_qmframework_links', $record);
        }
    }

    /**
     * Return tutors's Mahara advisees page link if it exist.
     *
     * The user must have the tutor role within the configured QM Framework
     * course for a advisees link to exist in the 'local_qmframework_links' table.
     *
     * @return array or boolean
     */
    public static function get_adviseeslinks() {
        global $DB, $USER;
        $settings = local_qmframework_get_connection_settings();
        $host     = local_qmframework_mahara_mnet_host($settings['host']);

        $records = $DB->get_records_sql(
            'SELECT l.link, g.name
               FROM {local_qmframework_links} l
               JOIN {groups} g ON g.id = l.groupid
              WHERE userid = ? AND groupid != 0', [$USER->id]);

        if ($records) {
            $links = array();
            foreach ($records as $record) {
                $groupdetails = array();
                $groupdetails['name'] = $record->name;
                $groupdetails['link'] = $record->link;

                // If the URL contains the dashboard host use a jump URL.
                if (strpos($record->link, $host->wwwroot) !== false) {
                    $link = str_replace($host->wwwroot, '', $record->link);
                    $groupdetails['link'] = new moodle_url('/auth/mnet/jump.php', array('hostid' => $host->id, 'wantsurl'=> $link));
                }

                array_push($links, $groupdetails);
            }
            return $links;
        }

        return false;
    }

    /**
     * Add an advisees link for a given user
     *
     * @param integer $userid the user to add the link for
     * @param integer $groupid the moodle group ID reference
     * @param string  $link the advisees URL string
     */
    public static function add_adviseeslink($userid, $groupid, $link) {
        global $DB;

        $record = new \stdClass;
        $record->userid  = $userid;
        $record->groupid = $groupid;
        $record->link    = $link;
        $params = array('userid' => $userid, 'groupid' => $groupid);
        if (!$id = $DB->get_record('local_qmframework_links', $params)) {
            $DB->insert_record('local_qmframework_links', $record);
        } else {
            $record->id = $id;
            $DB->update_record('local_qmframework_links', $record);
        }
    }

    /**
     * Remove any obsolete advisees links where users no longer
     * have tutor membership
     *
     * @param array   $tutorids the current list of group tutors IDs
     * @param integer $groupid the group ID
     */
    public static function remove_adviseeslinks($tutorids, $groupid) {
        global $DB;

        $params = [];
        $where = 'groupid = :groupid';
        if (!empty($tutorids)) {
            list($sql, $params) = $DB->get_in_or_equal($tutorids, SQL_PARAMS_NAMED, 'u', false);
            $where .= " AND userid {$sql}";
        }
        $params['groupid'] = $groupid;

        // Remove any existing advisees links where the user no longer has
        // a membership of tutor within the group.
        $DB->delete_records_select('local_qmframework_links', $where, $params);
    }
}
