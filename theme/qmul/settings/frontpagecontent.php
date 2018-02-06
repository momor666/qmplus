<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}

$main = new admin_settingpage('theme_qmul_frontpagecontent', get_string('frontcontentheading', 'theme_qmul'));

$main->add(new admin_setting_heading('theme_qmul_frontpagecontent', get_string('frontcontentheadingsub', 'theme_qmul'),
            format_text(get_string('frontcontentdesc' , 'theme_qmul'), FORMAT_MARKDOWN)));

$name = "theme_qmul/loginbg";
$title = get_string('loginbg', 'theme_qmul');
$description = get_string('loginbgdesc', 'theme_qmul');
$setting = new admin_setting_configstoredfile($name, $title, $description, "loginbg");
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/browsemodulesbg";
$title = get_string('browsemodulesbg', 'theme_qmul');
$description = get_string('browsemodulesbgdesc', 'theme_qmul');
$setting = new admin_setting_configstoredfile($name, $title, $description, "browsemodulesbg");
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/helpsupportbg";
$title = get_string('helpsupportbg', 'theme_qmul');
$description = get_string('helpsupportbgdesc', 'theme_qmul');
$setting = new admin_setting_configstoredfile($name, $title, $description, "helpsupportbg");
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/helpsupportlink";
$title = get_string('helpsupportlink', 'theme_qmul');
$description = get_string('helpsupportlinkdesc', 'theme_qmul');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/qmplusmediabg";
$title = get_string('qmplusmediabg', 'theme_qmul');
$description = get_string('qmplusmediabgdesc', 'theme_qmul');
$setting = new admin_setting_configstoredfile($name, $title, $description, "qmplusmediabg");
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/qmplusmedialink";
$title = get_string('qmplusmedialink', 'theme_qmul');
$description = get_string('qmplusmedialinkdesc', 'theme_qmul');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/qmplushubbg";
$title = get_string('qmplushubbg', 'theme_qmul');
$description = get_string('qmplushubbgdesc', 'theme_qmul');
$setting = new admin_setting_configstoredfile($name, $title, $description, "qmplushubbg");
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/qmplushublink";
$title = get_string('qmplushublink', 'theme_qmul');
$description = get_string('qmplushublinkdesc', 'theme_qmul');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/qmplusarchivebg";
$title = get_string('qmplusarchivebg', 'theme_qmul');
$description = get_string('qmplusarchivebgdesc', 'theme_qmul');
$setting = new admin_setting_configstoredfile($name, $title, $description, "qmplusarchivebg");
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);

$name = "theme_qmul/qmplusarchivelink";
$title = get_string('qmplusarchivelink', 'theme_qmul');
$description = get_string('qmplusarchivelinkdesc', 'theme_qmul');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$setting->set_updatedcallback('theme_reset_all_caches');
$main->add($setting);


if (!$ADMIN->locate($main->name)) {
    $ADMIN->add('theme_qmul', $main);
}