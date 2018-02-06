<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}

$temp = new theme_qmul_admin_settingspage_tabs('theme_qmul_marketing', get_string('marketingheading', 'theme_qmul'));

$main = new admin_settingpage('theme_qmul_marketing', get_string('marketingheading', 'theme_qmul'));
$main->add(new admin_setting_heading('theme_qmul_marketing', get_string('marketingheadingsub', 'theme_qmul'),
        format_text(get_string('marketingdesc' , 'theme_qmul'), FORMAT_MARKDOWN)));

// Toggle Marketing Spots.
$name = 'theme_qmul/togglemarketing';
$title = get_string('togglemarketing' , 'theme_qmul');
$description = get_string('togglemarketingdesc', 'theme_qmul');
$alwaysdisplay = get_string('alwaysdisplay', 'theme_qmul');
$displaybeforelogin = get_string('displaybeforelogin', 'theme_qmul');
$displayafterlogin = get_string('displayafterlogin', 'theme_qmul');
$dontdisplay = get_string('dontdisplay', 'theme_qmul');
$default = 'display';
$choices = array('1'=>$alwaysdisplay, '2'=>$displaybeforelogin, '3'=>$displayafterlogin, '0'=>$dontdisplay);
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$temp->add($main);

$spots = 3;

for ($i=1; $i <= $spots; $i++) {

    $main = new admin_settingpage('theme_qmul_marketingspot'.$i, get_string('marketingspot', 'theme_qmul', $i));

    $spotname = 'marketing'.$i;

    //This is the descriptor for Marketing Spot One
    $name = 'theme_qmul/info_'.$spotname;
    $heading = get_string('marketingspot', 'theme_qmul', $i);
    $information = get_string('marketinginfodesc', 'theme_qmul');
    $setting = new admin_setting_heading($name, $heading, $information);
    $main->add($setting);

    //Marketing Spot One.
    $name = 'theme_qmul/title_'.$spotname;
    $title = get_string('marketingtitle', 'theme_qmul');
    $description = get_string('marketingtitledesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $main->add($setting);

    $name = 'theme_qmul/image_'.$spotname;
    $title = get_string('marketingimage', 'theme_qmul');
    $description = get_string('marketingimagedesc', 'theme_qmul');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'image_'.$spotname);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $main->add($setting);

    $name = 'theme_qmul/content_'.$spotname;
    $title = get_string('marketingcontent', 'theme_qmul');
    $description = get_string('marketingcontentdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $main->add($setting);

    $name = 'theme_qmul/buttontext_'.$spotname;
    $title = get_string('marketingbuttontext', 'theme_qmul');
    $description = get_string('marketingbuttontextdesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $main->add($setting);

    $name = 'theme_qmul/buttonurl_'.$spotname;
    $title = get_string('marketingbuttonurl', 'theme_qmul');
    $description = get_string('marketingbuttonurldesc', 'theme_qmul');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $main->add($setting);

    $temp->add($main);

}

if (!$ADMIN->locate($temp->name)) {
    $ADMIN->add('theme_qmul', $temp);
}