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
 * Edit view permissions for dashboard.
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once(__DIR__.'/lib.php');
require(__DIR__.'/edit_form.php');

require_login();

if (!isloggedin()) {
    //redirect to moodle login page
    echo 'Not Logged In';
    redirect(new moodle_url('/login/index.php'));
} else {

    // Get URL parameters.
    $requestedType = optional_param('filetype', '', PARAM_SAFEDIR);

    $PAGE->set_pagelayout('admin');
    $PAGE->set_url('/local/qmul_dashboard/edit');
    $PAGE->set_title(get_string('navtitle', 'local_qmul_dashboard'));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_context(context_system::instance());

    $PAGE->requires->jquery();
//    $PAGE->requires->jquery_plugin('qmul_dashboard-bootstrap', 'local_qmul_dashboard'); //boostrap

    echo $OUTPUT->header();

    if (has_capability('local/qmul_dashboard:edit', context_system::instance())) {

        $mform = new local_qmul_dashboard_edit_form();

        //Form processing and displaying is done here
        if ($mform->is_cancelled()) {
            //Handle form cancel operation, if cancel button is present on form
        } else {
            //Set default data (if any)
            // $mform->set_data($toform);
            //displays the form
            $mform->display();

            if ($formdata = $mform->get_data()) {
                //In this case you process validated data. $mform->get_data() returns data posted in form.

                $results = local_qmul_dashboard_addViewSettings($formdata);

                foreach ($results as  $result) {
                    if($result['status']){

                    }else{

                    }

                    echo html_writer::div(
                        $result['mess'],
                        'alert alert-'.$result['type']
                    );
                }


            }
        }



        // Display current settings
        $settings = local_qmul_dashboard_getViewSettings();
        if($settings) {
            // Remove Alert
            echo html_writer::div('','alert alert-danger removemess hidden');

            $tableoptions['class'] = 'table table-bordered';
            echo html_writer::start_tag('table', $tableoptions);

            // Table headers
            echo html_writer::start_tag('thead');
            echo html_writer::start_tag('tr');
            echo html_writer::tag(
                'th',
                get_string('settingsColumnCategory', 'local_qmul_dashboard'),
                array('class' => 'span6')
            );
            echo html_writer::tag(
                'th',
                get_string('settingsColumnName', 'local_qmul_dashboard'),
                array('class' => 'span3')
            );
            echo html_writer::tag(
                'th',
                get_string('settingsColumnType', 'local_qmul_dashboard'),
                array('class' => 'span1')
            );
            echo html_writer::tag(
                'th',
                get_string('settingsColumnUser', 'local_qmul_dashboard'),
                array('class' => 'span1')
            );
            echo html_writer::tag(
                'th',
                '',
                array('class' => 'span1')
            );
            echo html_writer::end_tag('tr');
            echo html_writer::end_tag('thead');
            echo html_writer::start_tag('tbody');

            foreach ($settings as $setting) {

                echo HTML_WRITER::start_tag('tr');

                echo HTML_WRITER::tag('td', $setting->category, array('id'=>'cName'.$setting->id));
                echo HTML_WRITER::tag('td', $setting->itemname, array('id'=>'mName'.$setting->id));
                echo HTML_WRITER::tag('td', $setting->itemtype);
                echo HTML_WRITER::tag('td', $setting->user);

                echo html_writer::start_tag('td');

                    echo HTML_WRITER::tag(
                        'button',
                        get_string('removebutton', 'local_qmul_dashboard'),
                        array(
                            'class' => 'btn btn-info removesetting',
                            'type' => 'button',
                            'data-setting' => $setting->id,
                        )
                    );

                echo html_writer::end_tag('td');
                echo html_writer::end_tag('tr');

            }

            echo html_writer::end_tag('tbody');
            echo html_writer::end_tag('table');
        ?>

            <script>
                $(".removesetting").click(function() {

                    $('.removemess').html('');

                    var $settingId = $(this).data('setting'),
                        self = $(this);

                    $.ajax({
                        method: "POST",
                        url: '<?php echo new moodle_url('/local/qmul_dashboard/delete_settings.php');?>',
                        data: { category: $settingId }
                    })
                    .done(function (res) {
                       if(res){
                          $('.removemess').html(
                              $('#cName'+$settingId).text()
                              + ' '
                              + $('#mName'+$settingId).text()
                              + '<?php get_string('deletemess', 'local_qmul_dashboard') ?>'
                          );
                           $('.removemess').removeClass('hidden');
                       }

                        $(self).closest('tr').remove();


                    });


                });

            </script>
        <?php
        }
    }

}
// Footer.
echo $OUTPUT->footer();