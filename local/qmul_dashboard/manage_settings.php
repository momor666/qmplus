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
 * Gradesplus histogram visibilty
 *
 * @package    local
 * @subpackage qmul_dashboard
 * @copyright  2016 Queen Mary University of London
 * @author     Damian Hippisley  <d.j.hippisley@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__.'/lib.php');
require(__DIR__.'/manage_category_form.php');

$catid = required_param('categoryid', PARAM_INT);

$context = context_coursecat::instance($catid);

require_login();
require_capability('moodle/category:manage', $context);

$categories = coursecat::make_categories_list('moodle/category:manage');

$PAGE->set_url('/local/qmul_dashboard/manage_settings.php', array('categoryid' => $catid));
$PAGE->set_context($context);
$PAGE->set_title('Gradesplus histogram visibilty');
$PAGE->set_heading('Gradesplus histogram visibilty');

$PAGE->requires->jquery();
//$PAGE->requires->jquery_plugin('qmul_dashboard-bootstrap', 'local_qmul_dashboard'); //boostrap

$PAGE->set_pagelayout('admin');


//header
echo $OUTPUT->header();


$mform = new local_qmul_dashboard_manage_category_form(null, array('catid' => $catid));


if($mform->is_cancelled()){
//do nothing

} else if($data = $mform->get_data()){
//process data

    $results = local_qmul_dashboard_insertSettings($data, 'activities_histograms', 'modal', '');

    echo html_writer::start_div('manage_category_form');
    $mform->display();
    $resultsmessage = 'The setting ' . $results['mess'];
    echo html_writer::div($resultsmessage, 'alert alert-'.$results['type'].' removemess');
    echo html_writer::end_div();

} else{
    echo html_writer::start_div('manage_category_form');
    $mform->display();
    echo html_writer::end_div();
}

//display the currently hidden settings
$settings = local_qmul_dashboard_getUserContextViewSettings($categories);

if($settings) {
    // Remove Alert
    echo html_writer::div('', 'alert alert-danger hidemessage hidden');

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
        get_string('manageSettingsColumnType', 'local_qmul_dashboard'),
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

        echo HTML_WRITER::tag('td', $setting->category, array('id' => 'cName' . $setting->id));
        echo HTML_WRITER::tag('td', get_string('manageSettingsColumnValue','local_qmul_dashboard'));
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
            $('.hidemessage').html('');
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
                            'The setting "'
                            + $('#cName'+$settingId).text()
                            + ' '
                            + $('#mName'+$settingId).text()
                            + '<?php get_string('deletemess', 'local_qmul_dashboard') ?>'
                            + '" has been removed.'
                        );
                        $('.removemess').removeClass('hidden');
                    }

                    $(self).closest('tr').remove();


                });


        });

    </script>
    <?php

}



// Footer.
echo $OUTPUT->footer();
