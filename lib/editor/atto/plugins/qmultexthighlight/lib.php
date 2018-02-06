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
 * @package    atto_qmultexthighlight
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function atto_qmultexthighlight_strings_for_js() {
    global $PAGE;

    $PAGE->requires->strings_for_js(array('removehighlight',
                                          'highlight1',
                                          'highlight2',
                                          'highlight3',
                                          'highlight4',
                                          'highlight1_background',
                                          'highlight2_background',
                                          'highlight3_background',
                                          'highlight4_background',
                                          'preview',
                                          'saveheaders',
                                          'update'), 'atto_qmultexthighlight');
}

/**
 * Set params for this plugin.
 *
 */
function atto_qmultexthighlight_params_for_js() {

    // Header styles params.
    $styles = array('styles'=>
        array(
            'removehighlight' => array(
                'stylename' => 'removehighlight',
                'tagname' => '',
                'attr' => '',
                'attrValue' => '',
            ),

            'highlight1' => array(
                'stylename' => 'highlight1_background',
                'tagname' => 'div',
                'attr' => 'id',
                'attrValue' => get_config('atto_qmultexthighlight', 'highlight1'),
            ),

            'highlight2' => array(
                'stylename' => 'highlight2_background',
                'tagname' => 'div',
                'attr' => 'id',
                'attrValue' => get_config('atto_qmultexthighlight', 'highlight2'),
            ),

            'highlight3' => array(
                'stylename' => 'highlight3_background',
                'tagname' => 'div',
                'attr' => 'id',
                'attrValue' => get_config('atto_qmultexthighlight', 'highlight3'),
            ),

            'highlight4' => array(
                'stylename' => 'highlight4_background',
                'tagname' => 'div',
                'attr' => 'id',
                'attrValue' => get_config('atto_qmultexthighlight', 'highlight4'),
            )

        )
    );

    return $styles;
}
