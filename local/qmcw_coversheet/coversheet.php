<?php
/** @noinspection UntrustedInclusionInspection */
require_once('../../config.php');
require_login();
require_once(__DIR__.'/locallib.php');
//@ini_set('display_errors', '1');
//@error_reporting(E_ALL | E_STRICT);
// $PAGE->set_context( context_course::instance() );

$html = '';
/* get the course module ID as int parameter in URL */
$cmid = required_param('cmid',PARAM_INT);
$userid = optional_param('userid',0, PARAM_INT);
//TODO: "DamianH" : add userid param fetch
$error_state = false;

if($userid == 0){
    $userid = $USER->id;
}
$user_obj = $DB->get_record('user',array('id'=>$userid));

$module = get_coursemodule_from_id(/*$modulename='assign'*/ null , $cmid, $courseid=0, $sectionnum=false, MUST_EXIST);

/* delcare error state if there is no mudule declared*/
$error_state = ! ($module);

if(! $error_state) {
    $error_state = !local_qmcw_coversheet_isUserInCourse($user_obj->id, $module->course);
}

if(! $error_state) {
    //TODO: "DamianH" :  use param instead of logged in user object
    $participants = local_qmcw_coursework_get_module_group_participants($user_obj->id, $cmid);
    $error_state = (count($participants) < 1);
}
if(! $error_state){
    $modulename  = $module->name ;

    $assignment = $DB->get_record( $module->modname ,array('id'=>$module->instance) );
    $duedate = date("d-M-Y H:i:s", $assignment->duedate) ; // gmdate gives gmt time which can differ in presentation

    // get this course data
    $course = $DB->get_record('course',array('id'=>$assignment->course));

    $category = local_qmcw_coversheet_get_course_category_school($course->category);

    // check if the assignment is a group based submission or individual and create the correct barcode

    $barcode_data = local_qmcw_coversheet_get_barcode_data($user_obj->id, $cmid); // 'http://qmumotest/blocks/superiframe/view.php?blockid=168589&';


    $barcode_image = local_qmcw_coversheet_get_barcode_image($barcode_data,$barcode_style=2,$barcode_line_thickness=null,$barcode_height=null,$barcode_columns=null,$barcode_rows=null,$red=null,$green=null,$blue=null);
    //    $html .= $barcode_image;
    //    $course = get_course(2);
    //    $modinfo = get_fast_modinfo($course);

    $participant  = $participants[1]->userlastname.' '.$participants[1]->userfirstname ;
    $participant .= ( $participants[1]->teamsubmission == "1") ? ' (+'.($participants[1]->groupsize-1).')' : '' ;



    // read the html coversheet code
    $file = fopen('html/coversheet.html','r');
    $filehtml = fread($file,500000);
    fclose($file);

    //obtain module code if ' - ' delimiter is present
    $modulecode = "";
    if(strpos($course->fullname, ' - ') !== false){
        $array = explode(' - ', $course->fullname);
        $modulecode = $array[0];
    }

    // replace the data fields with real data
    $filehtml = str_replace("<barcode/>",$barcode_image,$filehtml);
    $filehtml = str_replace("<barcodedata/>",$barcode_data,$filehtml);
    $filehtml = str_replace("<studentname/>",$user_obj->lastname.' '.$user_obj->firstname,$filehtml);
    $filehtml = str_replace("<assessmentname/>",$modulename,$filehtml);
    $filehtml = str_replace("<duedate/>",$duedate,$filehtml);
    $filehtml = str_replace("<coursename/>",$course->fullname,$filehtml);
    $filehtml = str_replace("<school/>", (isset( $category->name ) ? $category->name  : '') ,$filehtml);
    $filehtml = str_replace("<cmidnumber/>",$modulecode,$filehtml);
    $filehtml = str_replace("<markername/>",$participants[1]->markerlastname.' '.$participants[1]->markerfirstname,$filehtml);
    $filehtml = str_replace("<dateprinted/>",date("j F Y h:i a"),$filehtml);
    $filehtml = str_replace("<teammember/>",$participant,$filehtml);
    $filehtml = str_replace("<advisor/>",'',$filehtml);
    // add the form to the pdf output
    $html .= $filehtml;

    $last_error = error_get_last();
//    $versionmismatch = ( null !== $last_error ) ? stripos( $last_error['message'],'version mismatch' ) : -1;
    $ispdf =  ( /*( $versionmismatch > -1 )  && */(null !== $cmid) && ( count($participants) > 0 ) );
    if ($ispdf) {
        /* pdf file creation to download begin */
        $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', true);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP-10, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER-10);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetTitle('QM+ Coversheet');
        $pdf->SetSubject($barcode_data);
        $pdf->SetDisplayMode($zoom='default', $layout='SinglePage', $mode='UseNone');
        $pdf->AddPage();
        $html=utf8_encode($html);
        $pdf->writeHTML($html, TRUE, FALSE, FALSE, FALSE, '');
        //        $lp = $pdf->getPage();
        // remove all excessive pages except the first
        while( ( $lp = $pdf->getPage()) > 1){
            $pdf->deletePage($lp);
        }
        $pdf->Output('coversheet.pdf', 'I');
    } else {
        echo $OUTPUT->header();
        echo $html;
        echo $OUTPUT->footer();
    }
} else {
    echo $OUTPUT->header();
    echo 'You need to be part of the course in order to be able to use this functionality';
    echo $OUTPUT->footer();
}