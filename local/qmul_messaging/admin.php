<?php

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('local_qmul_messaging');


$introduction = html_writer::div();

$mform = new local_qmul_messaging_admin_form();
if ($data = $mform->get_data()) {
    set_config('perms', json_encode($data->enable), 'local_qmul_messaging');
    set_config('wstoken', $data->wstoken, 'local_qmul_messaging');
} else {
    $data = array(
        'enable' => json_decode(get_config('local_qmul_messaging', 'perms'), true),
        'wstoken' => get_config('local_qmul_messaging', 'wstoken')
    );
    $mform->set_data($data);
}
echo $OUTPUT->header();
echo $OUTPUT->box(get_string('introduction', 'local_qmul_messaging'), 'introduction');

$mform->display();

echo $OUTPUT->footer();
