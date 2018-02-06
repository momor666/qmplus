<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 19/07/2017
 * Time: 13:36
 * QM+ Activities reporting plugin
 * Author: v.sotiras@qmul.ac.uk Vasileios Sotiras
 * php 7 ammendments to array variable based access
 */

// defined('MOODLE_INTERNAL') || die;
// Connection constants
define('MEMCACHED_HOST', 'localhost');
define('MEMCACHED_PORT', 11211 );

$string_form_action = get_string('form_action','local_qm_activities');
$string_form_export_calendar = get_string('form_export_calendar','local_qm_activities');
$string_export_calendar = get_string('export_calendar','local_qm_activities');
$form_admin_ajax = get_string('form_admin_ajax','local_qm_activities');
$string_reporter = get_string('reporter','local_qm_activities');
$string_menu = get_string('menu','local_qm_activities');
$string_user = get_string('user','local_qm_activities');
$string_state = get_string('state','local_qm_activities');
$string_request = get_string('request','local_qm_activities');
$string_submissions = get_string('submissions', 'local_qm_activities');
$string_form_admin_ajax = get_string('form_admin_ajax','local_qm_activities');
$string_select_school = get_string('select_school','local_qm_activities');
$string_select_category = get_string('select_category','local_qm_activities');
$string_select_course = get_string('select_course','local_qm_activities');
$string_select_teacher = get_string('select_teacher','local_qm_activities');
$string_select_student = get_string('select_student','local_qm_activities');
$string_page_title = get_string('page_title','local_qm_activities');
$string_site_administrator = get_string('site_administrator','local_qm_activities');
$string_school_administrator = get_string('school_administrator','local_qm_activities');
$string_course_administrator = get_string('course_administrator','local_qm_activities');
$string_course_teacher = get_string('course_teacher','local_qm_activities');
$string_course_student = get_string('course_student','local_qm_activities');
$string_date_from = get_string('date_from','local_qm_activities');
$string_date_to = get_string('date_to','local_qm_activities');
$string_date_latest = get_string('date_latest','local_qm_activities');
$string_activity = get_string('activity','local_qm_activities');
$string_weight = get_string('weight','local_qm_activities') ;
$string_no_students_found = get_string('no_students_found','local_qm_activities');
$string_please_wait = get_string('please_wait','local_qm_activities');
$string_request_not_permitted = get_string('request_not_permitted','local_qm_activities');
$string_no_activities_found = get_string('no_activities_found','local_qm_activities');
$string_school = get_string('school','local_qm_activities');
$string_category = get_string('category','local_qm_activities');
$string_course = get_string('course','local_qm_activities');
$string_teacher = get_string('teacher','local_qm_activities');
$string_student = get_string('student','local_qm_activities');
$string_site_admin_options = get_string('site_admin_options','local_qm_activities');
$string_preparing_menus = get_string('preparing_menus','local_qm_activities');
$string_report_page_title = get_string('report_page_title','local_qm_activities');
$string_report_error = get_string('report_error','local_qm_activities');
$string_back_to_menu = get_string('back_to_menu','local_qm_activities');
$string_label_css = get_string('label_css','local_qm_activities');
$string_label_empty_css = get_string('label_empty_css','local_qm_activities');
$string_range_label_css = get_string('range_label_css','local_qm_activities');
$string_pending = get_string('pending','local_qm_activities');
$string_submitted = get_string('submitted','local_qm_activities');
$string_cohort = get_string('cohort','local_qm_activities');
$string_total = get_string('total','local_qm_activities');
$string_daily_activities = get_string('daily_activities','local_qm_activities');
$string_daily_courses = get_string('daily_courses','local_qm_activities');
$string_daily_load = get_string('daily_load','local_qm_activities');
$string_max_daily_submissions = get_string('max_daily_submissions','local_qm_activities');
$string_max_daily_activities = get_string('max_daily_activities','local_qm_activities');
$string_rel_daily_submissions = get_string('rel_daily_submissions','local_qm_activities');
$string_to_List = get_string('to_List','local_qm_activities');
$string_to_Page = get_string('to_Page','local_qm_activities');
$string_hide_List = get_string('hideList','local_qm_activities');
$string_show_List = get_string('showList','local_qm_activities');
$string_hide_grid = get_string('hide_grid','local_qm_activities');
$string_show_grid = get_string('show_grid','local_qm_activities');
$qm_activities_config = get_config('local_qm_activities');
$cache_time = 60 * ( isset($qm_activities_config->qm_activities_cache_minutes) &&
    (int) $qm_activities_config->qm_activities_cache_minutes > 0
        ? (int)$qm_activities_config->qm_activities_cache_minutes : 5 );
/* never include the config.php here */
/** @noinspection UntrustedInclusionInspection */
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->libdir.'/coursecatlib.php';
require_once $CFG->libdir . '/filelib.php' ;

function local_qm_activities_get_date_format( ){
    return  'd-M-Y H:i:s';
    /*
        $locale = Locale::getDefault();
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::TRADITIONAL, IntlDateFormatter::NONE);
        if ($formatter === null){
            throw new InvalidConfigException(intl_get_error_message());
        }

        return $formatter->getPattern();
    */
}
/**
 * Get the School where this course category originates from
 * @param $course_category_id Course Category ID
 *
 * @return mixed School
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_category_school($course_category_id){
    global $DB;
    $category = null;
    // get the root category for this course
    try{
        $top_categories = coursecat::get($course_category_id,IGNORE_MISSING );
        if( isset($top_categories) ){
            $parent_cats = $top_categories->get_parents();
            if(isset($parent_cats[0])){
                $category = $DB->get_record('course_categories',array('id' => $parent_cats[0]));
            }
        }
    } catch (Error $error){

    } catch (Exception $exception){

    } catch (Throwable $throwable){

    }
    return $category;
}


/**
 * @return array of module ids for the moodle installation
 *
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_calendar_activities_module_ids(){
    global $DB;
    $mod_ids = array();
    try {
        $activities = local_qm_activities_get_calendar_activities();
        foreach($activities as $name => $activity){
            $mod = $DB->get_record('modules',array('name'=> $name));
            if($mod) { $mod_ids[] = (int)$mod->id; }
        }
        if(count($mod_ids) > 1){
            sort($mod_ids);
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    return $mod_ids;
}

/**
 * @param $userid
 *
 * @return array of courses where a user is enrolled
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_user_courses($userid ){
    return enrol_get_users_courses( $userid );
}

/**
 * @param $course_id
 *
 * @return array of course modules registered as activities with due dates (set or not)
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_activities($course_id ){
    global $DB;
    $activities = array();
    try {
        $ids = implode(',',local_qm_activities_get_calendar_activities_module_ids() );
        $sql = '
SELECT cm.*, m.name as modname
FROM {modules} m
JOIN {course_modules} cm ON cm.module = m.id AND m.visible = 1 AND cm.course = ? AND m.id IN ('.$ids.')';
        $activities = $DB->get_records_sql($sql ,array($course_id));
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    return $activities;
}

/**
 * @param $teacher_id
 * @param bool $ids_only
 *
 * @return array of courses for a teacher
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_teacher_courses($teacher_id, $ids_only = false )
{
    global $DB;
    $courses = array();
    $cids = array();
    try {
        if ((int) $teacher_id > 0) {
            $sql = 'select distinct
             @c := ifnull(@c,0)+1 id
            , mu.id userid
            , mr.shortname teachertype
            , mu.firstname
            , mu.lastname
            , mu.email
            , mu.phone1
            , mu.idnumber
            , mu.skype 
            , co.fullname
            , co.id courseid
    from {role_assignments} ra 
    join {role} mr on ra.roleid = mr.id and ra.userid = ' . ((int)$teacher_id) . ' and mr.archetype like \'%teacher%\'
    join {context} rc on rc.id = ra.contextid and contextlevel = ' . CONTEXT_COURSE . '
    join {course} co on co.id = rc.instanceid
    join {user} mu on mu.id = ra.userid and mu.deleted = 0 and mu.suspended = 0
    , (select @c := 0) cntrs
    order by co.fullname, co.id
    ';
            $courses = $DB->get_records_sql($sql);
            foreach ($courses as $course) {
                $cids[] = $course->courseid;
            }
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    if($ids_only){
        return $cids;
    } else {
        return $courses;
    }
}

/**
 * @param $course_id
 *
 * @return array of teachers for a course
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_teachers($course_id , $id_only = false ){
    global $DB;
    $teachers = array();
    try{
        if((int)$course_id > 0){
            if( $id_only ){
                $sql = 'select distinct
          @c := ifnull(@c,0)+1 id
		, mu.id userid
		, mr.shortname teachertype
		, mu.firstname
		, mu.middlename
        , mu.lastname
        , mu.username
        , mu.email
        , mu.phone1
        , mu.idnumber
        , mu.skype 
from {role_assignments} ra 
join {role} mr on ra.roleid = mr.id and mr.archetype like \'%teacher%\'
join {context} rc on rc.id = ra.contextid and contextlevel = '. CONTEXT_COURSE .'
join {course} co on co.id = rc.instanceid and rc.instanceid = '.( (int) $course_id ).'
join {user} mu on mu.id = ra.userid and mu.deleted = 0 and mu.suspended = 0
, (select @c := 0) cntrs
order by mr.archetype desc, mu.id';
                $teachers = $DB->get_records_sql($sql);
                $ids = array();
                foreach($teachers as $teacher){
                    $ids[] = (int)$teacher->userid;
                }
                $teachers = $ids;
            } else {
                $sql = 'select distinct
          @c := ifnull(@c,0)+1 id
		, mu.id userid
		, mr.shortname teachertype
		, mu.firstname
		, mu.middlename
        , mu.lastname
        , mu.username
        , mu.email
        , mu.phone1
        , mu.idnumber
        , mu.skype 
        , co.fullname coursename
        , co.id courseid
from {role_assignments} ra 
join {role} mr on ra.roleid = mr.id and mr.archetype like \'%teacher%\'
join {context} rc on rc.id = ra.contextid and contextlevel = '. CONTEXT_COURSE .'
join {course} co on co.id = rc.instanceid and rc.instanceid = '.( (int) $course_id ).'
join {user} mu on mu.id = ra.userid and mu.deleted = 0 and mu.suspended = 0
, (select @c := 0) cntrs
order by mr.archetype desc, mu.id
';
                $teachers = $DB->get_records_sql($sql);
            }
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    return $teachers;
}

/**
 * @param $teacher_id
 * @param null $from
 * @param null $to
 *
 * @return array|null get an activities calendar for a teacher
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_teacher_calendar($teacher_id , $from = null, $to = null ){
    return local_qm_activities_get_courses_calendar( local_qm_activities_get_teacher_courses($teacher_id, true)  , $from , $to  );
}

/**
 * @param $course_id
 * @param $activity_name
 * @param $instance
 *
 * @return mixed  a scale registered for a specific activity
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_module_grade_item($course_id, $activity_name , $instance ){
    global $DB;
    $grade_item = null;
    try{
        if((int)$course_id > 0 && $activity_name > '' && (int)$instance > 0){
            $sql = "SELECT id,aggregationcoef,aggregationcoef2 FROM {grade_items} gi WHERE gi.courseid = $course_id AND gi.itemtype = 'mod' AND gi.iteminstance = $instance AND gi.itemmodule = '$activity_name' ORDER BY gi.id DESC";
            $grade_item = $DB->get_records_sql($sql);
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    // return the first record if found
    return reset($grade_item);
}


/**
 * @return int maximum amount of calendar eventsper request
 *             used internally for processing the presentations and the identifiers of the events
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 *
 */
function local_qm_activities_get_calendar_max_counter(){
    // a million entries is more than adequate for any calendar reporting
    // it is used to distinct calendar activities
    return 1000000;
}

