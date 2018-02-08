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
 * Grid Format - A topics based format that uses a grid of user selectable images to popup a light box of the section.
 *
 * @package    course/format
 * @subpackage grid
 * @copyright  &copy; 2012+ G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com, about.me/gjbarnard and {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/lib.php'); // For format_base.
require_once($CFG->dirroot . '/course/format/grid/lib.php'); // For format_base.
require_once($CFG->dirroot . '/course/format/qmulgrid/lib.php'); // For format_base.

class format_qmulgrid extends format_grid {

    private static $imagecontainerwidths = array(128 => '128', 192 => '192', 210 => '210', 256 => '256', 320 => '320',
        384 => '384', 448 => '448', 512 => '512', 576 => '576', 640 => '640', 704 => '704', 768 => '768');
    // Ratio constants - 3-2, 3-1, 3-3, 2-3, 1-3, 4-3 and 3-4:...
    private static $imagecontainerratios = array(
        1 => '3-2', 2 => '3-1', 3 => '3-3', 4 => '2-3', 5 => '1-3', 6 => '4-3', 7 => '3-4');
    // Border width constants - 1 to 10:....
    private static $borderwidths = array(0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8',
        9 => '9', 10 => '10');


    /**
     * Adds format options elements to the course/section edit form
     *
     * This function is called from {@link course_edit_form::definition_after_data()}
     *
     * @param MoodleQuickForm $mform form the elements are added to
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form
     * @return array array of references to the added form elements
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $CFG, $OUTPUT;
        $elements = parent::create_edit_form_elements($mform, $forsection);
        if ($forsection == false) {

            // Assessment Information
            $elements[] = $mform->addElement('header', 'assessmentinformation', get_string('assessmentinformation', 'format_qmultc'));
            $mform->addHelpButton('assessmentinformation', 'assessmentinformation', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_assessmentinformation', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_assessmentinformation', get_string('assessmentinformation', 'format_qmultc'));

            // Extra Tab 1
            $elements[] = $mform->addElement('header', 'extratab1', get_string('extratab', 'format_qmultc', 1));
            $mform->addHelpButton('extratab1', 'extratab', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_extratab1', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('text', 'title_extratab1', get_string('tabtitle', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_extratab1', get_string('tabcontent', 'format_qmultc'));

            // Extra Tab 2
            $elements[] = $mform->addElement('header', 'extratab2', get_string('extratab', 'format_qmultc', 2));
            $mform->addHelpButton('extratab2', 'extratab', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_extratab2', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('text', 'title_extratab2', get_string('tabtitle', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_extratab2', get_string('tabcontent', 'format_qmultc'));

            // Extra Tab 3
            $elements[] = $mform->addElement('header', 'extratab3', get_string('extratab', 'format_qmultc', 3));
            $mform->addHelpButton('extratab3', 'extratab', 'format_qmultc', '', true);

            $elements[] = $mform->addElement('checkbox', 'enable_extratab3', get_string('enabletab', 'format_qmultc'));

            $elements[] = $mform->addElement('text', 'title_extratab3', get_string('tabtitle', 'format_qmultc'));

            $elements[] = $mform->addElement('htmleditor', 'content_extratab3', get_string('tabcontent', 'format_qmultc'));

        }

        return $elements;
    }

    public function edit_form_validation($data, $files, $errors) {

        $return = parent::edit_form_validation($data, $files, $errors);

        if (isset($data['enable_extratab1'])) {
            if (empty($data['title_extratab1'])) {
                $return['title_extratab1'] = get_string('titlerequiredwhenenabled', 'format_qmultc');
            }
        } else {
            $data['enabled_extratab1'] = 0;
        }
        if (isset($data['enable_extratab2'])) {
            if (empty($data['title_extratab2'])) {
                $return['title_extratab2'] = get_string('titlerequiredwhenenabled', 'format_qmultc');
            }
        } else {
            $data['enabled_extratab1'] = 0;
        }
        if (isset($data['enable_extratab3'])) {
            if (empty($data['title_extratab3'])) {
                $return['title_extratab3'] = get_string('titlerequiredwhenenabled', 'format_qmultc');
            }
        } else {
            $data['enabled_extratab1'] = 0;
        }

        return $return;
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'Collapsed Topics', we try to copy options
     * 'coursedisplay', 'numsections' and 'hiddensections' from the previous format.
     * If previous course format did not have 'numsections' option, we populate it with the
     * current number of sections.  The layout and colour defaults will come from 'course_format_options'.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;

        $newdata = (array) $data;
        $savedata = array();
        if (isset($newdata['fullname'])) {
            if (isset($newdata['enable_assessmentinformation'])) {
                $savedata['enable_assessmentinformation'] = $newdata['enable_assessmentinformation'];
            } else {
                $savedata['enable_assessmentinformation'] = 0;
            }
            if (isset($newdata['content_assessmentinformation'])) {
                $savedata['content_assessmentinformation'] = $newdata['content_assessmentinformation'];
            }
            if (isset($newdata['enable_extratab1'])) {
                $savedata['enable_extratab1'] = $newdata['enable_extratab1'];
            } else {
                $savedata['enable_extratab1'] = 0;
            }
            if (isset($newdata['title_extratab1'])) {
                $savedata['title_extratab1'] = $newdata['title_extratab1'];
            }
            if (isset($newdata['content_extratab1'])) {
                $savedata['content_extratab1'] = $newdata['content_extratab1'];
            }
            if (isset($newdata['enable_extratab2'])) {
                $savedata['enable_extratab2'] = $newdata['enable_extratab2'];
            } else {
                $savedata['enable_extratab2'] = 0;
            }
            if (isset($newdata['title_extratab2'])) {
                $savedata['title_extratab2'] = $newdata['title_extratab2'];
            }
            if (isset($newdata['content_extratab2'])) {
                $savedata['content_extratab2'] = $newdata['content_extratab2'];
            }
            if (isset($newdata['enable_extratab3'])) {
                $savedata['enable_extratab3'] = $newdata['enable_extratab3'];
            } else {
                $savedata['enable_extratab3'] = 0;
            }
            if (isset($newdata['title_extratab3'])) {
                $savedata['title_extratab3'] = $newdata['title_extratab3'];
            }
            if (isset($newdata['content_extratab3'])) {
                $savedata['content_extratab3'] = $newdata['content_extratab3'];
            }
        }

        $records = $DB->get_records('course_format_options',
                array('courseid' => $this->courseid,
                      'format' => $this->format,
                      'sectionid' => 0
                    ), '', 'name,id,value');

        foreach ($savedata as $key => $value) {
             if (isset($records[$key])) {
                if (array_key_exists($key, $newdata) && $records[$key]->value !== $newdata[$key]) {
                    $DB->set_field('course_format_options', 'value',
                            $value, array('id' => $records[$key]->id));
                    $changed = true;
                } else {
                    $DB->set_field('course_format_options', 'value',
                            $value, array('id' => $records[$key]->id));
                    $changed = true;
                }
            } else {
                $DB->insert_record('course_format_options', (object)array(
                    'courseid' => $this->courseid,
                    'format' => $this->format,
                    'sectionid' => 0,
                    'name' => $key,
                    'value' => $value
                ));
            }
        }

        $changes = parent::update_course_format_options($data, $oldcourse);

        return $changes;
    }

    /**
     * Definitions of the additional options that this course format uses for the course.
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            /* Note: Because 'admin_setting_configcolourpicker' in 'settings.php' needs to use a prefixing '#'
              this needs to be stripped off here if it's there for the format's specific colour picker. */
            $defaults = $this->get_course_format_colour_defaults();

            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = array(
                'numsections' => array(
                    'default' => $courseconfig->numsections,
                    'type' => PARAM_INT
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT
                ),
                'coursedisplay' => array(
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT
                ),
                'imagecontaineralignment' => array(
                    'default' => get_config('format_grid', 'defaultimagecontaineralignment'),
                    'type' => PARAM_ALPHANUM
                ),
                'imagecontainerwidth' => array(
                    'default' => get_config('format_grid', 'defaultimagecontainerwidth'),
                    'type' => PARAM_INT
                ),
                'imagecontainerratio' => array(
                    'default' => get_config('format_grid', 'defaultimagecontainerratio'),
                    'type' => PARAM_ALPHANUM
                ),
                'imageresizemethod' => array(
                    'default' => get_config('format_grid', 'defaultimageresizemethod'),
                    'type' => PARAM_INT
                ),
                'bordercolour' => array(
                    'default' => $defaults['defaultbordercolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'borderwidth' => array(
                    'default' => get_config('format_grid', 'defaultborderwidth'),
                    'type' => PARAM_INT
                ),
                'borderradius' => array(
                    'default' => get_config('format_grid', 'defaultborderradius'),
                    'type' => PARAM_INT
                ),
                'imagecontainerbackgroundcolour' => array(
                    'default' => $defaults['defaultimagecontainerbackgroundcolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'currentselectedsectioncolour' => array(
                    'default' => $defaults['defaultcurrentselectedsectioncolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'currentselectedimagecontainertextcolour' => array(
                    'default' => $defaults['defaultcurrentselectedimagecontainertextcolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'currentselectedimagecontainercolour' => array(
                    'default' => $defaults['defaultcurrentselectedimagecontainercolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'hidesectiontitle' => array(
                    'default' => get_config('format_grid', 'defaulthidesectiontitle'),
                    'type' => PARAM_INT
                ),
                'sectiontitlegridlengthmaxoption' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitlegridlengthmaxoption'),
                    'type' => PARAM_INT
                ),
                'sectiontitleboxposition' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitleboxposition'),
                    'type' => PARAM_INT
                ),
                'sectiontitleboxinsideposition' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitleboxinsideposition'),
                    'type' => PARAM_INT
                ),
                'sectiontitleboxheight' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitleboxheight'),
                    'type' => PARAM_INT
                ),
                'sectiontitleboxopacity' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitleboxopacity'),
                    'type' => PARAM_RAW
                ),
                'sectiontitlefontsize' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitlefontsize'),
                    'type' => PARAM_INT
                ),
                'sectiontitlealignment' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitlealignment'),
                    'type' => PARAM_ALPHANUM
                ),
                'sectiontitleinsidetitletextcolour' => array(
                    'default' => $defaults['defaultsectiontitleinsidetitletextcolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'sectiontitleinsidetitlebackgroundcolour' => array(
                    'default' => $defaults['defaultsectiontitleinsidetitlebackgroundcolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'showsectiontitlesummary' => array(
                    'default' => get_config('format_grid', 'defaultshowsectiontitlesummary'),
                    'type' => PARAM_INT
                ),
                'setshowsectiontitlesummaryposition' => array(
                    'default' => get_config('format_grid', 'defaultsetshowsectiontitlesummaryposition'),
                    'type' => PARAM_INT
                ),
                'sectiontitlesummarymaxlength' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitlesummarymaxlength'),
                    'type' => PARAM_INT
                ),
                'sectiontitlesummarytextcolour' => array(
                    'default' => $defaults['defaultsectiontitlesummarytextcolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'sectiontitlesummarybackgroundcolour' => array(
                    'default' => $defaults['defaultsectiontitlesummarybackgroundcolour'],
                    'type' => PARAM_ALPHANUM
                ),
                'sectiontitlesummarybackgroundopacity' => array(
                    'default' => get_config('format_grid', 'defaultsectiontitlesummarybackgroundopacity'),
                    'type' => PARAM_RAW
                ),
                'newactivity' => array(
                    'default' => get_config('format_grid', 'defaultnewactivity'),
                    'type' => PARAM_INT
                ),
                'fitsectioncontainertowindow' => array(
                    'default' => get_config('format_grid', 'defaultfitsectioncontainertowindow'),
                    'type' => PARAM_INT
                ),
                'greyouthidden' => array(
                    'default' => get_config('format_grid', 'defaultgreyouthidden'),
                    'type' => PARAM_INT
                )
            );
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            /* Note: Because 'admin_setting_configcolourpicker' in 'settings.php' needs to use a prefixing '#'
              this needs to be stripped off here if it's there for the format's specific colour picker. */
            $defaults = $this->get_course_format_colour_defaults();

            $context = $this->get_context();

            $courseconfig = get_config('moodlecourse');
            $sectionmenu = array();
            for ($i = 0; $i <= $courseconfig->maxsections; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections' => array(
                    'label' => new lang_string('numbersections', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'coursedisplay' => array(
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi')
                        )
                    ),
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                )
            );
            if (has_capability('format/grid:changeimagecontaineralignment', $context)) {
                $courseformatoptionsedit['imagecontaineralignment'] = array(
                    'label' => new lang_string('setimagecontaineralignment', 'format_grid'),
                    'help' => 'setimagecontaineralignment',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(self::get_horizontal_alignments())
                );
            } else {
                $courseformatoptionsedit['imagecontaineralignment'] = array('label' => get_config(
                            'format_grid', 'defaultimagecontaineralignment'), 'element_type' => 'hidden');
            }
            if (has_capability('format/grid:changeimagecontainersize', $context)) {
                $courseformatoptionsedit['imagecontainerwidth'] = array(
                    'label' => new lang_string('setimagecontainerwidth', 'format_grid'),
                    'help' => 'setimagecontainerwidth',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(self::$imagecontainerwidths)
                );
                $courseformatoptionsedit['imagecontainerratio'] = array(
                    'label' => new lang_string('setimagecontainerratio', 'format_grid'),
                    'help' => 'setimagecontainerratio',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(self::$imagecontainerratios)
                );
            } else {
                $courseformatoptionsedit['imagecontainerwidth'] = array('label' => get_config(
                            'format_grid', 'defaultimagecontainerwidth'), 'element_type' => 'hidden');
                $courseformatoptionsedit['imagecontainerratio'] = array('label' => get_config(
                            'format_grid', 'defaultimagecontainerratio'), 'element_type' => 'hidden');
            }

            if (has_capability('format/grid:changeimageresizemethod', $context)) {
                $courseformatoptionsedit['imageresizemethod'] = array(
                    'label' => new lang_string('setimageresizemethod', 'format_grid'),
                    'help' => 'setimageresizemethod',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => new lang_string('scale', 'format_grid'), // Scale.
                            2 => new lang_string('crop', 'format_grid')   // Crop.
                        )
                    )
                );
            } else {
                $courseformatoptionsedit['imageresizemethod'] = array('label' => get_config(
                            'format_grid', 'defaultimageresizemethod'), 'element_type' => 'hidden');
            }

            if (has_capability('format/grid:changeimagecontainerstyle', $context)) {
                $courseformatoptionsedit['bordercolour'] = array(
                    'label' => new lang_string('setbordercolour', 'format_grid'),
                    'help' => 'setbordercolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('tabindex' => -1, 'value' => get_config('format_grid', 'defaultbordercolour'))
                    )
                );

                $courseformatoptionsedit['borderwidth'] = array(
                    'label' => new lang_string('setborderwidth', 'format_grid'),
                    'help' => 'setborderwidth',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(self::$borderwidths)
                );

                $courseformatoptionsedit['borderradius'] = array(
                    'label' => new lang_string('setborderradius', 'format_grid'),
                    'help' => 'setborderradius',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => new lang_string('off', 'format_grid'),
                            2 => new lang_string('on', 'format_grid'))
                    )
                );

                $courseformatoptionsedit['imagecontainerbackgroundcolour'] = array(
                    'label' => new lang_string('setimagecontainerbackgroundcolour', 'format_grid'),
                    'help' => 'setimagecontainerbackgroundcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('tabindex' => -1, 'value' => get_config('format_grid', 'defaultimagecontainerbackgroundcolour'))
                    )
                );

                $courseformatoptionsedit['currentselectedsectioncolour'] = array(
                    'label' => new lang_string('setcurrentselectedsectioncolour', 'format_grid'),
                    'help' => 'setcurrentselectedsectioncolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('tabindex' => -1, 'value' => get_config('format_grid', 'defaultcurrentselectedsectioncolour'))
                    )
                );

                $courseformatoptionsedit['currentselectedimagecontainertextcolour'] = array(
                    'label' => new lang_string('setcurrentselectedimagecontainertextcolour', 'format_grid'),
                    'help' => 'setcurrentselectedimagecontainertextcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('tabindex' => -1, 'value' => get_config('format_grid', 'defaultcurrentselectedimagecontainertextcolour'))
                    )
                );

                $courseformatoptionsedit['currentselectedimagecontainercolour'] = array(
                    'label' => new lang_string('setcurrentselectedimagecontainercolour', 'format_grid'),
                    'help' => 'setcurrentselectedimagecontainercolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('tabindex' => -1, 'value' => get_config('format_grid', 'defaultcurrentselectedimagecontainercolour'))
                    )
                );
            } else {
                $courseformatoptionsedit['bordercolour'] = array('label' => $defaults['defaultbordercolour'],
                    'element_type' => 'hidden');
                $courseformatoptionsedit['borderwidth'] = array('label' => get_config('format_grid', 'defaultborderwidth'),
                    'element_type' => 'hidden');
                $courseformatoptionsedit['borderradius'] = array('label' => get_config('format_grid', 'defaultborderradius'),
                    'element_type' => 'hidden');
                $courseformatoptionsedit['imagecontainerbackgroundcolour'] = array(
                    'label' => $defaults['defaultimagecontainerbackgroundcolour'], 'element_type' => 'hidden');
                $courseformatoptionsedit['currentselectedsectioncolour'] = array(
                    'label' => $defaults['defaultcurrentselectedsectioncolour'], 'element_type' => 'hidden');
                $courseformatoptionsedit['currentselectedimagecontainertextcolour'] = array(
                    'label' => $defaults['defaultcurrentselectedimagecontainertextcolour'], 'element_type' => 'hidden');
                $courseformatoptionsedit['currentselectedimagecontainercolour'] = array(
                    'label' => $defaults['defaultcurrentselectedimagecontainercolour'], 'element_type' => 'hidden');
            }

            if (has_capability('format/grid:changesectiontitleoptions', $context)) {
                $courseformatoptionsedit['hidesectiontitle'] = array(
                    'label' => new lang_string('hidesectiontitle', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => new lang_string('no'), // No.
                            2 => new lang_string('yes') // Yes.
                        )
                    ),
                    'help' => 'hidesectiontitle',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitlegridlengthmaxoption'] = array(
                    'label' => new lang_string('sectiontitlegridlengthmaxoption', 'format_grid'),
                    'element_type' => 'text',
                    'element_attributes' => array('size' => 3),
                    'help' => 'sectiontitlegridlengthmaxoption',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitleboxposition'] = array(
                    'label' => new lang_string('sectiontitleboxposition', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => new lang_string('sectiontitleboxpositioninside', 'format_grid'),
                            2 => new lang_string('sectiontitleboxpositionoutside', 'format_grid')
                        )
                    ),
                    'help' => 'sectiontitleboxposition',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitleboxinsideposition'] = array(
                    'label' => new lang_string('sectiontitleboxinsideposition', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => new lang_string('sectiontitleboxinsidepositiontop', 'format_grid'),
                            2 => new lang_string('sectiontitleboxinsidepositionmiddle', 'format_grid'),
                            3 => new lang_string('sectiontitleboxinsidepositionbottom', 'format_grid')
                        )
                    ),
                    'help' => 'sectiontitleboxposition',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitleboxheight'] = array(
                    'label' => new lang_string('sectiontitleboxheight', 'format_grid'),
                    'element_type' => 'text',
                    'element_attributes' => array('size' => 3),
                    'help' => 'sectiontitleboxheight',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitleboxopacity'] = array(
                    'label' => new lang_string('sectiontitleboxopacity', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(self::get_default_opacities()),
                    'help' => 'sectiontitleboxopacity',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitlefontsize'] = array(
                    'label' => new lang_string('sectiontitlefontsize', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(self::get_default_section_font_sizes()),
                    'help' => 'sectiontitlefontsize',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitlealignment'] = array(
                    'label' => new lang_string('sectiontitlealignment', 'format_grid'),
                    'help' => 'sectiontitlealignment',
                    'help_component' => 'format_grid',
                    'element_type' => 'select',
                    'element_attributes' => array(self::get_horizontal_alignments())
                );
                $courseformatoptionsedit['sectiontitleinsidetitletextcolour'] = array(
                    'label' => new lang_string('sectiontitleinsidetitletextcolour', 'format_grid'),
                    'help' => 'sectiontitleinsidetitletextcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaults['defaultsectiontitleinsidetitletextcolour'])
                    )
                );
                $courseformatoptionsedit['sectiontitleinsidetitlebackgroundcolour'] = array(
                    'label' => new lang_string('sectiontitleinsidetitlebackgroundcolour', 'format_grid'),
                    'help' => 'sectiontitleinsidetitlebackgroundcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaults['defaultsectiontitleinsidetitlebackgroundcolour'])
                    )
                );
                $courseformatoptionsedit['showsectiontitlesummary'] = array(
                    'label' => new lang_string('showsectiontitlesummary', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => new lang_string('no'), // No.
                            2 => new lang_string('yes') // Yes.
                        )
                    ),
                    'help' => 'showsectiontitlesummary',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['setshowsectiontitlesummaryposition'] = array(
                    'label' => new lang_string('setshowsectiontitlesummaryposition', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            1 => new lang_string('top', 'format_grid'),
                            2 => new lang_string('bottom', 'format_grid'),
                            3 => new lang_string('left', 'format_grid'),
                            4 => new lang_string('right', 'format_grid')
                        )
                    ),
                    'help' => 'setshowsectiontitlesummaryposition',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitlesummarymaxlength'] = array(
                    'label' => new lang_string('sectiontitlesummarymaxlength', 'format_grid'),
                    'element_type' => 'text',
                    'element_attributes' => array('size' => 3),
                    'help' => 'sectiontitlesummarymaxlength',
                    'help_component' => 'format_grid'
                );
                $courseformatoptionsedit['sectiontitlesummarytextcolour'] = array(
                    'label' => new lang_string('sectiontitlesummarytextcolour', 'format_grid'),
                    'help' => 'sectiontitlesummarytextcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaults['defaultsectiontitlesummarytextcolour'])
                    )
                );
                $courseformatoptionsedit['sectiontitlesummarybackgroundcolour'] = array(
                    'label' => new lang_string('sectiontitlesummarybackgroundcolour', 'format_grid'),
                    'help' => 'sectiontitlesummarybackgroundcolour',
                    'help_component' => 'format_grid',
                    'element_type' => 'gfcolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaults['defaultsectiontitlesummarybackgroundcolour'])
                    )
                );
                $courseformatoptionsedit['sectiontitlesummarybackgroundopacity'] = array(
                    'label' => new lang_string('sectiontitlesummarybackgroundopacity', 'format_grid'),
                    'element_type' => 'select',
                    'element_attributes' => array(self::get_default_opacities()),
                    'help' => 'sectiontitlesummarybackgroundopacity',
                    'help_component' => 'format_grid'
                );
            } else {
                $courseformatoptionsedit['hidesectiontitle'] = array('label' => get_config('format_grid', 'defaulthidesectiontitle'),
                    'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlegridlengthmaxoption'] = array('label' => get_config('format_grid', 'defaultsectiontitlegridlengthmaxoption'),
                    'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxposition'] = array('label' => get_config('format_grid', 'defaultsectiontitleboxposition'),
                    'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxinsideposition'] = array(
                    'label' => get_config('format_grid', 'defaultsectiontitleboxinsideposition'), 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxheight'] = array(
                    'label' => get_config('format_grid', 'defaultsectiontitleboxheight'), 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleboxopacity'] = array(
                    'label' => get_config('format_grid', 'defaultsectiontitleboxopacity'), 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlefontsize'] = array(
                    'label' => get_config('format_grid', 'defaultsectiontitlefontsize'), 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlealignment'] = array(
                    'label' => get_config('format_grid', 'defaultsectiontitlealignment'), 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleinsidetitletextcolour'] = array(
                    'label' => $defaults['defaultsectiontitleinsidetitletextcolour'], 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitleinsidetitlebackgroundcolour'] = array(
                    'label' => $defaults['defaultsectiontitleinsidetitlebackgroundcolour'], 'element_type' => 'hidden');
                $courseformatoptionsedit['showsectiontitlesummary'] = array(
                    'label' => get_config('format_grid', 'defaultshowsectiontitlesummary'), 'element_type' => 'hidden');
                $courseformatoptionsedit['setshowsectiontitlesummaryposition'] = array(
                    'label' => get_config('format_grid', 'defaultsetshowsectiontitlesummaryposition'), 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarymaxlength'] = array(
                    'label' => get_config('format_grid', 'defaultsectiontitlesummarymaxlength'), 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarytextcolour'] = array(
                    'label' => $defaults['defaultsectiontitlesummarytextcolour'], 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarybackgroundcolour'] = array(
                    'label' => $defaults['defaultsectiontitlesummarybackgroundcolour'], 'element_type' => 'hidden');
                $courseformatoptionsedit['sectiontitlesummarybackgroundopacity'] = array(
                    'label' => $defaults['defaultsectiontitlesummarybackgroundopacity'], 'element_type' => 'hidden');
            }

            $courseformatoptionsedit['newactivity'] = array(
                'label' => new lang_string('setnewactivity', 'format_grid'),
                'element_type' => 'select',
                'element_attributes' => array(
                    array(
                        1 => new lang_string('no'), // No.
                        2 => new lang_string('yes') // Yes.
                    )
                ),
                'help' => 'setnewactivity',
                'help_component' => 'format_grid'
            );

            $courseformatoptionsedit['fitsectioncontainertowindow'] = array(
                'label' => new lang_string('setfitsectioncontainertowindow', 'format_grid'),
                'help' => 'setfitsectioncontainertowindow',
                'help_component' => 'format_grid',
                'element_type' => 'select',
                'element_attributes' => array(
                    array(
                        1 => new lang_string('no'), // No.
                        2 => new lang_string('yes') // Yes.
                    )
                )
            );

            $courseformatoptionsedit['greyouthidden'] = array(
                'label' => new lang_string('greyouthidden', 'format_grid'),
                'help' => 'greyouthidden',
                'help_component' => 'format_grid',
                'element_type' => 'select',
                'element_attributes' => array(
                    array(
                        1 => new lang_string('no'), // No.
                        2 => new lang_string('yes') // Yes.
                    )
                )
            );

            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    private function get_context() {
        global $SITE;

        if ($SITE->id == $this->courseid) {
            // Use the context of the page which should be the course category.
            global $PAGE;
            return $PAGE->context;
        } else {
            return context_course::instance($this->courseid);
        }
    }

    /**
     * Returns the format options stored for this course or course section
     *
     * When overriding please note that this function is called from rebuild_course_cache()
     * and section_info object, therefore using of get_fast_modinfo() and/or any function that
     * accesses it may lead to recursion.
     *
     * @param null|int|stdClass|section_info $section if null the course format options will be returned
     *     otherwise options for specified section will be returned. This can be either
     *     section object or relative section number (field course_sections.section)
     * @return array
     */
    public function get_format_options($section = null) {
        global $DB;

        $options = parent::get_format_options($section);

        if ($section === null) {
            // course format options will be returned
            $sectionid = 0;
        } else if ($this->courseid && isset($section->id)) {
            // course section format options will be returned
            $sectionid = $section->id;
        } else if ($this->courseid && is_int($section) &&
                ($sectionobj = $DB->get_record('course_sections',
                        array('section' => $section, 'course' => $this->courseid), 'id'))) {
            // course section format options will be returned
            $sectionid = $sectionobj->id;
        } else {
            // non-existing (yet) section was passed as an argument
            // default format options for course section will be returned
            $sectionid = -1;
        }

        if ($sectionid == 0) {
            $alloptions = $DB->get_records('course_format_options',
                        array('courseid'=>$this->courseid, 'format'=>'qmulgrid',
                            'sectionid'=>0));

            foreach ($alloptions as $option) {
                if (!isset($options[$option->name])) {
                    $options[$option->name] = $option->value;
                }
            }

            $this->formatoptions[$sectionid] = $options;
        }

        return $options;
    }

    /**
     * Returns a moodle_url to a grid format file.
     * @param string $url The URL to append.
     * @param array $params URL parameters.
     * @return moodle_url The created URL.
     */
    public function qmulgrid_moodle_url($url, array $params = null) {
        return new moodle_url('/course/format/qmulgrid/' . $url, $params);
    }

}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_qmulgrid_inplace_editable($itemtype, $itemid, $newvalue) {
    global $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        global $DB;
        $section = $DB->get_record_sql(
                'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
                array($itemid, 'qmulgrid'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}
