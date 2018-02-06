<?php

/****************************************************************

File:       local/physicsapp/settings.php

Purpose:    Global configuration page for the block

****************************************************************/

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/course/lib.php';

if ($hassiteconfig) { // needs this condition or there is error on login page

    $settings = new admin_settingpage('local_physicsapp',
        get_string('pluginname', 'local_physicsapp'));

    // General settings header.
    $settings->add(new admin_setting_heading('local_physicsapp/exdbheader', get_string('settingsheaderdb', 'local_physicsapp'), get_string('settingsavailable', 'local_physicsapp')));

    // Get the mods allowed on the front page (for the sake of argument...
    $modnamesplural = get_module_types_names(true);

    $settings->add(new admin_setting_configmultiselect('local_physicsapp/allowedmods', get_string('settingsallowedmods', 'local_physicsapp'), get_string('settingsallowedmods_desc', 'local_physicsapp'), array(), $modnamesplural));

    $settings->add(new admin_setting_configtext('local_physicsapp/linkwarning', get_string('settingslinkwarning', 'local_physicsapp'), get_string('settingslinkwarning_desc', 'local_physicsapp'), get_string('settingslinkwarning_default', 'local_physicsapp')));

    $settings->add(new admin_setting_configcheckbox('local_physicsapp/displaytopiczero', get_string('settingsdisplaytopiczero', 'local_physicsapp'), get_string('settingsdisplaytopiczero_desc', 'local_physicsapp'), 0));

    // Add link to configuration page.
    $ADMIN->add('localplugins', $settings);
}
