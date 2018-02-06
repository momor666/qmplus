<?php

/**
 * 1. Should be in plugin/tests/*_test.php
 * 2. Enhanced testcase class enhanced for easy testing of Moodle code. Basic test case is ulikely to be used in development.
 * 3. To run individual case: vendor/bin/phpunit my_test_class_name my/tests/filename.php
 *
 *
 */

defined('MOODLE_INTERNAL') || die();

//If you want to include some Moodle library files you should always declare global $CFG. The reason is that testcase files may be included from non-moodle code which does not make the global $CFG available automatically.
global $CFG;
require_once($CFG->dirroot . '/local/qmcw_coversheet/locallib.php');

class local_qmcw_coversheet_base_testcase extends advanced_testcase {
     // create function to set up and extend classes
     
     public function test_libraries() {
         global $CFG;

         $barcodeobj = new Barcode('QM+CW-S-0000000005-0000000350');

         // start tests
         // $this->assertEquals(2, 1+2);
//         $this->assertEquals('/Applications/MAMP/htdocs/moodle32', $CFG->dirroot);
//         $this->assertEquals('/Applications/MAMP/htdocs/moodle32/local/qmcw_coversheet/locallib.php', $CFG->dirroot . '/local/qmcw_coversheet/locallib.php');

         $this->assertEquals('QM+CW-S-0000000005-0000000350', $barcodeobj->givenbarcode);
         $this->assertEquals(true, isset($barcodeobj->is_already_scanned));

     }
 }
