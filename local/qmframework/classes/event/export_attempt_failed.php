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
 * Local qmframework export attempt fail event
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
namespace local_qmframework\event;

defined('MOODLE_INTERNAL') || die();

class export_attempt_failed extends export_attempt {

    /**
     * Create instance of event.
     *
     * @param integer $attemptid the ID of the quiz_attempt exported.
     * @param string $error the error message.
     * @return export_attempt_failed event
     */
    public static function fail_export($attemptid, $error) {
        $other = ['info' => 'Export quiz attempt error', 'error' => $error];
        $sync = parent::create_export($attemptid, 'failure', $other);
        return $sync;
    }
}
