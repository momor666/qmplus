<?php


class admin_upload_meta_form extends abstract_multiple_form
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


        $this->add_action_buttons(false, 'Upload Meta Courses');
    }

    /**
     * @param $OUTPUT core_renderer
     * @return void
     */
    function handle(core_renderer $OUTPUT)
    {
        global $CFG;
        $return_url = "$CFG->wwwroot/blocks/ulcc_diagnostics/actions/meta_course_upload.php";

        $data = $this->get_data();
        if (!$data) {
            echo $OUTPUT->heading('Bulk Upload Meta Courses');
            echo '<p>Format: course_id_number, child_course_id_number</p>';
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

class admin_upload_meta_intermediate extends abstract_multiple_form
{
    function definition()
    {
        global $CFG;

        //no editors here - we need proper empty fields
        $CFG->htmleditor = null;

        // hidden fields
        $this->_form->addElement('hidden', 'iid');
        $this->_form->setType('iid', PARAM_INT);

        $this->_form->addElement('hidden', 'previewrows');
        $this->_form->setType('previewrows', PARAM_INT);

        $this->_form->addElement('hidden', 'readcount');
        $this->_form->setType('readcount', PARAM_INT);

        $this->_form->addElement('hidden', 'cat_form_action', 'form2');
        $this->_form->setType('cat_form_action', PARAM_RAW);


        $this->add_action_buttons(true, 'Upload Meta Courses');
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

    private function process_Row(csv_import_reader $csv_importer, form_upload_progress_tracker $upload_tracker, moodle_database $DB, $STD_FIELDS, $columns, $meta_courses)
    {
        $line_number = 1; //column header is first line
        while ($line = $csv_importer->next()) {
            $upload_tracker->flush();
            $line_number++;

            $upload_tracker->track('line', $line_number);

            $course_from_file = new object();

            // add fields to user object
            foreach ($line as $key => $value) {
                if ($value !== '') {
                    $key = $columns[$key];
                }
                $course_from_file->$key = $value;
                if (in_array($key, $upload_tracker->columns)) {
                    $upload_tracker->track($key, $value);
                }
            }

            // add default values for remaining fields
            foreach ($STD_FIELDS as $field) {
                if (isset($course_from_file->$field)) {
                    continue;
                }
            }

            $error = '';

            $course = $DB->get_record('course', array('idnumber' => $course_from_file->course_id_number), '*');
            if (!$course) {
                $error = 'Course ' . $course_from_file->course_id_number . ' does not appear to exist. ';
            }

            $child_course = $DB->get_record('course', array('idnumber' => $course_from_file->child_course_id_number), '*');
            if (!$child_course) {
                $error .= 'Child course ' . $course_from_file->child_course_id_number . ' does not appear to exist';
            }

            if ($error === '') {
                $enrol = enrol_get_plugin('meta');
                if ($DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'meta','customint1' => $child_course->id))) {
                    $upload_tracker->track('created', 'Meta course instance exists');
                }else{
                    $eid = $enrol->add_instance($course, array('customint1' => $child_course->id));
                
                    enrol_meta_sync($course->id);
    
                    $course_reference = new stdClass();
                    $course_reference->itemid = $eid;
                    $course_reference->tablename = 'course_meta';
                    $course_reference->externalid = $course_from_file->course_id_number;
                    $DB->insert_record('ulcc_admin_flags', $course_reference);
                    $upload_tracker->track('created', 'Created meta course, parent: ' . $course_from_file->course_id_number . ' , child: ' . $course_from_file->child_course_id_number);
                    $meta_courses++;
                }
            } else {
                $upload_tracker->track('created', 'Error ' . $error);

            }


        }
        return $meta_courses;
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

class admin_upload_meta_form_final extends abstract_multiple_form
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


