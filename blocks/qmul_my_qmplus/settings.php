<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array('all'=>get_string('allcourses', 'block_qmul_my_qmplus'), 'own'=>get_string('owncourses', 'block_qmul_my_qmplus'));

    $settings->add(new admin_setting_configselect('block_qmul_my_qmplus_adminview', get_string('adminview', 'block_qmul_my_qmplus'),
                       get_string('configadminview', 'block_qmul_my_qmplus'), 'all', $options));

    $settings->add(new admin_setting_configcheckbox('block_qmul_my_qmplus_hideallcourseslink', get_string('hideallcourseslink', 'block_qmul_my_qmplus'),
                       get_string('confighideallcourseslink', 'block_qmul_my_qmplus'), 0));
}


