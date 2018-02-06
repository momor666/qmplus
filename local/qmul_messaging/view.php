<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/filelib.php');

global $USER;

require_login();
if (isguestuser()) {
    print_error('noguest');
}

$messageid = required_param('message', PARAM_INT);

//TODO: more data required here oops
$message = local_qmul_messaging_get_single_message($messageid);

if(!$message){
    throw new Exception('Message does not exist. It may have been deleted.');
}

$loggedinuserid = $USER->id;

//
$roles = local_qmul_messaging_get_user_roles($loggedinuserid);


if (!local_qmul_messaging_has_message_role($message->messageroleids, $roles, $message->author))
{
    throw new Exception('You do not have the required role to view this message.');
}

$message->subject = file_rewrite_pluginfile_urls($message->subject, 'pluginfile.php', $message->context, 'local_qmul_messaging', 'subject', $message->id);
$message->message = file_rewrite_pluginfile_urls($message->message, 'pluginfile.php', $message->context, 'local_qmul_messaging', 'message', $message->id);

$contextid = $message->context;
$context = context::instance_by_id($contextid);

//check user has role to view message


$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_url('/local/qmul_messaging/view.php', array('message' => $messageid));

$renderer = $PAGE->get_renderer('local_qmul_messaging');

//TODO: mark as read here
local_qmul_messaging_mark_as_read($messageid, $USER->id);

echo $OUTPUT->header();
echo $renderer->render_full_message($message);
echo $OUTPUT->footer();
