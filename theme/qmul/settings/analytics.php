<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}
/* Analytics Settings */
    $temp = new admin_settingpage('theme_qmul_analytics', get_string('analyticsheading', 'theme_qmul'));
    $temp->add(new admin_setting_heading('theme_qmul_analytics', get_string('analyticsheadingsub', 'theme_qmul'),
            format_text(get_string('analyticsdesc' , 'theme_qmul'), FORMAT_MARKDOWN)));

    // Enable Analytics
    $name = 'theme_qmul/useanalytics';
    $title = get_string('useanalytics', 'theme_qmul');
    $description = get_string('useanalyticsdesc', 'theme_qmul');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Google Analytics ID
    $name = 'theme_qmul/analyticsid';
    $title = get_string('analyticsid', 'theme_qmul');
    $description = get_string('analyticsiddesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Clean Analytics URL
    $name = 'theme_qmul/analyticsclean';
    $title = get_string('analyticsclean', 'theme_qmul');
    $description = get_string('analyticscleandesc', 'theme_qmul');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    if (!$ADMIN->locate($temp->name)) {
    $ADMIN->add('theme_qmul', $temp);
}