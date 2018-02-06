<?php
if (!$ADMIN->locate('theme_qmul')) {
    $ADMIN->add('themes', new admin_category('theme_qmul', get_string('configtitle', 'theme_qmul')));
}
	/* Custom Menu Settings */
    $temp = new admin_settingpage('theme_qmul_custommenu', get_string('custommenuheading', 'theme_qmul'));

    //This is the descriptor for the following Moodle color settings
    $name = 'theme_qmul/mycoursesinfo';
    $heading = get_string('mycoursesinfo', 'theme_qmul');
    $information = get_string('mycoursesinfodesc', 'theme_qmul');
    $setting = new admin_setting_heading($name, $heading, $information);
    $temp->add($setting);

    // Toggle courses display in custommenu.
    $name = 'theme_qmul/displaymycourses';
    $title = get_string('displaymycourses', 'theme_qmul');
    $description = get_string('displaymycoursesdesc', 'theme_qmul');
    $default = true;
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $temp->add($setting);

    // Set terminology for dropdown course list
	$name = 'theme_qmul/mycoursetitle';
	$title = get_string('mycoursetitle','theme_qmul');
	$description = get_string('mycoursetitledesc', 'theme_qmul');
	$default = 'course';
	$choices = array(
		'course' => get_string('mycourses', 'theme_qmul'),
		'unit' => get_string('myunits', 'theme_qmul'),
		'class' => get_string('myclasses', 'theme_qmul'),
		'module' => get_string('mymodules', 'theme_qmul')
	);
	$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
	$setting->set_updatedcallback('theme_reset_all_caches');
	$temp->add($setting);

    if (!$ADMIN->locate($temp->name)) {
    $ADMIN->add('theme_qmul', $temp);
}