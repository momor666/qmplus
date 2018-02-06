<?php
/**
 * Cron task
 *
 */

namespace assignsubmission_qmcw_coversheet\task;
 
// file assignsumbission/qmcw_coversheet/classes/event/cron_task.php
 
class cron_task extends \core\task\scheduled_task {

    public function get_name()
    {
        return get_string('syncsubs', 'assignsubmission_qmcw_coversheet');
    }

    public function execute()
    {
        global $DB;

        $count = 0;

        try {
            $select = 'submissiondate <> 0';
            $results = $DB->get_records_select('assignsubmission_coversheet', $select);
        } catch (Error $error){
        } catch (Throwable $throwable){
        } catch (Exception $exception){
        }

        if($results){
            foreach ($results as $result){
                $updateparams = new \stdClass;
                $updateparams->id = $result->submission;
                $updateparams->timemodified = $result->submissiondate;
                $updateresult = $DB->update_record('assign_submission', $updateparams);
                if($updateresult){
                    //reset the submission date in the plugin table to 0 as a flag
                    //the acutual data still exists in the local_coversheet_scan table.
                    $result->submissiondate = 0;
                    $DB->update_record('assignsubmission_coversheet', $result);
                }
                $count ++;
            }
        }
        $str = 'The cron has updated ' . $count . ' assignsubmission records';
        mtrace($str);
    }

}