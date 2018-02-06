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
 * Local qmframework sync group event
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
namespace local_qmframework\event;

defined('MOODLE_INTERNAL') || die();

class group_sync extends \core\event\base {

    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'groups';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('syncgroupevent', 'local_qmframework');
    }

    /**
     * Returns non-localised event description.
     *
     * @return string
     */
    public function get_description() {
        $details = 'Details: ' . json_encode($this->other);
        return 'Attempted to sync group: "' . $this->objectid . '" to mahara. ' . $details;
    }

    /**
     * Create instance of event.
     *
     * @param integer $groupid the ID of the group synced.
     * @param string $status the curent sync event's status
     * @param string $info details about the cause of the event's status.
     * @return user_sync_succeeded event
     */
    public static function create_sync($groupid, $status, $other) {
        $settings = local_qmframework_course_and_group_settings();
        $context = \context_course::instance($settings['course']);
        $data = [
            'context' => $context,
            'objectid' => $groupid,
            'other' => $other
        ];
        $event = self::create($data);
        return $event;
    }
}
