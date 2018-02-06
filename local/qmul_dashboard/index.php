<?php
require_once('../../config.php');
require_once $CFG->libdir . '/gradelib.php';
require_once(__DIR__.'/studentsView.php');
require_once(__DIR__.'/teachersView.php');


// require valid moodle login.  Will redirect to login page if not logged in.
if (!isloggedin()) {
    //redirect to moodle login page
    echo 'Not Logged In';
    redirect(new moodle_url('/login/index.php'));
} else {
    require_once(__DIR__ . '/lib.php');
    $PAGE->set_context(context_system::instance());

    $PAGE->set_pagelayout('standard');
    $PAGE->set_url('/local/qmul_dashboard/');
    $PAGE->set_title(get_string('navtitle', 'local_qmul_dashboard'));

    $PAGE->requires->css('/local/qmul_dashboard/css/bootstrap.css');
    $PAGE->requires->jquery_plugin('qmul_dashboard-bootstrap', 'local_qmul_dashboard'); //bootstrap
    $PAGE->requires->jquery_plugin('qmul_dashboard-highcharts', 'local_qmul_dashboard'); ///Highcharts

    $jsurl = new moodle_url($CFG->wwwroot.'/local/qmul_dashboard/jquery/turnitin_plagiarism.js');
    $PAGE->requires->js($jsurl);


    echo $OUTPUT->header();

    if(has_capability('local/qmul_dashboard:edit', context_system::instance())){
        echo html_writer::start_tag('div',array('class' => 'db-edit-button'));
        $button = html_writer::tag('button', 'Edit settings' , array('class' => 'button', 'type' => 'button'));
        echo html_writer::link('edit.php', $button);
        echo html_writer::end_tag('div');
    }

    echo $OUTPUT->heading(get_string('navtitle', 'local_qmul_dashboard'));


    try {

        //echo $OUTPUT->heading(get_string('navtitle', 'local_qmul_dashboard'));

        echo html_writer::div(
            get_string('welcometext', 'local_qmul_dashboard'),
            '',
            array('id' => 'welcometext', 'class' => 'alert')
        );


        $userCourses = local_qmul_dashboard_getUserCourses($USER->id);

        // TODO: build mahara notifications, this has to be moved and contiune work at block notifications
        // $mahara = get_mahara_content();

        $studentView = null;
        $teacherView = null;


        foreach ($userCourses as $key => $course) {

            // check for view permissions
            $permissionSettings = local_qmul_dashboard_checkCourseModulePermissions($course->category);
            if (!empty($permissionSettings)) {
                $viewPermissions = local_qmul_dashboard_buildViewPermissions($permissionSettings);
            }

            // Check if course progress config exists
            $courseContext = local_qmul_dashboard_getCourseContext($course->courseid);
            $userRoles = get_user_roles($courseContext);
            $studentRendered = false;
            $teacherRendered = false;


            foreach ($userRoles as $role) {

                if (strpos($role->shortname, 'student') !== false && !$studentRendered) {
                    $studentView .= renderStudent($course, $courseContext);
                    $studentRendered = true;
                } elseif (strpos($role->shortname, 'teacher') !== false && !$teacherRendered) {
                    $teacherView .= renderTeacher($course, $courseContext);
                    $teacherRendered = true;
                }

            }

        }


        //echo $studentView;
        if (!empty($studentView)) {
            echo '<div class="panel panel-primary">
                  <!-- Default panel contents -->
                  <div class="panel-heading">Student Courses</div>
                  <div class="panel-body">
    
                        ' .
                html_writer::start_tag('div', array('class' => 'accordion panel-group', 'id' => 'coursesaccordionstudents'))
                . $studentView . html_writer::end_tag('div') . '
                  </div>
                </div>';
        }

        if (!empty($teacherView)) {
            echo '<div class="panel panel-warning">
              <!-- Default panel contents -->
              <div class="panel-heading">Teacher Courses</div>
              <div class="panel-body">
                    ' . html_writer::start_tag('div', array('class' => 'accordion panel-group', 'id' => 'coursesaccordionteachers'))
                . $teacherView . html_writer::end_tag('div') . '
              </div>
            </div>';
        }

    }

    catch (Exception $e){
        echo html_writer::div(
            get_string('qmul_dashboard:pageerrormessage', 'local_qmul_dashboard'),
            '',
            array('id' => 'gradespluserror', 'class' => 'alert alert-error')
        );

    }

    echo $OUTPUT->footer();

}
?>

<script>
    $(window).ready(function(){


        //load students
        $.each($('.viewStudents'), function() {
            $(this).on('click',function(e){
                e.preventDefault();

                var target = $(this).data('target'),
                    key = $(this).data('key'),
                    courseid = $(this).data('course'),
                    itemid = $(this).data('itemid'),
                    self = $(this);

                $(target).find('.progress').show();
                $(target).modal();

                $.ajax({
                    method: "POST",
                    url: '<?php echo new moodle_url('/local/qmul_dashboard/studentsList.php');?>',
                    data: { courseid: courseid, key: key, item:itemid }
                })
                .done(function (data) {
                    $(target).find('.studentlist').hide();
                    $(key).html(data);
                    setTimeout(function() {
                        $(target).find('.progress').hide();
                        $(target).find('.studentlist').show();

                    }, 1);


                    $('.collapse').on('hidden', function(e){
                        e.stopPropagation();
                    });

                    $(target).on('hidden.bs.modal', function () {
                        $(key).find('.accordion-group').remove();
                    });


                });

                return false;
            });
        });



    });
</script>