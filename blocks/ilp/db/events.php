<?php
/*$handlers = array (
    'user_unenrolled' => array (
        'handlerfile'      => '/block/ilp/classes/ilp_report.class.php',
        'handlerfunction'  => 'ilp_report::roles_changed',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
    'user_unenrol_modified' => array (
        'handlerfile'      => '/block/ilp/classes/ilp_report.class.php',
        'handlerfunction'  => 'ilp_report::roles_changed',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
    'role_unassigned' => array (
        'handlerfile'      => '/block/ilp/classes/ilp_report.class.php',
        'handlerfunction'  => 'ilp_report::roles_changed',
        'schedule'         => 'instant',
        'internal'         => 1,
    )
);*/

$observers = array (
    array (
        'eventname'       =>  '\core\event\user_enrolment_deleted',
        'callback'        =>  'ilp_report::roles_changed',
        'includefile'     =>  '\blocks\ilp\classes\ilp_report.class.php'
    ),
    array (
        'eventname'       =>  '\core\event\user_enrolment_updated',
        'callback'        =>  'ilp_report::roles_changed',
        'includefile'     =>  '\blocks\ilp\classes\ilp_report.class.php'
    ),
    array (
        'eventname'       =>  '\core\event\role_unassigned',
        'callback'        =>  'ilp_report::roles_changed',
        'includefile'     =>  '\blocks\ilp\classes\ilp_report.class.php'
    )
);