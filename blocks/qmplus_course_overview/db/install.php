<?php

function xmldb_block_qmplus_course_overview_install() {

    global $DB;

    // Install an instance of this block on every page with a regular course_overview block
    $block_instances = $DB->get_records('block_instances', array('blockname' => 'course_overview'));
    foreach ($block_instances as $block_instance) {
        $block_instance->blockname = 'qmplus_course_overview';
	unset($block_instance->id);
        $DB->insert_record('block_instances', $block_instance);
        error_log(print_r($block_instance, true));
    }

    // Turn off the regular course_overview block
    $DB->set_field('block', 'visible', '0', array('name' => 'course_overview'));

    return true;
}
