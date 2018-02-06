<?php
// This file is part of The Bootstrap Moodle theme
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_qmul
 * @copyright  2016 Andrew Davidson, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/admin/renderer.php");


class theme_qmul_core_admin_renderer extends core_admin_renderer {

    protected function maturity_info($maturity) {
        if ($maturity == MATURITY_STABLE) {
            return ''; // No worries.
        }

        $level = 'warning';

        if ($maturity == MATURITY_ALPHA) {
            $level = 'error';
        }

        $maturitylevel = get_string('maturity' . $maturity, 'admin');
        $warningtext = get_string('maturitycoreinfo', 'admin', $maturitylevel);
        $warningtext .= ' ' . $this->doc_link('admin/versions', get_string('morehelp'));
        return $this->warning($warningtext, $level);
    }

    protected function warning($message, $type = 'warning') {
        return $this->box($message, 'generalbox admin alert alert-' . $type);
    }

    protected function test_site_warning($testsite) {
        if (!$testsite) {
            return '';
        }
        $warningtext = get_string('testsiteupgradewarning', 'admin', $testsite);
        return $this->notification($warningtext, 'notifyproblem');
    }

    /**
     * Display a link to the release notes.
     * @return string HTML to output.
     */
    protected function release_notes_link() {
        $releasenoteslink = get_string('releasenoteslink', 'admin', 'http://docs.moodle.org/dev/Releases');
        $releasenoteslink = str_replace('target="_blank"', 'onclick="this.target=\'_blank\'"', $releasenoteslink); // extremely ugly validation hack
        return $this->box($releasenoteslink, 'generalbox releasenoteslink alert alert-warning');
    }

    public function plugins_check_table(core_plugin_manager $pluginman, $version, array $options = array()) {
        $html = parent::plugins_check_table($pluginman, $version, $options);

        $replacements = array(
            'generaltable' => 'table table-striped',
            'status-missing' => 'danger',
            'status-downgrade' => 'danger',
            'status-upgrade' => 'info',
            'status-delete' => 'info',
            'status-new' => 'success',
            'label label-important' => 'badge badge-danger',
            'label label-info' => 'badge badge-info'
        );

        $find = array_keys($replacements);
        $replace = array_values($replacements);

        return str_replace($find, $replace, $html);
    }

