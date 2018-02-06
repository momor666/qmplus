<?php
/**
 * Generate a zip file for SITS CSV data for multiple SITS modules.
 *
 * @param array $courseids The courseids to include in the zip
 * @return string Filename of generated zip file.
 *
 * Note that it is the callers responsibility to delete the generate zip.
 */
function gradereport_qmul_sits_bulk_reports($courseids) {
    global $CFG, $DB;

    $zipfiles = array();
    foreach ($courseids as $courseid) {
        // Grab the course
        $course = $DB->get_record('course', array('id' => $courseid));

        // Grab the SITS modules mapped to the course
        $modules = local_qmul_sync_plugin::sits_modules($course->idnumber);

        // Iterate over the modules.
        foreach ($modules as $module) {
            // Grab the (valid) enrolled students
            // 1. Grab SITS enrolments
            $enrolments = local_qmul_sync_plugin::sits_module_enrolments($module, $course->idnumber);
            $userids = array();
            foreach ($enrolments as $enrolment) {
                $userid = $DB->get_field('user', 'id', array('idnumber' => $enrolment->user_id));
                if (isset($userids[$userid])) {
                    $userids[$userid]++;
                } else {
                    $userids[$userid] = 1;
                }
            }
            // 2. Check for dupes, and purge all instances of duped users
            foreach ($userids as $userid => $count) {
                if ($count > 1) {
                    unset($userids[$userid]);
                }
            }
            // Grab the potential gradeitems
            $gradeitemids = $DB->get_fieldset_select('grade_items', 'id', "courseid=? and (iteminfo rlike '^[0-9]{1,3}' or iteminfo rlike '^#[0-9]{1,3}')", array($course->id));
            if (count($userids) > 0 && count($gradeitemids) > 0) {
                $csvdata = gradereport_qmul_sits_csvdata($course->idnumber, $module, array_keys($userids), $gradeitemids);
                if ($csvdata == '') {
                    continue;
                }
                $zipfiles[$module.' ('.$course->idnumber.').csv'] = array($csvdata);
            }
        }
    }

    $packer = new zip_packer();
    check_dir_exists($CFG->tempdir.'/zip');
    $tmpfile = tempnam($CFG->tempdir.'/zip', 'sitsbulk');
    $packer->archive_to_pathname($zipfiles, $tmpfile);
    return $tmpfile;
}

/**
 * Get the gradereport data for the given users and gradeitems
 * taking the specified SITS module, formatted as a CSV string.
 *
 * @param string $coursecode The course of interest
 * @param string $modulecode The module of interest
 * @param int[] $userids The students
 * @param int[] $gradeitems The gradeitems
 * @return string representing CSV formatted data, ready for SITS upload
 */
function gradereport_qmul_sits_csvdata($coursecode, $modulecode, $userids, $gradeitems) {

    $result = '';
    $records = gradereport_qmul_sits_reportdata($coursecode, $modulecode, $userids, $gradeitems);
    if (count($records) > 0) {
        $columns = "Year,Period,Module,Occ,Map,#Ass#,#Cand Key,Name,#CD,Mark,Grade,CD,#Cand Key";
        $result .= "$columns\r\n";
        foreach ($records as $record) {
            // If no mark is recorded, don't transfer it. If it is 0, it will still get transferred.
            if (trim($record['Mark']) === '') {
                continue;
            }
            $line = '';
            foreach (explode(',', $columns) as $column) {
                if (!empty($line)) {
                    $line .= ',';
                }
                $line .= $record[$column];
            }
            $result .= "$line\r\n";
        }
    }
    return $result;
}

/**
 * Get the gradereport data for the given users and gradeitems
 * taking the specified SITS module.
 *
 * @param string $coursecode Moodle course
 * @param string $modulecode The SITS module of interest
 * @param int[] $userids The students
 * @param int[] $gradeitems The gradeitems
 * @return stdClass[] Records, one per student/course/gradeitem combo
 */
