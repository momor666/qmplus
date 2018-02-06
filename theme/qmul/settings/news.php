<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}

// "newssettings" settingpage
$temp = new admin_settingpage('theme_qmul_news',  get_string('newssettings', 'theme_qmul'));

$name = 'theme_qmul/enablesitemessage';
$title = get_string('enablesitemessage','theme_qmul');
$description = get_string('enablesitemessage_help', 'theme_qmul');
$setting = new admin_setting_configcheckbox($name, $title, $description, 0);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_qmul/sitemessage';
$title = get_string('sitemessage','theme_qmul');
$description = get_string('sitemessage_help', 'theme_qmul');
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_qmul/enablesitenews';
$title = get_string('enablesitenews','theme_qmul');
$description = get_string('enablesitenews_help', 'theme_qmul');
$setting = new admin_setting_configcheckbox($name, $title, $description, 1);
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_qmul/sitenewsrssfeed';
$title = get_string('sitenewsrssfeed','theme_qmul');
$description = get_string('sitenewsrssfeed_help', 'theme_qmul');
$setting = new admin_setting_configtextarea($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

$name = 'theme_qmul/itsservicerssfeed';
$title = get_string('itsservicerssfeed','theme_qmul');
$description = get_string('itsservicerssfeed_help', 'theme_qmul');
$setting = new admin_setting_configtextarea($name, $title, $description, '');
$setting->set_updatedcallback('theme_reset_all_caches');
$temp->add($setting);

if ($field = $DB->get_record('user_info_field', array('shortname'=>'landingpage'))) {
	$list = explode("\n", $field->param1);

	foreach ($list as $faculty) {
	    $text = str_replace('-', '_', trim($faculty));
	    $name = 'theme_qmul/'.$text.'_rssfeed';
	    $title = $faculty.' '.get_string('rssfeed','theme_qmul');
	    $description = get_string('rssfeed_help', 'theme_qmul');
	    $setting = new admin_setting_configtextarea($name, $title, $description, '');
	    $setting->set_updatedcallback('theme_reset_all_caches');
	    $temp->add($setting);
	    $name = 'theme_qmul/'.$text.'_landingid';
	    $title = get_string('landingid','theme_qmul');
	    $description = get_string('landingid_help', 'theme_qmul');
	    $setting = new admin_setting_configtext($name, $title, $description, '');
	    $setting->set_updatedcallback('theme_reset_all_caches');
	    $temp->add($setting);
	}
}

if (!$ADMIN->locate($temp->name)) {
    $ADMIN->add('theme_qmul', $temp);
}