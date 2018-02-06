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
 * Course toc section
 * @author    Andrew Davidson
 * @copyright Copyright 2017 Andrew Davidson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_qmul\renderables;

use stdClass;

class course_toc_progress {

    /**
     * @var stdClass
     * @wsparam {
     *     "complete": {
     *         "type": PARAM_INT,
     *         "required": true,
     *         "description": "Number of items completed"
     *     },
     *     "total": {
     *         "type": PARAM_INT,
     *         "required": true,
     *         "description": "Total items to complete"
     *     }
     * };
     */
    public $progress;

    /**
     * @var bool - completed?
     */
    public $completed;

    /**
     * @var string - pixurl for completed
     */
    public $pixcompleted;

    /**
     * Set properties from course and section.
     * @param \stdClass $course
     * @param \stdClass $section
     */
    public function __construct($course, $section) {
        global $OUTPUT;

        static $compinfos = [];
        if (isset($compinfos[$course->id])) {
            $completioninfo = $compinfos[$course->id];
        } else {
            $completioninfo = new \completion_info($course);
            $compinfos[$course->id] = $completioninfo;
        }

        // Set this to empty or web service won't be happy on early abort.
        $this->progress = (object) [
            'complete' => null,
            'total' => null
        ];

        if (!$completioninfo->is_enabled()) {
            return ''; // Completion tracking not enabled.
        }

        $sac = $this->section_activity_summary($section, $course, null);
        if (empty($sac->progress)) {
            return;
        }

        $this->progress = (object) [
            'complete' => $sac->progress->complete,
            'total' => $sac->progress->total
        ];
        $this->pixcompleted = $OUTPUT->pix_url('i/completion-manual-y');
        $this->completed = $sac->progress->complete === $sac->progress->total;
    }

    protected function section_activity_summary($section, $course, $mods) {
        global $CFG;

        require_once($CFG->libdir.'/completionlib.php');

        $modinfo = get_fast_modinfo($course);
        if (empty($modinfo->sections[$section->section])) {
            return '';
        }

        // Generate array with count of activities in this section.
        $sectionmods = array();
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();
        $completioninfo = new \completion_info($course);
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];
            if ($thismod->uservisible) {
                if (isset($sectionmods[$thismod->modname])) {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modplural;
                    $sectionmods[$thismod->modname]['count']++;
                } else {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modfullname;
                    $sectionmods[$thismod->modname]['count'] = 1;
                }
                if ($cancomplete && $completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $complete++;
                    }
                }
            }
        }

        if (empty($sectionmods)) {
            // No sections.
            return '';
        }

        // Output section activities summary.
        $o = '';
        $o .= "<div class='section-summary-activities mdl-right'>";
        foreach ($sectionmods as $mod) {
            $o .= "<span class='activity-count'>";
            $o .= $mod['name'].': '.$mod['count'];
            $o .= "</span>";
        }
        $o .= "</div>";

        $a = false;

        // Output section completion data.
        if ($total > 0) {
            $a = new stdClass;
            $a->complete = $complete;
            $a->total = $total;
            $a->percentage = ($complete / $total) * 100;

            $o .= "<div class='section-summary-activities mdl-right'>";
            $o .= "<span class='activity-count'>".get_string('progresstotal', 'completion', $a)."</span>";
            $o .= "</div>";
        }

        $retobj = (object) array (
            'output' => $o,
            'progress' => $a,
            'complete' => $complete,
            'total' => $total
        );

        return $retobj;
    }
}

