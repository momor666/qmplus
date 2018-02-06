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
 * @package    atto_qmulicons
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function atto_qmulicons_strings_for_js() {
    global $PAGE;

    $PAGE->requires->strings_for_js(array('icon1','icon2','icon3','icon4','icon5','icon6','icon7','icon8','icon9',
        'icon10','icon11','icon12','icon13','icon14','icon15','icon16',
        'icon1_name','icon2_name','icon3_name','icon4_name','icon5_name','icon6_name','icon7_name','icon8_name',
        'icon9_name','icon10_name','icon11_name','icon12_name','icon13_name','icon14_name','icon15_name','icon16_name',
        'icon1_text','icon2_text','icon3_text','icon4_text','icon5_text','icon6_text','icon7_text','icon8_text',
        'icon9_text','icon10_text','icon11_text','icon12_text','icon13_text','icon14_text','icon15_text','icon16_text',
                                          'inserticon',
                                          'update'), 'atto_qmulicons');
}

/**
 * Set params for this plugin.
 *
 */
function atto_qmulicons_params_for_js() {

    // Header styles params.
    $styles = array('styles'=>
        array(
            'icon1' => array(
                'stylename' => 'icon1_name',
                'text' => 'icon1_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon1'),
            ),
            'icon2' => array(
                'stylename' => 'icon2_name',
                'text' => 'icon2_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon2'),
            ),
            'icon3' => array(
                'stylename' => 'icon3_name',
                'text' => 'icon3_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon3'),
            ),
            'icon4' => array(
                'stylename' => 'icon4_name',
                'text' => 'icon4_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon4'),
            ),'icon5' => array(
                'stylename' => 'icon5_name',
                'text' => 'icon5_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon5'),
            ),
            'icon6' => array(
                'stylename' => 'icon6_name',
                'text' => 'icon6_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon6'),
            ),
            'icon7' => array(
                'stylename' => 'icon7_name',
                'text' => 'icon7_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon7'),
            ),
            'icon8' => array(
                'stylename' => 'icon8_name',
                'text' => 'icon8_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon8'),
            ),
            'icon9' => array(
                'stylename' => 'icon9_name',
                'text' => 'icon9_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon9'),
            ),
            'icon10' => array(
                'stylename' => 'icon10_name',
                'text' => 'icon10_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon10'),
            ),
            'icon11' => array(
                'stylename' => 'icon11_name',
                'text' => 'icon11_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon11'),
            ),
            'icon12' => array(
                'stylename' => 'icon12_name',
                'text' => 'icon12_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon12'),
            ),
            'icon13' => array(
                'stylename' => 'icon13_name',
                'text' => 'icon13_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon13'),
            ),
            'icon14' => array(
                'stylename' => 'icon14_name',
                'text' => 'icon14_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon14'),
            ),
            'icon15' => array(
                'stylename' => 'icon15_name',
                'text' => 'icon15_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon15'),
            ),
            'icon16' => array(
                'stylename' => 'icon16_name',
                'text' => 'icon16_text',
                'tagname' => 'li',
                'attr' => 'class',
                'attrValue' => get_config('atto_qmulicons', 'icon16'),
            )
        )
    );

    return $styles;
}
