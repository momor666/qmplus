<?php
/**
 * Version details.
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../lib/externallib.php');
require_once(__DIR__.'/lib_gradesRenderer.php');
require_once(__DIR__.'/lib_teacherGradesRenderer.php');
require_once(__DIR__.'/../../mod/assign/locallib.php');

require_once(__DIR__ .'/../../mod/assign/feedbackplugin.php' );
require_once(__DIR__ .'/../../mod/assign/feedback/comments/locallib.php' );
require_once(__DIR__ .'/../../mod/assign/externallib.php');
require_once(__DIR__ .'/../../mod/assign/submission/file/locallib.php');
require_once(__DIR__ .'/../../mod/assign/submission/onlinetext/locallib.php');

require_once(__DIR__ .'/../../lib/gradelib.php');
require_once(__DIR__  . '/../../grade/grading/form/lib.php');
require_once(__DIR__  . '/../../grade/grading/form/rubric/lib.php');
require_once(__DIR__  . '/../../grade/grading/form/guide/lib.php');

require_once(__DIR__ .'/../../lib/modinfolib.php');

// If progress plugin exist, load the library file
if (file_exists(__DIR__.'/../../blocks/progress/lib.php')) {
    require_once(__DIR__.'/../../blocks/progress/lib.php');
}
if (file_exists(__DIR__.'/../../plagiarism/turnitin/lib.php')) {
    require_once(__DIR__.'/../../plagiarism/turnitin/lib.php');
}

$ripcord = $CFG->dirroot.'/mod/assign/feedback/comments/locallib.php' ;
//$rip2 = __DIR__.'/../../lib/externallib.php';
//require_once(__DIR__.'/../../mod/assign/feedback/comments/locallib.php');


function local_qmul_dashboard_extend_settings_navigation($settingsnav, $context)
{
    global $CFG, $PAGE;

//    $path = $PAGE->url->get_path();
//    //this could be more clever
//    //if user has permission for this category display in category admin settings
//    if(($path == "/course/management.php") || ($path == "local/qmul_dashboard/manage_settings.php")){
//        return;
//    }

    if($context->get_level_name() != "Category"){
        return;
    }

    if (!has_capability('moodle/category:manage', $context)) {
        return;
    }

    $catid = $context->instanceid;


    $urltext = get_string('manageSettingsURL', 'local_qmul_dashboard');
    $url = new moodle_url($CFG->wwwroot . '/local/qmul_dashboard/manage_settings.php', array('categoryid'=>$catid));

    $catsettingsnode = $settingsnav->find('categorysettings', null);
    if($catsettinsnode) {
        $catsettingsnode->add($urltext, $url, navigation_node::NODETYPE_LEAF, null, 'qmul_dashboard',  new pix_icon('t/hide', ''));
    }

//    $settingnode = $settingsnav->add($urltext, $url, navigation_node::TYPE_CONTAINER);
//    $settingsnav->add_node($catsettingsnode);

}



/**
 * This method returns all user's enrollment courses
 * @param $userid
 * @return $courses Object
 */
