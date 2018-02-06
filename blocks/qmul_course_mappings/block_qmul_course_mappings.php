<?php
/**
 * Block displaying information about SITS courses mapped to this course.
 *
 * @package    block
 * @subpackage qmul_course_mappings
 * @copyright  2015 Queen Mary University
 * @author     Phil Lello <phil@dunlop-lello.uk>
 */
defined('MOODLE_INTERNAL') || die();

class block_qmul_course_mappings extends block_base
{

    /**
     * block initializations
     */
    public function init()
    {
        $this->title = get_string('pluginname', 'block_qmul_course_mappings');
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content()
    {
        // Use cached content if possible
        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!has_capability('block/qmul_course_mappings:viewmappings', $this->page->context)) {
            return '';
        }
        $showlinks = has_capability('block/qmul_course_mappings:editmappings', $this->page->context);

        $renderer = $this->page->get_renderer('block_qmul_course_mappings');
        $text = $renderer->render_qem_mappings($this->page->course, $showlinks);

        $this->content = new stdClass;
        $this->content->text = $text;
        $this->content->footer = '';
        $course = $this->page->course;

        return $this->content;
    }
}
