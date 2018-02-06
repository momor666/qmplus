<?php
require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');

// require valid moodle login.  Will redirect to login page if not logged in.
if (!isloggedin()) {
    //redirect to moodle login page
    echo 'Not Logged In';
} else {



}