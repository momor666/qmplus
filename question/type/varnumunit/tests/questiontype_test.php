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
 * Unit tests for the varnumunit question type class.
 *
 * @package   qtype_varnumunit
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/type/varnumunit/questiontype.php');


/**
 * Unit tests for the varnumunit question type class.
 *
 * @copyright 2012 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group qtype_varnumunit
 */
class qtype_varnumunit_test extends basic_testcase {
    public static $includecoverage = array(
        'question/type/questiontype.php',
        'question/type/varnumunit/questiontype.php',
    );

    protected $qtype;

    protected function setUp() {
        $this->qtype = new qtype_varnumunit();
    }

    protected function tearDown() {
        $this->qtype = null;
    }

    protected function get_test_question_data() {
        $q = new stdClass();
        $q->id = 1;
        $q->options = new stdClass();
        $q->options->answers[1] = (object) array('answer' => '1.5', 'fraction' => 1);
        $q->options->answers[2] = (object) array('answer' => '*', 'fraction' => 0.1);
        $q->options->unitfraction = 0.25;
        $q->options->units[1] = (object) array('unit' => 'match(frogs)', 'fraction' => 1);
        $q->options->units[2] = (object) array('unit' => '*', 'fraction' => 0.1);

        return $q;
    }

    public function test_get_random_guess_score() {
        $q = $this->get_test_question_data();
        $this->assertEquals(0.075, $this->qtype->get_random_guess_score($q));
    }

    public function test_get_possible_responses() {
        $q = $this->get_test_question_data();

        $this->assertEquals(array(
            'unitpart' => array(
                'match(frogs)' => new question_possible_response('match(frogs)', 1),
                '*' => new question_possible_response('*', 0.1),
                null => question_possible_response::no_response(),
            ),
            'numericpart' => array(
                1 => new question_possible_response('1.5', 1),
                2 => new question_possible_response('*', 0.1),
                null => question_possible_response::no_response(),
            ),
        ), $this->qtype->get_possible_responses($q));
    }
}
