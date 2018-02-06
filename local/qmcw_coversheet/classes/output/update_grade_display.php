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


class update_grade_display implements templatable, renderable {


    public $currentgrade = 0;
    public $notification = '';

    public function __construct($currentgrade, $notification){

        $this->currentgrade = $currentgrade;
        $this->notification = $notification;
    }


    public function export_for_template(renderer_base $output){
        global $CFG;
        $export = new stdClass();
        $export->currentgrade = $this->currentgrade;
        $export->notification = $this->notification;
        return $export;
    }


}