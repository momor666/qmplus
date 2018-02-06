<?php
/**
* Settings details.
*
* @package    local
* @subpackage local_qmul_download_submissions
* @copyright  2015 Queen Mary University of London
* @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
defined('MOODLE_INTERNAL') || die;

global $PAGE;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_qmul_download_submissions', get_string('pluginname', 'local_qmul_download_submissions'));

    $settings->add(new admin_setting_heading('local_qmul_download_submissions' . '/heading', ' '  , get_string('pluginname_desc', 'local_qmul_download_submissions')));


    $settings->add(new admin_setting_configcheckbox('local_qmul_download_submissions' . '/enable', get_string('enable', 'local_qmul_download_submissions') , get_string('useurl', 'local_qmul_download_submissions'), 0));
    $settings->add(new admin_setting_configtext('local_qmul_download_submissions' . '/label', get_string('linklabel', 'local_qmul_download_submissions') , '', 'Download all submissions'));

    $ADMIN->add('localplugins', $settings);
}