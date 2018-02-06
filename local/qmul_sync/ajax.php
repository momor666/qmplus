<?php

require_once('../../config.php');

$action = required_param('action', PARAM_ALPHANUMEXT);

switch ($action) {
case 'moodle_course':
    $idnumber = required_param('idnumber', PARAM_ALPHANUMEXT);
    $result = local_qmul_sync_plugin::ajax_moodle_course($idnumber);
    break;
default:
    $result = new stdClass();
    break;
}
echo json_encode($result);
