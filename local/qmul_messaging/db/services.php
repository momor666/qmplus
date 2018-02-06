<?php
/**
 * Define webserices here.
 * Date: 11/23/16
 * Time: 3:24 PM
 */

$services = array(
    'QMplus local messaging services' => array(
        'functions' => array ( 'local_qmul_messaging_get_user_messages_syndicate',
                               'local_qmul_messaging_hide_message',
                               'local_qmul_messaging_delete_message'
        ),
        'restrictedusers' =>0,
        'enabled'=>1,
    )
);

$functions = array(
     'local_qmul_messaging_get_user_messages_syndicate' => array(
         'classname'   => 'local_qmul_messaging_external',
         'methodname'  => 'get_user_messages_syndicate',
         'classpath'   => 'local/qmul_messaging/externallib.php',
         'description' => 'This delivers a RSS-syndicate like message feed, i.e. title, link and description.',
         'type'        => 'read',
         'capabilities'=> 'local/qmul_messaging:view',
     ),
    'local_qmul_messaging_hide_message' => array(
        'classname'   => 'local_qmul_messaging_external',
        'methodname'  => 'hide_message',
        'classpath'   => 'local/qmul_messaging/externallib.php',
        'description' => 'Services allows a messages to be hidden in the sent view.',
        'type'        => 'write',
        'capabilities'=> 'local/qmul_messaging:send',
    ),
    'local_qmul_messaging_delete_message' => array(
        'classname'   => 'local_qmul_messaging_external',
        'methodname'  => 'delete_message',
        'classpath'   => 'local/qmul_messaging/externallib.php',
        'description' => 'Services allows a messages to be deleted in the sent view.',
        'type'        => 'write',
        'capabilities'=> 'local/qmul_messaging:send',
    )

);