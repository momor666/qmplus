<?php

$hasanalytics = (empty($PAGE->theme->settings->useanalytics)) ? false : $PAGE->theme->settings->useanalytics;

$slideoutmenu = (empty($PAGE->theme->settings->slideoutmenu)) ? false : $PAGE->theme->settings->slideoutmenu;

$hasfootnote = (!empty($PAGE->theme->settings->footnote));
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);

if ($COURSE->format == 'landingpage') {
	$hassidepost = true;
}

$knownregionpre = $PAGE->blocks->is_known_region('side-pre');
$knownregionpost = $PAGE->blocks->is_known_region('side-post');
$knownregionlandingpage = $PAGE->blocks->is_known_region('landingpage');

$regions = qmul_bootstrap_grid($hassidepre, $hassidepost);

// Generic Settings
$haslogo = (!empty($PAGE->theme->settings->logo));
if ($haslogo) {
    $logo = html_writer::empty_tag('img', array('src'=>$PAGE->theme->setting_file_url('logo', 'logo')));
} else {
    $logo = html_writer::empty_tag('img', array('src'=>new moodle_url('/theme/'.$PAGE->theme->name.'/pix/logo.png')));
}

$hasios         = (empty($PAGE->theme->settings->ios)) ? false : $PAGE->theme->settings->ios;
$hasandroid     = (empty($PAGE->theme->settings->android)) ? false : $PAGE->theme->settings->android;

$footerl = 'footer-left';
$footerm = 'footer-middle';
$footerr = 'footer-right';

$hascopyright = (empty($PAGE->theme->settings->copyright)) ? false : $PAGE->theme->settings->copyright;
$hasfootnote = (empty($PAGE->theme->settings->footnote)) ? false : $PAGE->theme->settings->footnote;
$hasfootright = (empty($PAGE->theme->settings->footright)) ? false : $PAGE->theme->settings->footright;
$hasfooterleft = (empty($PAGE->layout_options['noblocks']));
$hasfootermiddle = (empty($PAGE->layout_options['noblocks']));
$hasfooterright = (empty($PAGE->layout_options['noblocks']));

$hasmarketing1image = (!empty($PAGE->theme->settings->marketing1image));
$hasmarketing2image = (!empty($PAGE->theme->settings->marketing2image));
$hasmarketing3image = (!empty($PAGE->theme->settings->marketing3image));

$hasalert1 = (empty($PAGE->theme->settings->enable1alert)) ? false : $PAGE->theme->settings->enable1alert;
$hasalert2 = (empty($PAGE->theme->settings->enable2alert)) ? false : $PAGE->theme->settings->enable2alert;
$hasalert3 = (empty($PAGE->theme->settings->enable3alert)) ? false : $PAGE->theme->settings->enable3alert;
$alertinfo = '<span><i class="glyphicon glyphicon-lightbulb"></i></span>';
$alertwarning = '<span><i class="glyphicon glyphicon-warning-sign"></i></span>';
$alertsuccess = '<span><i class="glyphicon glyphicon-bullhorn"></i></span>';

$loginbg = '';
if (!empty($PAGE->theme->settings->loginbackground)) {
    $img = $PAGE->theme->setting_file_url('loginbackground', 'loginbackground');
    $loginbg = "background-image: url('$img')";
}