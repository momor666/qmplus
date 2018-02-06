<?php
/****************************************************************

File:       block/module_info/db/access.php

Purpose:    This file holds the capability definition for the block

 ****************************************************************/
$capabilities = array(
    'block/module_info:addinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
        'guest' => CAP_PREVENT,
        'student' => CAP_PREVENT,
        'teacher' => CAP_PREVENT,
        'editingteacher' => CAP_ALLOW,
        'coursecreator' => CAP_ALLOW,
        'manager' => CAP_ALLOW
        )
    ),
);