/**
 * @param array $modules
 * @param null $from
 * @param null $to
 *
 * @return null/array of calendar activities for specific course modules (within a time frame)
 * * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_module_events(array $modules , $from = null, $to = null ){
    global $DB;
    $myStudyCalendar = null;
    $max_counter = local_qm_activities_get_calendar_max_counter();
    $activities = local_qm_activities_get_calendar_activities();
    $cal_counter = 0;
    $course_teachers = array();
    try {
        // set_time_limit(-1);
        # apache_reset_timeout();
        ignore_user_abort(true);
        foreach($modules as $module){
            $mod = $DB->get_record($module->modname,array('id'=>$module->instance));
            $from = null;
            $to = null;

            if(isset($mod->course) &&! isset($course_teachers[$mod->course])){
                $course_teachers[$mod->course] = local_qm_activities_get_course_teachers($mod->course);
            }
            // here is a special case where the time frames come from linked table entries
            if($module->modname == 'forumng'){
                $min = 0;
                $max = 0;
                $forum_discussions = $DB->get_records('forumng_discussions',array('forumgnid'=>$mod->id));
                foreach($forum_discussions as $forum_discussion){
                    if( (int) $forum_discussion->$activities[$module->modname]['from'] > 0
                        && (int) $forum_discussion->$activities[$module->modname]['from'] < $min ){
                        $min = $forum_discussion->$activities[$module->modname]['from'] ;
                    }
                    if( (int)$forum_discussion->$activities[$module->modname]['to'] > $max  ){
                        $max = $forum_discussion->$activities[$module->modname]['to'] ;
                    }
                }
                $mod->$activities[$module->modname]['from'] = $min;
                $mod->$activities[$module->modname]['to'] = $max;
            }
            if(isset($mod->course)){
                $course = get_course($mod->course);
                $students = count_enrolled_users( context_course::instance($mod->course),'',0);
            }
            // start with empty record/array
            $CalEvent = array();
            if( isset($activities[$module->modname]) ){
                // get the marker id and weight factors for the activity from the grade items
                $grade_book_item = local_qm_activities_get_course_module_grade_item( $course->id, $module->modname , $module->instance );
                if($grade_book_item /*->aggregationcoef*/){
                    $CalEvent['weight'] = number_format($grade_book_item->aggregationcoef ,5);
                } else {
                    $CalEvent['weight'] = 'N/A';
                }
                // check if the activity is a physical assignment
                $CalEvent['physical'] = 0;
                if($module->modname == 'assign'){
                    // get relevant records for the activity
                    $assgns = $DB->get_records('assign_plugin_config', array( 'assignment' => $module->instance ),'','*');
                    // hard coding the type of physical assignment plugin name
                    foreach($assgns as $assgn){
                        if( $assgn
                            && $assgn->id > 0
                            && $assgn->plugin == 'qmcw_coversheet'
                            && $assgn->subtype == 'assignsubmission'
                            && $assgn->name == 'enabled'
                            && $assgn->value == '1'
                        ){
                            $CalEvent['physical'] = 1;
                        }
                    }
                }

                $CalEvent['from'] = 'N/A';
                $CalEvent['to'] = 'N/A';
                $CalEvent['latest'] = 'N/A';

                if( isset($module->modname)  ){
                    $modname = $module->modname;
                    if( isset($activities[$modname])){
                        $from = $activities[$modname]['from'];
                        if( isset($mod->$from) ){
                            if( (int) $mod->$from > 0 ){
                                $from = (int)$mod->$from;
                                $CalEvent['from'] = $from; // $mod->$activities[$module->modname]['from'];
                            } else {
                                $from = null;
                            }
                        }
                        $to = $activities[$modname]['to'];
                        if( isset($mod->$to) ){
                            if( (int)($mod->$to) > 0){
                                $to = $mod->$to;
                                $CalEvent['to'] = $to;
                            } else {
                                $to = null;
                            }
                        }
                        $latest = $activities[$modname]['latest'];
                        if( isset($mod->$latest) ){
                            if( (int)($mod->$latest) > 0){
                                $CalEvent['latest'] = (int) $mod->$latest;
                            }
                        }

                    }
                }

            }
            // test if you have to add the event to the reported list based on the dead line time point
            $add_event = false;
            if( isset($activities[$module->modname]['to'])){
                if(!isset($from) && !isset($to)){
                    // no datetime borders defined, just add it
                    $add_event = true;
                }
                if( isset($from) && (int)$from <= (int)($CalEvent['to']) && (! isset($to)) ){
                    // after the start without ending point
                    $add_event = true;
                }
                if( isset($to) && (int)$to >= (int)($CalEvent['to']) && (! isset($from)) ){
                    // before the ending point without start
                    $add_event = true;
                }
                if(isset($from) && isset($to) && (int) $from <= (int)($CalEvent['to']) && (int)$to >= (int)($CalEvent['to'])){
                    // between the start and the ending point
                    $add_event =  true;
                }
                if( (int) $CalEvent['to'] == 0 ){
                    // this is an undefined due date. better to add it in order to be viewed and validated
                    // add all activities added last year with no due date
                    if( date('Y',$course->timecreated) -1 < date('Y',min((int)$from,(int)$to)) ){
                        $add_event = true;
                    } else {
                        $add_event = false;
                    }
                }
            }

            if ($add_event && isset($activities[$module->modname]['to'])) {
                if (isset($mod) && isset($mod->course)) {
                    if (isset($course_teachers[$mod->course])) {
                        $CalEvent['teachers'] = $course_teachers[$mod->course];
                    }
                } else {
                    $CalEvent['teachers'] = null;
                }
                #$CalEvent['teachers'] =  ? $course_teachers[$mod->course]: null;
                $CalEvent['teacher_url'] = isset($CalEvent['teachers'][1]->userid) ? new moodle_url('/user/profile.php', array('id' => $CalEvent['teachers'][1]->userid)) : null;
                $CalEvent['coursename'] = isset($course->fullname) ? $course->fullname : '';
                $CalEvent['courseid'] = isset($course->id) ? $course->id : 0;
                $CalEvent['module'] = isset($module->modname) ? $module->modname : '';
                $CalEvent['moduleid'] = isset($mod->id) ? $mod->id : 0;
                $CalEvent['instance'] = isset($module->instance) ? $module->instance : 0;
                $CalEvent['name'] = isset($mod->name) ? $mod->name : '';
                $ccat = isset($course->category) ? $DB->get_record('course_categories', array('id' => $course->category)) : null;
                $CalEvent['categoryid'] = isset($course->category) ? $course->category : 0;
                $CalEvent['category'] = isset($ccat) ? $ccat->name : null;
                $school = isset($ccat) ? local_qm_activities_get_course_category_school($course->category) : null;
                $CalEvent['school'] = isset($school) ? $school->name : null;
                $CalEvent['schoolid'] = isset($school) && isset($school->id) ? $school->id : null;
                $CalEvent['students'] = $students;
                $CalEvent['cmid'] = isset($module->id) ? $module->id : 0;
                $CalEvent['module_url'] = new moodle_url('/mod/' . $CalEvent['module'] . '/view.php', array('id' => $CalEvent['cmid']));
                $CalEvent['course_url'] = new moodle_url('/course/view.php', array('id' => $CalEvent['courseid']));
                $CalEvent['school_url'] = isset($school) ? new moodle_url('/course/index.php', array('categoryid' => $CalEvent['schoolid'])) : null;
                $CalEvent['category_url'] = isset($ccat) ? new moodle_url('/course/index.php', array('categoryid' => $CalEvent['categoryid'])) : null;
                $CalEvent['students_url'] = isset($course->id) ? new moodle_url('/enrol/users.php', array('id' => $course->id)) : null;
                $CalEvent['grades_url'] = isset($course->id) ? new moodle_url('/grade/edit/tree/index.php', array('id' => $course->id)) : null;

                if($add_event){
                    // cater for more than one event in the same date time by adding random value plus a counter to the index
                    $myStudyCalendar[ (int)($CalEvent['to']) * $max_counter + (float)microtime()* $max_counter + ++$cal_counter] = $CalEvent;
                }
            }
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    if(count($myStudyCalendar)>1){
        ksort($myStudyCalendar);
    }
    return $myStudyCalendar;
}

/**
 * @param $course_ids
 * @param null $from
 * @param null $to
 *
 * @return array|null get a course array of calendar activities
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_courses_calendar($course_ids , $from = null, $to = null ){
    $StudyCalendar = array();
    try {
        if( gettype($course_ids) == 'array' && count($course_ids) > 0){
            foreach($course_ids as $course_id){
                if((int)$course_id > 0 ){
                    $activities = local_qm_activities_get_course_activities( $course_id );
                    $new_events = local_qm_activities_get_module_events( $activities , $from , $to );
                    if(isset($new_events)){
                        $StudyCalendar += $new_events;
                    }
                }
            }
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    if(count( $StudyCalendar) > 1){
        ksort( $StudyCalendar );
    }
    return $StudyCalendar;
}

/**
 * @param $category_id
 * @param null $from
 * @param null $to
 *
 * @return array|null get a category array of calendar activities
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_category_calendar($category_id , $from = null , $to = null){
    return local_qm_activities_get_courses_calendar( local_qm_activities_get_category_course_ids( $category_id ) , $from , $to );
}


/**
 * @param $course_id
 * @param null $from
 * @param null $to
 *
 * @return array|null get a course array of calendar activities
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_calendar($course_id , $from = null, $to = null  ){
    $StudyCalendar = array();
    try {
        if((int)$course_id > 0){
            $activities = local_qm_activities_get_course_activities( $course_id  );
            $new_events = local_qm_activities_get_module_events( $activities , $from , $to );
        }
        if( isset( $new_events ) ){
            $StudyCalendar += $new_events;
        }
        if(count($StudyCalendar)>1){
            ksort($StudyCalendar);
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    return $StudyCalendar;
}


/**
 * @param $userid
 * @param null $from
 * @param null $to
 *
 * @return array|null  get a user calendar array of activities
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_user_calendar($userid , $from = null, $to = null ){
    $StudyCalendar = array();
    try {
        $courses = local_qm_activities_get_user_courses( $userid );
        foreach( $courses as $course){
            $activities = local_qm_activities_get_course_activities( $course->id );
            $new_events = local_qm_activities_get_module_events( $activities , $from , $to  );
            if(isset($new_events)){
                $StudyCalendar += $new_events;
            }
        }
        if(count($StudyCalendar)>1){
            ksort($StudyCalendar);
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    return $StudyCalendar;
}

/**
 * Get the categories administered by the user
 * @param $userid
 *
 * @return array get categories administered by a user
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_admin_categories($userid ){
    global $DB;
    $school_admin_categories = array();
    $school_admins_sql = 'select 
 @c:= ifnull(@c ,0)+1 id
, a.userid
, cc.id categoryid
, ifnull(cc.name,\'QM+\') as \'category\' 
from {role_assignments} a 
join {role} r on r.id = a.roleid and r.shortname = \'administrator\' 
join {context} cx on cx.id = a.contextid 
join {user} u on u.id = a.userid 
left join {course_categories} cc on cc.id = cx.instanceid
, (select @c := 0 ) dta
where a.userid = :userid and  cc.id is not null order by 4 ';
    $school_admins = $DB->get_records_sql($school_admins_sql,array('userid' => $userid ) );
    if($school_admins){
        foreach($school_admins as $school_admin_rec){
            if( (int)$school_admin_rec->userid == (int)$userid ){
                $school_admin = true;
                $school_admin_categories[] = array('id' => $school_admin_rec->categoryid,'category'=>$school_admin_rec->category);
            }
        }
    }
    return $school_admin_categories;
}

/**
 * @param $user_id
 *
 * @return bool id the user is a school administrator
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 * /
function is_school_admin($user_id){
return ( count(get_admin_categories( $user_id )) > 0);
}
 */
