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
 * Atto text editor integration version file.
 *
 * @package    atto_qmultitle
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function atto_qmultitle_strings_for_js() {
    global $PAGE;

    $PAGE->requires->strings_for_js(array('removeheader',
                                          'header1',
                                          'header2',
                                          'header2_lowercase',
                                          'header2_darkblue',
                                          'header2_lightblue',
                                          'header3',
                                          'header3_lowercase',
                                          'header3_darkblue',
                                          'header3_lightblue',
                                          'header3_primary',
                                          'header4',
                                          'header5',
                                          'header5_lightbrown',
                                          'header6',
                                          'preview',
                                          'saveheaders',
                                          'update'), 'atto_qmultitle');
}

/**
 * Set params for this plugin.
 *
 */
function atto_qmultitle_params_for_js() {

    // Header styles params.
    $styles = array('styles'=>
        array(
            'removeheader' => array(
                'stylename' => 'removeheader',
                'tagname' => '',
                'class' => '',
            ),
            'header1' => array(
                'stylename' => 'header1',
                'tagname' => 'h1',
                'class' => get_config('atto_qmultitle', 'header1'),
            ),

            'header2' => array(
                'stylename' => 'header2',
                'tagname' => 'h2',
                'class' => get_config('atto_qmultitle', 'header2'),
            ),

            'header2_lowercase' => array(
                'stylename' => 'header2_lowercase',
                'tagname' => 'h2',
                'class' => get_config('atto_qmultitle', 'header2_lowercase'),
            ),

            'header2_darkblue' => array(
                'stylename' => 'header2_darkblue',
                'tagname' => 'h2',
                'class' => get_config('atto_qmultitle', 'header2_darkblue'),
            ),

            'header2_lightblue' => array(
                'stylename' => 'header2_lightblue',
                'tagname' => 'h2',
                'class' => get_config('atto_qmultitle', 'header2_lightblue'),
            ),

            'header3' => array(
                'stylename' => 'header3',
                'tagname' => 'h3',
                'class' => get_config('atto_qmultitle', 'header3'),
            ),

            'header3_lowercase' => array(
                'stylename' => 'header3_lowercase',
                'tagname' => 'h3',
                'class' => get_config('atto_qmultitle', 'header3_lowercase'),
            ),
            'header3_primary' => array(
                'stylename' => 'header3_primary',
                'tagname' => 'h3',
                'class' => get_config('atto_qmultitle', 'header3_primary'),
            ),

            'header3_darkblue' => array(
                'stylename' => 'header3_darkblue',
                'tagname' => 'h3',
                'class' => get_config('atto_qmultitle', 'header3_darkblue'),
            ),

            'header3_lightblue' => array(
                'stylename' => 'header3_lightblue',
                'tagname' => 'h3',
                'class' => get_config('atto_qmultitle', 'header3_lightblue'),
            ),

            'header4' => array(
                'stylename' => 'header4',
                'tagname' => 'h4',
                'class' => get_config('atto_qmultitle', 'header4'),
            ),

            'header5' => array(
                'stylename' => 'header5',
                'tagname' => 'h5',
                'class' => get_config('atto_qmultitle', 'header5'),
            ),


            'header5_lightbrown' => array(
                'stylename' => 'header5_lightbrown',
                'tagname' => 'h5',
                'class' => get_config('atto_qmultitle', 'header5_lightbrown'),
            ),

            'header6' => array(
                'stylename' => 'header6',
                'tagname' => 'h6',
                'class' => get_config('atto_qmultitle', 'header6'),
            )
        )
    );

    return $styles;
}