    public function plugins_control_panel(core_plugin_manager $pluginman, array $options = array()) {

        $plugininfo = $pluginman->get_plugins();

        // Filter the list of plugins according the options.
        if (!empty($options['updatesonly'])) {
            $updateable = array();
            foreach ($plugininfo as $plugintype => $pluginnames) {
                foreach ($pluginnames as $pluginname => $pluginfo) {
                    $pluginavailableupdates = $pluginfo->available_updates();
                    if (!empty($pluginavailableupdates)) {
                        foreach ($pluginavailableupdates as $pluginavailableupdate) {
                            $updateable[$plugintype][$pluginname] = $pluginfo;
                        }
                    }
                }
            }
            $plugininfo = $updateable;
        }

        if (!empty($options['contribonly'])) {
            $contribs = array();
            foreach ($plugininfo as $plugintype => $pluginnames) {
                foreach ($pluginnames as $pluginname => $pluginfo) {
                    if (!$pluginfo->is_standard()) {
                        $contribs[$plugintype][$pluginname] = $pluginfo;
                    }
                }
            }
            $plugininfo = $contribs;
        }

        if (empty($plugininfo)) {
            return '';
        }

        $table = new html_table();
        $table->id = 'plugins-control-panel';
        $table->head = array(
            get_string('displayname', 'core_plugin'),
            get_string('version', 'core_plugin'),
            get_string('availability', 'core_plugin'),
            get_string('actions', 'core_plugin'),
            '',
            get_string('notes','core_plugin'),
        );
        $table->headspan = array(1, 1, 1, 1, 1, 1);
        $table->colclasses = array(
            'pluginname', 'version', 'availability', 'settings', 'uninstall', 'notes'
        );

        foreach ($plugininfo as $type => $plugins) {
            $heading = $pluginman->plugintype_name_plural($type);
            $pluginclass = core_plugin_manager::resolve_plugininfo_class($type);
            if ($manageurl = $pluginclass::get_manage_url()) {
                $heading .= $this->output->action_icon($manageurl, new pix_icon('i/settings',
                    get_string('settings', 'core_plugin')));
            }
            $header = new html_table_cell(html_writer::tag('span', $heading, array('id'=>'plugin_type_cell_'.$type)));
            $header->header = true;
            $header->colspan = array_sum($table->headspan);
            $header = new html_table_row(array($header));
            $header->attributes['class'] = 'plugintypeheader type-' . $type;
            $table->data[] = $header;

            if (empty($plugins)) {
                $msg = new html_table_cell(get_string('noneinstalled', 'core_plugin'));
                $msg->colspan = array_sum($table->headspan);
                $row = new html_table_row(array($msg));
                $row->attributes['class'] .= 'msg msg-noneinstalled';
                $table->data[] = $row;
                continue;
            }

            foreach ($plugins as $name => $plugin) {
                $row = new html_table_row();
                $row->attributes['class'] = 'type-' . $plugin->type . ' name-' . $plugin->type . '_' . $plugin->name;

                if ($this->page->theme->resolve_image_location('icon', $plugin->type . '_' . $plugin->name)) {
                    $icon = $this->output->pix_icon('icon', '', $plugin->type . '_' . $plugin->name, array('class' => 'icon pluginicon'));
                } else {
                    $icon = $this->output->pix_icon('spacer', '', 'moodle', array('class' => 'icon pluginicon noicon'));
                }
                $status = $plugin->get_status();
                $row->attributes['class'] .= ' status-'.$status;
                $pluginname  = html_writer::tag('div', $icon.$plugin->displayname, array('class' => 'displayname')).
                               html_writer::tag('div', $plugin->component, array('class' => 'componentname'));
                $pluginname  = new html_table_cell($pluginname);

                $version = html_writer::div($plugin->versiondb, 'versionnumber');
                if ((string)$plugin->release !== '') {
                    $version = html_writer::div($plugin->release, 'release').$version;
                }
                $version = new html_table_cell($version);

                $isenabled = $plugin->is_enabled();
                if (is_null($isenabled)) {
                    $availability = new html_table_cell('');
                } else if ($isenabled) {
                    $row->attributes['class'] .= ' enabled';
                    $availability = new html_table_cell(
                        html_writer::tag('span', get_string('pluginenabled', 'core_plugin'), array('class'=>'badge badge-success'))
                    );
                } else {
                    $row->attributes['class'] .= ' disabled';
                    $availability = new html_table_cell(
                        html_writer::tag('span', get_string('plugindisabled', 'core_plugin'), array('class'=>'badge badge-danger'))
                    );
                }

                $settingsurl = $plugin->get_settings_url();
                if (!is_null($settingsurl)) {
                    $settings = html_writer::link($settingsurl, get_string('settings', 'core_plugin'), array('class' => 'settings btn btn-sm btn-outline-primary'));
                } else {
                    $settings = '';
                }
                $settings = new html_table_cell($settings);

                if ($uninstallurl = $pluginman->get_uninstall_url($plugin->component, 'overview')) {
                    $uninstall = html_writer::link($uninstallurl, get_string('uninstall', 'core_plugin'), array('class'=>'btn btn-sm btn-outline-danger'));
                } else {
                    $uninstall = '';
                }
                $uninstall = new html_table_cell($uninstall);

                if ($plugin->is_standard()) {
                    $row->attributes['class'] .= ' standard';
                    $source = '';
                } else {
                    $row->attributes['class'] .= ' extension';
                    $source = html_writer::div(get_string('sourceext', 'core_plugin'), 'source label label-info');
                }

                if ($status === core_plugin_manager::PLUGIN_STATUS_MISSING) {
                    $msg = html_writer::div(get_string('status_missing', 'core_plugin'), 'statusmsg label label-important');
                } else if ($status === core_plugin_manager::PLUGIN_STATUS_NEW) {
                    $msg = html_writer::div(get_string('status_new', 'core_plugin'), 'statusmsg label label-success');
                } else {
                    $msg = '';
                }

                $requriedby = $pluginman->other_plugins_that_require($plugin->component);
                if ($requriedby) {
                    $requiredby = html_writer::tag('div', get_string('requiredby', 'core_plugin', implode(', ', $requriedby)),
                        array('class' => 'requiredby text-'));
                } else {
                    $requiredby = '';
                }

                $updateinfo = '';
                if (is_array($plugin->available_updates())) {
                    foreach ($plugin->available_updates() as $availableupdate) {
                        $updateinfo .= $this->plugin_available_update_info($pluginman, $availableupdate);
                    }
                }

                $notes = new html_table_cell($source.$msg.$requiredby.$updateinfo);

                $row->cells = array(
                    $pluginname, $version, $availability, $settings, $uninstall, $notes
                );
                $table->data[] = $row;
            }
        }

        $html = html_writer::table($table);

        $replacements = array(
            'generaltable' => 'table table-striped',
            'status-missing' => 'danger',
            'status-downgrade' => 'danger',
            'status-upgrade' => 'info',
            'status-delete' => 'info',
            'status-new' => 'success',
            'label label-important' => 'badge badge-danger',
            'label label-success' => 'badge badge-success',
            'label label-warning' => 'badge badge-warning',
            'label label-info' => 'badge badge-info'
        );

        $find = array_keys($replacements);
        $replace = array_values($replacements);

        return str_replace($find, $replace, $html);
    }

    public function environment_check_table($result, $environment) {
        $html = parent::environment_check_table($result, $environment);

        $replacements = array(
            '<span class="ok">' => '<span class="label label-success">',
            '<span class="warn">' => '<span class="label label-warning">',
            '<span class="error">' => '<span class="label label-danger">',
            '<p class="ok">' => '<p class="text-success">',
            '<p class="warn">' => '<p class="text-warning">',
            '<p class="error">' => '<p class="text-danger">',
        );

        $find = array_keys($replacements);
        $replace = array_values($replacements);

        return str_replace($find, $replace, $html);
    }
}
