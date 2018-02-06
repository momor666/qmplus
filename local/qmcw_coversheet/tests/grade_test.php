<?php

/**
 * This is a unit test for the local_qmcw_coversheet plugin
 * 
 * Should should be created in local_qmcw_coversheet/tests/*_test.php
 *
 * vendor/bin/phpunit local_qmcw_coversheet_grade_testcase local/qmcw_coversheet/tests/grade_test.php
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . 'local/qmcw_coversheet/locallib.php');


class local_qmcw_coversheet_grade_testcase extends advanced_testcase {
     
    // create function to set up and extend classes
    public function set_up(){

        //TODO: "DamianH" : what does this do other than self-evident




    }

    public function get_grades_test() {
         global $CFG;

         //get grades with sample assigns
         //need to set up assignments


         $this->assertEquals(2, 1+2);
    }
 }