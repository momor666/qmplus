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

namespace theme_qmul\tools;

defined('MOODLE_INTERNAL') || die();

/**
 * Activity meta data.
 *
 * @package   theme_qmul
 * @copyright Copyright Andrew Davidson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_meta {

    // Strings.
    /**
     * @var string $submittedstr - string to use when submitted
     */
    public $submittedstr;
    /**
     * @var string $notsubmittedstr - string to use when not submitted
     */
    public $notsubmittedstr;
    /**
     * @var string $submitstrkey - language string key
     */
    public $submitstrkey;
    /**
     * @var string $draftstr - string for draft status
     */
    public $draftstr;
    /**
     * @var string $reopenedstr - string for reopened status
     */
    public $reopenedstr;
    /**
     * @var string $duestr - string for due date
     */
    public $duestr;
    /**
     * @var string $overduestr - string for overdue status
     */
    public $overduestr;

    // General meta data.
    /**
     * @var int $timeopen - unix time stamp for time open
     */
    public $timeopen;
    /**
     * @var int $timeclose - unix time stamp for time closes
     */
    public $timeclose;
    /**
     * @var bool $isteacher - true if meta data is intended for teacher
     */
    public $isteacher = false;
    /**
     * @var bool $submissionnotrequired - true if a submission is not required
     */
    public $submissionnotrequired = false;

    // Student meta data.
    /**
     * @var bool $submitted - true if submission has been made
     */
    public $submitted = false; // Consider collapsing this variable + draft variable into one 'status' variable?
    /**
     * @var bool $draft - true if activity submission is in draft status
     */
    public $draft = false;
    /**
     * @var bool $reopened - true if reopened
     */
    public $reopened = false;
    /**
     * @var int $timesubmitted - unix time stamp for time submitted
     */
    public $timesubmitted;
    /**
     * @var bool $grade - has the submission been graded
     */
    public $grade = false;
    /**
     * @var bool $overdue - is the submission overdue
     */
    public $overdue = false;

    // Teacher meta data.
    /**
     * @var int $numsubmissions - number of submissions
     */
    public $numsubmissions = 0;
    /**
     * @var int $numrequiregrading - number of submissions requiring grading
     */
    public $numrequiregrading = 0;

    protected $_defaults = [];

    /**
     * Has this class been set?
     * @param bool $ignoreinitialstate - if true, will consider an object with default values set by set_default as
     * not set.
     * @return bool
     */
    public function is_set($ignoreinitialstate = false) {
        $reflect = new \ReflectionClass($this);
        $props   = $reflect->getDefaultProperties();
        foreach ($props as $prop => $default) {
            if ($prop === '_defaults') {
                continue;
            }
            if (isset($this->$prop) && $this->$prop != $default) {
                if ($ignoreinitialstate) {
                    if (!isset($this->_defaults[$prop]) || $this->_defaults[$prop] !== $this->$prop) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Set and track default value
     * @param $prop
     * @param $val
     */
    protected function set_default($prop, $val) {
        if (isset($this->_defaults[$prop])) {
            throw new \coding_exception('Default value already set for '.$prop.' - '.$this->_defaults[$prop]);
        }
        $this->$prop = $val;
        $this->_defaults[$prop] = $this->$prop;
    }

    public function __construct() {
        // Set default strings.
        $this->set_default('overduestr', get_string('overdue', 'theme_qmul'));
        $this->set_default('duestr', get_string('due', 'theme_qmul'));
    }
}
