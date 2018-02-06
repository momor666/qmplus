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

namespace enrol_databaseextended\event;

/**
 * Class view_coursework is responsible for logging the view coursework event.
 *
 * @package mod_coursework\event
 */
class sync_started extends \core\event\base {

    /**
     * Override in subclass.
     *
     * Set all required data properties:
     *  1/ crud - letter [crud]
     *  2/ level - using a constant self::LEVEL_*.
     *  3/ objecttable - name of database table if objectid specified
     *
     * Optionally it can set:
     * a/ fixed system context
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @return string
     * @throws \coding_exception
     */
    public static function get_name() {
        return get_string('sync_started', 'enrol_databaseextended');
    }


    /**
     * @return string
     */
    public function get_description() {
        return "A sync was started for the databaseextended plugin";
    }

    /**
     * @return array
     */
    public function get_legacy_logdata() {
        return array(SITEID,
                     'databaseextended',
                     'mail',
                     '',
                     "Starting database sync");

    }
}