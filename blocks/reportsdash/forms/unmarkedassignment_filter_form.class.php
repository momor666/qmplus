<?php
/**
 * Version details
 *
 * @package    reportsdash
 * @copyright  2013 ULCC, University of London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("$CFG->libdir/enrollib.php");
//Default filter block

class block_reportsdash_unmarkedassignment_filter_form extends moodleform
{
    function definition() {
        global $DB,$CFG;

        $imports = (object)$this->_customdata;

        $L = 'block_reportsdash'; //Default language file

        $mform =& $this->_form;

        $buttonarray = array();

        if(empty($imports->singlemode))
        {
            $course = $DB->get_records_sql("SELECT  DISTINCT shortname
                   FROM {course} c
                   JOIN {enrol} e ON c.id= e.courseid
                   JOIN {user_enrolments} ue ON e.id = ue.enrolid ");
            $select = $mform->addElement('select', 'coursefilter', get_string('coursefilter', $L));

            $select->addOption('All',0,array());

            foreach($course as $name=>$value)
            {
                $select->addOption($name,$name,array());
            }
            block_reportsdash_report::make_filter($mform,$DB);

            $actions = array(
                '1' => get_string('nextweek', $L),
                '2' => get_string('weekafter', $L)
            );
            $select = $mform->addElement('select', 'duefilter', get_string('duefilter', $L));
            $select->addOption('Same day',0,array());
            foreach($actions as $name=>$value)
            {
                $select->addOption($value,$name,array());
            }

            $mform->addElement('hidden', 'rptname', $imports->rptname);

            $buttonarray[] = & $mform->createElement('submit', 'submitbutton', get_string('filter', $L));
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');
        }
    }
}