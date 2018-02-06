<?php


class admin_category_merge_form extends abstract_multiple_form
{


    /**
     * @return void
     */
    function definition()
    {
        $this->_form->addElement('header', 'settingsheader', get_string('upload'));

        $this->_form->addElement('filepicker', 'userfile', get_string('file'));
        $this->_form->addRule('userfile', null, 'required');

        $choices = csv_import_reader::get_delimiter_list();
        $this->_form->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'admin'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $this->_form->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $this->_form->setDefault('delimiter_name', 'semicolon');
        } else {
            $this->_form->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $this->_form->addElement('select', 'encoding', get_string('encoding', 'admin'), $choices);
        $this->_form->setDefault('encoding', 'UTF-8');

        $choices = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $this->_form->addElement('select', 'previewrows', get_string('rowpreviewnum', 'admin'), $choices);
        $this->_form->setType('previewrows', PARAM_INT);

        $this->_form->addElement('hidden', 'cat_form_action', 'form1');
        $this->_form->setType('cat_form_action', PARAM_RAW);


        $this->add_action_buttons(false, 'Upload Categories');
    }

    public function handle(core_renderer $OUTPUT)
    {
        $this->next = false;
        global $CFG;
        $data = $this->get_data();
        if (!$data) {
            echo $OUTPUT->heading_with_help('Bulk Upload Categories', 'uploadusers', 'admin');
            echo '<p>Format: name, idnumber, description, parent</p>';
            $this->display();
            $this->displayFooter($OUTPUT);
        } else {
            //Form has data
            $import_id = csv_import_reader::get_new_iid('uploaduser');
            $csv_importer = new csv_import_reader($import_id, 'uploaduser');

            $content = $this->get_file_content('userfile');

            $read_count = $csv_importer->load_csv_content($content, $data->encoding, $data->delimiter_name, 'ulcc_admin_validate_user_upload_columns');
            unset($content);

            if ($read_count === false) {
                print_error($csv_importer->get_error(), "$CFG->wwwroot/blocks/ulcc_diagnostics/actions/category_upload.php");
                die();
            }

            if ($read_count == 0) {
                print_error('csvemptyfile', "$CFG->wwwroot/blocks/ulcc_diagnostics/actions/category_upload.php");
                die();
            }

            //  $csv_importer = new csv_import_reader($this->import_id, 'uploaduser');
            $preview_rows = optional_param('previewrows', 10, PARAM_INT);
            $read_count = optional_param('readcount', 0, PARAM_INT);

            if (!$columns = $csv_importer->get_columns()) {
                error('Error reading temporary file', "$CFG->wwwroot/blocks/ulcc_diagnostics/actions/category_upload.php");
            }


            if ($this->is_cancelled()) {
                $csv_importer->cleanup(true);
                redirect("$CFG->wwwroot/course/index.php");
                return;

            }

            echo $OUTPUT->heading(get_string('uploaduserspreview', 'admin'));

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

        echo '<table id="uupreview" class="generaltable boxaligncenter" summary="' . get_string('uploaduserspreview', 'admin') . '">';
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

class admin_category_merge_form_intermediate extends abstract_multiple_form
{

    /**
     * Abstract method - always override!
     */
    protected function definition()
    {
        global $CFG;

        //no editors here - we need proper empty fields
        $CFG->htmleditor = null;

        // $this->_form->addElement('header', 'settingsheader', get_string('settings'));

        // hidden fields
        $this->_form->addElement('hidden', 'cat_form_action', 'form2');
        $this->_form->setType('cat_form_action', PARAM_RAW);

        $this->_form->addElement('hidden', 'iid');
        $this->_form->setType('iid', PARAM_INT);

        $this->_form->addElement('hidden', 'previewrows');
        $this->_form->setType('previewrows', PARAM_INT);

        $this->_form->addElement('hidden', 'readcount');
        $this->_form->setType('readcount', PARAM_INT);

        $this->add_action_buttons(true, 'Upload Categories');

    }

    /**
     * @param $OUTPUT core_renderer
     * @return void
     */
    function handle(core_renderer $OUTPUT)
    {
        $this->next = false;

        global $STD_FIELDS, $DB;

        $import_id = optional_param('iid', '', PARAM_INT);


        $data = $this->get_data();

        if ($data === NULL) {
            return;
        }
        // Print the header
        echo $OUTPUT->heading('Upload Result');

        // verification moved to two places: after upload and into form2
        $category_created_counter = 0;

        // init csv import helper
        $csv_importer = new csv_import_reader($import_id, 'uploaduser');
        $csv_importer->init();
        $line_number = 1; //column header is first line
        $columns = $csv_importer->get_columns();


        // init upload progress tracker
        $upload_progress = new form_upload_progress_tracker();
        $upload_progress->init(); // start table

        while ($line = $csv_importer->next()) {
            $line_number++;
            $category_created_counter = $this->process_row($upload_progress, $line, $columns, $STD_FIELDS, $DB, $line_number, $category_created_counter);
        }
        $upload_progress->flush();
        $upload_progress->close(); // close table

        $csv_importer->close();
        $csv_importer->cleanup(true);

        $OUTPUT->box_start('generalbox uploadresults');
        echo '<p>Categories Created: ' . $category_created_counter . '</p>';
        $OUTPUT->box_end();

        //$OUTPUT->continue_button(new moodle_url("$CFG->wwwroot/course/index.php"));

        $this->next = true;


    }

    /**
     * @param form_upload_progress_tracker $upload_tracker
     * @param $line
     * @param $columns
     * @param $STD_FIELDS
     * @param $DB moodle_database
     * @param $line_number
     * @param $category_counter
     * @return
     */
    private function process_row(form_upload_progress_tracker $upload_tracker, $line, $columns, $STD_FIELDS, moodle_database $DB, $line_number, $category_counter)
    {
        $upload_tracker->flush();

        $upload_tracker->track('line', $line_number);

        $out_record = new object();

        // add fields to user object
        foreach ($line as $key => $value) {
            if ($value !== '') {
                $key = $columns[$key];
            }
            $out_record->$key = $value;
            if (in_array($key, $upload_tracker->columns)) {
                $upload_tracker->track($key, $value);
            }
        }

        // add default values for remaining fields
        foreach ($STD_FIELDS as $field) {
            if (isset($out_record->$field)) {
                continue;
            }
        }

        //If the category already exists
        if ($category = $DB->get_record('ulcc_admin_flags', array('externalid' => $out_record->idnumber, 'tablename' => 'course_categories'))) {
	        if ($parent_category = $DB->get_record('ulcc_admin_flags', array('externalid' => $out_record->parent, 'tablename' => 'course_categories')))
	        {        
	        	if ($catmove = $DB->get_record('course_categories', array('id' => $category->itemid)))
		        {
		            $catmove->parent = 857;
		            $DB->update_record('course_categories',$catmove);
		            $upload_tracker->track('created', 'Category '.$category->itemid.' moved to: 857', 'error');
		        }
	            $category->itemid = $parent_category->itemid;
	            $DB->update_record('ulcc_admin_flags',$category);
	            $upload_tracker->track('created', 'Category link updated to: ' . $category->itemid, 'error');
	        }
            return $category_counter;
        }

        $upload_tracker->track('created', 'Category doesn\'t exist: ' . $out_record->idnumber, 'error');
        return $category_counter; // Required to build course_categories.depth and .path.


    }


}

class admin_category_merge_form_final extends abstract_multiple_form
{

    /**
     * @return void
     */
    function definition()
    {
        global $CFG;

        //no editors here - we need proper empty fields
        $CFG->htmleditor = null;

        //$this->_form->addElement('header', 'settingsheader', get_string('settings'));

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
        echo $OUTPUT->heading('Bulk Upload Categories Completed');
        echo '<p>Upload completed</p>';
        $this->display();
        $this->displayFooter($OUTPUT);

    }


}







