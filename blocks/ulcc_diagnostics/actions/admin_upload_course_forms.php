<?php


class admin_upload_course_form extends abstract_multiple_form
{
    function definition()
    {
        $this->_form->addElement('header', 'settingsheader', get_string('upload'));

        $this->_form->addElement('filepicker', 'userfile', get_string('file'));
        $this->_form->addRule('userfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $this->_form->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'block_ulcc_diagnostics'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $this->_form->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $this->_form->setDefault('delimiter_name', 'semicolon');
        } else {
            $this->_form->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $this->_form->addElement('select', 'encoding', get_string('encoding', 'block_ulcc_diagnostics'), $choices);
        $this->_form->setDefault('encoding', 'UTF-8');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $this->_form->addElement('select', 'previewrows', get_string('rowpreviewnum', 'block_ulcc_diagnostics'), $choices);
        $this->_form->setType('previewrows', PARAM_INT);
        $this->_form->addElement('hidden', 'cat_form_action', 'form1');
        $this->_form->setType('cat_form_action', PARAM_RAW);

        $this->add_action_buttons(false, 'Upload Courses');
    }

    /**
     * @param $OUTPUT core_renderer
     * @return void
     */
    function handle(core_renderer $OUTPUT)
    {
        global $CFG;
        $return_url = "$CFG->wwwroot/blocks/ulcc_diagnostics/actions/course_upload.php";

        $data = $this->get_data();
        if (!$data) {
            echo $OUTPUT->heading('Bulk Upload Courses');
            echo '<p>Format: fullname, shortname,idnumber,description,category</p>';
            $this->display();
            $this->displayFooter($OUTPUT);
        } else {
            $preview_rows = optional_param('previewrows', 10, PARAM_INT);
            $import_id = csv_import_reader::get_new_iid('uploaduser');
            $csv_importer = new csv_import_reader($import_id, 'uploaduser');

            $content = $this->get_file_content('userfile');

            $read_count = $csv_importer->load_csv_content($content, $data->encoding, $data->delimiter_name, 'ulcc_admin_validate_user_upload_columns');
            unset($content);

            if ($read_count === false) {
                error($csv_importer->get_error(), $return_url);
            } else if ($read_count == 0) {
                print_error('csvemptyfile', 'error', $return_url);
            }

            if (!$columns = $csv_importer->get_columns()) {
                error('Error reading temporary file', $return_url);
            }

            if ($this->is_cancelled()) {
                $csv_importer->cleanup(true);
                redirect("$CFG->wwwroot/course/index.php");
            }

            echo $OUTPUT->heading(get_string('uploaduserspreview', 'block_ulcc_diagnostics'));

            $this->displayDataTable($columns, $csv_importer, $preview_rows, $read_count);
            $this->next_data = array('iid' => $import_id, 'previewrows' => $preview_rows, 'readcount' => $read_count);

            $this->next = true;

        }
    }

    /**
     * @param $columns
     * @param csv_import_reader $csv_importer
     * @param $preview_rows
     * @param $read_count
     * @return void
     */
    private function displayDataTable($columns, csv_import_reader $csv_importer, $preview_rows, $read_count)
    {
        $ci = 0;
        $ri = 0;

        echo '<table id="uupreview" class="generaltable boxaligncenter" summary="' . get_string('uploaduserspreview', 'block_ulcc_diagnostics') . '">';
        echo '<tr class="heading r' . $ri++ . '">';
        foreach ($columns as $col) {
            echo '<th class="header c' . $ci++ . '" scope="col">' . s($col) . '</th>';
        }
        echo '</tr>';

        $csv_importer->init();
        while ($fields = $csv_importer->next()) {
            if ($ri > $preview_rows) {
                echo '<tr class="r' . $ri++ . '">';
                foreach ($fields as $field) {
                    echo '<td class="cell c' . $ci++ . '">...</td>';
                }
                break;
            }
            $ci = 0;
            echo '<tr class="r' . $ri++ . '">';
            foreach ($fields as $field) {
                echo '<td class="cell c' . $ci++ . '">' . s($field) . '</td>';
                ;
            }
            echo '</tr>';
        }
        $csv_importer->close();

        echo '</table>';
        echo '<div class="centerpara">' . get_string('uupreprocessedcount', 'block_ulcc_diagnostics', $read_count) . '</div>';
    }


}

class admin_upload_course_intermediate extends abstract_multiple_form
{
    function definition()
    {
        global $CFG,$DB;

        //no editors here - we need proper empty fields
        $CFG->htmleditor = null;

        // hidden fields
        $this->_form->addElement('hidden', 'iid');
        $this->_form->setType('iid', PARAM_INT);

        $this->_form->addElement('hidden', 'previewrows');
        $this->_form->setType('previewrows', PARAM_INT);

        $this->_form->addElement('hidden', 'readcount');
        $this->_form->setType('readcount', PARAM_INT);
        
        $this->_form->addElement('selectyesno', 'moodle_cat', get_string('moodle_cat', 'block_ulcc_diagnostics'));
        $this->_form->setType('moodle_cat', PARAM_INT);

        $frontpage = $DB->get_record('course', array('id' => SITEID));
        $frontpage_context = get_context_instance(CONTEXT_COURSE,$frontpage->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($frontpage_context->id, 'backup', 'course', 0, 'sortorder', false);

        $options = array(0 => get_string('notemplate', 'block_ulcc_diagnostics'));
        foreach ($files as $file) {
            $options[$file->get_id()] = $file->get_filename();
        }

        $this->_form->addElement('select', 'course_template', get_string('course_template', 'block_ulcc_diagnostics'), $options);

        $this->_form->addElement('hidden', 'cat_form_action', 'form2');
        $this->_form->setType('cat_form_action', PARAM_RAW);


        $this->add_action_buttons(true, 'Upload Courses');
    }

    /**
     * @param $OUTPUT core_renderer
     * @return void
     */
    function handle(core_renderer $OUTPUT)
    {
        global $STD_FIELDS, $DB;
        $import_id = optional_param('iid', '', PARAM_INT);

        $data = $this->get_data(false); // no magic quotes here!!!
        if ($data === NULL) {
            return;
        }
        // Print the header
        echo $OUTPUT->heading('Upload Result');

        // verification moved to two places: after upload and into form2
        $courses = 0;

        // init csv import helper
        $csv_importer = new csv_import_reader($import_id, 'uploaduser');
        $csv_importer->init();
        $columns = $csv_importer->get_columns();


        // init upload progress tracker
        $upload_tracker = new form_upload_progress_tracker();
        $upload_tracker->init(); // start table

        $courses = $this->process_Row($csv_importer, $upload_tracker, $DB, $STD_FIELDS, $columns, $courses);
        $upload_tracker->flush();
        $upload_tracker->close(); // close table

        $csv_importer->close();
        $csv_importer->cleanup(true);

        echo $OUTPUT->box_start('generalbox uploadresults');
        echo '<p>';
        echo 'Courses Created: ' . $courses . '</p>';

        echo $OUTPUT->box_end();
        $this->next = true;

    }

    private function process_Row(csv_import_reader $csv_importer, form_upload_progress_tracker $upload_tracker, moodle_database $DB, $STD_FIELDS, $columns, $courses)
    {
        global $CFG;
        $line_number = 1; //column header is first line
        while ($line = $csv_importer->next()) {
            $upload_tracker->flush();
            $line_number++;

            $upload_tracker->track('line', $line_number);

            $course_rec = new object();

            // add fields to user object
            foreach ($line as $key => $value) {
                if ($value !== '') {
                    $key = $columns[$key];
                }
                $course_rec->$key = $value;
                if (in_array($key, $upload_tracker->columns)) {
                    $upload_tracker->track($key, $value);
                }
            }

            // add default values for remaining fields
            foreach ($STD_FIELDS as $field) {
                if (isset($course_rec->$field)) {
                    continue;
                }
            }
            $existing_course = $DB->get_record('course', array('idnumber' => $course_rec->idnumber));
            $moodle_cat = optional_param('moodle_cat', 0, PARAM_INT);
            $course_template = optional_param('course_template', 0, PARAM_INT);
            
            if($moodle_cat == 1) {
                $parent_category = $DB->get_record('course_categories',array('id'=>$course_rec->category));
                $parent_category->itemid = $parent_category->id;
            }else{
                $parent_category = $DB->get_record('ulcc_admin_flags', array('externalid' => $course_rec->category, 'tablename' => 'course_categories'));
            }
            if (!$existing_course) {

                //Get standard course default settings
                $courseconfig = get_config('moodlecourse');

                //Create Units as Courses
                $course = new object();
                $course->fullname = $course_rec->fullname;
                $course->shortname = $course_rec->shortname;
                $course->idnumber = $course_rec->idnumber;
                $course->summary = $course_rec->description;
                $course->format = 'topics';
                $course->category = $parent_category ? $parent_category->itemid : 1;

                $course->numsections    = $courseconfig->numsections;
                $course->format         = $courseconfig->format;
                $course->numsections    = $courseconfig->numsections;
                $course->hiddensections = $courseconfig->hiddensections;
                $course->newsitems      = $courseconfig->newsitems;
                $course->showgrades     = $courseconfig->showgrades;
                $course->showreports    = $courseconfig->showreports;
                $course->maxbytes       = $courseconfig->maxbytes;
                $course->groupmode      = $courseconfig->groupmode;
                $course->groupmodeforce = $courseconfig->groupmodeforce;
                $course->visible        = $courseconfig->visible;
                $course->lang           = $courseconfig->lang;
                $course->groupmodeforce = $courseconfig->groupmodeforce;
                $course->timecreated = time();
                $course->timemodified = time();
                
                if(!$courseclash = $DB->get_record('course', array('shortname' => $course_rec->shortname))) {
                    if($course_template > 0) {
                        $fs = get_file_storage();
                        $fileid = $course_template;
                        $frontpage = $DB->get_record('course', array('id' => SITEID));
                        $frontpage_context = get_context_instance(CONTEXT_COURSE,$frontpage->id);
                        if ($files = $fs->get_area_files($frontpage_context->id, 'backup', 'course', 0, 'sortorder', false)) {
                            /** @var stored_file */
                            $found = null;
                            foreach ($files as $file) {
                                if ($file->get_id() == $fileid) {
                                    $found = $file;
                                }
                            }
                            // Unzip backup
                            $rand = 2;
                            while (strlen($rand) < 10) {
                                $rand = '0' . $rand;
                            }
                            $rand .= rand();
                            check_dir_exists($CFG->dataroot . '/temp/backup');
                            $found->extract_to_pathname(get_file_packer(), $CFG->dataroot . '/temp/backup/' . $rand);

                            require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
                        }

                        $newcourse->fullname  = $course_rec->fullname;
                        $newcourse->shortname = $course_rec->shortname;
                        $newcourse->idnumber  = $course_rec->idnumber;
                        $newcourse->category  = $parent_category ? $parent_category->itemid : 1;

                        // Create new course
                        $courseid = restore_dbops::create_new_course($newcourse->fullname, $newcourse->shortname, $newcourse->category);

                        // Restore backup into course
                        $controller = new restore_controller($rand, $courseid,
                            backup::INTERACTIVE_NO, backup::MODE_SAMESITE, 2,
                            backup::TARGET_NEW_COURSE);
                        $controller->get_logger()->set_next(new output_indented_logger(backup::LOG_INFO, false, true));
                        $controller->execute_precheck();
                        $controller->execute_plan();
                        $newcourse->id = $courseid;

                        // Set shortname and fullname back!
                        $DB->update_record('course', $newcourse);

                        $course_reference = new stdClass();
                        $course_reference->itemid = $newcourse->id;
                        $course_reference->tablename = 'course';
                        $course_reference->externalid = $course_rec->idnumber;
                        $DB->insert_record('ulcc_admin_flags', $course_reference);
                        $upload_tracker->track('created', 'Created course: ' . $course_rec->fullname . ' - ' . $course_rec->shortname);
                        $courses++;
                    }elseif ($new_course = create_course($course)) {
                        $course_reference = new stdClass();
                        $course_reference->itemid = $new_course->id;
                        $course_reference->tablename = 'course';
                        $course_reference->externalid = $course_rec->idnumber;
                        $DB->insert_record('ulcc_admin_flags', $course_reference);
                        $upload_tracker->track('created', 'Created course: ' . $course_rec->fullname . ' - ' . $course_rec->shortname);
                        $courses++;
                    }
                } else {
                    $upload_tracker->track('created', 'Duplicate short name, check existing course: ' . $courseclash->id . ' with ' . $course_rec->shortname);
                }
            } else if ($parent_category && $existing_course->category != $parent_category->itemid) {
                $existing_course->category = $parent_category->itemid;
                $existing_course->fullname = $course_rec->fullname;
                $existing_course->shortname = $course_rec->shortname;
                $existing_course->timemodified = time(); 
                $DB->update_record('course', $existing_course);
                $upload_tracker->track('created', 'Course category updated to: '.$parent_category->itemid. ' for ' . $course_rec->fullname . ' - ' . $course_rec->shortname);
            } else {
            	$existing_course->fullname = $course_rec->fullname;
                $existing_course->shortname = $course_rec->shortname;
                $existing_course->summary = mysql_real_escape_string($course_rec->description);
                $existing_course->timemodified = time();
                $DB->update_record('course',$existing_course);
                $upload_tracker->track('created', 'Course names updated: ' . $course_rec->fullname . ' - ' . $course_rec->shortname.' as Moodle ID '.$existing_course->id);
            }

        }
        return $courses;
    }


    /**
     * Form tweaks that depend on current data.
     */
    function definition_after_data()
    {
        $columns = $this->_customdata;

        if (!$columns) {
            return;
        }
        foreach ($columns as $column) {
            if ($this->_form->elementExists($column)) {
                $this->_form->removeElement($column);
            }
        }
    }

    /**
     * Server side validation.
     * @param $data
     * @param $files
     * @return array
     */
    function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        $columns =& $this->_customdata;

        if (!$columns) {
            return $errors;
        }

        // look for other required data
        if (!in_array('fullname', $columns)) {
            $errors['tutor'] = get_string('missingfield', 'error', 'fullname');
        }
        if (!in_array('shortname', $columns)) {
            $errors['student'] = get_string('missingfield', 'error', 'shortname');
        }
        if (!in_array('idnumber', $columns)) {
            $errors['role'] = get_string('missingfield', 'error', 'idnumber');
        }
        return $errors;
    }
}

class admin_upload_course_form_final extends abstract_multiple_form
{

    /**
     * @return void
     */
    function definition()
    {
        global $CFG;

        //no editors here - we need proper empty fields
        $CFG->htmleditor = null;

        // hidden fields
        $this->_form->addElement('hidden', 'cat_form_action', 'form3');
        $this->_form->setType('cat_form_action', PARAM_RAW);

        $a = $this->_form->getAttributes();
        $a['method'] = 'get';
        $a['action'] = "$CFG->wwwroot/course/index.php";
        $this->_form->setAttributes($a);

        $this->add_action_buttons(true, 'Finish');
    }


    public function handle(core_renderer $OUTPUT)
    {
        echo $OUTPUT->heading('Bulk Upload Course Completed');
        echo '<p>Upload completed</p>';
        $this->display();
        $this->displayFooter($OUTPUT);

    }


}

 
