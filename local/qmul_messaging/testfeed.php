<?php

require_once('../../config.php');
require_once(__DIR__.'/lib.php');

require_login();

//$contextid = required_param('context', PARAM_INT);
$contextid = optional_param('context', '',  PARAM_INT);
$contextid = optional_param('sesskey', '',  PARAM_INT);
$context = context_system::instance();
$format = optional_param('format', 'atom', PARAM_ALPHA);



$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('local_qmul_messaging');

//$messages = new local_qmul_messaging_message_iterator($context);
//$messages->get_message_recordset_by_user();

$messages = new local_qmul_messaging_messagelist($context);
$messages->get_message_list_by_user($userid);

if ($format == 'rss') {
    echo $renderer->rss_messages($messages);
} else {
    header("Content-type:application/atom+xml");
    echo $renderer->atom_messages($messages);
}
