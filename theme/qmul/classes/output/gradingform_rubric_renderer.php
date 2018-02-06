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
 * Contains renderer used for displaying rubric
 *
 * @package    gradingform_rubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Grading method plugin renderer
 *
 * @package    gradingform_rubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . "/grade/grading/form/rubric/renderer.php");
class theme_qmul_gradingform_rubric_renderer extends gradingform_rubric_renderer {

    /**
     * This function returns html code for displaying rubric template (content before and after
     * criteria list). Depending on $mode it may be the code to edit rubric, to preview the rubric,
     * to evaluate somebody or to review the evaluation.
     *
     * This function is called from display_rubric() to display the whole rubric.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode see {@link gradingform_rubric_controller}
     * @param array $options display options for this rubric, defaults are: {@link gradingform_rubric_controller::get_default_options()}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string $criteriastr evaluated templates for this rubric's criteria
     * @return string
     */
    protected function rubric_template($mode, $options, $elementname, $criteriastr) {
        $classsuffix = ''; // CSS suffix for class of the main div. Depends on the mode
        switch ($mode) {
            case gradingform_rubric_controller::DISPLAY_EDIT_FULL:
                $classsuffix = ' editor editable'; break;
            case gradingform_rubric_controller::DISPLAY_EDIT_FROZEN:
                $classsuffix = ' editor frozen';  break;
            case gradingform_rubric_controller::DISPLAY_PREVIEW:
            case gradingform_rubric_controller::DISPLAY_PREVIEW_GRADED:
                $classsuffix = ' editor preview';  break;
            case gradingform_rubric_controller::DISPLAY_EVAL:
                $classsuffix = ' evaluate editable'; break;
            case gradingform_rubric_controller::DISPLAY_EVAL_FROZEN:
                $classsuffix = ' evaluate frozen';  break;
            case gradingform_rubric_controller::DISPLAY_REVIEW:
                $classsuffix = ' review';  break;
            case gradingform_rubric_controller::DISPLAY_VIEW:
                $classsuffix = ' view';  break;
        }

        $rubrictemplate = html_writer::start_tag('div', array('id' => 'rubric-{NAME}', 'class' => 'clearfix gradingform_rubric'.$classsuffix));

        // Rubric table.
        $rubrictableparams = array(
            'class' => 'criteria',
            'id' => '{NAME}-criteria',
            'aria-label' => get_string('rubric', 'gradingform_rubric'));
        $rubrictable = html_writer::tag('table', $criteriastr, $rubrictableparams);
        $rubrictemplate .= html_writer::start_tag('div', array('style'=>'overflow: auto'));
        $rubrictemplate .= $rubrictable;
        $rubrictemplate .= html_writer::end_tag('div');
        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $value = get_string('addcriterion', 'gradingform_rubric');
            $criteriainputparams = array(
                'type' => 'submit',
                'name' => '{NAME}[criteria][addcriterion]',
                'id' => '{NAME}-criteria-addcriterion',
                'value' => $value
            );
            $input = html_writer::empty_tag('input', $criteriainputparams);
            $rubrictemplate .= html_writer::tag('div', $input, array('class' => 'addcriterion'));
        }
        $rubrictemplate .= $this->rubric_edit_options($mode, $options);
        $rubrictemplate .= html_writer::end_tag('div');

        return str_replace('{NAME}', $elementname, $rubrictemplate);
    }

    /**
     * This function returns html code for displaying rubric. Depending on $mode it may be the code
     * to edit rubric, to preview the rubric, to evaluate somebody or to review the evaluation.
     *
     * It is very unlikely that this function needs to be overriden by theme. It does not produce
     * any html code, it just prepares data about rubric design and evaluation, adds the CSS
     * class to elements and calls the functions level_template, criterion_template and
     * rubric_template
     *
     * @param array $criteria data about the rubric design
     * @param array $options display options for this rubric, defaults are: {@link gradingform_rubric_controller::get_default_options()}
     * @param int $mode rubric display mode, see {@link gradingform_rubric_controller}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array $values evaluation result
     * @return string
     */
    public function display_rubric($criteria, $options, $mode, $elementname = null, $values = null) {
        $criteriastr = '';
        $cnt = 0;
        foreach ($criteria as $id => $criterion) {
            $criterion['class'] = $this->get_css_class_suffix($cnt++, sizeof($criteria) -1);
            $criterion['id'] = $id;
            $levelsstr = '';
            $levelcnt = 0;
            if (isset($values['criteria'][$id])) {
                $criterionvalue = $values['criteria'][$id];
            } else {
                $criterionvalue = null;
            }
            $index = 1;
            foreach ($criterion['levels'] as $levelid => $level) {
                $level['id'] = $levelid;
                $level['class'] = $this->get_css_class_suffix($levelcnt++, sizeof($criterion['levels']) -1);
                $level['checked'] = (isset($criterionvalue['levelid']) && ((int)$criterionvalue['levelid'] === $levelid));
                if ($level['checked'] && ($mode == gradingform_rubric_controller::DISPLAY_EVAL_FROZEN || $mode == gradingform_rubric_controller::DISPLAY_REVIEW || $mode == gradingform_rubric_controller::DISPLAY_VIEW)) {
                    $level['class'] .= ' checked';
                    //in mode DISPLAY_EVAL the class 'checked' will be added by JS if it is enabled. If JS is not enabled, the 'checked' class will only confuse
                }
                if (isset($criterionvalue['savedlevelid']) && ((int)$criterionvalue['savedlevelid'] === $levelid)) {
                    $level['class'] .= ' currentchecked';
                }
                $level['tdwidth'] = 100/count($criterion['levels']);
                $level['index'] = $index;
                $levelsstr .= $this->level_template($mode, $options, $elementname, $id, $level);
                $index++;
            }
            $criteriastr .= $this->criterion_template($mode, $options, $elementname, $criterion, $levelsstr, $criterionvalue);
        }
        return $this->rubric_template($mode, $options, $elementname, $criteriastr);
    }

}
