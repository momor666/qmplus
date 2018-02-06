<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once(__DIR__.'/lib.php');

require_login();
if (isguestuser()) {
    print_error('noguest');
}

$contextid = required_param('context', PARAM_INT);
$messageid = optional_param('message', 0, PARAM_INT);

$context = context::instance_by_id($contextid);

require_capability('local/qmul_messaging:send', $context);

switch ($context->contextlevel) {
case CONTEXT_SYSTEM:
case CONTEXT_COURSE:
    $PAGE->set_pagelayout('course');
    break;
default:
    $PAGE->set_pagelayout('admin');
    break;
}

$PAGE->set_context($context);
$PAGE->set_url('/local/qmul_messaging/compose.php', array('context' => $contextid));

$editoroptions = array(
    'context' => $context,
    'subdirs' => 0,
    'maxbytes' => $CFG->maxbytes,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
);

// Enumerate the user's active roles, adding roles they can message.
$accessdata = get_user_accessdata($USER->id);
$roleids = array();
$permissions = json_decode(get_config('local_qmul_messaging', 'perms'), true);
foreach ($accessdata['ra'] as $contextpath => $roles) {
    if (strpos($context->path, "$contextpath/") === 0 || $contextpath == $context->path) {
        foreach ($roles as $role) {
            foreach ($permissions[$role] as $role_to => $enabled) {
                if ($enabled) {
                    $roleids[$role_to] = $enabled;
                }
            }
        }
    }
}

// Fetch the role names for roles the user can message.

if(!empty($roleids)){
    $ioe = $DB->get_in_or_equal(array_keys($roleids));
}
else{
    throw new Exception('User cannot message any roles. Ask a developer to revise the code to include this scenario.');
}

$roles = $DB->get_records_sql_menu('SELECT id,name FROM {role} WHERE id '.$ioe[0], $ioe[1]);

$form = new local_qmul_messaging_compose_form(null, array('editoroptions' => $editoroptions, 'roles' => $roles, 'messageid' => $messageid));

if($form->is_cancelled()){

    $somevar = null;

    redirect(new moodle_url('/local/qmul_messaging/index.php', array('context' => $contextid)));

}
else if (($data = $form->get_data()) == null) {
    //data set is null, so new form, nothing to process, get message if one exists
    if ($messageid) {
    //if there is a message id
        $data = $DB->get_record('local_qmul_messaging', array('id' => $messageid, 'context' => $contextid));
        $roles = $DB->get_records_sql('SELECT role FROM {local_qmul_messaging_roles} WHERE message = ?',
                       array($messageid));
        $data->roles = [];
        foreach($roles as $key => $value){
            array_push($data->roles, $key);
        }

    }
    if ($data == null) {
        $data = new stdClass();
        $data->id = null;
        $data->context = $contextid;
    }

    $data = file_prepare_standard_editor($data, 'message', $editoroptions, $context, 'local_qmul_messaging', 'message', $data->id);
    $form->set_data($data);

    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
} else {
    //object exists, so process
    if (!$form->is_cancelled()) {

        $data->author = $USER->id;
        //editor field migration to textarea as table expects value
        $data->subjectformat = 1;

        //new data being submitted
        if (!$data->id) {
            $data->created = time();

            $data = file_postupdate_standard_editor($data, 'message', $editoroptions, $context, 'local_qmul_messaging', 'message', null);

            $data->id = $DB->insert_record('local_qmul_messaging', $data);
        }
        //badly written data either updating or inserting if new


        $data = file_postupdate_standard_editor($data, 'message', $editoroptions, $context, 'local_qmul_messaging', 'message', $data->id);

        $DB->update_record('local_qmul_messaging', $data);

        // Save the roles who can see this message.
        $DB->delete_records('local_qmul_messaging_roles', array('message' => $data->id));
        foreach ($data->roles as $role) {
            $record = new stdClass();
            $record->message = $data->id;
            $record->role = $role;
            $DB->insert_record('local_qmul_messaging_roles', $record);
        }
    }
    redirect(new moodle_url('/local/qmul_messaging/index.php', array('context' => $contextid)));
}

