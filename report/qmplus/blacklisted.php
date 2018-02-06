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
 * Displays different views of the qmplus reports.
 *
 * @package    report_qmplus
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/coursecatlib.php');
require_once(dirname(__FILE__).'/lib.php');
require(dirname(__FILE__).'/blacklisted_form.php');

require_login();

// Get URL parameters.
$requestedType = optional_param('filetype', '', PARAM_SAFEDIR);

// Print the header & check permissions.
admin_externalpage_setup('reportqmplus', '', null, '', array('pagelayout'=>'report'));

/*if ($requestedType) {

}*/

$PAGE->requires->jquery();
echo $OUTPUT->header();

if (has_capability('report/qmplus:view', context_system::instance())) {

    $mform = new report_qmplus_blacklisted_form();

    //Form processing and displaying is done here
    if ($mform->is_cancelled()) {
        //Handle form cancel operation, if cancel button is present on form
    } else if ($fromform = $mform->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.
    } else {

        //Set default data (if any)
        // $mform->set_data($toform);
        //displays the form
        $mform->display();
    }

?>

    <script>

        /**
         * Helper method to create timestamp
         *
         * @param selectors
         * @returns {number} timestamp
         */
        function getTimestamp(selectors){
            var timestamp = '';

            $.each(selectors,function(){
                timestamp+='/'+$(this).val();
            });

            timestamp = timestamp.split('/'),
            timestamp = timestamp[2]+' '+timestamp[1]+' '+timestamp[3]+' '+timestamp[4]+':'+timestamp[5]+':00 GMT';
            timestamp = Date.parse(timestamp); // timestamp in miliseconds

            return timestamp/1000.0; // timestamp in seconds

        }

        $('#filetypesubmit').click(function(e) {
            e.preventDefault();
            window.onbeforeunload = null;
            // Create timestamps
            datefrom = getTimestamp($('.id_reportfrom').find('select'));
            dateto = getTimestamp($('.id_reportto').find('select'));

            //load page to download zip file
            document.location.href = "<?php echo $CFG->wwwroot;?>/report/qmplus/zipGenerator.php?report=blacklisted&val="+
            $('#blacklisted').val()+"&datefrom="+datefrom+"&dateto="+dateto;
        });

    </script>

<?php
}
// Footer.
echo $OUTPUT->footer();