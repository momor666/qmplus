<?php

/****************************************************************

File:      local/landingpages/db/install.php

Purpose:   Inatall script for landingpage

****************************************************************/

function xmldb_local_landingpages_install($oldversion =0) {

	GLOBAL $DB;
	if(!$DB->record_exists('user_info_field', array('shortname' => 'landingpage'))) {
		$dataobject = new stdClass();
		$dataobject->shortname = 'landingpage';
		$dataobject->name = 'Landing Page';
		$dataobject->datatype = 'menu';
		$dataobject->description = '';
		$dataobject->descriptionformat = 1;
		$dataobject->categoryid = 1;
		$dataobject->sortorder = 1;
		$dataobject->required = 0;
		$dataobject->locked = 0;
		$dataobject->visible = 2;
		$dataobject->forceunique = 0;
		$dataobject->signup = 1;
		$dataobject->defaultdata = '';
		$dataobject->defaultdataformat =0;
		$dataobject->param1 = "\nEECS-Landing\nELU-Landing\nHIST-Landing\nMBBS-Landing\nNanchang-Landing\nSBCS-Landing\nSEFP-Landing\nSEMS-Landing\nSMS-Landing\nSPA-Landing";
		$DB->insert_record('user_info_field', $dataobject);
	}
	return true;


}
