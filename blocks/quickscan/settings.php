<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $configs = array();

    $configs[] = new admin_setting_configtext('url', get_string('configurl', 'block_quickscan'),
        get_string('configurldescription', 'block_quickscan'), '', PARAM_TEXT);
    $configs[] = new admin_setting_confightmleditor('explanation', get_string('configexplanation', 'block_quickscan'),
        get_string('configexplanationdescription', 'block_quickscan'), '', PARAM_RAW, 80, 25);
    $configs[] = new admin_setting_confightmleditor('footer', get_string('configfooter', 'block_quickscan'),
        get_string('configfooterdescription', 'block_quickscan'), '', PARAM_RAW, 80, 10);

    foreach ($configs as $config) {
        $config->plugin = 'blocks/quickscan';
        $settings->add($config);
    }
}