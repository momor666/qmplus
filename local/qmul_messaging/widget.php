<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');

require_login();

$contextid = required_param('context', PARAM_INT);
$context = context::instance_by_id($contextid);
$userid = $USER->id;
$can_send = has_capability('local/qmul_messaging:send', $context);

$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/local/qmul_messaging/widget.php', array('context' => $contextid));
$PAGE->set_title('Widget example');

$renderer = $PAGE->get_renderer('local_qmul_messaging');
$feedurl = "$CFG->wwwroot/local/qmul_messaging/feed.php?context=$contextid";
$feedurl = "$CFG->wwwroot/local/qmul_messaging/somefeed.php";

//modernized
$PAGE->requires->js_call_amd('local_qmul_messaging/messageview', 'init');
//$PAGE->requires->js_call_amd('local_qmul_messaging/messageview', 'init_feed',  array(array('url' => $feedurl)));

echo $OUTPUT->header();

$heading = $OUTPUT->heading('Dialog widget (Demo only)');
echo html_writer::div($heading, array('class' => 'message-viewer-view'));

//generate html list for dialog - this should occur in theme
$messages = new local_qmul_messaging_messagelist(context_system::instance());
$messages->get_message_list_by_user($USER->id);

$messagelist =  $renderer->render_message_list_dialog_html($messages);
echo $messagelist;

//widget code
$notificationicon = html_writer::img($CFG->wwwroot . '/local/qmul_messaging/pix/notifications32px.png',
    'notifications', array('class'=>'ui-dialog-icon-open'));
echo html_writer::link(new moodle_url('#'), $notificationicon, array('class' => 'btn dialog-opener', 'style' => ''));
echo html_writer::div($dialogcontent, 'message-dialog');

echo $OUTPUT->footer();
