<?php
/**
 * @package    block
 * @subpackage qmul_course_mappings
 * @copyright  2015 Queen Mary University
 */
defined('MOODLE_INTERNAL') || die();

class block_qmul_course_mappings_renderer extends plugin_renderer_base
{
    /**
     * Render QEM mappings information for a course
     *
     * @param stdClass $course
     * @param boolean $showlinks
     * @return string HTML representing the mappings
     */
    public function render_qem_mappings(stdClass $course, $showlinks = false) {

        $context = context_course::instance($course->id);

        $defaults = local_qmul_sync_plugin::qem_defaults_for_course($course);
        $mappings = local_qmul_sync_plugin::qem_mappings_for_course($course);
        $sitsmodule = local_qmul_sync_plugin::sits_data_for_course($course);

        $data = new stdClass();
        foreach ($course as $k => $v) {
            $k = "course_$k";
            $data->$k = $v;
        }
        if ($sitsmodule) {
            foreach ($sitsmodule as $k => $v) {
                $k = "sitsmodule_$k";
                $data->$k = $v;
            }
        }

        $text  = "Mappings for course:";
        $text .= html_writer::start_tag('ul', array('class' => 'qem-mappings'));
        if (count($defaults) > 0) {
            foreach ($defaults as $id => $label) {
                if ($showlinks) {
                    $data->qem_id = $id;
                    $data->qem_description = $label;
                    $url = new moodle_url(get_string('fakemappingurl', 'block_qmul_course_mappings', $data));
                    $text .= html_writer::tag('li', html_writer::link($url, $label, array('target' => 'qem-mappings')));
                } else {
                    $text .= html_writer::tag('li', $label);
                }
            }
        }
        if (count($mappings) > 0) {
            foreach ($mappings as $id => $label) {
                if ($showlinks) {
                    $data->qem_id = $id;
                    $data->qem_description = $label;
                    $url = new moodle_url(get_string('realmappingurl', 'block_qmul_course_mappings', $data));
                    $text .= html_writer::tag('li', html_writer::link($url, $label, array('target' => 'qem-mappings')));
                } else {
                    $text .= html_writer::tag('li', $label);
                }
            }
        }
        $text .= html_writer::end_tag('ul');
        if (count($defaults) == 0 && count($mappings) == 0) {
            $label = get_string('logintext', 'block_qmul_course_mappings');
            if ($showlinks) {
                $url = new moodle_url(get_string('loginurl', 'block_qmul_course_mappings'));
                $text .= html_writer::link($url, $label);
            } else {
                $text .= html_writer::tag('li', $label);
            }
        }
        return html_writer::tag('div', $text, array('class' => 'qem-mappings'));
    }
}
