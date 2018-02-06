<?php
/**
 * Created by PhpStorm.
 * User: damian
 * Date: 24/10/2017
 * Time: 12:20
 */

namespace local_qmcw_coversheet\output;

defined('MOODLE_INTERNAL') || die();

use renderer_base;
use renderable;
use templatable;
use stdClass;


class update_grade implements templatable, renderable {


    public $currentgrade = 0;

    public function __construct($currentgrade){

        $this->currentgrade = $currentgrade;
    }


    public function export_for_template(renderer_base $output){
        global $CFG;
        $export = new stdClass();
        $export->currentgrade = $this->currentgrade;
        return $export;
    }


}