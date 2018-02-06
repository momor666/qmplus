<?php

require_once('../../config.php');
require_once(__DIR__.'/lib.php');

require_login();

$contextid = required_param('context', PARAM_INT);
$context = context::instance_by_id($contextid);
$format = optional_param('format', 'atom', PARAM_ALPHA);

$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('local_qmul_messaging');
$messages = new local_qmul_messaging_message_iterator($context);

if ($format == 'rss') {
    echo $renderer->rss_messages($messages);
} else {
    echo $renderer->atom_messages($messages);
}
