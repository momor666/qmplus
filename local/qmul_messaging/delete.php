<?php


require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/filelib.php');

require_login();

$messageid = required_param('message', PARAM_INT);