<?php

/****************************************************************

File:     /block/module_info/classes/task/increment_weekrange.php

Purpose:  Cron tasks for module info

 ****************************************************************/

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
 *
 * @package    blocks
 * @subpackage module_info
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace block_module_info\task;

class increment_weekrange extends \core\task\scheduled_task
{
    public function get_name() {
        return get_string('increment_weekrange', 'block_module_info');
    }

    public function execute() {
        //get module_info config
        $autoincrement_enabled = get_config('block_module_info', 'autoincrementweek');
        if(!$autoincrement_enabled){
            return;
        }

        $config = get_config('block_module_info', 'week');
        $new_config = '';

        //increment week range based on patterns
        $single_weekrange_pattern = '/^[0-9]{1,2}$/';
        $consecutive_weekrange_pattern = '/^([0-9]{1,2})-([0-9]{1,2})$/';

        if(preg_match($single_weekrange_pattern, $config)){
            //single week range
            $new_config =  preg_replace_callback($single_weekrange_pattern, array($this,'increment_single'), $config);
        }
        else if(preg_match($consecutive_weekrange_pattern, $config)){
            //consecutive week range
            $new_config =  preg_replace_callback('/^([0-9]{1,2})-([0-9]{1,2})$/', array($this,'increment_consecutive'), $config);
        } else if(count($weeks = explode(';', $config)) > 1){
            //non consecutive week range
            $new_config = $this->increment_nonconsecutive($weeks);
        }

        if($new_config) {
            set_config('week', $new_config, 'block_module_info');
        }
    }
    //preg callbacks
    private function increment_single($matches){
        return ($matches[0] == 52) ? 1 : (int)$matches[0]+1;
    }

    private function increment_consecutive($matches){
        $start_week = ($matches[1] == 52) ? 1 : (int)$matches[1]+1;
        $end_week = ($matches[2] == 52) ? 1 : (int)$matches[2]+1;

        return $start_week.'-'.$end_week;
    }

    private function increment_nonconsecutive($weeks) {
        $newconfig = '';
        foreach($weeks as $key => $week) {
            if((int)$week) {
                $newconfig .= ($week == 52) ? 1: (int)$week+1;
                if($key < (count($weeks)-1)){
                    $newconfig .= ';';
                }
            }
        }
        return $newconfig;
    }
}