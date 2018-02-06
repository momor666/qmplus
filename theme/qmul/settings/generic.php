<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}
    // "genericsettings" settingpage
	$temp = new admin_settingpage('theme_qmul_generic',  get_string('genericsettings', 'theme_qmul'));

    // Use slide out drawer menus
    $name = 'theme_qmul/mymoduleslimit';
    $title = get_string('mymoduleslimit', 'theme_qmul');
    $description = get_string('mymoduleslimitdesc', 'theme_qmul');
    $default = 10;
    $setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_INT);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Logo file setting.
    $name = 'theme_qmul/logo';
    $title = get_string('logo', 'theme_qmul');
    $description = get_string('logodesc', 'theme_qmul');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Logo file setting.
    $name = 'theme_qmul/loginbackground';
    $title = get_string('loginbackground', 'theme_qmul');
    $description = get_string('loginbackgrounddesc', 'theme_qmul');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'loginbackground');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Use slide out drawer menus
    $name = 'theme_qmul/drawermenus';
    $title = get_string('drawermenus', 'theme_qmul');
    $description = get_string('drawermenusdesc', 'theme_qmul');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Use dashboard course boxes
    $name = 'theme_qmul/coursebox';
    $title = get_string('coursebox', 'theme_qmul');
    $description = get_string('courseboxdesc', 'theme_qmul');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Use sticky tables
    $name = 'theme_qmul/stickytables';
    $title = get_string('stickytables', 'theme_qmul');
    $description = get_string('stickytablesdesc', 'theme_qmul');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_qmul/showteacherimages';
    $title = get_string('showteacherimages', 'theme_qmul');
    $description = get_string('showteacherimagesdesc', 'theme_qmul');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Use full screen scorm page
    $name = 'theme_qmul/fullscreenscorm';
    $title = get_string('fullscreenscorm', 'theme_qmul');
    $description = get_string('fullscreenscormdesc', 'theme_qmul');
    $default = false;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Copyright setting.
    $name = 'theme_qmul/copyright';
    $title = get_string('copyright', 'theme_qmul');
    $description = get_string('copyrightdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $temp->add($setting);

    // Footnote setting.
    $name = 'theme_qmul/footnote';
    $title = get_string('footnote', 'theme_qmul');
    $description = get_string('footnotedesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    $name = 'theme_qmul/footright';
    $title = get_string('footright', 'theme_qmul');
    $description = get_string('footrightdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Custom CSS file.
    $name = 'theme_qmul/customcss';
    $title = get_string('customcss', 'theme_qmul');
    $description = get_string('customcssdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

if (!$ADMIN->locate($temp->name)) {
    $ADMIN->add('theme_qmul', $temp);
}
