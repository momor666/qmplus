<?php
/**
 * To get an instance of this use the following code:
 * $renderer = $PAGE->get_renderer('gradereport_qmul_sits');
 */
class gradereport_qmul_sits_renderer extends plugin_renderer_base {

    protected $expand_icon;
    protected $collapse_icon;
    protected $hidden_expand_icon;
    protected $hidden_collapse_icon;

    /**
     * Constructor
     *
     * The constructor takes two arguments. The first is the page that the renderer
     * has been created to assist with, and the second is the target.
     * The target is an additional identifier that can be used to load different
     * renderers for different options.
     *
     * @param moodle_page $page the page we are doing output for.
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {

        parent::__construct($page, $target);

        $this->expand_icon = new pix_icon('tree-collapsed', get_string('expand'), 'theme', array('class' => 'expand-node'));
        $this->collapse_icon = new pix_icon('tree-expanded', get_string('collapse'), 'theme', array('class' => 'collapse-node'));
    }

    /**
     * Return HTML for a tree of courses.
     *
     * @param coursecat $category - The category to render from
     * @param array $options - Output options. See below.
     * @param array $courses - Courses to render.
     * @return Rendered HTML
     *
     * Currently defined options are:
     * - link (to make course names a link to the course)
     * - check (to add a checkbox by a course)
     * - hidden (to include hidden courses and categories)
     * - noroot (to hide root node)
     */
    public function course_tree($category, &$options = array(), &$courses = null) {
        global $CFG;

        $output  = '';
        foreach ($category->get_children() as $id => $childcategory) {
            if ($childcategory->visible or in_array('hidden', $options)) {
                $output .= $this->course_tree($childcategory, $options, $courses);
            }
        }

        foreach ($category->get_courses() as $id => $course) {
            if (!in_array('hidden', $options) and $course->visible == 0) {
                continue;
            }
            if ($courses !== null and !in_array($id, $courses)) {
                continue;
            }

            $label = $course->fullname;

            // Make titles into links
            if (in_array('link', $options)) {
                $url = new moodle_url("$CFG->wwwroot/course/view.php", array('id' => $id));
                $label = html_writer::link($url, $label);
            }
            // Add a checkbox
            if (in_array('check', $options)) {
                $label = html_writer::checkbox("course[$id]", 1, false).$label;
            }

            if ($course->visible) {
                $output .= html_writer::tag('li', $label, array('class' => 'course'));
            } else {
                $output .= html_writer::tag('li', $label, array('class' => 'course moodle-hidden'));
            }
        }
        if ($output) {
            if (!in_array('noroot', $options) || $category->parent) {
                $label = trim($category->name);
                if ($label == '') {
                    $label = 'All categories';
                }
                $label = $this->render($this->collapse_icon).$this->render($this->expand_icon).$label;
                $classes = 'expanded node';
                if (!$category->visible) {
                    $classes .= 'moodle-hidden';
                }
                $output = html_writer::tag('li', $label.html_writer::tag('ul', $output), array('class' => $classes));
            } else {
                $output = html_writer::tag('ul', $output);
            }
        }
        return $output;
    }
}
