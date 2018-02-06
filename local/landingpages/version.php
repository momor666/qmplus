<?php

/****************************************************************

File:      local/landingpages/version.php

Purpose:   Version details.

****************************************************************/

defined('MOODLE_INTERNAL') || die;

$plugin->version  = 20160622000;
//$plugin->version  = 20160113000; //this is too long - will have to del from db!
$plugin->requires = 2010112400;
$plugin->maturity = MATURITY_STABLE;
$plugin->component = 'local_landingpages';
