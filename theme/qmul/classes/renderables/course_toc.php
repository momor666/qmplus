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
 * Coures toc renderable
 * @author    Andrew Davidson
 * @copyright Copyright 2017 Andrew Davidson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_qmul\renderables;

use context_course;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/format/lib.php');

class course_toc implements \renderable, \templatable{

    public $formatsupportstoc = false;
    public $modules = [];
    public $chapters;
    public $footer;
    protected $course;
    protected $format;

    function __construct($course = null) {
        global $COURSE;
        if (empty($course)) {
            $course = $COURSE;
        }

        $supportedformats = ['weeks', 'topics'];
        if (!in_array($course->format, $supportedformats)) {
            return;
        } else {
            $this->formatsupportstoc = true;
        }

        $this->format  = course_get_format($course);
        $this->course  = $this->format->get_course(); // Has additional fields.

        course_create_sections_if_missing($course, range(0, $this->course->numsections));

        $this->set_modules();
        $this->set_chapters();
        $this->set_footer();
    }

    /**
     * Set modules.
     * @throws \coding_exception
     */
    protected function set_modules() {
        global $CFG, $PAGE;

        // If course does not have any sections then exit - note, module search is not supported in course formats
        // that don't have sections.
        if (!isset($this->course->numsections)) {
            return;
        }

        $modinfo = get_fast_modinfo($this->course);

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname == 'label') {
                continue;
            }
            if ($cm->sectionnum > $this->course->numsections) {
                continue; // Module outside of number of sections.
            }
            if (!$cm->uservisible && (empty($cm->availableinfo))) {
                continue; // Hidden completely.
            }

            $module = new course_toc_module();
            $module->cmid = $cm->id;
            $module->uservisible = $cm->uservisible;
            $module->modname = $cm->modname;
            $module->iconurl = $cm->get_icon_url();
            if ($cm->modname !== 'resource') {
                $module->srinfo = get_string('pluginname', $cm->modname);
            }
            $module->url = $cm->url;
            $module->formattedname = $cm->get_formatted_name();
            $this->modules[] = $module;
        }
    }

    protected function set_chapters() {

        $this->chapters = (object) [];

        $this->chapters->listlarge = $this->course->numsections > 9 ? 'list-large' : '';

        $this->chapters->chapters= [];

        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($this->course->id));

        $modinfo = get_fast_modinfo($this->course);

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {

            if ($section > $this->course->numsections) {
                continue;
            }
            // Students - If course hidden sections completely invisible & section is hidden, and you cannot
            // see hidden things, bale out.
            if ($this->course->hiddensections
                && !$thissection->visible
                && !$canviewhidden) {
                continue;
            }

            $conditional = $this->is_section_conditional($thissection);
            $chapter = new course_toc_chapter();
            $chapter->outputlink = true;

            if ($canviewhidden) { // Teachers.
                if ($conditional) {
                    $chapter->availabilityclass = 'badge-warning';
                    $chapter->availabilitystatus = get_string('conditional', 'theme_qmul');
                }
                if (!$thissection->visible) {
                    $chapter->availabilityclass = 'badge-warning';
                    $chapter->availabilitystatus = get_string('notpublished', 'theme_qmul');
                }
            } else { // Students.
                if ($conditional && !$thissection->uservisible && !$thissection->availableinfo) {
                    // Conditional section, totally hidden from user so skip.
                    continue;
                }
                if ($conditional && $thissection->availableinfo) {
                    $chapter->availabilityclass = 'badge-warning';
                    $chapter->availabilitystatus = get_string('conditional', 'theme_qmul');
                }
                if (!$conditional && !$thissection->visible) {
                    // Hidden section collapsed, so show as text in TOC.
                    $chapter->outputlink  = false;
                    $chapter->availabilityclass = 'badge-warning';
                    $chapter->availabilitystatus = get_string('notavailable');
                }
            }

            $chapter->title = get_section_name($this->course, $section);
            if ($chapter->title == get_string('general')) {
                $chapter->title = get_string('introduction', 'theme_qmul');
            }

            if ($this->format->is_section_current($section)) {
                $chapter->iscurrent = true;
            }

            if ($chapter->outputlink) {
                $singlepage = $this->course->format !== 'folderview';
                if ($singlepage) {
                    $chapter->url = '#section-'.$section;
                } else
                    if ($section > 0) {
                        $chapter->url = course_get_url($this->course, $section, ['navigation' => true, 'sr' => $section]);
                    } else {
                        // We need to create the url for section 0, or a hash will get returned.
                        $chapter->url = new moodle_url('/course/view.php', ['id' => $this->course->id, 'section' => $section]);
                    }
            }

            $chapter->progress = new course_toc_progress($this->course, $thissection);
            $this->chapters->chapters[]=$chapter;
        }
    }

    /**
     * @throws \coding_exception
     */
    protected function set_footer() {
        global $OUTPUT;
        $this->footer = (object) [
            'canaddnewsection' => has_capability('moodle/course:update', context_course::instance($this->course->id)),
            'imgurladdnewsection' => $OUTPUT->pix_url('pencil', 'theme'),
            'imgurltools' => $OUTPUT->pix_url('course_dashboard', 'theme')
        ];
    }

    protected function is_section_conditional(\section_info $section) {
        // Are there any conditional fields populated?
        if (!empty($section->availableinfo)
            || !empty(json_decode($section->availability)->c)) {
            return true;
        }
        // OK - this isn't conditional.
        return false;
    }

    /**
     * Makes an object suitable for exporting - converts objects to string where necessary - e.g. moodle_urls.
     * @param array|object $object
     * @return string
     */
    public function convert_object_for_export($object) {

        if (is_array($object)) {
            foreach ($object as $key => $val) {
                $object[$key] = $this->convert_object_for_export($val);
            }
            return $object;
        }

        // Get protected vars so we can exclude them from get_object_vars - note, get_object_vars can return protected
        // variables if they are in scope and we don't want them!
        $reflect = new \ReflectionClass($object);
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PROTECTED);
        $protected = [];
        foreach ($props as $prop){
            $protected[] = $prop->getName();
        }

        // Get publicly accessible vars, which also includes those that have been set manually, e.g. $myobj->test = 123;
        $vals = get_object_vars($object);
        $public = [];
        foreach ($vals as $key => $val) {
            if (!in_array($key, $protected)) {
                $public[$key] = $val;
            }
        }

        // Convert to string if no public properties, else iterate through properties and recurse.
        if (empty($public)) {
            if ($reflect->hasMethod('__toString')) {
                return strval($object);
            }
        } else {
            foreach ($public as $key => $val) {
                if (is_array($val)) {
                    $this->convert_object_for_export($val);
                }
                if ($val instanceof \moodle_url) {
                    $object->$key = $this->convert_object_for_export($val);
                    continue;
                }
                if (is_object($val)) {
                    if ($val instanceof \renderable || get_class($val) === 'stdClass') {
                        $object->$key = $this->convert_object_for_export($val);
                    } else {
                        if (method_exists($val, '__toString')) {
                            $object->$key = strval($val);
                        } else {
                            $object->$key = $this->convert_object_for_export($val);
                        }
                    }
                }
            }
        }

        return $object;
    }

    /**
     * @param \renderer_base $output
     * @return object
     */
    public function export_for_template(\renderer_base $output) {
        $clone = clone $this;
        return $this->convert_object_for_export($clone);
    }

}
