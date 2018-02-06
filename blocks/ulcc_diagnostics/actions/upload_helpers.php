<?php
/**
 * Interface for command pattern form wizards with multiple forms
 * using the moodle_form / quick form classes
 * User: kevin.saunders
 * Date: 05/07/11
 */
interface category_upload_interface
{

    /**
     * @abstract
     * @param $OUTPUT core_renderer
     * @return void
     */
    function handle(core_renderer $OUTPUT);

    /**
     * @abstract
     * @return bool
     */
    function isDisplayNext();

    /**
     * Returns the data array for the next form
     * @abstract
     * @return array
     */
    function getDataForNextForm();

    /**
     * Display the the footer
     * @abstract
     * @param $OUTPUT core_renderer
     * @return void
     */
    function displayFooter(core_renderer $OUTPUT);


}

/**
 * Controller class for the the forms
 * @throws moodle_exception
 *
 */
class form_controller
{
    /**
     * @var category_upload_interface
     */
    private $form_class;

    /**
     * @var category_upload_interface
     */
    private $next_form_class;

    private $actions = array( );

    /**
     * @param $OUTPUT core_renderer
     * @return void
     */
    public function Action(core_renderer $OUTPUT)
    {
        if(!$this->actions) {
            throw new moodle_exception('', 'ulcc_diagnostics');
        }
        $form_name = optional_param('cat_form_action', 'form1', PARAM_TEXT);
        $this->form_class = new $this->actions[$form_name]['form']();

        //handle data
        $this->form_class->handle($OUTPUT);

        //If the form has been processed, display the next form
        if ($this->form_class->isDisplayNext()) {
            $this->next_form_class = new $this->actions[$form_name]['next']();
            if ($this->form_class->getDataForNextForm()) {
                $this->next_form_class->set_data($this->form_class->getDataForNextForm());
            }
            $this->next_form_class->display();
            $this->next_form_class->displayFooter($OUTPUT);
        }
    }

    /**
     * Sets the actions
     * @param $form_name string value of the 'form' hidden field
     * @param $form_class_name string class name of the current form
     * @param $next_form_class_name string class name of the next form to be displayed after the current form has been processed
     * @return void
     */
    public function setAction($form_name, $form_class_name, $next_form_class_name) {
        $this->actions[$form_name] = array('form' => $form_class_name, 'next' => $next_form_class_name);
    }
}

abstract class abstract_multiple_form extends moodleform implements category_upload_interface {
    protected  $next = false;
    protected  $next_data = array();

    /**
     * Display the the footer
     * @param $OUTPUT core_renderer
     * @return void
     */
    function displayFooter(core_renderer $OUTPUT)
    {
        echo $OUTPUT->footer();
    }

    /**
     * Returns the data array for the next form
     * @return array
     */
    function getDataForNextForm()
    {
        return $this->next_data;
    }


    /**
     * @return bool
     */
    function isDisplayNext()
    {
        return $this->next;
    }
}

/**
 * Utility class
 */
class form_upload_progress_tracker
{
    private $row;
    public $columns = array('line', 'name', 'description', 'parent', 'created');

    function __construct()
    {
    }

    function init()
    {
        $ci = 0;
        echo '<table id="uuresults" class="generaltable boxaligncenter" summary="' . get_string('uploadusersresult', 'block_ulcc_diagnostics') . '">';
        echo '<tr class="heading r0">';
        echo '<th class="header c' . $ci++ . '" scope="col">' . get_string('uucsvline', 'block_ulcc_diagnostics') . '</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">Code</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">Title</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">Summary</th>';
        echo '<th class="header c' . $ci++ . '" scope="col">Result</th>';
        echo '</tr>';
        $this->row = null;
    }

    function flush()
    {
        if (empty($this->row) or empty($this->row['line']['normal'])) {
            $this->row = array();
            foreach ($this->columns as $col) {
                $this->row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
            }
            return;
        }
        $ci = 0;
        $ri = 1;
        echo '<tr class="r' . $ri++ . '">';
        foreach ($this->row as $field) {
            foreach ($field as $type => $content) {
                if ($field[$type] !== '') {
                    $field[$type] = '<span class="uu' . $type . '">' . $field[$type] . '</span>';
                } else {
                    unset($field[$type]);
                }
            }
            echo '<td class="cell c' . $ci++ . '">';
            if (!empty($field)) {
                echo implode('<br />', $field);
            } else {
                echo '&nbsp;';
            }
            echo '</td>';
        }
        echo '</tr>';
        foreach ($this->columns as $col) {
            $this->row[$col] = array('normal' => '', 'info' => '', 'warning' => '', 'error' => '');
        }
    }

    function track($col, $msg, $level = 'normal', $merge = true)
    {
        if (empty($this->row)) {
            $this->flush(); //init arrays
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:' . $col);
            return;
        }
        if ($merge) {
            if ($this->row[$col][$level] != '') {
                $this->row[$col][$level] .= '<br />';
            }
            $this->row[$col][$level] .= s($msg);
        } else {
            $this->row[$col][$level] = s($msg);
        }
    }

    function close()
    {
        echo '</table>';
    }
}




/**
 * Validation callback function - verified the column line of csv file.
 * Converts column names to lowercase too.
 * @param $columns
 * @return bool
 */
function ulcc_admin_validate_user_upload_columns(&$columns)
{
    global $STD_FIELDS, $PRF_FIELDS;

    if (count($columns) < 2) {
        return get_string('csvfewcolumns', 'error');
    }

    // test columns
    $processed = array();
    foreach ($columns as $key => $unused) {
        $columns[$key] = strtolower($columns[$key]); // no unicode expected here, ignore case
        $field = $columns[$key];
        if (!in_array($field, $STD_FIELDS) && !in_array($field, $PRF_FIELDS) && // if not a standard field and not an enrolment field, then we have an error
            !preg_match('/^course\d+$/', $field) && !preg_match('/^group\d+$/', $field) &&
            !preg_match('/^type\d+$/', $field) && !preg_match('/^role\d+$/', $field)
        ) {
            return get_string('invalidfieldname', 'error', $field);
        }
        if (in_array($field, $processed)) {
            return get_string('csvcolumnduplicates', 'error');
        }
        $processed[] = $field;
    }
    return true;
}
