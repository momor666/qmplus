<?php

require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/navigationlib.php');

class local_qmul_messaging {
    const HIDE=1;
    const DELETE=2;
}

function local_qmul_messaging_extend_navigation(global_navigation $navigation) {
    global $PAGE, $USER;

//    if ($users = $navigation->find('users', navigation_node::TYPE_CONTAINER)) {
//        $action = new moodle_url('/local/qmul_messaging/index.php');
//        $label = get_string('pluginname', 'local_qmul_messaging');
//        $users->add($label, $action);
//    }

    //user must be logged in (not guest) to see ticker message
    $userisloggedin = isloggedin();
    $userisguest = isguestuser($USER);
    $token = get_config('local_qmul_messaging', 'wstoken');

    //calls js
    if($token and !$userisguest and ($USER->id != 0) and $userisloggedin){
        $params = array(array('token' => $token, 'user' => $USER->id, 'context' => 1));
        $PAGE->requires->js_call_amd('local_qmul_messaging/messageview', 'message_ticker', $params);
    }

}



function local_qmul_messaging_extend_settings_navigation(settings_navigation $navigation, context $context) {

    if(!has_capability('local/qmul_messaging:send', $context)){
        return false;
    }

    $contextlevel = $context->contextlevel;

    //urls
    $viewaction = new moodle_url('/local/qmul_messaging/index.php', array('context' => $context->id));
    $sentaction = new moodle_url('/local/qmul_messaging/sent.php', array('context' => $context->id));
    $composeaction = new moodle_url('/local/qmul_messaging/compose.php', array('context' => $context->id));
    $widget = new moodle_url('/local/qmul_messaging/widget.php', array('context' => 1));

    $label = get_string('navgroup', 'local_qmul_messaging');


    //only display if the menus are there
    $usersnode = $navigation->find('users', navigation_node::TYPE_USER );
    $rootsnode = $navigation->find('root', navigation_node::TYPE_SITE_ADMIN );
    $coursecatnode = $navigation->find('categorysettings', navigation_node::TYPE_CATEGORY);


    if ((($contextlevel == 10) or ($contextlevel == 30)) && ($rootsnode)) {
        $menukey = get_string('menukey', 'local_qmul_messaging');
        $rootsnode->add($label, null, navigation_node::NODETYPE_BRANCH,
                           get_string('shorttxtmenunode', 'local_qmul_messaging'),
                           $menukey);
        $messagemenunode = $rootsnode->find($menukey, navigation_node::NODETYPE_BRANCH);
        //view messages
        $messagemenunode->add(get_string('inboxlabel', 'local_qmul_messaging'), $viewaction, navigation_node::NODETYPE_LEAF,
                           get_string('shorttxtviewnode', 'local_qmul_messaging'),
                           get_string('viewnodekey', 'local_qmul_messaging')
                           );
        //sent messages
        $messagemenunode->add(get_string('sentmessageslable', 'local_qmul_messaging'), $sentaction, navigation_node::NODETYPE_LEAF,
                            get_string('shorttxtsentnode', 'local_qmul_messaging'),
                            get_string('sentnodekey', 'local_qmul_messaging')
                            );
        //compose message
        $messagemenunode->add(get_string('composelable', 'local_qmul_messaging'), $composeaction, navigation_node::NODETYPE_LEAF,
                            get_string('shorttxtcomposenode', 'local_qmul_messaging'),
                            get_string('composenodekey', 'local_qmul_messaging')
                            );

    }
    else if (($contextlevel == 40) && ($coursecatnode)) {

        $menukey = get_string('menukey', 'local_qmul_messaging');
        $coursecatnode->add($label, null, navigation_node::NODETYPE_BRANCH,
            get_string('shorttxtmenunode', 'local_qmul_messaging'),
            $menukey);
        $messagemenunode = $coursecatnode->find($menukey, navigation_node::NODETYPE_BRANCH);
        //view messages
        $messagemenunode->add(get_string('inboxlabel', 'local_qmul_messaging'), $viewaction, navigation_node::NODETYPE_LEAF,
            get_string('shorttxtviewnode', 'local_qmul_messaging'),
            get_string('viewnodekey', 'local_qmul_messaging')
        );
        //sent messages
        $messagemenunode->add(get_string('sentmessageslable', 'local_qmul_messaging'), $sentaction, navigation_node::NODETYPE_LEAF,
            get_string('shorttxtsentnode', 'local_qmul_messaging'),
            get_string('sentnodekey', 'local_qmul_messaging')
        );
        //compose message
        $messagemenunode->add(get_string('composelable', 'local_qmul_messaging'), $composeaction, navigation_node::NODETYPE_LEAF,
            get_string('shorttxtcomposenode', 'local_qmul_messaging'),
            get_string('composenodekey', 'local_qmul_messaging')
        );
//        $messagemenunode->add('Dialog Widget (demo only)', $widget);

    }



}


/**
 * TODO : add to messagelist class to benefit from full message object
 * @param $messageid
 * @return bool|mixed
 *
 */