function gradereport_qmul_sits_reportdata($coursecode, $modulecode, $userids, $gradeitems) {
    global $DB;

    $records = array();

    //D.H. - change $coursecode to $modulecode fix bug
    $sitsmodule = local_qmul_sync_plugin::sits_data_for_course_code($modulecode);

    // Grab all the relevant student IDs
    $ioe = $DB->get_in_or_equal($userids);
    $studentids = $DB->get_records_select_menu("user", "id {$ioe[0]}", $ioe[1], "", "idnumber,id");
    $sitsusers = local_qmul_sync_plugin::sits_users(array_keys($studentids));

    // Iterate through all enrolments
    $rs = local_qmul_sync_plugin::sits_module_enrolments($modulecode, $coursecode);
    $grades = array();
    foreach ($rs as $sitsenrolment) {
        if ($sitsenrolment->stuprog_code == 'COMPLETED') {
            continue;
        }
        if (array_key_exists($sitsenrolment->user_id, $sitsusers)) {
            $sitsuser = $sitsusers[$sitsenrolment->user_id];
            $userid = $studentids[$sitsenrolment->user_id];
        } else {
            continue;
        }
        if (!in_array($userid, $userids)) {
            continue;
        }
        foreach ($gradeitems as $gradeitem) {
            $gradeitem = grade_item::fetch(array('id' => $gradeitem));

            // This is significantly faster than using the API, but makes me nervous.
            // However, https://docs.moodle.org/dev/Grades#grade_grades suggests this is safe:
            //
            // "The finalgrade field is effectively a cache and values are rebuilt whenever raw values or the grade_item changes."
            $grade = $DB->get_record('grade_grades', array('itemid' => $gradeitem->id, 'userid' => $userid));
            if (!$grade) {
                continue;
            }
            $record = array(
                "Year" => "TODO",
                "Period" => "TODO",
                "Module" => "TODO",
                "Occ" => "TODO",
                "Map" => "TODO",
                "#Ass#" => "TODO",
                "#Cand Key" => "TODO",
                "Name" => "TODO",
                "#CD" => "",
                "Mark" => "TODO",
                "Grade" => "",
                "CD" => ""
            );
            $record["Year"] = $sitsmodule->academic_year;
            $record["Period"] = $sitsmodule->period;
            $record["Module"] = $sitsmodule->module_code;
            $record["Occ"] = $sitsenrolment->occurrence;
            $record["Map"] = $sitsmodule->assessment_pattern;
            $record["#Ass#"] = gradereport_qmul_sits_get_assessment($gradeitem);
            $record["#Cand Key"] = '#'.$sitsenrolment->stuprog_code;
            //DH change for UTF-8 garbling bug 11/06/2015
            $record["Name"] = mb_strtoupper("{$sitsuser->lastname} {$sitsuser->initials}", 'UTF-8');
            $record["Mark"] = $grade->finalgrade;
            $records[] = $record;
        }
    }
    $rs->close();
    return $records;
}

/**
 * Check the Module Assessment Pattern is valid for the gradeitem
 *
 * @param stdClass $gradeitem
 * @return mixed false if valid, string otherwise
 */
function gradereport_qmul_sits_has_invalid_assessment($gradeitem) {
    if (empty($gradeitem->iteminfo)) {
        return get_string('e_assessment_empty', 'gradereport_qmul_sits');
    }
    if (empty(gradereport_qmul_sits_get_assessment($gradeitem))) {
        return get_string('e_assessment_invalid', 'gradereport_qmul_sits');
    }
    return false;
}

/**
 * Get the assessment number for the gradeitem
 *
 * @param stdClass $gradeitem
 * @return string
 */
function gradereport_qmul_sits_get_assessment($gradeitem) {
    return gradereport_qmul_sits_clean_assessment($gradeitem->iteminfo);
}

/**
 * Clean a SITS assessment number
 *
 * @param string $assessment Dirty assessment number
 * @return string
 */
function gradereport_qmul_sits_clean_assessment($assessment) {
    if (preg_match('/^#?([0-9]+)$/', $assessment, $matches)) {
        return sprintf("#%03d", $matches[1]);
    }
    return "";
}

/**
 * Save edits to gradeitem->sits mappings
 *
 * @param $mappings associative array of gradeitem->MAB_CODE mappings
 */
function gradereport_qmul_sits_save_mappings($mappings) {
    foreach ($mappings as $gradeitem => $mab_code) {
        $gradeitem = grade_item::fetch(array('id' => $gradeitem));
        $gradeitem->iteminfo = gradereport_qmul_sits_clean_assessment($mab_code);
        $gradeitem->update();
    }
}


