<?php
/**
 * qmul_messaging plugin settings and presets.
 *
 * @package    local_qmul_messaging
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
 $ADMIN->add('localplugins', new admin_externalpage('local_qmul_messaging', get_string('pluginname', 'local_qmul_messaging'),
       $CFG->wwwroot . '/local/qmul_messaging/admin.php', 'moodle/site:config'));
}