/**
 * @param $userid
 *
 * @return bool if the user is a site administrator
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_is_an_admin($userid ){
    global $DB;
    $isadmin = false;
    $admins = get_admins();
    foreach( $admins as $admin ) {
        if ( $userid == $admin->id ) {
            $isadmin = true;
            break;
        }
    }
    return $isadmin;
}

/**
 * @return array get root categories (Schools)
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_school_categories(){
    global $DB;
    return $DB->get_records('course_categories',array('parent'=>0),'id','name,id');
}

/**
 * @auth
 * @param $school_cat_id
 *
 * @return \course_in_list[]|null
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_category_course_ids($school_cat_id ){
    $allcourses = null;
    try {
        $school_cat = coursecat::get( $school_cat_id );
        if($school_cat){
            $allcourses = $school_cat->get_courses(array('recursive' => true , 'idonly'=>true ));
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    if(count($allcourses) > 0 ){
        asort($allcourses);
    }
    return $allcourses;
}


/**
 * @param $category_id
 * @param bool $id_only
 * @param bool $only_visible
 * @param int $depth
 *
 * @return array of subcategories in records or in ids only
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_category_subcategories($category_id , $id_only = false , $only_visible = true , $depth = 0 ){
    global $DB;
    $cat_array = array();
    $children = null;
    try {
        if( gettype($depth) != 'integer' || (int)$depth < 0){
            $depth = 0;
        }
        $cat = $DB->get_record('course_categories',array('id' => (int)$category_id));
        if($cat){
            $cat_depth = (int)$cat->depth;
            $parent = (int)$cat->parent;
            $sql = 'select ';
            if($id_only){
                $sql .= 'id';
            } else {
                $sql .= 'id, name, idnumber, description, descriptionformat, parent, sortorder, coursecount, visible, visibleold, timemodified, depth, path, theme';
            }
            $sql .= ' from {course_categories} where path like :sql_like ';
            if( $only_visible ){
                $sql .= ' and visible = 1 ';
            }
            if( $depth > 0 ){
                $sql .= ' and depth <= '.($cat_depth + $depth);
            }
            if( $parent > 0) {
                $sql_like = '%/'.$parent .'/'.$cat->id .'/%' ;
            } else {
                $sql_like = '/'.$cat->id.'/%';
            }
            $children = $DB->get_records_sql($sql,array('sql_like'=>$sql_like));
        }
        if( $children ){
            foreach( $children as $id => $child ){
                if( $id_only ){
                    $cat_array[] = (int)$child->id;
                } else {
                    $cat_array[] = $child;
                }
            }
            asort($cat_array);
        }
    } catch (Exception $exception){

    } catch (Throwable $throwable){

    } catch (Error $error){

    }
    return $cat_array;
}

/**
 * @param $course_ids an array if course IDs
 * @return array of related teacher IDs for all the course IDs
 *
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_courses_teachers($course_ids , $id_only = false ){
    $teachers = array();
    if(gettype($course_ids) == 'array' && count($course_ids) > 0 && (int)$course_ids[0] > 0 ){
        /** @noinspection ForeachSourceInspection */
        foreach( $course_ids as $course_id){
            $course_teachers = local_qm_activities_get_course_teachers( $course_id , $id_only  );
            if( count( $course_teachers ) > 0){
                if( $id_only){
                    $teachers = array_merge ($teachers , $course_teachers);
                } else {
                    foreach($course_teachers as $course_teacher){
                        if(! in_array( $course_teacher , $teachers)) {
                            $teachers[] = $course_teacher;
                        }
                    }
                }
            }
        }
        if( $id_only && count( $teachers ) > 0 ){
            $teachers = array_unique($teachers);
        }
    }
    return $teachers;
}

/**
 * @param $category_id
 *
 * @return array of teacher in a category of courses
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_categories_teachers($category_id ){
    $teachers = array();
    if((int)$category_id > 0 ){
        try {
            $category = coursecat::get( $category_id );
            $allcourses = $category->get_courses(array('recursive' => true , 'idonly'=>true ));
            foreach($allcourses as $cid => $course){
                $course_teachers = local_qm_activities_get_course_teachers($cid);
                if(count($course_teachers) > 0 ){
                    foreach ($course_teachers as $tid => $teacher){
                        if(! in_array($tid, $teachers)){
                            $teachers[$tid] = $teacher;
                        }
                    }
                    asort($teachers);
                }
            }
        } catch (Exception $exception){

        } catch (Throwable $throwable){

        } catch (Error $error){

        }
    }
    return $teachers;
}

/**
 * @param $StudyCalendar
 * This is an array of calendar entries with all associated data for each activity.
 * This code creates a table which is handled by a javascript library called DataTable for jQuery.
 * This gives to the table the ability to change its row size, filter by search, change page,
 * single sorting and multi-sorting via SHIFT Click actions.
 *
 * @return null|string HTML / Javascript
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_calendar_table($StudyCalendar ){
    global $USER,$string_school, $string_course, $string_student, $string_teacher,
           $string_activity, $string_weight, $string_date_from, $string_to_Page, $string_to_List,
           $string_hide_List, $string_show_List, $string_date_to, $string_date_latest,
           $string_submissions, $string_no_activities_found;
    $html = null;
    $is_staff = ( local_qm_activities_is_an_admin($USER->id) || local_qm_activities_is_teacher($USER->id) || local_qm_activities_is_school_admin($USER->id) || local_qm_activities_is_course_admin($USER->id) );
    if(count($StudyCalendar) > 0){
        $randomid = 'cal_table' .(float)microtime() * local_qm_activities_get_calendar_max_counter();
        $triangle = '<span style="font-weight: 100; float:left; left:15px; margin:2.0px; height: 0; width: 3px; border-left: 10px solid transparent; border-right: 10px solid transparent; border-bottom: 18.0px solid red;color:white;">!</span>';
        $dateFormat = "j M Y H:i";
        $action_message = 'Contact';
        $html = '';
        $html_table_head = '';
        $html_table_body = '';
        // report the calendar entries
        if(count($StudyCalendar) > 0){
            // define the table header
            $html_table_head .= '<thead><tr><th>'.$string_school.'</th>'.
                '<th>'.$string_course.'</th>'.
                '<th>'.$string_student.'</th>'.
                '<th>'.$string_teacher.'</th>'.
                '<th>'.$string_activity.'</th>'.
                '<th>'.$string_weight.'</th>'.
                '<th>'.$string_date_from.'</th>'.
                '<th>'.$string_date_to.'</th>'.
                '<th>'.$string_date_latest.'</th></tr></thead>';
            // define the table body
            $html_table_body .= '<tbody>';
            foreach ($StudyCalendar as $index => $StudyEvent) {
                // one table row at a time
                $html_row  = '<tr>';
                // add the school
                $html_row .= '<td>'.html_writer::link( $StudyEvent['school_url'] , $StudyEvent['school'],array('target'=>'_blank')) .'</td>' ;
                // add the course and the course category
                $html_row .= '<td>'.html_writer::link( $StudyEvent['course_url'] , $StudyEvent['coursename'],array('target'=>'_blank')).
                    '<br />'.html_writer::link( $StudyEvent['category_url'] , $StudyEvent['category'],array('target'=>'_blank')).'</td>';
                // add the student cohort
                if($is_staff){
                    $students_work = local_qm_activities_get_course_module_submissions($StudyEvent['module'] ,$StudyEvent['instance'] );
                    $staff_link = '<a target="_blank" href="'.$string_submissions.'?id='.$StudyEvent['instance'].'&module='.
                        $StudyEvent['module'].'">'.( isset($students_work['undone']) ? count($students_work['undone']) : '').
                        '/'.( isset($students_work['done']) ? count($students_work['done']) : '' ).'</a><br />';
                } else {
                    $staff_link = '';
                }
                $html_row .= '<td>' . $staff_link . html_writer::link( $StudyEvent['students_url'] ,$StudyEvent['students'] ,array('target'=>'_blank')) .'</td>';
                // add the teacher
                $html_row .= '<td>'.(isset($StudyEvent['teacher_url'])
                        ? html_writer::link( $StudyEvent['teacher_url'] , $StudyEvent['teachers'][1]->firstname.' '.$StudyEvent['teachers'][1]->lastname,array('target'=>'_blank'))
                        :'&nbsp;Contact<br />'.html_writer::link( $StudyEvent['school_url'] , $StudyEvent['school'],array('target'=>'_blank'))).'</td>';
                // add the activity and if it is a physical submission
                $html_row .= '<td>'.html_writer::link( $StudyEvent['module_url'], $StudyEvent['name'],array('target'=>'_blank')).
                    '<br />' . ( $StudyEvent['physical'] == 1 ? '<strong>Physical Submission</strong>':'') .'</td>';
                // add the weight of the activity
                $html_row .= '<td>'. html_writer::link( $StudyEvent['grades_url'] , $StudyEvent['weight'],array('target'=>'_blank') ) .'</td>';
                // add the date from
                $html_row .= '<td data-order="'.(int)$StudyEvent['from'].'">' ;
                if($StudyEvent['from'] != 'N/A'){
                    $html_row .= date($dateFormat,$StudyEvent['from']) ;
                } else {
                    $html_row .= $triangle;
                }
                $html_row .= '</td>';
                // add the date to
                if($StudyEvent['to'] != 'N/A'){
                    $html_row .= '<td data-order="'.(int)$StudyEvent['to'].'">'. date($dateFormat,$StudyEvent['to']) . '</td>';
                } else {
                    $html_row .= '<td data-order="'.(int)$StudyEvent['to'].'">Contact<br/>';
                    if(isset($StudyEvent['teachers'][1]->firstname)){
                        $html_row .= html_writer::link( $StudyEvent['teacher_url'] , $StudyEvent['teachers'][1]->firstname.' '.$StudyEvent['teachers'][1]->lastname.'<br/>'.$StudyEvent['teachers'][1]->phone1, array('target'=>'_blank')) ;
                    } else {
                        $html_row .= html_writer::link( $StudyEvent['school_url'] , $StudyEvent['school'],array('target'=>'_blank'));
                    }
                    $html_row .= '</td>';
                }
                $html_row .= '<td data-order="'.(int)$StudyEvent['latest'].'">';
                // add the date latest
                if($StudyEvent['latest'] != 'N/A'){
                    $html_row .= date($dateFormat,$StudyEvent['latest']);
                } else {
                    $html_row .= $triangle;
                }
                // close the table row
                $html_row .= '</td></tr>';
                // ad the row to the table
                $html_table_body .= $html_row ;
            }
            $html_table_body .= '</tbody>';
        }
        // create the table
        $html = '<style>'
            .' #'.$randomid.'_hideList {cursor: pointer; cursor: hand;} '
            .' #'.$randomid.'_showList {cursor: pointer; cursor: hand;} '
            .' #'.$randomid.'_toList {cursor: pointer; cursor: hand;} '
            .' #'.$randomid.'_toPage {cursor: pointer; cursor: hand;} '
            .'</style>'
            .'<span style="font-weight:bold; background-color:rgb(175, 238, 238);" id="'.$randomid.'_hideList">'.$string_hide_List.'</span> '
            .'<span style="display:none; font-weight:bold; background-color:rgb(175, 238, 238);" id="'.$randomid.'_showList">'.$string_show_List.'</span> '
            .'<span style="font-weight:bold; background-color:rgb(175, 238, 238);" id="'.$randomid.'_toList">'.$string_to_List.'</span> '
            .'<span style="display:none; font-weight:bold; background-color:rgb(175, 238, 238);" id="'.$randomid.'_toPage">'.$string_to_Page.'</span> <br/>'
            .'<table style="width:100%;" id="'.$randomid.'">'. $html_table_head . $html_table_body . '</table>';
        $html .= '
<link id="DataTableScriptCss" rel="stylesheet" type="text/css" href="./css/jquery.dataTables.min.css">
<script id="DataTableScript" src="./js/jquery.dataTables.min.js"></script>
<script>
function activateTable'.$randomid.'() {
    if(! $.fn.DataTable.isDataTable( "#'.$randomid.'" ) ){
        $("#'.$randomid.'").DataTable( {
            order:[[7,"asc"],[1,"asc"],[3,"asc"]],
            columnDefs: [ {
                targets: [ 0 ],
                orderData: [ 0, 1 ]
            }, {
                targets: [ 1 ],
                orderData: [ 1, 0 ]
            }, {
                targets: [ 4 ],
                orderData: [ 4, 0 ]
            } ]
        });
    } 
    $("#'.$randomid.'_filter label").css("font-weight","Bold");
    $("#'.$randomid.'_filter input").css("background-color","#AFEEEE");
    $("#'.$randomid.'_length").css("background","#AFEEEE");
}
$(document).ready(function(){
    setTimeout(function(){activateTable' . $randomid . '();},100);
    $("#'.$randomid.'_toList").on("click", function() {
        $("#'.$randomid.'").DataTable().destroy();
        $("#'.$randomid.'_toPage").show();
        $("#'.$randomid.'_toList").hide();
    })
    $("#'.$randomid.'_toPage").on("click", function() {
        activateTable'.$randomid.'();
        $("#'.$randomid.'_toList").show();
        $("#'.$randomid.'_toPage").hide();

    })
    $("#'.$randomid.'_hideList").on("click",function(){
        $("#'.$randomid.'").hide();
        $("#'.$randomid.'_hideList").hide();
        $("#'.$randomid.'_showList").show();
        $("#'.$randomid.'_toList").hide();
        $("#'.$randomid.'_toPage").hide();
        $("#'.$randomid.'_length").hide();
        $("#'.$randomid.'_filter").hide();
        $("#'.$randomid.'_info").hide();
        $("#'.$randomid.'_paginate").hide();
    });
    $("#'.$randomid.'_showList").on("click",function(){
        $("#'.$randomid.'").show();
        $("#'.$randomid.'_showList").hide();
        $("#'.$randomid.'_hideList").show();
        if( $.fn.DataTable.isDataTable( "#'.$randomid.'" ) ){
            $("#'.$randomid.'_toList").show();
            $("#'.$randomid.'_toPage").hide();
            $("#'.$randomid.'_length").show();
            $("#'.$randomid.'_filter").show();
            $("#'.$randomid.'_info").show();
            $("#'.$randomid.'_paginate").show();
        } else {
            $("#'.$randomid.'_toList").hide();
            $("#'.$randomid.'_toPage").show();
        }
    });
    $("#'.$randomid.'_toPage").hide();
    $("#'.$randomid.'_showList").hide();
});
</script>
    ';
    } // else { echo $string_no_activities_found; }
    return $html;
}

/**
 * @param $date_time
 * @param null $span one from array('hour',DEFAULT 'day','week','month','year','acyear')
 * @author Vasileios Sotiras v.sotiras@qmul.ac.uk
 * @return array of from/to/division length as in array('from'=>null,'to'=>null,'div'=>null)
 *               to deternime the begin/end for a date/time in
 *               hour, day, locale week, month, year and academic year
 */
