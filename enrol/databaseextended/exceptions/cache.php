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
 * Database enrolment plugin.
 *
 * This plugin synchronises enrolment and roles with external database table.
 *
 * @package    enrol
 * @subpackage databaseextended
 * @copyright  2012-2013 ULCC {@link http://ulcc.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Any errors thrown when attempting to use the cache should throw a 'cache_exception' error. This extends the
 * coding_exception error to also include a code specific to cache-related problems. We extend from coding_exception
 * in order to dump a stack back trace.
 */
class cache_exception_dbx extends coding_exception {

    // Fail codes

    /**
     * Generic fail. Avoid if possible and be more specific.
     */
    const CACHE_GENERIC_FAIL = 0;

    /**
     * Row not there. May mean that another part of the sync has not happened yet.
     */
    const CACHE_MISSING_ROW = 1;

    /**
     *
     */
    const CACHE_ROW_MISSING_FIELD = 2;

    /**
     *
     */
    const CACHE_NON_ID_FIELD = 3;

    /**
     * @var null|string Name of the Moodle table that this cache has been working on
     */
    private $internaltablename = '';

    /**
     * @var null|string Whatever might help with debugging.
     */
    private $extrainfo = '';

    /**
     * @return null|string
     */
    public function getInternalTableName() {
        return $this->internaltablename;
    }

    /**
     * @return null|string
     */
    public function getExtraInfo() {
        return $this->extrainfo;
    }

    /**
     * Constructor
     * @param string $hint short description of problem
     * @param null|string $failcode
     * @param null $internaltablename
     * @param null $extrainfo
     * @param string $debuginfo detailed information how to fix problem
     * @internal param $int
     */
    function __construct($hint, $failcode=substitute_exception::CACHE_GENERIC_FAIL, $internaltablename=null, $extrainfo=null, $debuginfo=null) {

        $this->code = $failcode;
        $this->internaltablename = $internaltablename;
        $this->extrainfo = $extrainfo;

        parent::__construct($hint, $debuginfo);
    }
}
