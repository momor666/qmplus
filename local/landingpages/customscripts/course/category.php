<?php

/****************************************************************

File:     local/landingpages/customscript/course/category.php

Purpose:  Custom category page

****************************************************************/

$config = get_config('local_landingpages');
//make sure the redirect is enabled
if(	$config->enabled == 1 ) {
	$id = required_param('id', PARAM_INT); // Category id
	GlOBAL $DB;
	// make sure we have a caegory id
    if (!empty($id)) {
	// if the user has landingpage:categoryedit then do nothing allowing them to access the normal category layout
		if(!has_capability('local/landingpage:categoryedit', context_coursecat::instance($id))) {
			$sql =
				'SELECT
					`c`.`id`,
					`c`.`idnumber`
				FROM
					mdl_course_categories cat
				INNER JOIN
					mdl_course c
				ON
					`cat`.`idnumber` = `c`.`idnumber`
				WHERE
					`cat`.`id` = ?
				LIMIT 1';

			$result =reset($DB->get_records_sql_menu($sql,array($id)));
			if (!empty($result)){
				//redirect to a course as a matching has been found
				redirect(new moodle_url('/course/view.php', array('idnumber' => $result)));
			}
		}
	}
}

?>