function local_qm_activities_get_timestamp_range($date_time , $span = null ){
    $range = array( 'from' => null, 'to' => null, 'div' => null);
    $myDate = null;
    if(is_null($span)){
        $span = 'day';
    }
    if(gettype($date_time)=='array'){
        if(null !== ($date_time['year']) && null !== ($date_time['mon']) && null !== ($date_time['mday']) && null !== ($date_time['hours'])){
            $myDate = $date_time['year'].'-'.$date_time['mon'].'-'.$date_time['mday'].' '.$date_time['hours'].':00:00';
        }
    } elseif(gettype($date_time)=='string'){
        try{
            if (\DateTime::createFromFormat('Y-m-d H:i:s', $date_time) !== FALSE) {
                $myDate = \DateTime::createFromFormat('Y-m-d H:i:s', $date_time)->format("Y-m-d H:i:s");
            }
        } catch (\Throwable $throwable){

        } catch (\Exception $exception){

        }
    } elseif(gettype($date_time) == 'integer'){
        $myDate = date('Y-m-d H:i:s',$date_time);
    } elseif( gettype($date_time) == 'object' && get_class($date_time) == 'DateTime' ){
        $myDate = \DateTime::createFromFormat('Y-m-d H:i:s', $date_time);
    }

    if(gettype($myDate)=='string'){
        $myUnixTS = strtotime($myDate);
    } elseif(gettype($myDate)=='integer'){
        $myUnixTS = $myDate;
    }
    if( in_array( $span , array('hour','day','week','month','year','acyear') ) && gettype($myUnixTS) == 'integer' ) {
        switch ($span){
            case 'hour':
                $div = (60*60);
                $range['from']  = (int)( $myUnixTS / $div ) * $div ;
                $range['to']    = (int)( $myUnixTS / $div ) * $div + $div - 1 ;
                break;
            case 'day':
                $div = 86400;
                $range['from']  = (int)(( $myUnixTS )/ $div ) * $div - date('Z',$myUnixTS) ;
                $range['to']    = strtotime('+1 day -1 second',$range['from'] );
                $div = $range['to'] - $range['from'];
                break;
            case 'week':
                $range['from'] = mktime(0,0,0,date('m',$myUnixTS),date('d',$myUnixTS),date('Y',$myUnixTS));
                if(date('w',$range['from']) > 1 ){ # 1 here implies =>  get_first_day_of_week_locale()->fdow
                    $range['from'] = strtotime('last '.local_qm_activities_get_first_day_of_week_locale()->name,$range['from']);
                }
                $range['to']    = strtotime('+1 week -1 second',$range['from']);
                $div = $range['to'] - $range['from'];
                break;
            case 'month':
                $range['from']  = mktime(0,0,0, date('m',$myUnixTS),1,date('Y',$myUnixTS));
                $range['to']    = strtotime('+1 month -1 second ',$range['from']);
                $div = $range['to'] - $range['from'];
                break;
            case 'year':
                $range['from']  = mktime(0,0,0,1,1,date('Y',$myUnixTS));
                $range['to']    = strtotime('+1 year -1 second',$range['from']);
                $div = $range['to'] - $range['from'];
                break;
            case 'acyear':
                if(date('m',$myUnixTS) < 9 ){
                    $range['from']  = mktime(0,0,0,9,1,date('Y',$myUnixTS)-1);
                } else {
                    $range['from']  = mktime(0,0,0,9,1,date('Y',$myUnixTS));
                }
                $range['to'] = strtotime('+1 years -1 second',$range['from']);
                $div = $range['to'] - $range['from'];
                break;
            default:
                $div = null;
                $range['from']  = null;
                $range['to']    = null;
                break;
        }
        $range['div'] = $div;
    }
    return $range;
}

/**
 * @param null $optional_date
 *
 * @return array begin/end unix time stamps for the academic year for a date
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_term_date_range($optional_date = null){
    $range = array();
    //$d = new Date();
    // $offset = $d.getTimezoneOffset();
    if(is_null($optional_date) ){ $optional_date = getdate(); }
    if( local_qm_activities_is_date($optional_date) ){
        if(gettype($optional_date) == 'array'){
            $optional_date = date("Y-M-d h:i:s",$optional_date);
        } elseif(gettype($optional_date == 'string')){
            $optional_date = getdate(strtotime($optional_date));
        }

    }
    $current_month = $optional_date['mon'];
    if($current_month < 9){
        if((int)$optional_date['year'] > 1970){
            $from   = (string)($optional_date['year']-1).'-09-01 00:00:00';
        } else {
            $from   = '1970-01-01 00:00:00';
        }
        $to     = $optional_date['year'].'-08-31 23:59:59 ';
    } else {
        $from   = $optional_date['year'].'-09-01 00:00:00';
        $to     = (string)($optional_date['year']+1).'-08-31 23:59:59';
    }
    return array('from'=>max(strtotime($from),0),'to'=>max(strtotime($to),0));
}


/**
 * @param $string
 *
 * @return bool if the passed value is a date or not
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 *         requires php-intl, libinti18n
 */
function local_qm_activities_is_date($string){
    $res = false;
    if(gettype($string)=='string') {
        $t = strtotime($string);
        $m = date('m', $t);
        $d = date('d', $t);
        $y = date('Y', $t);
        $res = checkdate($m, $d, $y);
    } elseif(gettype($string)=='array'){
        if(isset($string['mon']) && isset($string['year']) && isset($string['mday'])){
            $ret = checkdate($string['mon'],$string['mday'],$string['year']);
        }
    } else {
        $res = false;
    }
    return $res;
}

