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


$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/qmul_messaging/sent.php', array('context' => $contextid));
$PAGE->set_title(get_string('messagessent', 'local_qmul_messaging'));


$renderer = $PAGE->get_renderer('local_qmul_messaging');
//$feedurl = "$CFG->wwwroot/local/qmul_messaging/feed.php?context=$contextid";

if($token = get_config('local_qmul_messaging', 'wstoken')){
    $params = array(array('token' => $token,
                          'status' => array('hidden' =>get_string('hiddenstatus', 'local_qmul_messaging'),
                                             'deleted' => get_string('delete', 'local_qmul_messaging')),
                          'action' => array('hide' =>get_string('hide', 'local_qmul_messaging'),
                                            'delete' => get_string('delete', 'local_qmul_messaging'))
    ));
    $PAGE->requires->js_call_amd('local_qmul_messaging/messageview', 'sent_actions', $params);
}

echo $OUTPUT->header();

$can_send = has_capability('local/qmul_messaging:send', $context);


echo $OUTPUT->heading(get_string('messagessent', 'local_qmul_messaging'));

if(!isguestuser($USER)){
    $messages = new local_qmul_messaging_messagelist($context, $USER);
    $messages->get_authored_messages($userid);
    echo $renderer->sent_messagelist($messages);
}
else{

}



echo $OUTPUT->footer();
