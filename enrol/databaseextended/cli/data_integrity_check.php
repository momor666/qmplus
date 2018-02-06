<?php

// If the external data source is inconsistent, then the sync will try to work around it, but often
// this is not possible. This script will check to see if there are any major problems and report back.
// It is not necessarily comprehensive and if it passes, that doesn't mean your data is fine, just that
// it is free of some of the more glaring errors.

define('CLI_SCRIPT', true);


require_once(dirname(__FILE__).'/../../../config.php');

/* @var enrol_databaseextended_plugin $enrol */
$enrol = enrol_get_plugin('databaseextended');

$enrol->check_data_integrity();

