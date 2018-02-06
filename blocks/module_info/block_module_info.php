<?php
/****************************************************************

File:       block/module_info/block_module_info.php

Purpose:    This file holds the class definition for the block,
and is used both to manage it as a plugin and to
render it onscreen.

 ****************************************************************/

class block_module_info extends block_base
{
    /**
     * Initialize the block
     *
     * Give values to any class member variables
     * that need instantiating
     *
     * @return void
     */
    public function init()
    {

        $this->title = get_string('module_info', 'block_module_info');
    }

    /**
     * Configure the block
     *
     * This method is called immediately after init()
     * but before anything else is done with the block
     * (e.g before it has been displayed)
     *
     * @return void
     */
    public function specialization() {
        $this->title = (isset($this->config->title_override) && $this->config->title_override) ?
            format_string($this->config->title) :
            format_string(get_string('module_info', 'block_module_info'));
    }

    /**
     * This block can appear only on course pages
     *
     * @return boolean
     */
    public function applicable_formats()
    {
        return array('course' => true);
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Allow instances to have their own configuration pages
     *
     * @return boolean
     */
    function instance_allow_config() {
        return true;
    }

    /**
     * Disable adding multiple instances of this block to one course
     *
     * @return boolean
     */
    public function instance_allow_multiple()
    {
        return false;
    }

    /**
     * Create the content that needs to be displayed by this block
     *
     * @return mixed
     */
    public function get_content()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $output_buffer = '';
        $renderer = $this->page->get_renderer('block_module_info');
        $renderer->initialise($this);

        // Display module info
        $output_buffer .= $renderer->get_moduleinfo_output();

        // Display Teaching info
        $output_buffer .= $renderer->get_teaching_output();

        // Display Schedule info
        $output_buffer .= $renderer->get_sessioninfo_output();

        // Links to documents
        $output_buffer .= $renderer->get_documentinfo_output();

        //Other information
        $output_buffer .= $renderer->get_otherinfo_output();

        //Staff Directory
        $output_buffer .= $renderer->get_staff_directory_output();

        // The output buffer is now complete so copy this to the content
        $this->content = new stdClass();
        $this->content->text = $output_buffer;
        $this->content->footer = '';
        return $this->content;
    }
}