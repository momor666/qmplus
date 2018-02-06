<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
defined('MOODLE_INTERNAL') || die();
require_login();
global $USER;

$config = get_config('block_module_info');
$params = array();

$linkstring = get_string('default_personal_smart_link', 'block_module_info');

if (strlen($USER->idnumber) == 9) {
    $params['objectclass'] = 'student+set';
    $linkstring = get_string('student_personal_smart_link', 'block_module_info');
} elseif (strlen($USER->idnumber) == 6) {
    $params['objectclass'] = 'staff';
    $linkstring = get_string('staff_personal_smart_link', 'block_module_info');
} else {
    $params['objectclass'] = 'student';
}
$params['week'] = (empty($config->week)) ? '' : $config->week;
$params['day'] =  (empty($config->day)) ? '' : $config->day;
$params['period'] =  (empty($config->period)) ? '' : $config->period;
$params['identifier'] = $USER->idnumber;
$params['style'] = isset($config->style) ? $config->style : '';
$params['template'] = isset($config->template) ? $config->template : '';
$baseurl = isset($config->baseurl) ? $config->baseurl : '';
redirect(new moodle_url($baseurl, $params));