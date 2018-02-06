<?php

/****************************************************************

File:      local/landingpages/db/access.php

Purpose:   Capability definition for landingpage

****************************************************************/

$capabilities = array(
    'local/landingpage:categoryedit' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_PREVENT,
            'manager' => CAP_ALLOW
        )
    )
);
