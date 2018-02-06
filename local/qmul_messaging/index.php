<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');

require_login();
if (isguestuser()) {
    print_error('noguest');
}



$contextid = required_param('context', PARAM_INT);
$context = context::instance_by_id($contextid);
$userid = $USER->id;

$can_send = has_capability('local/qmul_messaging:send', $context);
if($can_send){
    $PAGE->set_pagelayout('admin');
}else{
    $PAGE->set_pagelayout('standard');
}


$PAGE->set_context($context);
$PAGE->set_url('/local/qmul_messaging/index.php', array('context' => $contextid));
$PAGE->set_title(get_string('messageviewer', 'local_qmul_messaging'));

$renderer = $PAGE->get_renderer('local_qmul_messaging');
$feedurl = "$CFG->wwwroot/local/qmul_messaging/feed.php?context=$contextid";
//$feedurl = "$CFG->wwwroot/local/qmul_messaging/somefeed.php";

//modernized
$PAGE->requires->js_call_amd('local_qmul_messaging/messageview', 'init');
$PAGE->requires->js_call_amd('local_qmul_messaging/messageview', 'init_feed',  array(array('url' => $feedurl)));

echo $OUTPUT->header();

if ($can_send) {
    $composeurl = new moodle_url('/local/qmul_messaging/compose.php', array('context' => $context->id));
    echo $OUTPUT->heading(get_string('messageinbox', 'local_qmul_messaging'));
    echo $OUTPUT->single_button($composeurl, get_string('composemessage', 'local_qmul_messaging'), 'get');
}else{
    $heading = $OUTPUT->heading(get_string('messageviewer', 'local_qmul_messaging'));
    echo html_writer::div($heading, array('class' => 'message-viewer-view'));
}


    $messages = new local_qmul_messaging_messagelist($context, $USER);
    $messages->get_message_list_by_user($userid);
    echo $renderer->inbox_messagelist($messages);





echo $OUTPUT->footer();
