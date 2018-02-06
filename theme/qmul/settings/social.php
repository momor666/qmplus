<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}
/* Social Network Settings */
	$temp = new admin_settingpage('theme_qmul_social', get_string('socialheading', 'theme_qmul'));
	$temp->add(new admin_setting_heading('theme_qmul_social', get_string('socialheadingsub', 'theme_qmul'),
            format_text(get_string('socialdesc' , 'theme_qmul'), FORMAT_MARKDOWN)));

    // Facebook url setting.
    $name = 'theme_qmul/facebook';
    $title = get_string('facebook', 'theme_qmul');
    $description = get_string('facebookdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Flickr url setting.
    $name = 'theme_qmul/flickr';
    $title = get_string('flickr', 'theme_qmul');
    $description = get_string('flickrdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Twitter url setting.
    $name = 'theme_qmul/twitter';
    $title = get_string('twitter', 'theme_qmul');
    $description = get_string('twitterdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Google+ url setting.
    $name = 'theme_qmul/googleplus';
    $title = get_string('googleplus', 'theme_qmul');
    $description = get_string('googleplusdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // LinkedIn url setting.
    $name = 'theme_qmul/linkedin';
    $title = get_string('linkedin', 'theme_qmul');
    $description = get_string('linkedindesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Pinterest url setting.
    $name = 'theme_qmul/pinterest';
    $title = get_string('pinterest', 'theme_qmul');
    $description = get_string('pinterestdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Instagram url setting.
    $name = 'theme_qmul/instagram';
    $title = get_string('instagram', 'theme_qmul');
    $description = get_string('instagramdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // YouTube url setting.
    $name = 'theme_qmul/youtube';
    $title = get_string('youtube', 'theme_qmul');
    $description = get_string('youtubedesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Skype url setting.
    $name = 'theme_qmul/skype';
    $title = get_string('skype', 'theme_qmul');
    $description = get_string('skypedesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    if (!$ADMIN->locate($temp->name)) {
    $ADMIN->add('theme_qmul', $temp);
}