<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}
/* User Alerts */
    $temp = new admin_settingpage('theme_qmul_alerts', get_string('alertsheading', 'theme_qmul'));
    $temp->add(new admin_setting_heading('theme_qmul_alerts', get_string('alertsheadingsub', 'theme_qmul'),
            format_text(get_string('alertsdesc' , 'theme_qmul'), FORMAT_MARKDOWN)));

    //This is the descriptor for Alert One
    $name = 'theme_qmul/alert1info';
    $heading = get_string('alert1', 'theme_qmul');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Enable Alert
    $name = 'theme_qmul/enable1alert';
    $title = get_string('enablealert', 'theme_qmul');
    $description = get_string('enablealertdesc', 'theme_qmul');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Type.
    $name = 'theme_qmul/alert1type';
    $title = get_string('alerttype' , 'theme_qmul');
    $description = get_string('alerttypedesc', 'theme_qmul');
    $alert_info = get_string('alert_info', 'theme_qmul');
    $alert_warning = get_string('alert_warning', 'theme_qmul');
    $alert_general = get_string('alert_general', 'theme_qmul');
    $default = 'info';
    $choices = array('info'=>$alert_info, 'warning'=>$alert_warning, 'success'=>$alert_general);
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Title.
    $name = 'theme_qmul/alert1title';
    $title = get_string('alerttitle', 'theme_qmul');
    $description = get_string('alerttitledesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Text.
    $name = 'theme_qmul/alert1text';
    $title = get_string('alerttext', 'theme_qmul');
    $description = get_string('alerttextdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    //This is the descriptor for Alert Two
    $name = 'theme_qmul/alert2info';
    $heading = get_string('alert2', 'theme_qmul');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Enable Alert
    $name = 'theme_qmul/enable2alert';
    $title = get_string('enablealert', 'theme_qmul');
    $description = get_string('enablealertdesc', 'theme_qmul');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Type.
    $name = 'theme_qmul/alert2type';
    $title = get_string('alerttype' , 'theme_qmul');
    $description = get_string('alerttypedesc', 'theme_qmul');
    $alert_info = get_string('alert_info', 'theme_qmul');
    $alert_warning = get_string('alert_warning', 'theme_qmul');
    $alert_general = get_string('alert_general', 'theme_qmul');
    $default = 'info';
    $choices = array('info'=>$alert_info, 'warning'=>$alert_warning, 'success'=>$alert_general);
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Title.
    $name = 'theme_qmul/alert2title';
    $title = get_string('alerttitle', 'theme_qmul');
    $description = get_string('alerttitledesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Text.
    $name = 'theme_qmul/alert2text';
    $title = get_string('alerttext', 'theme_qmul');
    $description = get_string('alerttextdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    //This is the descriptor for Alert Three
    $name = 'theme_qmul/alert3info';
    $heading = get_string('alert3', 'theme_qmul');
    $setting = new admin_setting_heading($name, $heading, '');
    $temp->add($setting);

    // Enable Alert
    $name = 'theme_qmul/enable3alert';
    $title = get_string('enablealert', 'theme_qmul');
    $description = get_string('enablealertdesc', 'theme_qmul');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Type.
    $name = 'theme_qmul/alert3type';
    $title = get_string('alerttype' , 'theme_qmul');
    $description = get_string('alerttypedesc', 'theme_qmul');
    $alert_info = get_string('alert_info', 'theme_qmul');
    $alert_warning = get_string('alert_warning', 'theme_qmul');
    $alert_general = get_string('alert_general', 'theme_qmul');
    $default = 'info';
    $choices = array('info'=>$alert_info, 'warning'=>$alert_warning, 'success'=>$alert_general);
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Title.
    $name = 'theme_qmul/alert3title';
    $title = get_string('alerttitle', 'theme_qmul');
    $description = get_string('alerttitledesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Alert Text.
    $name = 'theme_qmul/alert3text';
    $title = get_string('alerttext', 'theme_qmul');
    $description = get_string('alerttextdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    if (!$ADMIN->locate($temp->name)) {
    $ADMIN->add('theme_qmul', $temp);
}