/**
 * @return \stdClass first day of the locale week
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_first_day_of_week_locale(){
    $locale_first_day_of_week = new stdClass();
    $locale_first_day_of_week->fdow = (IntlCalendar::createInstance(NULL,'en-GB')->getFirstDayOfWeek() );
    $locale_first_day_of_week->name = date('l', strtotime("Saturday +{$locale_first_day_of_week->fdow} days"));
    return $locale_first_day_of_week;
}

/**
 * Rescale a value within a range of numeric values and return a scaled background color
 * and a foreground contrasted color (black or white)
 *
 * @param int $load
 * @param int $min_value
 * @param int $max_value
 *
 * @return array of foreground and background colours
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_scaled_colors($load = 0 , $min_value = 0, $max_value = 255 ){
    $qm_activities_settings = get_config('local_qm_activities');
    $colors = array('fg'=>'#FFFFFF','bg'=>'#000000');
    $max_color_rate = 255;
    if((int)$min_value < (int)$max_value){
        $min_value = (int)$min_value;
        $max_value = (int)$max_value;
        $load = max( $min_value ,(int)$load ) ;
        $load = min( $max_value , (int)$load );
        $rate_color = ( ($load - $min_value)/($max_value - $min_value)) * $max_color_rate ;
        if($qm_activities_settings->qm_activities_heatmap_method == 'cyan-red'){
            $bg_color = '#'.substr('0' . dechex( $rate_color ) , -2 , 2 )
                .''. str_repeat (substr('0'.dechex( max(( $max_color_rate - $rate_color ),0) ),-2,2),2);
        } elseif($qm_activities_settings->qm_activities_heatmap_method == 'blue-red'){
            $bg_color = '#'.substr('0' . dechex( $rate_color ) , -2 , 2 )
                .'00'. str_repeat (substr('0'.dechex( max(( $max_color_rate - $rate_color ),0) ),-2,2),1);
        } else {
            // black
            $bg_color = '#000000';
        }
        $fg_color = ( hexdec($bg_color) > 0xffffff / 2 ? '#000000' : '#FFFFFF');
        $colors = array('fg'=>$fg_color,'bg'=>$bg_color);
    }
    return $colors;
}

/**
 * @param null $EventsList
 * @param null $from
 * @param null $to
 *
 * @return string HTML table of an Activities Calendar
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_calendar_grid($EventsList = null , $from = null, $to = null){
    global $string_no_activities_found, $string_date_from, $string_date_to, $string_cohort, $string_total,
           $string_daily_activities, $string_daily_courses, $string_daily_load,$string_hide_grid,$string_show_grid,
           $string_max_daily_submissions, $string_max_daily_activities, $string_rel_daily_submissions;
    $max_counter = local_qm_activities_get_calendar_max_counter();
    $random_id = 'cal_table_grid_' .(float)microtime() * $max_counter;
    $html = '';
    $html .= '<span id="'.$random_id.'_hideGrid" style="font-weight:bold;background-color:rgb(175, 238, 238);">'.$string_hide_grid.'</span> ';
    $html .= '<span id="'.$random_id.'_showGrid" style="display:none;font-weight:bold;background-color:rgb(175, 238, 238);">'.$string_show_grid.'</span> <br/>';
    $html .= '<style>'
        .' #'.$random_id.'_hideGrid {cursor: pointer; cursor: hand;} '
        .' #'.$random_id.'_showGrid {cursor: pointer; cursor: hand;} '
        .'</style>';
    if(count($EventsList) > 0){
        $total_activities = 0;
        $total_load = 0;
        if(is_null($from) || is_null($to)){
            $range = local_qm_activities_get_timestamp_range( time() , $span = 'acyear' );
            if(is_null($from)){
                $from =  $range['from'];
            }
            if(is_null($to)){
                $to = $range['to'];
            }
        }
        // get the first weekday for the locale in the range
        $firstWeekday = strtotime('last '.local_qm_activities_get_first_day_of_week_locale()->name,$from);
        // get the last day for the locale in the range
        $lastWeekday = strtotime('next '.local_qm_activities_get_first_day_of_week_locale()->name,$to);
        $lastWeekday = strtotime('-1 second',$lastWeekday);
        $html .= '<style>
#'.$random_id.' td {
    border: 1px dotted blue;
    height: 2em;
    text-align: center;
    vertical-align:top;
}
#'.$random_id.' th {
    border: 1px solid blue;
    height: 2em;
}
</style>'.PHP_EOL;
        $html .= '<table id="'.$random_id.'" style="width:100%; border: 1px solid blue;"><thead>';

        // make week days row
        $weekdays_row = '<tr>';
        for($w = 0 ;$w < 7; $w++){
            $weekdays_row .= '<th style="width:calc(100% / 7 )">'.date('l', strtotime(local_qm_activities_get_first_day_of_week_locale()->name." +$w days")).'</th>';
        }
        $weekdays_row .= '</tr>';

        // add weekdays row to the header
        $max_load = 0;
        $max_tasks = 0;
        $cells = array();
        for($day = $firstWeekday; $day < $lastWeekday; $day = strtotime('+1 day', $day)){
            $dailyTasks = 0;
            $dailyLoad = 0;
            $dailyCourses = array();
            $td_html = '';
            // depending on the contents, the table data item will start plain or with style defined at the end
            // of the investigation of the day's activities processing
            $td_html .= '<span style="font-size: smaller;">'.date('d-M-Y' , $day).'</span><br/>' ;

            /** @noinspection ForeachSourceInspection */
            foreach($EventsList as $key => $Event){
                /** @noinspection TypeUnsafeComparisonInspection */
                if( date('d-M-Y' , (int)($key / $max_counter ) ) == date('d-M-Y' , $day ) ){
                    $td_html .=  html_writer::link( $Event['module_url'], ' &#x1f310; '
                        ,array('style'=>'font-size:1em!important;color:white;'
                        ,'title'=>$Event['name']."\r".$Event['coursename']
                                .(isset($Event['teachers'][1])? "\r(".$Event['teachers'][1]->firstname.' '.$Event['teachers'][1]->lastname.')':'')
                                ."\r".$Event['category']
                                ."\r".$Event['school']."\r"
                                .$string_cohort.' #'.$Event['students']
                        ,'target'=>'_blank')
                    );
                    if(! in_array($Event['courseid'],$dailyCourses)){
                        $dailyCourses[] = $Event['courseid'];
                    }
                    $dailyTasks++;
                    $dailyLoad += (int)$Event['students'];
                    $total_activities += $dailyTasks;
                    $total_load += $dailyLoad;
                    unset($EventsList[$key]);
                }
            }
            // here we define the table data styling, based on contents
            $td_style = '';
            if( $dailyTasks > 0 ){
                $td_html .= '<br/> '.$string_daily_activities.':'.$dailyTasks;
                $td_html .= '<br/> '.$string_daily_courses.':'.count($dailyCourses);
                $td_html .= '<br/> '.$string_daily_load.':'.$dailyLoad;
                $td_style .= 'font-weight:bold;';
            }
            if(date('d' , $day ) == 1){
                $td_style .= 'border-left: 3px solid blue;border-top: 3px solid blue; border-collapse:separate;';
            }
            // close the day item
            $td_html = '<td style="'.$td_style.'">' . $td_html . '</td>';

            // save the maximum daily load and tasks
            $max_load = max($max_load, $dailyLoad);
            $max_tasks = max($max_tasks,$dailyTasks);

            // add the cell to the array
            $cells[] = array('html'=>$td_html,'dl'=>$dailyLoad,'dt'=>$dailyTasks);
        }
        // create a key row for the cell colours
        $keyRow = local_qm_activities_make_calendar_grid_key_row( $keyRowCells = 21 , $max_load , $min_value = 0, $max_value = 100 );
        $html .= $keyRow.$weekdays_row.'</thead><tbody>';

        // create counters for days and weeks
        $days_counter = 0;
        $weeks_counter = 0;
        $table_rows = '';
        #$table_rows .= $keyRow;
        foreach($cells as $cell){
            if($days_counter == 0){
                $table_rows .= '<tr>';
            }
            if((int)$cell['dl'] > 0 && $max_load > 0 ){
                $rate_color = local_qm_activities_get_scaled_colors( (int)$cell['dl']  , $min_value = 0, $max_load );
                $bg_color = $rate_color['bg'];
                $fg_color = $rate_color['fg'];
                $style = 'style="background-color: '.$bg_color.';color: '.$fg_color.';" ';
                $cell['html'] = str_replace('<td ','<td '.$style, $cell['html']);
            }
            $table_rows .= $cell['html']."\n";
            $days_counter++;
            // check if you need to close the week/row
            if( $days_counter == 7 ){
                $table_rows .= '</tr>'."\n";
                $days_counter = 0;
                $weeks_counter++;
                // occasionally add the scale for the heatmap
                if($weeks_counter == 20 ){
                    #$table_rows .= $keyRow.$weekdays_row;
                    $weeks_counter = 0;
                }
            }
        }
        $table_rows .= '</tbody><tfoot>'. $weekdays_row .'</tfoot></table>';
        $html_notes = '<div id="'.$random_id.'_footNote" style="font-weight:bold;">'
            . $string_date_from.date('l d-m-Y H:i:s',$firstWeekday ).'<br/>'
            . $string_date_to.date('l d-m-Y H:i:s',$lastWeekday ).'<br/>'
            . $string_max_daily_submissions.':' .$max_load
            . '<br/>'.$string_max_daily_activities.':'.$max_tasks
            . '<br/>'.$string_total.$string_daily_activities.':'.$total_activities
            . '<br/>'.$string_total.$string_daily_load.':'.$total_load
            . '<br/>'.'</div>';
        $html .= $html_notes . $table_rows;
        $html .= '
        <script>
            $(document).ready(function(){
                // noinspection JSAnnotator
                $("#' .$random_id.'_hideGrid").on("click",function(){
                    $("#' . $random_id . '").hide();
                    $("#' .$random_id.'_hideGrid").hide();      
                    $("#' .$random_id.'_footNote").hide();      
                    $("#' .$random_id.'_showGrid").show();
                            
                });
                $("#'.$random_id.'_showGrid").on("click",function(){
                    $("#'.$random_id.'").show();
                    $("#' .$random_id.'_hideGrid").show();
                    $("#' .$random_id.'_footNote").show();        
                    $("#' .$random_id.'_showGrid").hide();        
                });
                $("#' .$random_id.'_showGrid").hide();
            });
        </script>
        ';
    } else {
        $html .= $string_no_activities_found;
    }
    return $html;
}

/**
 * @param $course_id
 *
 * @return array of students for a specific course
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_students($course_id , $id_only = true ){
    global $DB,$cache_time;
    $students = array();
    $students_array = false;
    if((int)$course_id > 0 ){
        try {
            // Connection creation
            $cacheAvailable = false;
            if(extension_loaded('Memcache') ){
                $memcache = new Memcache;
                @$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
            }
            // check the cache first
            if ($cacheAvailable == true) {
                $cacheKey = 'students_in_course_' . $course_id ;
                $students_array = $memcache->get($cacheKey);
            }

            $context = context_course::instance( $course_id );
            if(! $students_array){
                $students_in_course_sql = 'SELECT DISTINCT u.id userid, u.firstname, u.middlename , u.lastname , u.username
, concat_ws(" ",u.firstname, u.middlename , u.lastname) student
 , '.$course_id.' course_id
FROM {role_assignments} ra
JOIN {role} ro ON ro.id  = ra.roleid AND ro.archetype LIKE "%student%" 
JOIN {user} u  ON ra.contextid = ' . $context->id . ' and ra.userid = u.id';
                $students_array = $DB->get_records_sql($students_in_course_sql);
                if($cacheAvailable == true && count($students_array) > 0){
                    $memcache->set($cacheKey, $students_array, MEMCACHE_COMPRESSED, $cache_time);
                }
            }
            if(count($students_array) > 0 ){
                if($id_only){
                    foreach($students_array as $student ){
                        $students[] = $student->userid;
                    }
                } else {
                    $students = $students_array;
                }
            }
            if(count($students) > 0 ){
                asort($students);
                if($id_only){
                    $students = array_unique($students);
                } else {
                    $students = local_qm_activities_array_multi_unique( $students );
                }
            }
        } catch (Error $error){

        } catch (Throwable $throwable){

        } catch (Exception $exception){

        }
    }
    return $students;
}

/**
 * @param $course_ids an array if course IDs
 *
 * @return array of students belonging to a set of courses
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_courses_students($course_ids , $id_only = true ){
    global $DB;
    $students = array();
    $students_array = array();
    if( gettype($course_ids) == 'array' && count($course_ids) > 0 ){
        $students_in_course_sql = 'SELECT DISTINCT @c := ifnull(@c ,0) +1 id , ';
        if($id_only){
            $students_in_course_sql .= ' u.id userid';
        } else {
            $students_in_course_sql .= ' u.id userid, cox.instanceid courseid, u.firstname, u.middlename , u.lastname , u.username
, concat_ws(" ",u.firstname, u.middlename , u.lastname) student';

        }
        $students_in_course_sql .= ' FROM  (SELECT @c := 0) dta, {role_assignments} ra
JOIN {role} ro ON ro.id  = ra.roleid AND ro.archetype LIKE "%student%" 
JOIN {context} cox ON cox.instanceid IN ( '.implode(',', $course_ids ).' ) AND cox.contextlevel = '.CONTEXT_COURSE.' 
JOIN {user} u ON ra.contextid = cox.id and ra.userid = u.id';
        if($id_only){
            $students_in_course_sql .= ' ORDER BY 2 ';
        } else {
            $students_in_course_sql .= ' ORDER BY 3,2';
        }
        # echo $students_in_course_sql.'<br/>';
        $students_array = $DB->get_records_sql($students_in_course_sql);
    }
    if( count($students_array) > 0 && $id_only){
        foreach ($students_array as $student){
            $students[] = $student->userid;
        }
    } else {
        $students = $students_array;
    }
    if(count($students) > 0){
        asort($students);
    }
    return $students;
}

/**
 * @param $student_id
 * @param $course_id
 *
 * @return bool if a student belongs to a course
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_is_student_in_course($student_id , $course_id ){
    $is_in = false;
    if((int)$student_id > 0 && (int)$course_id > 0){
        $is_in = in_array( $student_id, local_qm_activities_get_course_students( $course_id ) );
    }
    return $is_in;
}

/**
 * is this user a teacher ?
 * @param $user_id
 *
 * @return bool if the user is a teacher to any course
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_is_teacher($user_id ){
    global $DB ;
    $is_teacher = false;
    $is_teacher_sql = 'select 
@c := ifnull(@c,0)+1 id, u.id userid, concat_ws(" ",u.firstname, u.middlename, u.lastname, u.username) courseadmin, co.id courseid, co.fullname coursename
from (select @c := 0) dta
, {user} u 
join {role_assignments} ra on ra.userid = u.id  and u.id = :userid
join {role} ro on ro.id = ra.roleid and ro.archetype like \'%teacher%\' 
join {context} cx on cx.id = ra.contextid
join {course} co on co.id = cx.instanceid
order by 2,4 asc';
    $is_teacher_records = $DB->get_records_sql($is_teacher_sql,array('userid' => (int)$user_id));
    if(count($is_teacher_records) > 0 && (int)$is_teacher_records[1]->userid == (int)$user_id){
        # echo 'we have a teacher here<br />';
        $is_teacher = true;
    }
    return $is_teacher;
}

/**
 * is this user a student ?
 * @param $user_id
 *
 * @return bool if the user is a student to any course
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_is_student($user_id ){
    global $DB ;
    $is_student = false;
    $is_student_sql = 'select 
@c := ifnull(@c,0)+1 id, u.id userid, concat_ws(" ",u.firstname, u.middlename, u.lastname, u.username) courseadmin, co.id courseid, co.fullname coursename
from (select @c := 0) dta
, {user} u 
join {role_assignments} ra on ra.userid = u.id  and u.id = :userid
join {role} ro on ro.id = ra.roleid and ro.archetype like \'%student%\' 
join {context} cx on cx.id = ra.contextid
join {course} co on co.id = cx.instanceid
order by 2,4 asc';
    $is_student_records = $DB->get_records_sql( $is_student_sql , array('userid' => (int)$user_id));
    if(count($is_student_records) > 0 && (int)$is_student_records[1]->userid == (int)$user_id){
        # echo 'we have a student here<br />';
        $is_student = true;
    }
    return $is_student;

}

/**
 * @param $multiArray
 *
 * @return array associative unique sorted elements
 */
