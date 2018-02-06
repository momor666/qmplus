<?php

/**
* Block Course Reports
*
* @copyright &copy; 2011 University of London Computer Centre
* @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License

*/
class block_course_reports extends block_base {
    function init() {
        $this->title = get_string('pluginname','block_course_reports');
    }

    function has_config() {
        return true;
    }

    function get_content() {
	

	global $CFG, $COURSE, $PAGE;
	if (has_capability('moodle/site:viewreports', $PAGE->context)) { // basic capability for listing of reports 
	$test = "  <img src=\"$CFG->wwwroot/blocks/course_reports/pix/report.svg\" alt=\"Logs\" />
				<a href=\"$CFG->wwwroot/report/log/index.php?id=$COURSE->id\">Logs</a></br>
				<img src=\"$CFG->wwwroot/blocks/course_reports/pix/report.svg\" alt=\"Live Logs\" />
              	<a href=\"$CFG->wwwroot/report/loglive/index.php?id=$COURSE->id&inpopup=1\">Live Logs</a><br/>
				<img src=\"$CFG->wwwroot/blocks/course_reports/pix/report.svg\" alt=\"Activity\" />
                <a href=\"$CFG->wwwroot/report/outline/index.php?id=$COURSE->id\">Activity</a></br>
				<img src=\"$CFG->wwwroot/blocks/course_reports/pix/report.svg\" alt=\"Participation\" />
                <a href=\"$CFG->wwwroot/report/participation/index.php?id=$COURSE->id\">Participation</a></br>
				<img src=\"$CFG->wwwroot/blocks/course_reports/pix/report.svg\" alt=\"Completion\" />
                <a href=\"$CFG->wwwroot/report/progress/index.php?course=$COURSE->id\">Completion</a> ";
	}else{
$test = '';
}	
						
	$this->content         =  new stdClass;    
		$this->content->text   = $test;    
		$this->content->footer = '';     
		return $this->content;
		return $this->content->text;
     
    }
}