function local_qmul_dashboard_getUserCourses($userid)
{

    global $DB;

    $userCourses = null;
    $enrolledUserCourses = null;
    $roleUserCourses = null;

    try {
        // use courseid as first field to remove duplicates in php object.
        $enrolledUserCourses = $DB->get_records_sql(
            'SELECT e.courseid,a.*,c.*,crs.*,e.*,r.* FROM {role_assignments} as a
            INNER JOIN {context} as c ON c.id=a.contextid AND c.contextlevel = ?
            INNER JOIN {course} as crs ON c.instanceid=crs.id AND crs.visible = 1
            INNER JOIN {enrol} as e on e.courseid=crs.id
            INNER JOIN {user_enrolments} as r on r.userid=a.userid AND r.enrolid=e.id
            WHERE a.userid = ? ORDER BY a.contextid',
            array(50,$userid)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    try {
        // use courseid as first field to remove duplicates in php object.
        $roleUserCourses = $DB->get_records_sql(
            'SELECT e.courseid,a.*,c.*,crs.*,e.*,r.* FROM {role_assignments} as a
            INNER JOIN {context} as c ON c.id=a.contextid AND c.contextlevel = ?
            INNER JOIN {course} as crs ON c.instanceid=crs.id AND crs.visible = 1
            INNER JOIN {enrol} as e on e.courseid=crs.id
            INNER JOIN {role_assignments} as r on r.userid=a.userid AND r.itemid=e.id
            WHERE a.userid = ? ORDER BY a.contextid',
            array(50,$userid)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    // Merge arrays and remove duplicates
    $userCourses = $enrolledUserCourses+$roleUserCourses;

    usort($userCourses, 'sortCoursesByName');
    return $userCourses;

}

/**
 * @param $course object
 * @param $course object
 * @return int
 */
function sortCoursesByName($a, $b) {
    return strcmp($a->fullname, $b->fullname);
}



/**
 * This method returns all grades categories of a course
 *
 * @return $gradeCategories Object
 */
function local_qmul_dashboard_getGradeCourseCategoriesTree($course)
{

    global $DB;

    $gradeCategories = null;

    try {
        $gradeCategories = $DB->get_records_sql(
            'SELECT * FROM {grade_categories} where courseid = ? ORDER by path',
            array($course)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


    $gradeCategoriesTree = array();
    foreach ($gradeCategories as $category) {
        $path = explode('/',$category->path);
        unset($path[0]);
        unset($path[count($path)]);

        $parentArr = &$gradeCategoriesTree;

        foreach ($path as $index => $c) {

            if (!isset($parentArr[$c])) {
                $parentArr[$c] = array('category'=>$category,'grades'=>array());
            }else{
                $parentArr = &$parentArr[$c];
            }
        }
    }

    return $gradeCategoriesTree;

}


class GradeCategories {
    private $categoriesTree;

    public function __construct($categoriesTree) {
        $this->categoriesTree = $categoriesTree;
    }


    /**
     * @param $categoryid
     * @param $tree
     * @return string
     */
    public function getGradesCatArrayPath($categoryid,$tree){

        $path = null;
        foreach ($tree as $key => $item) {
            if ($key == $categoryid && isset($item['category'])) {
                $path =  $item['category']->path;
            }
            else if (is_array($item) && $key!=='grades') {
                $path =  $this->getGradesCatArrayPath($categoryid,$item);
            }

            if($path!==null){
                break;
            }
        }

        return $path;
    }

    /**
     * @return mixed
     */
    public function getCategoriesTree()
    {
        return $this->categoriesTree;
    }

    /**
     * @param mixed $categoriesTree
     */
    public function pushToCategoriesTree($categoryid,$data)
    {
        $path = $this->getGradesCatArrayPath($categoryid,$this->categoriesTree);

        $paths = explode('/',$path);
        unset($paths[0]);
        unset($paths[count($paths)]);
        $arrTree = &$this->categoriesTree;
        $arr = null;
        foreach ($paths as $index => $path) {
            if($arr===null){
                $arr = &$arrTree[$path];
            }else{
                $arr = &$arr[$path];
            }
        }

        if($arr===null){
            $arr = &$arrTree[$categoryid];
        }
        array_push($arr['grades'], $data);
    }


    /**
     * @param mixed $categoriesTree
     */
    public function pushToCategoryGrade($categoryid,$data,$item = null)
    {

        $arr = $this->categoriesTree;
        if($item!==null){
            $arr = $item;
        }

        foreach ($arr as $key => $item) {
            if ($key == $categoryid) {
                $item['category']->finalgrade = $data;
                return true;
                //return $item['category']->finalgrade= $data ;//'/'.$categoryid;

            }
            else if (is_array($item) && $key!=='grades') {
                return $this->pushToCategoryGrade($categoryid,$data,$item);
            }
        }

    }
}



/**
 * This method returns all user's enrollment courses
 *
 * @return $courses Object
 */
function local_qmul_dashboard_getUserCourseGrades($course)
{

    global $DB;

    $courseGrades = null;

    try {
        $courseGrades = $DB->get_records_sql
        (//g.hidden as ghidden,gi.hidden as gihidden
            'SELECT g.id as gradeid,g.*,gi.*, gi.hidden as itemihidden, m.id as coursemoduleid, m.*
            FROM {grade_grades} as g
            INNER JOIN {grade_items} as gi on gi.id=g.itemid
            INNER JOIN {context} as c ON c.contextlevel=? AND c.instanceid=gi.courseid AND gi.courseid=?
            LEFT JOIN {modules} as md ON md.name = gi.itemmodule
            LEFT JOIN {course_modules} as m ON m.course = gi.courseid
              AND m.instance = gi.iteminstance AND md.id = m.module AND m.visible = 1
            WHERE  g.finalgrade IS NOT NULL AND g.hidden = 0 AND g.userid=?  AND gi.display=0',
            array(50,$course->courseid,$course->userid)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    $grades = false;
    $gradeCategoriesTree = local_qmul_dashboard_getGradeCourseCategoriesTree($course->courseid);
    $grades = New GradeCategories($gradeCategoriesTree);
    $first_key = key($gradeCategoriesTree);

    foreach ($courseGrades as $grade) {

        if($grade->itemtype == 'category') {
            //push category grade to category
            if(isset($grade->finalgrade)){
                $grades->pushToCategoryGrade($grade->iteminstance,$grade->finalgrade,$grades);
            }
            $grades->pushToCategoriesTree($grade->iteminstance,$grade);
        } elseif ($grade->categoryid!==null) {
            //  $gradesCat = &getGradesCatArray($grade->categoryid,$gradeCategoriesTree);
            //array_push($gradesCat['grades'],$grade);
            $grades->pushToCategoriesTree($grade->categoryid,$grade);
        } else {
            $grades->pushToCategoriesTree($first_key,$grade);
            //    $cats->pushToCategoriesTree('grades',$grade);
        }
    }

    //return $courseGrades;
    return $grades;

}




/**
 * This method returns all grades of a course minus user's grade
 *
 * @return $courses Object
 */
function local_qmul_dashboard_getRestCourseGrades($course, $itemId)
{

    global $DB;

    $courseGrades = null;

    try {
        $courseGrades = $DB->get_records_sql(
            'SELECT g.* FROM {grade_grades} as g
            INNER JOIN {grade_items} as gi on gi.id=g.itemid AND g.itemid = ?
            INNER JOIN {context} as c ON c.contextlevel=? AND c.instanceid=gi.courseid AND gi.courseid=?
            LEFT JOIN {course_modules} as m ON m.course = gi.courseid AND m.instance = gi.iteminstance AND m.visible = 1
            WHERE  g.finalgrade IS NOT NULL AND g.hidden = 0 AND g.userid<>? AND gi.display=0 ORDER BY g.finalgrade ASC',
            array($itemId, 50,$course->courseid,$course->userid)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $courseGrades;

}

/**
 * This method returns all course graded activities
 *
 * @return $courses Object
 */
function local_qmul_dashboard_getAllCourseGradedActivities($course)
{

    global $DB;

    $courseActivities = null;

    try {
        $courseActivities = $DB->get_records_sql(
            'SELECT DISTINCT(gi.id), gi.*, m.id as coursemoduleid FROM {grade_items} as gi
            JOIN mdl_grade_grades as g on gi.id=g.itemid  AND gi.courseid=? AND gi.itemtype<>? AND g.hidden = 0  AND gi.hidden=0 AND gi.display=0
            LEFT JOIN {modules} as md ON md.name = gi.itemmodule
            LEFT JOIN {course_modules} as m ON m.course = gi.courseid AND m.instance = gi.iteminstance AND md.id = m.module',
            array($course->courseid,'course')
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    $grades = false;
    $gradeCategoriesTree = local_qmul_dashboard_getGradeCourseCategoriesTree($course->courseid);
    $grades = New GradeCategories($gradeCategoriesTree);
    $first_key = key($gradeCategoriesTree);

    foreach ($courseActivities as $grade) {

        if($grade->itemtype == 'category') {
            //push category grade to category
            if(isset($grade->finalgrade)){
                $grades->pushToCategoryGrade($grade->iteminstance,$grade->finalgrade,$grades);
            }
            $grades->pushToCategoriesTree($grade->iteminstance,$grade);
        } elseif ($grade->categoryid!==null) {
            //  $gradesCat = &getGradesCatArray($grade->categoryid,$gradeCategoriesTree);
            //array_push($gradesCat['grades'],$grade);
            $grades->pushToCategoriesTree($grade->categoryid,$grade);
        } else {
            $grades->pushToCategoriesTree($first_key,$grade);
            //    $cats->pushToCategoriesTree('grades',$grade);
        }
    }

    //return $courseGrades;
    return $grades;

    //    return $courseActivities;

}



/**
 * This method returns all course activity grades
 *
 * @return $grades Object
 */
function local_qmul_dashboard_getAllCourseActivityGrades($course,$item)
{

    global $DB;

    $activityGrades = null;


    /**
     *
     * SELECT g.id as gradeid,g.*,gi.*, m.id as coursemoduleid, m.*
    FROM mdl_grade_grades as g
    INNER JOIN mdl_grade_items as gi on gi.id=g.itemid
    INNER JOIN mdl_context as c ON c.contextlevel='50' AND c.instanceid=gi.courseid AND gi.courseid='3535'

    LEFT JOIN mdl_modules as md ON md.name = gi.itemmodule
    LEFT JOIN mdl_course_modules as m ON m.course = gi.courseid AND m.instance = gi.iteminstance AND md.id = m.module
    WHERE  g.finalgrade IS NOT NULL  AND gi.itemtype<>'course' AND gi.id = '36097';

     *
     *
     */

    try {
        $activityGrades = $DB->get_records_sql(
            'SELECT g.id as gradeid,g.*,gi.*, m.id as coursemoduleid, m.*
            FROM {grade_grades} as g
            INNER JOIN {grade_items} as gi on gi.id=g.itemid
            INNER JOIN {context} as c ON c.contextlevel=? AND c.instanceid=gi.courseid AND gi.courseid=?
            LEFT JOIN {modules} as md ON md.name = gi.itemmodule
            LEFT JOIN {course_modules} as m ON m.course = gi.courseid AND m.instance = gi.iteminstance AND md.id = m.module
            AND m.visible = 1
            WHERE  g.finalgrade IS NOT NULL AND g.hidden = 0  AND gi.id = ? AND gi.hidden=0 AND gi.display=0',
            array(50,$course->id,$item)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $activityGrades;

}





/**
 * Helper method to sort array by field value
 * @param $grades
 * @return array
 */
function local_qmul_dashboard_sortGrades($grades)
{

    $array = [];
    foreach ($grades as $key => $grade) {
        $array[$grade->userid] = $grade->finalgrade;
    }

    asort($array);

    return $array;
    /*if($a->finalgrade == 58.5){
        $tat =  $a->finalgrade - $b->finalgrade;
    }
    $tata = $a->finalgrade - $b->finalgrade;
    return $tata; //strcmp($a->finalgrade, $b->finalgrade);*/
}



/**
 * This method returns all grades of a course minus user's grade
 *
 * @return $courses Object
 */
function local_qmul_dashboard_getCourseAverageGrade($grades)
{

    $totalGrades = count($grades);
    $total  = 0;

    if ($totalGrades) {
        foreach ($grades as $grade) {
            $total+=$grade->finalgrade;
        }

        $avg = $total/$totalGrades;
        return  round($avg, 2);

    }

    return $totalGrades;

}



/**
 * This method checks if user has authorized the facebook app. If it has user will exist in the database
 *
 * @param $user  Moodle user object
 * @return Object or false
 */
function local_qmul_dashboard_getUser($user)
{
    global $DB;

    $fbUser = null;

    try {
        $fbUser = $DB->get_record('qmul_dashboard_users', array('userid' => $user->id));
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $fbUser;

}

/**
 * Get Grade Category Name
 *
 * @param $id int category id
 * @return Object or false
 */
function local_qmul_dashboard_getGradeCategory($id)
{
    global $DB;

    $category = null;

    try {
        $category = $DB->get_record('grade_categories', array('id' => $id));
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $category;

}


/**
 * Get course context
 * @param int $course
 * @return context_course
 */
function local_qmul_dashboard_getCourseContext($course)
{
    return context_course::instance($course);
}


/**
 * This method checks if block progress exist and is activated in this course
 *
 * @param int $course
 * @return Object or false
 */
function local_qmul_dashboard_getProgressConfig($courseContext)
{

    global $DB;

    $progress = null;

    try {
        $progress = $DB->get_record_sql(
            'SELECT * FROM {block_instances} WHERE blockname = ? AND  parentcontextid = ?',
            array('progress',$courseContext->id)
        );
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $progress;

}

/**
 * This method returns all course enrolled users
 *
 * @return $courses Object
 */
function local_qmul_dashboard_getCourseUsers($courseid)
{
    global $DB;

    $courseUsers = null;
    $enrolledCourseUsers = null;
    $roleCourseUsers = null;

    try {
        $enrolledCourseUsers = $DB->get_records_sql(
            'SELECT u.* FROM {user} as u
            INNER JOIN {user_enrolments} as a ON a.userid = u.id
            INNER JOIN {enrol} as r ON r.id = a.enrolid
            INNER JOIN {course} as c on c.id = r.courseid
            WHERE c.id = ? AND c.visible = 1',
            array($courseid)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    try {
        $roleCourseUsers = $DB->get_records_sql(
            'SELECT u.* FROM {user} as u
            INNER JOIN {role_assignments} as a ON a.userid = u.id
            INNER JOIN {role} as r ON r.id = a.roleid
            INNER JOIN {course} as c
            INNER JOIN {context} as ct ON ct.instanceid=c.id AND a.contextid = ct.id
            WHERE c.id = ? AND c.visible = 1',
            array($courseid)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    // Merge arrays and remove duplicates
    $courseUsers = $enrolledCourseUsers+$roleCourseUsers;

    return $courseUsers;

}




/**
 * Draws a progress bar
 *
 * @param array    $modules  The modules used in the course
 * @param stdClass $config   The blocks configuration settings
 * @param array    $events   The possible events that can occur for modules
 * @param int      $userid   The user's id
 * @param int      instance  The block instance (in case more than one is being displayed)
 * @param array    $attempts The user's attempts on course activities
 * @param bool     $simple   Controls whether instructions are shown below a progress bar
 * @param array    $attempts all users attempts on course activities
 * @return string  Progress Bar HTML content
 */
function local_qmul_dashboard_drawProgressBar($modules, $config, $events, $userid, $instance, $attempts, $course,
                                              $simple = false, $usersattempts) {
    global $OUTPUT, $CFG;

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $now = time();
    $numevents = count($events);
    $dateformat = get_string('strftimerecentfull', 'langconfig');
    $tableoptions = array('class' => 'progressBarProgressTable table table-bordered',
        'cellpadding' => '0',
        'cellspacing' => '0');

    // Get colours and use defaults if they are not set in global settings.
    $colournames = array(
        'attempted_colour' => 'attempted_colour',
        'notattempted_colour' => 'notAttempted_colour',
        'futurenotattempted_colour' => 'futureNotAttempted_colour'
    );
    $colours = array();
    foreach ($colournames as $name => $stringkey) {
        if (get_config('block_progress', $name)) {
            $colours[$name] = get_config('block_progress', $name);
        }
        else {
            $colours[$name] = get_string('block_progress', $stringkey);
        }
    }


    // PP do not need now arrow
    $tableoptions['class'] = 'table table-bordered noNow';
    $content = HTML_WRITER::start_tag('table', $tableoptions);

    // Table headers
    $content .= HTML_WRITER::start_tag('thead');
    $content .= HTML_WRITER::start_tag('tr');
    $content .= HTML_WRITER::tag('th', get_string('progressColumnName', 'local_qmul_dashboard'),array('class'=>'span4'));
    $content .= HTML_WRITER::tag('th', get_string('progressColumnStatus', 'local_qmul_dashboard'),array('class'=>'span3'));
    $content .= HTML_WRITER::tag('th', get_string('progressColumnExpexted', 'local_qmul_dashboard'),array('class'=>'span2'));
    //$content .= HTML_WRITER::tag('th',get_string('progressColumnAverage', 'local_qmul_dashboard'),array('class'=>'span3'));
    $content .= HTML_WRITER::end_tag('tr');
    $content .= HTML_WRITER::end_tag('thead');

    // Start progress bar.
    $width = 100 / $numevents;
    // $content .= HTML_WRITER::start_tag('tr');
    $counter = 1;

    $displaydate = (!isset($config->orderby) || $config->orderby == 'orderbytime') &&
        (!isset($config->displayNow) || $config->displayNow == 1);


    $content .= HTML_WRITER::start_tag('tbody');
    if($events){
        foreach ($events as $event) {

            // Event per row
            $content .= HTML_WRITER::start_tag('tr');

            $link = '/mod/'.$event['type'].'/view.php?id='.$event['cm']->id;
            $text = $OUTPUT->pix_icon('icon', '', $event['type'], array('class' => 'moduleIcon')).s($event['name']);

            if (!empty($event['cm']->available)) {
                $content .= HTML_WRITER::tag('td', $OUTPUT->action_link($link, $text));
            } else {
                $content .= HTML_WRITER::tag('td', $text);
            }

            $attempted = $attempts[$event['type'].$event['id']];

            $action = isset($config->{'action_'.$event['type'].$event['id']})?
                $config->{'action_'.$event['type'].$event['id']}:
                $modules[$event['type']]['defaultAction'];

            // A cell in the progress bar.
            $celloptions = array(
                'class' => '',
                'id' => '',
                'style' => 'background-color:');

            if ($attempted === true) {
                $celloptions['style'] .= $colours['attempted_colour'].';';
                $cellcontent = $OUTPUT->pix_icon(
                    isset($config->progressBarIcons) && $config->progressBarIcons == 1 ?
                        'tick' : 'blank', '', 'block_progress');
            }
            else if (((!isset($config->orderby) || $config->orderby == 'orderbytime') && $event['expected'] < $now) ||
                ($attempted === 'failed')) {
                $celloptions['style'] .= $colours['notattempted_colour'].';';
                $cellcontent = $OUTPUT->pix_icon(
                    isset($config->progressBarIcons) && $config->progressBarIcons == 1 ?
                        'cross':'blank', '', 'block_progress');
            }
            else {
                $celloptions['style'] .= $colours['futurenotattempted_colour'].';';
                $cellcontent = $OUTPUT->pix_icon('blank', '', 'block_progress');
            }

            /*if (!empty($event['cm']->available)) {
                $celloptions['onclick'] = 'document.location=\''.
                    $CFG->wwwroot.'/mod/'.$event['type'].'/view.php?id='.$event['cm']->id.'\';';
            }

            if ($counter == 1) {
                $celloptions['id'] .= 'first';
            }

            if ($counter == $numevents) {
                $celloptions['id'] .= 'last';
            }*/

            $counter++;

            $cellcontent .= ucfirst(get_string($action, 'local_qmul_dashboard')).'&nbsp;';
            $icon = ($attempted && $attempted !== 'failed' ? 'tick' : 'cross');
            $cellcontent .= $OUTPUT->pix_icon($icon, '', 'block_progress');

            $content .= HTML_WRITER::tag('td', $cellcontent, $celloptions);

            if ($displaydate) {
                // $expected = get_string('time_expected', 'block_progress').': ';
                $expected = userdate($event['expected'], $dateformat, $CFG->timezone);
                $content .= HTML_WRITER::tag('td', $expected);
            } else {
                $content .= HTML_WRITER::tag('td', '');
            }

            /*
             Average removed per request

            $averageCell = local_qmul_dashboard_averageProgressStatus($usersattempts,$config,$colours,$event,$modules,$now);
            if($averageCell){
                $content .= HTML_WRITER::tag('td', $averageCell['cellcontent'], $averageCell['celloptions']);
            }else{
                $content .= HTML_WRITER::tag('td', ' - ', array('class'=>'text-center'));
            }
            */


            $content .= HTML_WRITER::end_tag('tr');
        }
    }
    $content .= HTML_WRITER::end_tag('tbody');
    $content .= HTML_WRITER::end_tag('table');

    return $content;
}



/**
 * Draws a progress bar
 *
 * @param array    $modules  The modules used in the course
 * @param stdClass $config   The blocks configuration settings
 * @param array    $events   The possible events that can occur for modules
 * @param int      $userid   The user's id
 * @param int      instance  The block instance (in case more than one is being displayed)
 * @param array    $attempts The user's attempts on course activities
 * @param bool     $simple   Controls whether instructions are shown below a progress bar
 * @param array    $attempts all users attempts on course activities
 * @return string  Progress Bar HTML content
 */
function local_qmul_dashboard_averageProgressStatus($usersattempts,$config,$colours,$event,$modules,$now) {

    if(empty($usersattempts)){
        return false;
    }

    global $OUTPUT, $CFG;

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $celloptions = array(
        'class' => 'progressBarCell ds_progresscell',
        'id' => '',
        'style' => 'background-color:');

    $attempts['attemptedCounter'] = 0;
    $attempts['attemptedFailed'] = 0;
    $attempts['notAttempted'] = 0;

    $action = isset($config->{'action_'.$event['type'].$event['id']})?
        $config->{'action_'.$event['type'].$event['id']}:
        $modules[$event['type']]['defaultAction'];

    foreach ($usersattempts as $attempt) {
        $attempted = $attempt[$event['type'].$event['id']];
        if ($attempted === true || $attempted=='true') {
            $attempts['attemptedCounter']++;
        }
        elseif (((!isset($config->orderby) || $config->orderby == 'orderbytime') && $event['expected'] < $now) ||
            ($attempted === 'failed')) {
            $attempts['attemptedFailed']++;
        }
        else {
            $attempts['notAttempted']++;
        }

    }

    $maxAttempt = array_keys($attempts, max($attempts));

    switch($maxAttempt[0]){
        case 'attemptedCounter':
            $celloptions['style'] .= $colours['attempted_colour'].';';
            $cellcontent = $OUTPUT->pix_icon(
                isset($config->progressBarIcons) && $config->progressBarIcons == 1 ?
                    'tick' : 'blank', '', 'block_progress');
            $icon = 'tick';
            break;
        case 'attemptedFailed':
            $celloptions['style'] .= $colours['notattempted_colour'].';';
            $cellcontent = $OUTPUT->pix_icon(
                isset($config->progressBarIcons) && $config->progressBarIcons == 1 ?
                    'cross':'blank', '', 'block_progress');
            $icon = 'cross';
            break;
        case 'notAttempted':
            $celloptions['style'] .= $colours['futurenotattempted_colour'].';';
            $cellcontent = $OUTPUT->pix_icon('blank', '', 'block_progress');
            $icon = 'cross';
            break;
    }

    $cellcontent .= get_string($action, 'block_progress').'&nbsp;';
    $cellcontent .= $OUTPUT->pix_icon($icon, '', 'block_progress');

    $averageCell = array('celloptions' =>$celloptions, 'cellcontent'=>$cellcontent);


    return $averageCell;
}



/**
 * Draws a progress bar
 *
 * @param array    $modules  The modules used in the course
 * @param stdClass $config   The blocks configuration settings
 * @param array    $events   The possible events that can occur for modules
 * @param int      $userid   The user's id
 * @param int      instance  The block instance (in case more than one is being displayed)
 * @param array    $attempts The user's attempts on course activities
 * @param bool     $simple   Controls whether instructions are shown below a progress bar
 * @param array    $attempts all users attempts on course activities
 * @return string  Progress Bar HTML content
 */
function local_qmul_dashboard_renderModuleHeader($course,$mode = 1) {

    /*
    *  Courses Accordion
    */
    $courselink = html_writer::link('#courseBody'.$mode.$course->courseid,
        '<span class="glyphicon glyphicon-collapse-down collapsedown" aria-hidden="true"></span>'.$course->fullname,
        array(
            'role'=> 'button',
            'href'=>'#courseBodystudents' .$course->courseid,
            'class' => 'accordion-toggle',
            'data-toggle' => 'collapse',
            'data-parent' => '#coursesaccordion'.$mode));



    $output = html_writer::start_tag('div', array('class' => 'accordion-group panel panel-default', 'data-animation-effect' => 'zoomIn', 'data-effect-delay'=> '90000' ));

//    $output .= html_writer::start_tag('div', array('class' => ' panel-heading'));
    $output .= html_writer::div('<h4>'.$courselink.'</h4>', null ,array('class'=>' panel-title'));
//    $output .= html_writer::end_tag('div');


    $output .= html_writer::start_tag('div', array(
            'id'=>'courseBody'.$mode.$course->courseid,'class' => 'accordion-body panel-collapse collapse')
    );

    // TODO add link : $output .= html_writer::link('');
    $output .= html_writer::start_tag('div', array('class' => 'accordion-inner panel-body'));



    return $output;

}

/**
 * @return string
 */
function local_qmul_dashboard_renderModuleFooter() {


    $output = html_writer::end_tag('div'); // course accordion-heading
    $output .= html_writer::end_tag('div'); // course accordion-group
    $output .= html_writer::end_tag('div'); // course accordion panel

    return $output;
}


/**
 * @param object $course
 * @return object checklists
 */
function local_qmul_dashboard_getCourseChecklists($course) {

    global $DB;

    try {
        $checklists = $DB->get_records_sql('SELECT ch.*,cm.id as coursemodule
                FROM {checklist} ch
                INNER JOIN {course_modules} cm ON cm.instance = ch.id
                INNER JOIN {modules} m ON cm.module = m.id
                JOIN {modules} md ON md.id = cm.module
                WHERE ch.course = ?
                ',
            array($course->courseid)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $checklists;


    $sql = "SELECT cm.*, m.name, md.name AS modname $sectionfield
              FROM {course_modules} cm
                   JOIN {modules} md ON md.id = cm.module
                   JOIN {".$modulename."} m ON m.id = cm.instance
                   $sectionjoin
             WHERE m.id = :instance AND md.name = :modulename
                   $courseselect";

    return $DB->get_record_sql($sql, $params, $strictness);
}


/**
 * Get the progress for each checklist.
 *
 * @param object[] $checkl checklist
 * @return object[]
 */
function local_qmul_dashboard_get_user_ChecklistProgress($checklist) {

    global $DB, $USER, $CFG;

    if (empty($checklist)) {
        return false;
    }


    try {
        $items = $DB->get_records_sql('SELECT * FROM {checklist_item}
                WHERE checklist = ? AND hidden = 0 AND itemoptional = 0',
            array($checklist->id)
        );
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    if (empty($items)) {
        return false;
    }

    // Get all the checks for this user for these items.
    list($isql, $params) = $DB->get_in_or_equal(array_keys($items), SQL_PARAMS_NAMED);
    $params['userid'] = $USER->id;
    $checkmarks = $DB->get_records_select('checklist_check', "item $isql AND userid = :userid", $params, 'item',
        'item, usertimestamp, teachermark');

    // If 'groupmembersonly' is enabled, get a list of groupings the user is a member of.
    $groupings = !empty($CFG->enablegroupmembersonly) && !empty($CFG->enablegroupmembersonly);
    $groupingids = array();
    if ($groupings) {
        $sql = "
            SELECT gs.groupingid
              FROM {groupings_groups} gs
              JOIN {groups_members} gm ON gm.groupid = gs.groupid
             WHERE gm.userid = ?
            ";
        $groupingids = $DB->get_fieldset_sql($sql, array($USER->id));
    }

    //$checklist = null;

    // Loop through all items, counting those visible to the user and the total number of checkmarks for them.
    foreach ($items as $item) {
        //$checklist = $checkl[$item->checklist];
        if ($groupings && $checklist->autopopulate) {
            // If the item has a grouping, check against the grouping memberships for this user.
            if ($item->grouping && !in_array($item->grouping, $groupingids)) {
                continue;
            }
        }

        if (!isset($checklist->totalitems)) {
            $checklist->totalitems = 0;
            $checklist->checked = 0;
        }

        $checklist->totalitems++;

        if (isset($checkmarks[$item->id])) {
            if (!$checklist->teacheredit) {
                if ($checkmarks[$item->id]->usertimestamp) {
                    $checklist->checked++;
                }
            } else {
                if ($checkmarks[$item->id]->teachermark) {
                    $checklist->checked++;
                }
            }
        }
    }

    if (empty($checklist->totalitems)) {
        $checklist->percent = 0;
    } else {
        $checklist->percent = $checklist->checked * 100.0 / $checklist->totalitems;
    }

    return $checklist;
}

/**
 *  Returns user course categories
 */
function local_qmul_dashboard_getUserSchools()
{
    global $DB,$CFG;

    if (is_siteadmin())
    {
        require_once($CFG->libdir.'/coursecatlib.php');
        $categories = coursecat::make_categories_list('moodle/category:manage');

    }
    else
    {
        $courses = enrol_get_my_courses();

        $categories = array();

        foreach ($courses as $course) {
            $categories[] = $course->category;

        }

        $categories = array_unique($categories);

        $catIds = '';

        foreach ($categories as $id) {

            if($catIds!==''){
                $catIds .= ',';
            }

            $catIds .= $id;

        }

        try {
            $cats = $DB->get_records_sql("SELECT * FROM {course_categories}
                WHERE id IN ($catIds) ORDER BY sortorder ASC"
            );
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

        if (empty($cats)) {
            return false;
        }

        $categories = array();

        foreach ($cats as $category) {

            $category->name = local_qmul_dashboard_getCategoryName($category->path);//join(' / ', $namechunks);

            $categories[$category->id]= $category->name;
        }
    }

    return $categories;
}


/**
 * @param object $data
 * @return object checklists
 */
function local_qmul_dashboard_addViewSettings($data) {

    global $DB,$USER;

    $results = array();

    if(isset($data->activities)){

        switch($data->activities){
            case 'activities_panel':
                $itemtype = 'panel';
                break;
            case 'activities_histograms':
                $itemtype = 'modal';
                break;
        }
        $itemname = $data->activities;
        $messName = get_string('activitysummarymess', 'local_qmul_dashboard');
        $results[] = local_qmul_dashboard_insertSettings($data,$itemname,$itemtype,$messName);

    }

    if(isset($data->facebook) and $data->facebook){
        $messName = get_string('facebookmess', 'local_qmul_dashboard');
        $results[] = local_qmul_dashboard_insertSettings($data,'facebook','block', $messName);

    }

    return $results;
}

/**
 * @param object $data
 * @return object checklists
 */
function local_qmul_dashboard_insertSettings($data, $itemname, $itemtype,$messName) {

    global $DB,$USER;

    // Check if setting exists
    $result = $DB->get_records('qmul_dashboard_settings',
        array(
            'category'=>$data->category,
            'itemname'=>$itemname,
            'itemtype'=>$itemtype
        )
    );

    // Get category
    try {
        $category = $DB->get_record_sql('SELECT path FROM {course_categories}
                        WHERE id = ? ORDER BY sortorder ASC',
            array($data->category)
        );
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    //get full path name
    $categoryName = local_qmul_dashboard_getCategoryName($category->path);

    // Insert new setting
    if(empty($result)){

        $record = new stdClass();
        $record->category = $data->category;
        $record->itemname = $itemname;
        $record->itemtype = $itemtype;
        $record->userid = $USER->id;

        try {
            $DB->insert_record('qmul_dashboard_settings', $record);
        } catch (Exception $e) {
            return array('status'=>false,'mess'=>'Caught exception: '. $e->getMessage(). "\n",'type'=>'danger');
        }

        return array(
            'status'=>true,
            'mess'=>'"'.$categoryName.' '.$messName.'" '.get_string('addmess','local_qmul_dashboard'),
            'type'=>'success');

    }else{
        return array(
            'status'=>false,
            'mess'=>'"'.$categoryName.' '.$messName.'" '.get_string('existmess','local_qmul_dashboard'),
            'type'=>'warning');
    }

}


/**
 * @param $categoryPath
 * @return string
 */
function local_qmul_dashboard_getCategoryName($categoryPath){

    global $DB;

    $path = explode('/',$categoryPath);

    unset($path[0]);
    $namechunks = array();

    foreach ($path as $parentid) {

        try {
            $parent = $DB->get_record_sql('SELECT name FROM {course_categories}
                        WHERE id = ? ORDER BY sortorder ASC',
                array($parentid)
            );
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

        $namechunks[] = $parent->name;
    }
    $name = join(' / ', $namechunks);

    return $name;
}

/**
 * @return array|bool
 * @throws coding_exception
 */
function local_qmul_dashboard_getViewSettings(){

    global $DB;

    // Get category
    try {
        $settings = $DB->get_records('qmul_dashboard_settings');
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


    if(!empty($settings)){

        foreach ($settings as $setting) {

            // Get category
            try {
                $category = $DB->get_record_sql('SELECT path FROM {course_categories}
                        WHERE id = ? ORDER BY sortorder ASC',
                    array($setting->category)
                );
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

            //get full path name
            $setting->category = local_qmul_dashboard_getCategoryName($category->path);

            // Get user
            try {
                $user = $DB->get_record('user', array('id'=>$setting->userid));
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

            $setting->user = $user->firstname.' '.$user->lastname;

            switch($setting->itemname){
                case 'activities_panel':
                case 'activities_histograms':
                    $setting->itemname =  get_string('activitysummarymess', 'local_qmul_dashboard');;
                    break;
                case 'facebook':
                    $setting->itemname =  get_string('facebookmess', 'local_qmul_dashboard');;
                    break;
            }

            $setting->itemname = str_replace('"', '', $setting->itemname);
            $setting->itemname = ucfirst($setting->itemname);

        }

        return $settings;
    }

    return false;


}

function local_qmul_dashboard_getUserContextViewSettings($categories){

    //get all the settings from the database
    $settings = local_qmul_dashboard_getViewSettings();

    $usercontextviewsettings = array();
    foreach ($settings as $setting){
        $needle = htmlspecialchars($setting->category);
        if(in_array($needle, $categories)){
            array_push($usercontextviewsettings, $setting);
        }
    }

    return $usercontextviewsettings;

}


/**
 * @param $id
 */
function local_qmul_dashboard_removeSetting($id){

    global $DB;

    // Get category
    try {

        $result = $DB->execute('DELETE FROM {qmul_dashboard_settings} WHERE id = ?',array($id));
        return $result;

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return false;
}


/**
 * This function gets all the settings from the qmul_dashboard_setting table 
 * and then checks if this category is affected by any of the settings.
 * 
 * 
 * @param $category id
 * @return array
 */
function local_qmul_dashboard_checkCourseModulePermissions($category)
{

    global $DB;

    $results = array();

    // Get all categories and look if course category or parent category exist
    try {
        $settings = $DB->get_records('qmul_dashboard_settings');
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    foreach ($settings as $setting) {

        // category exist
        if((int)$setting->category === (int)$category){
            $results[] = $setting;

        }else{ //check category parents

            $parent = $DB->get_record_sql('SELECT path FROM {course_categories}
                        WHERE id = ? ORDER BY sortorder ASC',
                array($category)
            );

            $path = explode('/',$parent->path);

            foreach ($path as $parentid) {
                if((int)$parentid===(int)$setting->category){
                    $results[] = $setting;
                    break;
                }
            }
        }
    }

    return $results;

}


function local_qmul_dashboard_buildViewPermissions($permissionSettings){

    $viewSettings =  array();
    foreach ($permissionSettings as  $permissionSetting ) {
        // manually check for all defined permissions
        // currently is activities panel and histograms
        switch($permissionSetting->itemname){
            case'activities_panel':
                $viewSettings['gradepanel'] = true;
                break;
            case'activities_histograms':
                $viewSettings['histograms'] = true;
                break;
        }

    }

    return $viewSettings;
}




function local_qmul_dashboard_renderCategory($category,$depth,$headerDefined,$gradeView = 'Students',$course)
{

    $RENDER = '';

    if(!$headerDefined){
        $headerDefined = true;
    }

    $catFullname = '';
    $catFinalgrade = null;
    $catId = '';
    $renderCategory = false;
    if (is_array($category) && !empty($category)) {
        $catFullname = $category['category']->fullname;

        $catId = $category['category']->id;

        if(isset($category['category']->finalgrade)){
            $catFinalgrade  = $category['category']->finalgrade;
        }

        if($category['category']->hidden=='0'){
            $renderCategory  = true;
        }

    } else if (!is_array($category)) {
        $catFullname = $category->fullname;

        $catId = $category->id;

        if(isset($category->finalgrade)){
            $catFinalgrade  = $category->finalgrade;
        }

        if($category->hidden=='0'){
            $renderCategory  = true;
        }
    }

    // Table headers
    if($renderCategory){
        if($catFullname !== "?") {
            $RENDER .= html_writer::start_tag('tr');

            $span = 'span12';
            $colspan = 4;
            if (isset($catFinalgrade)) {
                $span = 6;
                $colspan = 1;
                $colspan2 = 3;
            }

            $dash = '';
            for ($i = 1; $i <= $depth; $i++) {
                $dash .= '- ';
            }

            $RENDER .= html_writer::tag(
                'td',
                $dash . $catFullname,
                array(
                    'class' => $span . ' bg-info',
                    'colspan' => $colspan,
                    'style' => 'padding-left:' . (20 + (int)$depth * 10) . 'px;'

                )
            );


            if (isset($catFinalgrade)) {

                //                $grade_item = grade_item::fetch(array('iteminstance' => $catId));//grade_item::fetch_course_item($grade->gradeid);
                //                $formattedgrade = grade_format_gradevalue($catFinalgrade, $grade_item);

                $RENDER .= html_writer::tag(
                    'td',
                    '',//'Total: '.$formattedgrade,
                    array(
                        'class' => $span . ' bg-info',
                        'colspan' => $colspan2
                    )
                );
            }


            $RENDER .= html_writer::end_tag('tr');
        }

        /*
         * Renders grades here
         */
        if(is_array($category) && isset($category['grades'])) {
            foreach ($category['grades'] as $grade) {
                if ($gradeView === 'Students') {
                    $RENDER .= local_qmul_dashboard_renderGrades($grade, $depth,$course);
                } else {
                    $RENDER .= local_qmul_dashboard_renderTeacherGrades($grade, $depth,$course);
                }
            }
        }
    }



    //subcategories
    foreach ($category as $key => $subcategory) {
        if(is_array($subcategory) && $key!=='grades'){
            // First print Category
            $RENDER .= local_qmul_dashboard_renderCategory($subcategory,$depth+1,$headerDefined,$gradeView,$course);
        }
    }

    return $RENDER;
}

/**
 * Helper method that checks grade visibility
 *
 * @param $grade
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_qmul_dashboard_isGradeItemVisible($grade)
{
    global $DB,$USER;

    $display = true;
    $coursecontext = context_course::instance($grade->courseid);
    /*
     * Assuming editing teachers have the capability to viewhidden
     */
    $canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);

    /*
     * Check the various parameters form the grade, grade item 
     */
    
    if ((($grade->itemihidden == 1 or ($grade->itemihidden != 0 and $grade->itemihidden > time()))
            || $grade->hidden == 1 or ($grade->hidden != 0 and $grade->hidden > time())) && !$canviewhidden) {
        return false;
    }

    
    /*
     * Check the activity settings by getting the standard course mod info 
     * 
     */
    $course = $DB->get_record('course', array('id' => $grade->courseid));
    $modinfo = new course_modinfo($course, $USER->id);
    $instances = $modinfo->get_instances_of($grade->itemmodule);
    if (!empty($instances[$grade->iteminstance])) {
        $cm = $instances[$grade->iteminstance];

        if ($cm->uservisible) {
            $display = true;
        } else if ($cm->availableinfo) {
            $display = false;
        } else {
            $display = false;
        }
    }

    /*if ($grade->itemtype === 'category' || $grade->itemtype === 'course') {
        $display = true;
    }

    if ($canviewhidden) {
        $display = true;
    }*/

    return $display;
}


/**
 * Return turnitin Comments
 *
 * @param $grade
 * @return array|null
 */
function local_qmul_dashboard_turnitinComments($grade){
    global $DB,$USER;

    $comment = null;

    try {
//        $comment = $DB->get_record_sql(
//            'SELECT * FROM {turnitintool_comments} as c
//            INNER JOIN {turnitintool_submissions} as s ON s.id = c.submissionid
//            WHERE  s.turnitintoolid = ?  AND s.userid = ?',
//            array($grade->instance,$USER->id)
//        );

        $comments = $DB->get_records_sql(
            'SELECT c.*,  u.firstname,u.lastname FROM {turnitintool_comments} as c
            INNER JOIN {turnitintool_submissions} as s ON s.id = c.submissionid
            INNER JOIN {user} as u ON u.id = c.userid
            WHERE  s.turnitintoolid = ? ',
            array($grade->instance)
        );
        
        if(!empty($comments)){
            $comment .= '<h3>Turnitin Comments</h3>';
            foreach($comments as $comm){
                $comment .= html_writer::start_tag('div');
                $comment .= html_writer::tag('h5',
                    'From '.$comm->firstname .' '.$comm->lastname .' on '.userdate($comm->dateupdated)
            );
                $comment .= $comm->commenttext;
                /*$comment .= html_writer::start_tag('div');
                $comment .= $feedbackfiles;
                $comment .= html_writer::end_tag('div');*/
                $comment .= html_writer::end_tag('div');


//                $comment .= $comm->commenttext;
            }
        }
//        if($comment && null !== $comment->commenttext && !empty($comment->commenttext) && $comment->commenttext!==''){
//            return  '<h3>Turnitin Comments</h3>'. $comment->commenttext;
//        }

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $comment;
};


/**
 * Return turnitin required data
 *
 * @param $grade
 * @return array|null
 */
function local_qmul_dashboard_turnitinData($grade){
    global $DB,$USER;

    $data = null;

    try {
        $data = $DB->get_record_sql(
            'SELECT u.*,s.id as "submissionid", s.*, t.*
            FROM {turnitintool_users} as u
            INNER JOIN {turnitintool_submissions} as s ON s.userid = u.userid
            INNER JOIN {turnitintool} as t ON t.id = s.turnitintoolid
            WHERE  s.turnitintoolid = ?  AND s.userid = ?',
            array($grade->instance,$USER->id)
        );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $data;
};

/**
 * @param $cmid
 * @return bool
 */
function local_qmul_dashboard_is_tiiv2_assign($cmid){

    if(is_null($cmid)){
        return false;
    }

    $tiiv2plagiarism = new plagiarism_plugin_turnitin();
    $tiiv2settings =  $tiiv2plagiarism->get_settings($cmid);

    if(isset($tiiv2settings['use_turnitin'])){
       if ($tiiv2settings['use_turnitin'] == "1"){
            return $tiiv2settings;
        }
    }
    else{
        return false;
    }
}


/**
 * @param $cmid
 * @param $userid
 * @return array|null
 */
function local_qmul_dashboard_get_tii_plagairism_files($cmid, $userid){
    global $DB;

    $data = null;
    try{

       $data = $DB->get_records_sql(
           'SELECT * FROM {plagiarism_turnitin_files} where cm = ? AND userid = ?',
           array($cmid, $userid)
       );

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return $data;
}



/**
 * @param $submission
 * @return string
 */
function local_qmul_dashboard_render_tii_assign_grademarklink($submission){
    $feedback = get_string('grademarkfeedbacklabel', 'local_qmul_dashboard');
    $link = new moodle_url('/plagiarism/turnitin/extras.php', array('cmid' => $submission->cm, ));

    $icon = '<span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>';

    $output = html_writer::div($feedback . $icon,
        'pp_grademark_open scoreLink tii_tooltip grademark_' . $submission->externalid . '_' . $submission->cm . ' tooltipstered',
        array('id'=>$link));

    return $output;
}

/**
 * @param $submission
 * @return string
 */
function local_qmul_dashboard_render_tii_assign_simlink($submission){
    global $PAGE;

    $link = new moodle_url('/plagiarism/turnitin/extras.php', array('cmid' => $submission->cm, ));

    $scorecolour = local_qmul_dashboard_similarityscore_band($submission->similarityscore);

    $output = html_writer::div($submission->similarityscore . '% ',
        'tii_tooltip origreport_score score_colour score_colour_' . $scorecolour . ' tooltipstered');
    $output .= html_writer::div('',
        'launch_form origreport_form_' . $submission->externalid);

    $output = html_writer::div($output ,
        'box row_score pp_origreport pp_origreport_open  origreport_' . $submission->externalid . '_' . $submission->cm . ' ',
        array('id'=>$link));

    $output = html_writer::div($output ,
        'tii_links_container'
        );


    return $output;
}

/**
 * @param $similarityscore
 * @return float|int
 */
function local_qmul_dashboard_similarityscore_band($similarityscore){
    $band = $similarityscore / 10;
    $band = round($band, 0);
    $band = $band * 10;

    $acceptedvalues = [0,10,20,30,40,50,60,70,80,90,100];

    if(in_array($band, $acceptedvalues)){
        return $band;
    }
    else{
        return 0;
    }
}




/**
 * Return quiz feedback for grade
 *
 * @param $grade
 * @return array|null
 */
function local_qmul_dashboard_quizFeedback($grade){
    global $DB,$USER;

    $comment = null;

    try {
        // use courseid as first field to remove duplicates in php object.
        $feedbacks = $DB->get_records_sql(
            'SELECT * FROM {quiz_feedback} as f
            WHERE  f.quizid = ?',
            array($grade->iteminstance)
        );

        if(count($feedbacks)){
            foreach ($feedbacks as $index => $feedback) {
                if($grade->finalgrade >= $feedback->mingrade && $grade->finalgrade <= $feedback->maxgrade
                    && $feedback->feedbacktext!==''){
                    return  '<h3>Quiz Feedback</h3>'. $feedback->feedbacktext;
                }
            }
        }



    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


    return $comment;

};


// TODO: refactor this to make it more efficient. move this to notifications block
/*
 *
GET MAHARA NOTIFICATIONS
function get_mahara_content() {
    global $CFG, $USER, $DB;

    require_once($CFG->dirroot.'/mnet/xmlrpc/client.php');

    if($content !== NULL) {
        return$content;
    }

    if (!isset($config)) {
       $config = new StdClass;
    }
    if (!isset($config->limit)) {
       $config->limit = 10;
    }

    if (isset($config->title)) {
       $title =$config->title;
    }

    $content = new stdClass;
    $content->items = array();
    $content->icons = array();
    $content->footer = '';

    if (!is_enabled_auth('mnet')) {
        return $content;
    }

    $hosts = $DB->get_records_select('mnet_host', "id NOT IN (?, ?) AND deleted = 0", array($CFG->mnet_localhost_id, $CFG->mnet_all_hosts_id), 'wwwroot ASC');
    foreach($hosts as $host) {
        $request = new mnet_xmlrpc_client();
        $request->set_method('mod/mahara/rpclib.php/get_notifications_for_user');
        $request->add_param($USER->username);
        $request->add_param(array('feedback', 'groupmessage', 'institutionmessage', 'maharamessage', 'usermessage', 'viewaccess', 'watchlist'));
        $request->add_param($config->limit);
        $peer = new mnet_peer();
        $peer->set_id($host->id);
        // check if it is subscribing
        if ($request->send($peer) && $request->response['data']) {
            foreach ($request->response['data'] as $activity) {
               $content->items[] = '<a title="' . format_string($activity['subject']) . '" '
                    .'href="' . $CFG->wwwroot . '/auth/mnet/jump.php?hostid=' . $host->id . '&wantsurl=' . urlencode(substr($activity['url'], strlen($peer->wwwroot) + 1)) . '">'
                    .format_string($activity['subject']).'</a>';
            }
        }
        // Don't care about errors
    }

    if (empty($content->items)) {
        $content->items[] = get_string('norecentactivity', 'block_mahara_recent_activity');
    }

    return $content;
}
*/



function local_qmul_dashboard_hasUserAssignFeedbackFiles($userid, $assignid){
    global $DB;
    $results = $DB->get_record_sql(
        'SELECT  aff.id as assignfeedbackid, aff.assignment as assignmentid, aff.grade,  aff.numfiles
        FROM {assignfeedback_file} aff
        JOIN {assign_grades} ag ON aff.grade = ag.id
        WHERE  ag.userid = ? AND aff.assignment = ? AND aff.numfiles <> \'0\' ', array($userid, $assignid)
    );

    return $results;
}

function local_qmul_dashboard_getUserAssignFeedbackFiles($contextid, $gradeid){
    $fs = get_file_storage();
    $files = $fs->get_area_files($contextid, 'assignfeedback_file', 'feedback_files', $gradeid);
    return $files;
}

function   local_qmul_dashboard_RenderAssignFeedbackFiles($files)
{
    global $OUTPUT;
    $out = array();
    foreach ($files as $file) {
        $filename = $file->get_filename();

        if($filename!=='.'){
            $image = $OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));
            $url = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                'assignfeedback_file',
                'feedback_files',
                $file->get_itemid(),
                $file->get_filepath(),
                $filename,
                true
            );

            $out[] =  $image . html_writer::link($url, $filename);
        }
    }

    $br = html_writer::empty_tag('br');

    return  implode($br, $out);

}


function local_qmul_dashboard_getUserAssignGrade($assignid, $contextid, $userid){
    global $DB;

    try {

        $assignarearesults = $DB->get_record_sql(
            'SELECT  ga.id as areaid, ga.contextid, ga.component, ga.areaname, ga.activemethod
        FROM {grading_areas} ga
        WHERE  ga.component = \'mod_assign\' AND ga.contextid = ?', array($contextid)
        );
    }
    catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


    try {
         $assigngradesresults = $DB->get_record_sql(
             'SELECT  ag.id as assigngradeid, ag.assignment as assignid, ag.userid,  ag.grader, ag.grade
        FROM {assign_grades} ag
        WHERE  ag.userid = ? AND ag.assignment = ?', array($userid, $assignid)
         );
    }
    catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }



    if($assigngradesresults){
        $assigngrade = (object) array_merge((array)$assignarearesults, (array)$assigngradesresults);
    }else{
        $assigngrade = $assignarearesults;
    }

    return $assigngrade;

}



/*
 * This function returns the rubric with no grade info
 * Not required currently.
 * 
 * 
 */
function local_qmul_dashboard_renderRubricForm($context, $component, $area, $areaid, $grade){

    global $PAGE;

    $gradingformcontroller = new gradingform_rubric_controller($context, $component, $area, $areaid);


    if (!$gradingformcontroller->is_form_defined()) {
        throw new coding_exception('It is the caller\'s responsibility to make sure that the form is actually defined');
    }

    $defintion = $gradingformcontroller->get_definition();
    $criteria = $defintion->rubric_criteria;
    $options = $gradingformcontroller->get_options();
    $rubric = '';

    if (empty($options['alwaysshowdefinition']))  {
            // ensure we don't display unless show rubric option enabled
            return '';
     }
    $showdescription = $options['showdescriptionstudent'];

    //start rendering
    $output = $PAGE->get_renderer('gradingform_rubric');

    if ($showdescription) {
        $rubric .= $output->box($gradingformcontroller->get_formatted_description(), 'gradingform_rubric-description');
    }
    $rubric .= $output->display_rubric($criteria, $options, $gradingformcontroller::DISPLAY_PREVIEW_GRADED, 'rubric');


    return $rubric;

}