function local_qm_activities_array_multi_unique($multiArray ){
    return array_unique( $multiArray, SORT_REGULAR);
}

/**
 * @param $user_id
 *
 * @return bool is a school administrator or not
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_is_school_admin($user_id ){
    global $DB;
    $is_school_admin = false;
    if( (int)$user_id > 0 ){
        try {
            $school_admins = local_qm_activities_get_school_admin_categories( $user_id );
            if( count( $school_admins ) > 0 ) {
                $is_school_admin = true;
            }
            unset($school_admins);
        } catch (Error $error){

        } catch (Throwable $throwable){

        } catch (Exception $exception){

        }
    }
    return $is_school_admin;
}

/**
 * @param $user_id
 *
 * @return array of schools administered by a user
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_school_admin_categories($user_id ){
    global $DB;
    $categories = array();
    if( (int)$user_id > 0 ){
        $sql = 'select @c:= ifnull(@c ,0)+1 id
, cc.id categoryid
, u.id userid, concat_ws(" ", u.firstname,u.middlename,u.lastname) schooladmin
, u.username
,ifnull(concat(cc.name),\'QM+\') as \'Category\' 
,concat_ws(\' \',r.archetype ,r.shortname) as \'Role\' 
from (select @c := 0 ) dta, {role_assignments} a 
join {role} r on r.id = a.roleid and r.shortname = \'administrator\' and a.userid = :user_id
join {context} cx on cx.id = a.contextid 
join {user} u on u.id = a.userid 
/*left */ join {course_categories} cc on cc.id = cx.instanceid
order by 2,3;';
        try {
            $categories = $DB->get_records_sql($sql,array('user_id' => (int)$user_id));
        } catch (Error $error){

        } catch (Throwable $throwable){

        } catch (Exception $exception){

        }
    }
    return $categories;
}

/**
 * Is this user a course administrator ?
 * @param $user_id
 *
 * @return bool if the user is acourse administrator on any course
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_is_course_admin($user_id ){
    $is_course_admin = false;
    if( (int) $user_id > 0  ) {
        $courses = local_qm_activities_get_course_admin_courses( $user_id );
        if(count($courses) > 0) {
            $is_course_admin = true;
        }
    }
    return $is_course_admin;
}

/**
 * @param $user_id
 *
 * @return array of courses administered by a user
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_admin_courses ($user_id , $id_only = false ){
    global $DB;
    $course_admin_courses = array();
    if( (int)$user_id > 0 ){
        try {
            $course_admin_courses_sql = 'select 
@c := ifnull(@c,0)+1 id, u.id userid, concat_ws(" ",u.firstname, u.middlename, u.lastname, u.username) courseadmin, co.id courseid, co.fullname coursename
from (select @c := 0) dta
, {user} u 
join {role_assignments} ra on ra.userid = u.id and u.id = :userid
join {role} ro on ro.id = ra.roleid and ro.shortname like \'%course_admin%\' 
join {context} cx on cx.id = ra.contextid
join {course} co on co.id = cx.instanceid
order by 2,4 asc;';
            $course_admin_courses = $DB->get_records_sql( $course_admin_courses_sql , array('userid' => $user_id ) );
            if($id_only){
                $ids = array();
                foreach($course_admin_courses as $course_admin_course){
                    $ids[] = (int) $course_admin_course->courseid;
                }
                if( count( $ids ) > 1 ){
                    asort($ids);
                    $ids = array_unique($ids);
                }
                $course_admin_courses = $ids;
            }
        } catch (Error $error){

        } catch (Throwable $throwable){

        } catch (Exception $exception){

        }
    }
    return $course_admin_courses;
}

/**
 * @return array of site wide teachers
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_site_teachers(){
    global $DB;
    $teachers = array();
    $site_teachers_sql = 'SELECT ra.userid , concat_ws(" ",u.firstname, u.middlename, u.lastname) teacher , u.username 
FROM {role_assignments} ra
JOIN {role} ro ON ro.id = ra.roleid AND ro.archetype LIKE \'%teacher%\'
JOIN {user} u ON u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0
JOIN {context} co ON co.id = ra.contextid AND co.contextlevel = '.CONTEXT_COURSE.'
JOIN {course} mc ON mc.id = co.instanceid AND mc.visible = 1
GROUP BY ra.userid,  u.firstname, u.lastname ORDER BY 2';
    try {
        $teachers = $DB->get_records_sql( $site_teachers_sql );
    } catch (Error $error){

    } catch (Throwable $throwable){

    } catch (Exception $exception){

    }
    return $teachers;
}

/**
 * @return array of all active site wide students
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_site_students(){
    global $DB;
    $students = array();
    try {
        $site_studens_sql = 'SELECT ra.userid , concat_ws(" ",u.firstname, u.middlename, u.lastname) student, u.username 
FROM {role_assignments} ra
JOIN {role} ro ON ro.id = ra.roleid AND ro.archetype LIKE \'%student%\'
JOIN {user} u ON u.id = ra.userid AND u.deleted = 0 AND u.suspended = 0
JOIN {context} co ON co.id = ra.contextid AND co.contextlevel = '.CONTEXT_COURSE.'
JOIN {course} mc ON mc.id = co.instanceid AND mc.visible = 1
GROUP BY ra.userid,  u.firstname, u.lastname ORDER BY 2';
        $DB->get_records_sql( $site_studens_sql );
    } catch (Error $error){

    } catch (Throwable $throwable){

    } catch (Exception $exception){

    }
    return $students;
}


/**
 * @param $dc
 * @param $from
 * @param $to
 * @param $string_range_label_css
 * @param $string_date_from
 * @param $string_date_to
 *
 * @return string HTML and JavaScript for Date Picker input fields
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_date_pickers($from, $to , $string_range_label_css , $string_date_from , $string_date_to ){
    static $dc = 0;
    $random = (string)time();
    $date_pickers = '<label for="datepicker'.$random.++$dc.'" style="'.$string_range_label_css.'">'.$string_date_from.'</label>'.
        '<input size="12" type="text" id="datepicker'.$random.$dc.'" name="from" value="'.date('d-M-Y',$from).'">'.
        '<label for="datepicker'.$random.++$dc.'" style="'.$string_range_label_css.'">'.$string_date_to.'</label>'.
        '<input size="12" type="text" id="datepicker'.$random.$dc.'" name="to" value="'.date('d-M-Y',$to).'">';
    $date_pickers .= '<script>
$( function() {
    $("#datepicker'.$random.($dc -1).'").datepicker({ dateFormat: "dd-M-yy" });
    $("#datepicker'.$random.($dc -1).'").datepicker("setDate", "'.date('d', $from ).'-'.( date('M',$from) ).'-'.date('Y',$from ).'");    
    $("#datepicker'.$random.($dc   ).'").datepicker({ dateFormat: "dd-M-yy" });
    $("#datepicker'.$random.($dc   ).'").datepicker("setDate", "'.date('d', $to ).'-'.( date('M',$to) ).'-'.date('Y',$to ).'");    
} );
  </script>';
    return $date_pickers;
}

/**
 * @param $data_array
 * @param $id
 * @param $form_action
 * @param $mode
 * @param $form_class
 * @param $no_choice
 * @param $label
 * @param $string_label_css
 * @param $from
 * @param $to
 * @param $string_range_label_css
 * @param $string_date_from
 * @param $string_date_to
 * @param bool $is_selector
 *
 * @return string HTML form  select and submit for the array values
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_selection_form($data_array , $id , $form_action , $mode , $form_class , $no_choice , $label , $string_label_css , $from, $to , $string_range_label_css , $string_date_from , $string_date_to , $is_selector = false ){
    global $string_label_empty_css;
    static $form_counter = 0;
    $random = time();
    $html = '';
    if(count($data_array)> 0 ){
        $html .= '<form action="' . $form_action . '"  id="Form_'.$random.++$form_counter.'" name="Form_'.$random.$form_counter.'" method="post">';
        $html .= local_qm_activities_get_date_pickers( $from, $to , $string_range_label_css , $string_date_from , $string_date_to );
        $html .= '<label style="'.( $label == '' ?  $string_label_empty_css : $string_label_css).'" for="id">'.$label.'</label>';
        $html .= '<select name="id" style="min-width:400px;" ' .( $is_selector ? ' id="AdminSelectCourseMenu" ': '').' >';
        if(count($data_array) > 1 ){
            $html .= '<option value="0" '.($id == 0 ? 'selected="selected"' : '').'>'.$no_choice.'</option>'.PHP_EOL;
        }
        foreach($data_array as $index => $data ){
            $html .= '<option value="'.(int)$index.'" '.( (int)$index == $id && $mode == $form_class ? 'selected="selected"' : '').'>'.$data.'</option>'.PHP_EOL;
        }
        $html .= '</select>';
        $html .= '<input type="submit" name="mode" value="'.$form_class.'">';
        $html .= '<br />';
        $html .= '</form>';
    }
    return $html;
}


/**
 * @param $req_user_id
 * @param $mode
 * @param $id
 * @param $from
 * @param $to
 *
 * @return bool if a user has the right to perform a calendar report request
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_check_user_request_permissions($req_user_id , $mode , $id , $from , $to ){
    global $DB;
    $allow_exec = false;
    $req_user = $DB->get_record('user', array('id' => $req_user_id));
    if(mydebg == true ){
        echo '<hr/>Requested by :' . fullname($req_user) . ' (' . $req_user->username . ') @ ' . date('d-M-Y H:i:s', time()) . ' ';
        echo $mode . ' #' . $id . ' From:' . date('d-M-Y H:i:s', $from) . ' To:' . date('d-M-Y H:i:s', $to) . '<br />';
    }
    if( (int)$req_user_id > 0 && (int)$id > 0 && in_array( $mode , array('school','category', 'course', 'teacher', 'student')) ) {
        // get the requestor's record
        // check if the requestor is a site administrator
        $isadmin = local_qm_activities_is_an_admin($req_user_id);
        if (isset($isadmin) && $isadmin) {
            // allow everything for the site administrators
            $requestor = 'siteadmin';
            $allow_exec = true;
            if (mydebg == true ) {
                echo "$requestor => $mode => $id <br />";
            }
        } else {
            $requestor = false;
            $category_ids = array();
            $course_ids = array();
            $teacher_ids = array();
            $student_ids = array();
            // check for school administrator
            if ( ! $allow_exec && local_qm_activities_is_school_admin( $req_user_id ) && in_array($mode, array('category', 'course', 'teacher', 'student'))) {
                $requestor = 'schooladmin';
                if (mydebg == true) {
                    echo "$requestor $mode => $id <br />";
                }
                switch ($mode) {
                    case 'category': // check for school administrator
                        $school_admin_categories = local_qm_activities_get_admin_categories($req_user_id);
                        if (count($school_admin_categories) > 0) {
                            foreach ($school_admin_categories as $school_admin_category) {
                                if ((int)$school_admin_category['id'] == $id) {
                                    $allow_exec = true;
                                    break;
                                }
                            }
                        }
                        break;

                    case 'course': // check for school administrator
                        // get the school administrator category courses
                        $school_admin_categories = local_qm_activities_get_admin_categories( $req_user_id );
                        if (count($school_admin_categories) > 0) {
                            // get all category course IDs for this administrator
                            foreach ($school_admin_categories as $school_admin_category) {
                                $category_course_ids = local_qm_activities_get_category_course_ids($school_admin_category['id']);
                                if (count($category_course_ids) > 0) {
                                    if (in_array($id, $category_course_ids)) {
                                        $allow_exec = true;
                                        break;
                                    }
                                }
                            }
                        }
                        break;

                    case 'teacher': // check for school administrator
                        // get school teacher IDs
                        $courses_teachers = local_qm_activities_get_school_admin_teachers( $req_user_id , $id_only = true );
                        if( count( $courses_teachers ) > 0 ){
                            if( in_array( $id , $courses_teachers )){
                                $allow_exec = true ;
                            }
                        }
                        break;

                    case 'student': // check for school administrator
                        $admin_students = ( local_qm_activities_get_school_admin_courses( $req_user_id , true ) );
                        if(count($admin_students) > 0 ){
                            asort($admin_students);
                            if( in_array( $id , $admin_students)){
                                $allow_exec = true;
                            }
                        }
                        break;
                }
            }

            // check for the requestor to be a course administrator
            if ( ! $allow_exec && local_qm_activities_is_course_admin($req_user_id) && in_array($mode, array('course', 'teacher', 'student'))) {
                // check for course administrator
                $requestor = 'courseadmin';
                if (mydebg == true) {
                    echo "$requestor $mode => $id <br />";
                }
                switch($mode){
                    case 'course':
                        $course_admin_courses = local_qm_activities_get_course_admin_courses ( $req_user_id );
                        foreach($course_admin_courses as $course_admin_course){
                            if( $id == (int) $course_admin_course->courseid ){
                                $allow_exec = true;
                                break;
                            }
                        }
                        break;

                    case 'teacher':
                        $courses_teachers = ( local_qm_activities_get_courses_teachers(local_qm_activities_get_course_admin_courses ( $req_user_id , true ) , true ) );
                        if( in_array( $id , $courses_teachers )){
                            $allow_exec = true;
                        }
                        break;

                    case 'student':
                        # $course_admin_students = get_usernames_with_ids(get_courses_students(get_course_admin_courses ( $req_user_id , true )));
                        $course_admin_students = local_qm_activities_get_courses_students(local_qm_activities_get_course_admin_courses ( $req_user_id , true ));
                        if( in_array( $id , $course_admin_students ) ){
                            $allow_exec = true;
                        }
                        break;
                }
            }

            // check for the requestor to be a teacher
            if ( ! $allow_exec && local_qm_activities_is_teacher($req_user_id) && in_array($mode, array('course', 'teacher', 'student'))) {
                // check for teacher
                $requestor = 'teacher';
                if (mydebg == true) {
                    echo "$requestor $mode => $id <br />";
                }
                switch($mode){
                    case 'course':
                        $teacher_courses = local_qm_activities_get_teacher_courses ( $req_user_id , true );
                        if( in_array($id , $teacher_courses )){
                            $allow_exec = true;
                        }
                        break;

                    case 'teacher':
                        $courses_teachers = ( local_qm_activities_get_courses_teachers( local_qm_activities_get_teacher_courses ( $req_user_id , true ) , true ) );
                        if( in_array( $id , $courses_teachers )){
                            $allow_exec = true;
                        }
                        break;

                    case 'student':
                        $teacher_students =  local_qm_activities_get_courses_students( local_qm_activities_get_teacher_courses( $req_user_id ,true ));
                        if( in_array( $id , $teacher_students ) ){
                            $allow_exec = true;
                        }
                        break;
                }

            }

            // check for the requestor to be a student
            if ( ! $allow_exec && local_qm_activities_is_student($req_user_id) && $mode === 'student') {
                // check for student
                $requestor = 'student';
                if (mydebg == true) {
                    echo "$requestor $mode => $id <br />";
                }
                if($req_user_id == $id){
                    $allow_exec = true;
                }
            }
        }
    }
    return $allow_exec;
}

/**
 * @param $mode
 * @param $id
 * @param $from
 * @param $to
 *
 * @return string of the request for getting a calendar by a user for an item
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_calendar_record($user_id , $mode , $id , $from , $to ){
    global $DB , $string_school, $string_category, $string_course, $string_teacher, $string_student,$string_user,$string_request;
    $html = '';
    if( (int)$id > 0 ){
        $user = $DB->get_record('user' , array( 'id' => $user_id));
        if($user){
            $html .= $string_user.fullname($user). ' (ID:'.$user_id .' '.$user->username.' ) '.$string_request;
        }
        switch($mode){
            case 'school':
                $record = $DB->get_record('course_categories',array( 'id' => $id ));
                if($record){
                    $html .= $string_school . ' : '. $record->name . ' ('.$id.')';
                }
                break;

            case 'category':
                $record = $DB->get_record('course_categories',array( 'id' => $id ));
                if($record){
                    $html .= $string_category . ' : '. $record->name . ' ('.$id.')';
                }
                break;

            case 'course':
                $record = $DB->get_record('course',array( 'id' => $id ));
                if($record){
                    $html .= $string_course . ' : '. $record->fullname . ' ('.$id.')';
                }
                break;

            case 'teacher':
                $record = $DB->get_record('user',array( 'id' => $id ));
                if($record){
                    $html .= $string_teacher . ' : '. fullname($record) . ' ('.$id.')';
                }
                break;

            case 'student':
                $record = $DB->get_record('user',array( 'id' => $id ));
                if($record){
                    $html .= $string_student . ' : '. fullname($record) . ' ('.$id.')';
                }
                break;
        }
        $html .= ' From : '.date( local_qm_activities_get_date_format(), $from ).' To : '.date( local_qm_activities_get_date_format(), $to).' @ ';
        $html .= date(local_qm_activities_get_date_format(), time()).'<br/>';
    }
    return $html;
}

/**
 * @param $req_user_id
 * @param bool $id_only
 *
 * @return array of teachers in courses administered by the user
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_school_admin_teachers($req_user_id , $id_only = false ){
    $courses_teachers = array();
    if( (int)$req_user_id > 0 ){
        $school_admin_categories = local_qm_activities_get_admin_categories( $req_user_id );
        // get the school administrator category courses
        $admin_courses_ids = array();
        // get all category course IDs for this administrator
        foreach ($school_admin_categories as $school_admin_category) {
            $category_course_ids = local_qm_activities_get_category_course_ids($school_admin_category['id']);
            if(count($category_course_ids) > 0 ){
                $admin_courses_ids += $category_course_ids;
            }
            if( count( $admin_courses_ids ) > 1 ){
                $admin_courses_ids = array_unique( $admin_courses_ids );
            }
        }
        $courses_teachers = local_qm_activities_get_courses_teachers( $admin_courses_ids , $id_only );
        if($id_only && count($courses_teachers) > 0 ){
            $courses_teachers = array_unique($courses_teachers);
            asort($courses_teachers);
            /*
                        $ids = array();
                        foreach( $courses_teachers as $courses_teacher ){
                            $ids[] = (int)$courses_teacher->userid;
                        }
                        $courses_teachers = $ids;
                        unset($ids);
            */
        }
    }
    return $courses_teachers;
}

