<?php

/****************************************************************

File:      local/landingpages/settings.php

Purpose:   Settings details.

****************************************************************/

defined('MOODLE_INTERNAL') || die;

global $PAGE;

if ($hassiteconfig) { // needs this condition or there is error on login page

	$ADMIN->add('appearance', new admin_category('landingsettings_category', get_string('landingsettings_category', 'local_landingpages')));

	$ADMIN->add('landingsettings_category', new admin_externalpage('local_landingpages',get_string('redirect_setting', 'local_landingpages'),new moodle_url('/local/landingpages/index.php')));
}

