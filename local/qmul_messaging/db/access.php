<?php

/****************************************************************

File:      local/landingpages/db/access.php

Purpose:   Capability definition for landingpage

****************************************************************/

$capabilities = array(
    'local/qmul_messaging:send' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/qmul_messaging:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM
    )
);
