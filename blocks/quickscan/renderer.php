<?php
// This file is part of Moodle - http://moodle.org/
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
 * Block quickscan renderer
 *
 * @package    block
 * @subpackage quickscan
 * @copyright  2012, Lancaster University, Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_quickscan_renderer extends plugin_renderer_base {

    /**
     * Display block content
     * @param int courseid
     * @return string html
     */
    public function get_block_content($courseid) {
        $url = new moodle_url('/blocks/quickscan/launchtest.php', array('courseid' => $courseid));
        $button = new single_button($url,
            get_string('quickscantest', 'block_quickscan'));
        $html = html_writer::tag('div', $this->output->render($button));
        return $html;
    }

    /**
     * Display block footer
     * @return string html
     */
    public function get_block_footer() {
        $config = get_config('blocks/quickscan');
        return $config->footer;
    }

    /**
     * Display explanation content and start button
     * @return string html
     */
    public function get_test_description() {
        global $USER;
        $config = get_config('blocks/quickscan');

        $html = html_writer::start_tag('div', array('id' => 'page-blocks-quickscan-launchtest'));
        $html .= $config->explanation;
        if ($config->url) {
            $url = new moodle_url($config->url, array('txtpi' => $USER->username));
            $button = new single_button($url, get_string('starttest', 'block_quickscan'));
            $action = new popup_action('click', $url, 'popup', array('width' => 0, 'height' => 0,
                    'toolbar' => false, 'fullscreen' => true));
            $button->add_action($action);
            $html .= html_writer::tag('div', $this->output->render($button));
        }
        $html .= html_writer::end_tag('div');

        return $html;
    }
}