function local_qmul_messaging_get_single_message($messageid){
    global $DB;

    $query = <<<END_SQL
        SELECT lqm.id as messageid,  lqm.*, r.*, r.name AS messagerolename,
               lqmr.role as messagerole, u.firstname, u.lastname,
            CASE
                  WHEN lqm.validfrom = 0 THEN lqm.created
                  ELSE lqm.validfrom END AS postdate
            FROM
                {local_qmul_messaging} lqm,
                {local_qmul_messaging_roles} lqmr,
                {user} u,
                {role} r
            WHERE
                u.id = lqm.author
                AND lqmr.message = lqm.id
                AND lqm.id = ?
                AND lqmr.role = r.id
         ORDER BY postdate DESC;  

END_SQL;

    $results =  $DB->get_recordset_sql($query, array($messageid));
    $userfileds = 'id, picture, firstname, lastname, firstnamephonetic, lastnamephonetic,
            middlename, alternatename, imagealt, email';

    $messageroles = array();
    $messageroleids = array();
    foreach($results as $result){
        if($result->messageid != $messageid){
            return false;
        }
        //TODO - pluralise rolenames if required
        array_push($messageroles, $result->messagerolename);
        array_push($messageroleids, $result->messagerole);
        $result->messagerolenames = implode(', ', $messageroles);
        $result->displaytime = local_qmul_messaging_get_record_display_time($result->postdate);
        $result->authorrecord = $DB->get_record('user', array('id' => $result->author), $userfileds);
        $context = context::instance_by_id($result->context);
        $result->contextname = $context->get_context_name(false);
    }
    $result->messagerolenames = $messageroles;
    $result->messageroleids = $messageroleids;


    if($result){
        return $result;
    }
    else{
        return false;
    }
}



/**
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param $options
 */
function local_qmul_messaging_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_qmul_messaging', $filearea, $args[0], '/', $args[1]);
    header('Content-type: '.$file->mimetype);
    $file->readfile();
    exit;
}

/**
 * @param $message
 * @return bool
 */
function local_qmul_messaging_delete_message($message){
    global $DB;

//    $DB->delete_records('local_qmul_messaging_roles', array('message' => $message->messageid));
    return $DB->delete_records('local_qmul_messaging', array('id' => $message->id));
}


/**
 * @param $messageid
 * @param $userid
 * @return bool
 */
function local_qmul_messaging_mark_as_read($messageid, $userid){
    global $DB;

    //has message already been read
    if(local_qmul_messaging_is_read($messageid, $userid)){
        return false;
    }

    //insert record
    $date = new DateTime();
    $record = new stdClass();
    $record->message = $messageid;
    $record->userid = $userid;
    $record->timeread = $date->getTimestamp();

    if($markasreadinsert = $DB->insert_record('local_qmul_messaging_mark', $record, false)){
        return true;
    }
    else{
        return false;
    }

}

/**
 * @param $messageid
 * @param $userid
 * @return bool
 */
function local_qmul_messaging_is_read($messageid, $userid){
    global $DB;

    //test if already exists
    $params = array('message' => $messageid, 'userid' => $userid);
    if($DB->record_exists('local_qmul_messaging_mark', $params)){
        return true;
    }
    else{
        return false;
    }
}

/**
 * @param $userid
 * @return array
 */
function local_qmul_messaging_get_read_list($userid){
    global $DB, $USER;

    $readlist = array();
    $results =  $DB->get_records('local_qmul_messaging_mark', array('userid' => $userid));
    foreach ($results as $result){
         array_push($readlist, $result->message);
    }

    return $readlist;
}







/**
 * @param $postdate
 * @return false|string
 */
function local_qmul_messaging_get_record_display_time($postdate){

    date_default_timezone_set('UTC');
    $displaydate = '';

    $today = date('j M Y');

    $year = date('Y', $postdate);
    $date = date('j M Y', $postdate);

    if($today == $date){
        $time = date('H:i', $postdate);
        $displaydate = $time;
    }
    else{
        $displaydate = $date;
    }

    return $displaydate;
}



/**
 *
 */
function local_qmul_messaging_cron() {
    global $DB;

    mtrace(print_r(__FUNCTION__, true));
    $messages = $DB->get_recordset_sql('SELECT * FROM {local_qmul_messaging} WHERE emailsent=0 AND timenow() > validfrom AND validfrom != 0', array());
    foreach ($messages as $message) {
        m_trace(print_r($message));
    }
    $messages->close();
}


/*
 * Security flag - this gets absolute roles for the user
 * not context relative roles.
 *
 */
function local_qmul_messaging_get_user_roles($userid){
    global $DB;

    $results =  $DB->get_records('role_assignments', array('userid' => $userid), '', 'roleid');

    $userroles = [];
    $num = 0;
    foreach ($results as $result){
        $userroles[$num] = $result->roleid;
        $num++;
    }

    return $userroles;

}

/**
 * @param $messageroles
 * @param $userroles
 * @param $messageauthor
 * @return bool
 */
function local_qmul_messaging_has_message_role($messageroles, $userroles, $messageauthor){
    global $USER;

    if($messageauthor == $USER->id){
        return true;
    }

    foreach($userroles as $userrole){
        if(in_array($userrole, $messageroles)){
            return true;
        }
    }
    return false;
}