/**
 * @param $user_id
 *
 * @return array of courses administered by a user
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_school_admin_courses($user_id , $id_only = false ){
    global $DB;
    $admin_courses = array();
    if( (int)$user_id > 0){
        $school_admin_categories = local_qm_activities_get_admin_categories( $user_id );
        $admin_courses_ids = array();
        // get all category course IDs for this administrator
        foreach ($school_admin_categories as $school_admin_category) {
            $category_course_ids = local_qm_activities_get_category_course_ids($school_admin_category['id']);
            if(count($category_course_ids) > 0 ){
                $admin_courses_ids += $category_course_ids;
            }
        }
        if( count( $admin_courses_ids ) > 0){
            $admin_courses_ids = array_unique($admin_courses_ids);
        }
        if( ! $id_only && count( $admin_courses_ids ) > 0 ){
            $admin_courses_sql = 'SELECT id,fullname,idnumber,shortname FROM {course} co WHERE co.id IN ('.implode(',',$admin_courses_ids).') ORDER BY 2';
            $admin_courses = $DB->get_records_sql( $admin_courses_sql );
        } else {
            $admin_courses = $admin_courses_ids;
        }
    }
    return $admin_courses;
}

/**
 * @param $ids
 *
 * @return array of users with id, username, firstname, middlename, lastname and fullname
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_usernames_with_ids($ids ){
    global $DB;
    $users = array();
    try {
        if( gettype($ids) == 'array' &&  count( $ids ) > 0 && (int) $ids[0] > 0 ){
            $users_sql = 'SELECT u.id userid, u.username, concat_ws(" ", u.firstname,u.middlename,u.lastname) fullname , u.firstname,u.middlename,u.lastname FROM {user} u WHERE u.id IN ('.implode(', ', $ids ).') ORDER BY 3';
            $users = $DB->get_records_sql( $users_sql ,array());
        }

    } catch (Exception $exception){

    } catch (Error $error){

    } catch (Throwable $throwable){

    }
    return $users;
}

/**
 * @return array of an associative array of known activities registering due dates
 *               this list covers what QMPlus is currently using
 *               This list is compiled from the plugin table and field names
 *               to create a common reference structure for all the activites
 *               containing due dates provisioning
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_calendar_activities(){
    return  array(
        'assign'        => array('from'=>'allowsubmissionsfromdate','to'=>'duedate','latest'=>'cutoffdate', 'leftjoin' => '{assign_submission} rec on rec.userid = u.id and rec.assignment = :id') ,
        'coursework'    => array('from'=>'startdate','to'=>'deadline','latest'=>'deadline', 'leftjoin' => '{coursework_submissions} rec ON rec.courseworkid = :id  AND u.id = rec.userid ') ,
        'kalvidassign'  => array('from'=>'timeavailable','to'=>'timedue','latest'=>'timedue', 'leftjoin' => '{kalvidassign_submission} rec on rec.vidassignid = :id  AND u.id = rec.userid ') ,
        'choice'        => array('from'=>'timeopen','to'=>'timeclose','latest'=>'timeclose', 'leftjoin' => '{choice_answers} rec on rec.userid = u.id and rec.choiceid = :id') ,
        'choicegroup'   => array('from'=>'timeopen','to'=>'timeclose','latest'=>'timeclose', 'leftjoin' => '{groups_members} rec ON rec.userid = u.id LEFT JOIN {choicegroup_options} cgo ON cgo.choicegroupid = :id AND cgo.groupid = rec.groupid') ,
        'quiz'          => array('from'=>'timeopen','to'=>'timeclose','latest'=>'timeclose', 'leftjoin' => '{quiz_attempts} rec on rec.userid = u.id and rec.quiz = :id') ,
        'feedback'      => array('from'=>'timeopen','to'=>'timeclose','latest'=>'timeclose', 'leftjoin' => '{feedback_completed} rec on rec.userid = u.id and rec.feedback = :id') ,
        'scorm'         => array('from'=>'timeopen','to'=>'timeclose','latest'=>'timeclose', 'leftjoin' => '{scorm_scoes_track} rec on rec.userid = u.id and rec.scormid = :id') ,
        'hotpot'        => array('from'=>'timeopen','to'=>'timeclose','latest'=>'timeclose', 'leftjoin' => '{hotpot_attempts} rec on rec.userid = u.id and rec.hotpotid = :id') ,
        'glossary'      => array('from'=>'assesstimestart','to'=>'assesstimefinish','latest'=>'assesstimefinish', 'leftjoin' => '{glossary_entries} rec ON rec.userid = u.id AND rec.glossaryid = :id') ,
        'oublog'        => array('from'=>'postfrom','to'=>'postuntil','latest'=>'postuntil', 'leftjoin' => '{oublog_instances} rec ON rec.oublogid = :id  AND u.id = rec.userid ') ,
        'forum'         => array('from'=>'assesstimestart','to'=>'assesstimefinish','latest'=>'assesstimefinish', 'leftjoin' => '{forum_posts} rec ON rec.userid = u.id LEFT JOIN {forum_discussions} fd ON rec.discussion = fd.id AND fd.forum = :id') ,
        'forumng'       => array('from'=>'timestart','to'=>'timeend','latest'=>'removeafter', 'leftjoin' => '{forumng_posts} rec ON rec.userid = u.id LEFT JOIN {forumng_discussions} fd ON fd.id = rec.discussionid LEFT JOIN {forumng} f ON f.id = fd.forumngid AND f.id = :id') ,
        'data'          => array('from'=>'timeavailablefrom','to'=>'timeavailableto','latest'=>'timeavailableto', 'leftjoin' => '{data_records} rec ON rec.userid = u.id and rec.dataid = :id') ,
        'ouwiki'        => array('from'=>'editbegin','to'=>'editend','latest'=>'editend', 'leftjoin' => '{ouwiki_versions} rec on rec.userid = u.id left join {ouwiki_pages} owp on owp.id = rec.pageid left join {ouwiki_subwikis} ows on ows.id = owp.subwikiid left join {ouwiki} ouw on ouw.id = ows.wikiid and ouw.id = :id') ,
        'questionnaire' => array('from'=>'opendate','to'=>'closedate','latest'=>'closedate', 'leftjoin' => '{questionnaire_attempts} rec on rec.userid = u.id and rec.qid = :id') ,
        'workshop'      => array('from'=>'submissionstart','to'=>'submissionend','latest'=>'submissionend', 'leftjoin' => '{workshop_submissions} rec on rec.authorid = u.id and rec.workshopid = :id') ,
        'adobeconnect'  => array('from'=>'starttime','to'=>'stoptime','latest'=>'stoptime', 'leftjoin' => '{groups_members} rec ON rec.userid = u.id left join {groups} g on g.id = rec.groupid left join {adobeconnect_meeting_groups} acg on acg.groupid = g.id AND acg.meetingscoid = :id') ,
        'lesson'        => array('from'=>'available','to'=>'deadline','latest'=>'deadline', 'leftjoin' => '{lesson_attempts} rec ON rec.userid = u.id AND rec.lessonid = :id') ,
    );

}



/**
 * @param $module
 * @param $id
 *
 * @return array
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_course_module_submissions($module , $id){
    global $DB,$cache_time;
    $submissions = array();
    $submissions['done'] = array();
    $submissions['undone'] = array();
    if( ( (int) $id > 0 ) && (array_key_exists($module, local_qm_activities_get_calendar_activities() ) ) ){
        try {
            // Connection creation
            $cacheAvailable = false;
            if(extension_loaded('Memcache') ){
                $memcache = new Memcache;
                @$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
            }
            // check the cache first
            if ($cacheAvailable == true) {
                $cacheKey = 'student_submissions_'.$module.'_' . $id ;
                $submissions = $memcache->get($cacheKey);
            }
            if( ! $submissions || (isset($submissions['done']) && isset($submissions['undone']) && ( count($submissions['done']) + count($submissions['undone'])) == 0 ) ){
                $submissions = array();
                $submissions['done'] = array();
                $submissions['undone'] = array();

                $sql = 'SELECT * FROM {course_modules} cm JOIN {modules} mo ON mo.name = :module AND mo.id = cm.module AND cm.instance = :id ';
                $course = $DB->get_records_sql($sql,array('module' => $module, 'id' => $id ));
                if( count($course) > 0 ){
                    $course = reset($course);
                }
                $course_student_ids = local_qm_activities_get_course_students($course->course , true);
                if( count($course_student_ids) > 1){
                    sort ($course_student_ids, SORT_STRING );
                }
                if( count($course_student_ids) > 0 ){
                    $get_records = false;
                    $sql = 'SELECT u.id , u.firstname, u.middlename,u.lastname, u.username, MIN(CASE WHEN rec.userid IS NULL THEN NULL ELSE "Done" END) AS state FROM {user} u LEFT JOIN $LEFTJOIN$ WHERE u.id IN ('.implode(',',$course_student_ids).') GROUP BY u.id ORDER BY 2,3,4 ';
                    $sql = str_replace('$LEFTJOIN$',local_qm_activities_get_calendar_activities()[$module]['leftjoin'],$sql);
                    $get_records = true;
                    if($get_records == true){
                        $data_records = $DB->get_records_sql($sql,array('id' => (int) $id));
                        if( count( $data_records ) > 0 ){
                            foreach ($data_records as $data_record) {
                                if( isset( $data_record->state)){
                                    $submissions['done'][] = $data_record;
                                } else {
                                    $submissions['undone'][] = $data_record;
                                }
                            }
                        }
                        unset($data_records);
                        $get_records = false;
                    }
                    if($cacheAvailable && $submissions && $cacheKey){
                        $memcache->set($cacheKey,$submissions, MEMCACHE_COMPRESSED ,$cache_time);
                    }
                }
            }
        } catch (Exception $exception){

        } catch (Error $error){

        } catch (Throwable $throwable){

        }
    }
    return $submissions;
}

/**
 * @param $from
 * @param $to
 *
 * @return array
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_site_courses_with_activities($from, $to){
    global $DB;
    $courses = array();
    try {
        $sql = 'select co.id from {course} co join {course_modules} cm on cm.course = co.id and cm.visible = 1 join {modules} mo on mo.id = cm.module 
and mo.name in '."('".implode("','",array_keys(local_qm_activities_get_calendar_activities()))."')".' group by co.id order by 1';
        $course_ids = $DB->get_records_sql($sql);
        if( count( $course_ids ) > 0 ){
            $ids = array();
            foreach ($course_ids as $course_id) {
                $ids[] = (int) $course_id->id;
            }
            $sql = 'SELECT co.* , FROM_UNIXTIME(co.startdate ,"%Y") ayear, count(cm.id) activities 
FROM {course} co 
JOIN {course_modules} cm ON cm.course = co.id AND cm.visible = 1 AND co.visible = 1 AND co.id IN ('.$sql.') 
JOIN {modules} mo ON mo.id = cm.module AND mo.name in '."('".implode("','",array_keys(local_qm_activities_get_calendar_activities()))."')".' 
GROUP BY co.id 
HAVING activities > 0 
ORDER BY ayear desc, activities desc, trim(fullname),idnumber,id ';
            $courses = $DB->get_records_sql($sql);
        }
    } catch (Error $error){} catch (Exception $exception){} catch (Throwable $throwable){}
    return $courses;
}

/**
 * @param $mode
 * @param $id
 * @param $from
 * @param $to
 *
 * @return string
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_export_calendar_button($mode, $id, $from, $to){
    global $string_form_export_calendar,$string_export_calendar;
    $html = '';
    $html .= '<form id="export_form_'.(time()).'" action="'.$string_form_export_calendar.'" method="POST" >';
    $html .= '<input type="hidden" name="mode" value="'.$mode.'">';
    $html .= '<input type="hidden" name="id" value="'.$id.'">';
    $html .= '<input type="hidden" name="from" value="'.$from.'">';
    $html .= '<input type="hidden" name="to" value="'.$to.'">';
    $html .= '<input type="submit" name="submit" value="'.$string_export_calendar.'">';
    $html .= '<br /><br />';
    $html .= '</form>';
    return $html;
}

/**
 * @param $mode
 * @param $id
 * @param $from
 * @param $to
 *
 * @return array|bool|null|string
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_calendar($mode, $id, $from, $to){
    global $cache_time;
    $calendar = false;
    try {
        // Connection creation
        $cacheAvailable = false;
        if(extension_loaded('Memcache') ){
            $memcache = new Memcache;
            @$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
        }
        // check the cache first
        if ($cacheAvailable == true) {
            $cacheKey = 'calendar_' . $mode . '_' . $id . '_' . $from . '_' . $to;
            $calendar = $memcache->get($cacheKey);
        }
    } catch (Exception $exception){ } catch (Error $error){ } catch (Throwable $throwable){}
    // if the calendar does not exist in cache then make one
    if( ! $calendar ) {
        switch ($mode) {
            case 'school':
                $calendar = local_qm_activities_get_category_calendar($id, $from, $to);
                break;
            case 'category':
                $calendar = local_qm_activities_get_category_calendar($id, $from, $to);
                break;
            case 'course':
                $calendar = local_qm_activities_get_course_calendar($id, $from, $to);
                break;
            case 'teacher':
                $calendar = local_qm_activities_get_teacher_calendar($id, $from, $to);
                break;
            case 'student':
                $calendar = local_qm_activities_get_user_calendar($id, $from, $to);
                break;
        }
        try {
            // if caching is available then store this calendar to cache for some time
            if ($cacheAvailable == true && ($calendar)) {
                $cacheKey = 'calendar_' . $mode . '_' . $id . '_' . $from . '_' . $to;
                $memcache->set($cacheKey, $calendar, MEMCACHE_COMPRESSED, $cache_time);
            }
        } catch (Exception $exception){ } catch (Error $error){ } catch (Throwable $throwable){}
    }
    return $calendar;
}

/**
 * @param $user_id
 * @param $form_id
 * @param bool $check_for_student
 *
 * @return bool
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_get_report_permission($user_id, $form_id , $check_for_student = false ){
    $permission = false;
    if( local_qm_activities_is_an_admin( $user_id )){
        $permission = true;
    }
    if(! $permission && local_qm_activities_is_school_admin( $user_id )){
        $permission = in_array( (int) $form_id, local_qm_activities_get_school_admin_courses( $user_id , true ) );
    }
    if(! $permission && local_qm_activities_is_course_admin( $user_id )){
        $permission = in_array( (int) $form_id ,local_qm_activities_get_course_admin_courses( $user_id , true ));
    }
    if(! $permission && local_qm_activities_is_teacher( $user_id )  ) {
        $permission = in_array( (int) $form_id ,local_qm_activities_get_teacher_courses( $user_id , true ));
    }
    if( $check_for_student && ! $permission && local_qm_activities_is_student($user_id)){
        $permission = (int) $form_id == (int) $user_id;
    }
    return $permission;
}

/**
 * @param int $keyRowCells
 * @param $max_load
 * @param int $min_value
 * @param int $max_value
 *
 * @return string
 * @author v.sotiras@qmul.ac.uk Vasileios Sotiras
 */
function local_qm_activities_make_calendar_grid_key_row($keyRowCells = 21 , $max_load , $min_value = 0, $max_value = 100 ){
    $keyCells = array();
    $keyRow = '';
    for($c = 1; $c <= $keyRowCells; $c++){
        $load = (int)(100 * $c / $keyRowCells );
        $rate_color = local_qm_activities_get_scaled_colors( $load , $min_value = 0, $max_value = 100 );
        $bg_color = $rate_color['bg'];
        $fg_color = $rate_color['fg'];
        $style = 'background-color: '.$bg_color.';color: '.$fg_color.'; width: calc(100% / '.($keyRowCells).' ) ';
        $keyCells[$c] = '<td style="'.$style.'">' /* .$rel_daily_submissions.' '*/ .$load.'%'.($c <= $keyRowCells ? '<br/> ~ '.(int)($load * $max_load /100) :'').'</td>';
    }
    $keyRow = '<tr><td colspan="7"><table style="width:100%; margin: 0px;"><tr>';
    foreach($keyCells as $keyCell){
        $keyRow .= $keyCell;
    }
    $keyRow .= '</tr></table></td></tr>';
    return $keyRow;
}