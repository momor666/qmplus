<?php
/**
 * Created by PhpStorm.
 * User: vasileios
 * Date: 26/06/2017
 * Time: 09:30
 * QM+ Activities reporting plugin
 */

/** @noinspection UntrustedInclusionInspection */
# echo 'HERE';
require '../../config.php' ;
require_once __DIR__. '/locallib.php' ;
$urlparams  = array();
$PAGE->set_url('/local/qm_activities/index.php', $urlparams);
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Calendar Activities Menu');
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');

// Prevent caching of this page to stop confusion when changing page after making AJAX changes.
$PAGE->set_cacheable(false);

if( (int)$USER->id < 2 || ! isloggedin() ){
    $urltogo= $CFG->wwwroot.'/';
    redirect($urltogo);
}

$mode   = optional_param('mode','', PARAM_ALPHA);
$id     = optional_param('id',0, PARAM_INT);
$from   = optional_param('from', 0,PARAM_INT);
$to     = optional_param('to', 0, PARAM_INT);

if( (int)$from === 0 || (int)$to === 0){
    $range = local_qm_activities_get_timestamp_range(getdate(),'acyear');
}
if((int)$from === 0 ){
    $from = $range['from'];
}
if( (int)$to === 0 ){
    $to = $range['to'];
}

$error = '';
$school_admin = false;
$school_admin_categories = array();
$isadmin = false;
$string_form_action = $string_reporter;


