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
 * Local qmframework sync group success event
 *
 * @package    local_qmframework
 * @copyright  2017 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Ionut Marchis <ionut.marchis@catalyst-eu.net>
 */
namespace local_qmframework\event;

defined('MOODLE_INTERNAL') || die();

class group_sync_succeeded extends group_sync {

    /**
     * Create instance of event.
     *
     * @param integer $groupid the ID of the synced group.
     * @param string $maharagroup mahara group details.
     * @return group_sync_succeeded event
     */
    public static function success_sync($groupid, $maharagroup) {
        $other = ['info' => 'Mahara group created.', 'mahara_group' => $maharagroup];
        $sync = parent::create_sync($groupid, 'success', $other);
        return $sync;
    }
}