if(in_array($mode,array('','new','school','category','course','teacher','student'),false)){
    if($mode === ''){
        $mode = 'new';
    }
} else {
    $error = 'Wrong mode';
    $id = 0;
    $from = 0;
    $to = 0;
}
echo $OUTPUT->header();
if($error > ''){
    echo $error;
} else {
    # echo '<script src="./js/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>';
    echo '<h2>'.$string_page_title.'</h2><br/>';
    # if( $mode > '' ){  echo '<b> '.$mode.' ID:'.$id.'</b><br />';  }
    # echo 'Mode:'.$mode.' ID:'.$id.' from:'.date('d-M-Y',$from).' to:'.date('d-M-Y',$to).'<br />';
    echo PHP_EOL.'<link rel="stylesheet" type="text/css" href="./css/jquery-ui.css">'.PHP_EOL;
    # echo '<script src="./js/jquery-ui.js"></script>';
    echo '<style>
.ui-widget-content {
    border: 1px solid #dddddd;
    background: #afffff;
    color: #333333;
}
</style>';

    // STUDENT Account
    $user_id = (int) $USER->id ;
    if( local_qm_activities_is_student( $user_id ) ){
        echo '<hr /><h3>'.$string_course_student.'</h3><hr/>';
        $data_array = array();
        #foreach(){ // only the personal account is allowed
        $data_array[ (int)$user_id ] = fullname($USER).' ('.$USER->username.')';
        #}
        echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'student' , $no_choice = $string_select_student , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
        unset($data_array);
    }
    // STUDENT Account

    // TEACHER Account
    $teacher_id = (int) $USER->id;
    if( local_qm_activities_is_teacher( $teacher_id )){  // TEACHER Account
        echo '<hr /><h3>'.$string_course_teacher.'</h3><hr/>';
        $data_array = array();
        #foreach(){ // only the personal account is allowed
        $data_array[ (int)$USER->id  ] = fullname($USER).' ('.$USER->username.')';
        # }
        echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'teacher' , $no_choice = $string_select_teacher , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
        unset($data_array);

        $teacher_courses = local_qm_activities_get_teacher_courses( $teacher_id );
        if(count( $teacher_courses ) > 0 ){
            $data_array = array();
            foreach($teacher_courses as $cid => $teacher_course){
                $data_array[ (int)$teacher_course->courseid  ] = $teacher_course->fullname;
            }
            echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'course' , $no_choice = $string_select_course , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
            unset($data_array , $teacher_courses );

            // show students
            // get courses students

            $course_teacher_students = local_qm_activities_get_usernames_with_ids( local_qm_activities_get_courses_students( local_qm_activities_get_teacher_courses( (int) $teacher_id , true )) );
            if(count($course_teacher_students) > 0 ){
                $data_array = array();
                foreach($course_teacher_students as $cid => $course_teacher_student){
                    $data_array[ (int)$course_teacher_student->userid  ] = $course_teacher_student->fullname.' ('.$course_teacher_student->username.')';
                }
                echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'student' , $no_choice = $string_select_student , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
                unset($data_array, $course_teacher_students);
            }
        }
    }
    // TEACHER Account


    // COURSE Administrator
    //
    $user_id = (int) $USER->id;
    // $user_id = 7;  // debugging time only
    if(local_qm_activities_is_course_admin( $user_id )){ // COURSE Administrator
        $course_admin_courses = local_qm_activities_get_course_admin_courses ( $user_id );
        if(count( $course_admin_courses ) > 0){
            echo '<hr /><h3>'.$string_course_administrator.'</h3><hr/>';

            $data_array = array();
            foreach($course_admin_courses as $cid => $course_admin_course){
                $data_array[ (int)$course_admin_course->courseid  ] = $course_admin_course->coursename;
            }
            echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'course' , $no_choice = $string_select_course , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
            unset($data_array);

            #unset($course_admin_courses);

            // Course Admin Course IDs
            $course_admin_courses_ids = array();
            foreach($course_admin_courses as $admin_course){
                $course_admin_courses_ids[] = (int)$admin_course->courseid;
            }
            unset($course_admin_courses);

            // show teachers for the courses
            $courses_teachers = local_qm_activities_get_usernames_with_ids( local_qm_activities_get_courses_teachers( local_qm_activities_get_course_admin_courses ( $user_id , true ) , true ) );
            if( count($courses_teachers) > 0 ){
                $data_array = array();
                foreach($courses_teachers as $cid => $courses_teacher){
                    $data_array[ (int)$courses_teacher->userid  ] = $courses_teacher->fullname.' ('.$courses_teacher->username.')';
                }
                echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'teacher' , $no_choice = $string_select_teacher , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
                unset($data_array, $courses_teachers);
            }

            // show students
            // get courses students
            # $course_admin_students = array();
            # $course_admin_students_ids = get_courses_students($course_admin_courses_ids);
            $course_admin_students = local_qm_activities_get_usernames_with_ids(local_qm_activities_get_courses_students(local_qm_activities_get_course_admin_courses ( $user_id , true )));
            if(count($course_admin_students) > 0 ){
                $data_array = array();
                foreach($course_admin_students as $cid => $course_admin_student){
                    $data_array[ (int)$course_admin_student->userid  ] = $course_admin_student->fullname.' ('.$course_admin_student->username.')';
                }
                echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'student' , $no_choice = $string_select_student , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
                unset($data_array, $course_admin_students);
            }
            // COURSE Administrator End
        }
        // COURSE Administrator
    }

    // School administrator in Categories
    $user_id = (int) $USER->id;
    // $user_id = 240 ; // debug / development
    if( local_qm_activities_is_school_admin( $user_id ) ){  // School administrator in Categories
        $school_admin_categories = local_qm_activities_get_admin_categories( $user_id );
        if( count($school_admin_categories ) > 0 ){ #  This User controls course category(ies)
            echo '<hr /><h3>'.$string_school_administrator.'</h3><hr />';

            $data_array = array();
            foreach($school_admin_categories as $sid => $school_admin_category){
                $data_array[ (int)$school_admin_category['id']  ] = $school_admin_category['category'];
            }
            echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'category' , $no_choice = $string_select_category , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
            unset($data_array);

            // get the school administrator category courses
            $admin_courses_ids = local_qm_activities_get_school_admin_courses( $user_id , true );

            // if there are any courses in the controlled category(ies) then show the choices
            $admin_courses = local_qm_activities_get_school_admin_courses( $user_id );
            if( count( $admin_courses ) > 0 ){
                $data_array = array();
                foreach( $admin_courses as $cid => $admin_course){
                    $data_array[ (int)$admin_course->id ] = $admin_course->fullname;
                }
                echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'course' , $no_choice = $string_select_course , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
                unset($data_array, $admin_courses);
            }
            // Category(ies) teachers
            // $courses_teachers = get_courses_teachers( $admin_courses_ids );
            $courses_teachers = local_qm_activities_get_usernames_with_ids( local_qm_activities_get_school_admin_teachers( $user_id , true ) );

            if( count( $courses_teachers ) > 0 ){
                $data_array = array();
                foreach($courses_teachers as $cid => $courses_teacher){
                    $data_array[ (int)$courses_teacher->userid ] = $courses_teacher->fullname.' ('.$courses_teacher->username.')';
                }
                echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'teacher' , $no_choice = $string_select_teacher , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
                unset($data_array, $courses_teachers);
            }

            // get courses students
            $admin_students = local_qm_activities_get_usernames_with_ids( $admin_courses_ids );
            if(count($admin_students) > 0 ){
                $data_array = array();
                foreach($admin_students as $cid => $admin_student){
                    $data_array[ (int)$admin_student->userid ] = $admin_student->fullname.' ('.$admin_student->username.')';
                }
                echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'student' , $no_choice = $string_select_student , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
                unset($data_array, $admin_students);
            }
        }
    }

// Connection creation
    $cacheAvailable = false;
    if(extension_loaded('Memcache') ){
        $memcache = new Memcache;
        @$cacheAvailable = $memcache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
    }

    // SITE ADMINISTRATORS can see all schools
    // check if the user is a site administrator
    $isadmin = local_qm_activities_is_an_admin( $USER->id );
    if( isset($isadmin) && $isadmin ){
        $site_schools       = null;
        $site_categories    = null;
        $site_courses       = null;
        $site_teachers      = null;

        // check for development time caching facility via MemCache / APC for PHP
        if( $cacheAvailable == true ){
            if( ! $site_schools = $memcache->get('schools_'.$from )) {
                $site_schools = local_qm_activities_get_school_categories();
                $memcache->set( 'schools_'.$from , $site_schools , MEMCACHE_COMPRESSED , $cache_time );
            }
            if( ! $site_categories = $memcache->get('school_site_categories_'.$from ) ){
                $site_categories = $DB->get_records('course_categories', array('visible' => 1) ,'name,id');
                $memcache->set( 'school_site_categories_'.$from , $site_categories , MEMCACHE_COMPRESSED , $cache_time );
            }
            if( ! $site_courses = $memcache->get('site_courses_'.$from ) ){
                # $site_courses = $DB->get_records('course', array('visible' => 1), 'trim(fullname),idnumber,id');
                $site_courses = local_qm_activities_get_site_courses_with_activities($from,$to);
                $memcache->set( 'site_courses_'.$from , $site_courses , MEMCACHE_COMPRESSED , $cache_time );
            }
            if( ! $site_teachers = $memcache->get('site_teachers_'.$from ) ){
                $site_teachers = local_qm_activities_get_site_teachers();
                $memcache->set( 'site_teachers_'.$from  , $site_teachers , MEMCACHE_COMPRESSED , $cache_time );
            }
        }
        // check if any cache access was failed
        if( count( $site_schools ) === 0 ) {
            $site_schools = local_qm_activities_get_school_categories();
        }
        if( count($site_categories) === 0 ){
            $site_categories = $DB->get_records('course_categories', array('visible' => 1), 'id,name');
        }
        if( count($site_courses) === 0 ) {
            # $site_courses = $DB->get_records('course', array('visible' => 1), 'trim(fullname),idnumber,id');
            $site_courses = local_qm_activities_get_site_courses_with_activities($from,$to);
        }
        if( count($site_teachers) === 0 ){
            $site_teachers      = local_qm_activities_get_site_teachers();
        }

        echo '<hr /><h3>'.$string_site_administrator.'</h3><hr/>';
        if(count($site_schools) > 0){
            $data_array = array();
            foreach($site_schools as $sid => $site_school){
                $data_array[ (int)$site_school->id ] = $site_school->name;
            }
            echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'school' , $no_choice = $string_select_school , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
            unset($data_array, $site_schools);
        }

        // SITE ADMINISTRATORS can see all Categories
        if(count($site_categories) > 0 ){
            $data_array = array();
            foreach( $site_categories as $cid => $site_category ){
                $data_array[ (int)$site_category->id ] = $site_category->name;
            }
            echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'category' , $no_choice = $string_select_category , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
            unset($data_array, $site_categories );
        }

        // SITE ADMINISTRATORS can see all Teachers
        if( count( $site_teachers ) > 0 ){
            $data_array = array();
            foreach( $site_teachers as $cid => $site_teacher){
                $data_array[ (int)$site_teacher->userid ] = $site_teacher->teacher .' ('.$site_teacher->username.')';
            }
            echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'teacher' , $no_choice = $string_select_teacher , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to );
            unset($data_array, $site_teachers );
        }
        flush();

        // SITE ADMINISTRATORS can see all Students

        // split requests and lists per course
        echo '<hr/><h3>'.$string_select_course.' & '.$string_select_student.'</h3>';

        if(count($site_courses) > 0 ){
            $data_array = array();
            foreach($site_courses as $cid => $site_course){
                $data_array[ (int)$site_course->id  ] = $site_course->fullname.' ('.$site_course->shortname.') @'.$site_course->ayear.' #'.$site_course->activities ;
            }
            echo local_qm_activities_get_selection_form( $data_array , $id , $string_form_action , $mode , $form_class = 'course' , $no_choice = $string_select_course , $label = '' , $string_label_css , $from , $to , $string_range_label_css , $string_date_from , $string_date_to , true);
            echo '<div style="height: 250px;" id="AdminGetCourseStudents">'.$string_select_course.'</div>
<script>
$(document).ready(function() {
    $("#AdminSelectCourseMenu").on("change", function() {
        getAdminCourseStudents(this.value);
    });
    '.( count($data_array) == 1 ? 'getAdminCourseStudents('.current(array_keys($data_array)).');' : '').'

});
function getAdminCourseStudents( course_id ) {
    $("#AdminGetCourseStudents").html("'.$string_please_wait.'<br/><br/>").load("'.$form_admin_ajax.'", 
                            { "mode": "course", "id": course_id , "from":"'.$from.'","to": "'.$to.'"} 
                            , function(response, status, xhr) {
                                if (status === "error") {
                                    console.log( xhr.message + " " + xhr.status + " " + xhr.statusText);
                                }
                            });
}
</script>
<style>
    .no-ui-icon-circle-triangle-e {
        background-image: url("images/ui-icon-circle-triangle-e.png");
    }
</style>
';
        }
        unset($data_array, $site_courses);
    }
    // SITE ADMINISTRATORS

}
echo $OUTPUT->footer();

