<?php
// This file is part of The Bootstrap Moodle theme
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

require_once(dirname(__FILE__).'/setup.php');
global $SESSION;

$bpclass = ' closed';
$bpopen = false;
$userpreference = get_user_preferences('theme_qmul_logindashboard');
if (is_null($userpreference) || $userpreference == 1) {
    if (isset($SESSION->justloggedin)) {
        $bpclass = '';
        $bpopen = true;
    }
}

$PAGE->set_popup_notification_allowed(false);
echo $OUTPUT->doctype() ?>
<html class="qmul" <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<?php
    require_once(dirname(__FILE__).'/includes/header.php');
?>
    <?php
        if ($PAGE->pagelayout == 'login') {
            echo html_writer::start_tag('div', array('class'=>'loginwrap', 'style'=>$loginbg));
        } else {
            echo html_writer::start_tag('div', array('class'=>'outer'));
            if (isloggedin() && !isguestuser()) {
                echo html_writer::start_tag('div', array('class'=>'backpack text-white'.$bpclass));
                    echo html_writer::start_tag('div', array('class'=>'container-fluid'));
                        $svg = '<svg width="20px" height="20px" viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><title>'.get_string('myqmplusdashboard', 'theme_qmul').'</title><g transform="translate(-655.000000, -106.000000)" fill="#005A9A"><g transform="translate(655.000000, 100.000000)"><path d="M11.0933333,6 L20,6 L20,12.6666667 L11.0933333,12.6666667 L11.0933333,6 Z M11.0933333,26 L11.0933333,14.9066667 L20,14.9066667 L20,26 L11.0933333,26 Z M0,26 L0,19.3333333 L8.90666667,19.3333333 L8.90666667,26 L0,26 Z M0,17.0933333 L0,6 L8.90666667,6 L8.90666667,17.0933333 L0,17.0933333 Z" id="dashboard---material"></path></g></g></g></svg>';
                        echo html_writer::tag('div', $OUTPUT->heading($svg.get_string('myqmplusdashboard', 'theme_qmul'), 2), array('class'=>'text-center heading mb-2'));
                        echo html_writer::start_tag('div', array('class'=>'row user mb-2'));
                            //User info
                            echo html_writer::start_tag('div', array('class'=>'col-12 col-md-4'));
                                echo $OUTPUT->user_info();
                            echo html_writer::end_tag('div');
                            //Menu
                            echo html_writer::start_tag('div', array('class'=>'col-12 col-md-8 text-right'));
                                echo $OUTPUT->user_menu();
                            echo html_writer::end_tag('div');
                        echo html_writer::end_tag('div');
                        echo html_writer::start_tag('div', array('class'=>'row content'));
                            //User info
                            echo html_writer::start_tag('div', array('class'=>'col-12 col-md-6'));
                                echo $OUTPUT->qmul_modules();
                            echo html_writer::end_tag('div');
                            //Menu
                            echo html_writer::start_tag('div', array('class'=>'col-12 col-md-6'));
                                echo $OUTPUT->blocks('widgets');
                            echo html_writer::end_tag('div');
                        echo html_writer::end_tag('div');
                    echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');
            }
        }
    ?>
    <div class="moodlecontent">
        <?php
            require_once(dirname(__FILE__).'/includes/alerts.php');
            if ($PAGE->pagelayout == 'course' && $COURSE->format == 'landingpage') {
                $extra = '';
                $extraclass = '';
                if ($PAGE->pagelayout == 'course' || $PAGE->pagelayout == 'incourse') {
                    require_once($CFG->libdir.'/coursecatlib.php');
                    $course = new course_in_list($COURSE);

                    $image = '';
                    $url = '';
                    try {
                        foreach ($course->get_course_overviewfiles() as $file) {
                            $isimage = $file->is_valid_image();
                            $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                                    '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                                    $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                        }
                    } catch (Exception $e) {
                        $url = '';
                    }
                    if (!empty($url)) {
                        $extraclass .= ' hasimage';
                        $extra = "style='background-image: url($url);'";
                    }
                }
            ?>
            <div class="courseheader col-12">
                <div class="row">
                    <div class="coursebanner col-12 <?php echo $extraclass ?>" <?php echo $extra ?>>
                        <div class="container-fluid">
                            <?php
                                echo html_writer::tag('h1', format_text($COURSE->fullname), array('class'=>'coursename'));
                            ?>
                        </div>
                    </div>
                </div>
            </div>
                <?php
            }
            $navbar = $OUTPUT->navbar();
            if (!isset($PAGE->layout_options['nonavbar']) || $PAGE->layout_options['nonavbar'] == false) { ?>
                <?php echo $OUTPUT->context_header(); ?>
                <?php if ($COURSE->format != 'landingpage' && !empty($navbar)) { ?>
                    <div id="page-navbar" class="clearfix container-fluid">
                        <div class="bg-white col-12 py-12 clearfix">
                            <nav class="breadcrumb-nav" role="navigation" aria-label="breadcrumb"><?php echo $navbar ?></nav>
                        </div>
                    </div>
                <?php } ?>
            <?php
            }
            ?>

            <?php
            if ($PAGE->pagetype == 'course-view-landingpage') {
                echo html_writer::start_tag('div', ['id'=>'region-main', 'class'=>'mbbs-main-region']);
                echo html_writer::tag('div', $COURSE->summary, ['class'=>'container-fluid']);
                echo html_writer::end_tag('div');
            }

            ?>

        <div id="page" class="container-fluid pt-1">
            <div id="page-content" class="row">
                <div id="region-main" class="<?php echo $regions['content']; ?>">

                <?php
                echo $OUTPUT->render_news_ticker();
                if ($PAGE->pagelayout == 'course' && $COURSE->format != 'landingpage') {
                    $extra = '';
                    $extraclass = '';
                    if ($PAGE->pagelayout == 'course' || $PAGE->pagelayout == 'incourse') {
                        require_once($CFG->libdir.'/coursecatlib.php');
                        $course = new course_in_list($COURSE);

                        $image = '';
                        $url = '';
                        try {
                            foreach ($course->get_course_overviewfiles() as $file) {
                                $isimage = $file->is_valid_image();
                                $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                                        '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                                        $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                            }
                        } catch (Exception $e) {
                            $url = '';
                        }
                        if (!empty($url)) {
                            $extraclass .= ' hasimage';
                            $extra = "style='background-image: url($url);'";
                        }
                    }
                    $news = $OUTPUT->course_news();
                    if (!empty($news)) { ?>
                        <div class="courseheader mb-1 col-12">
                            <div class="row d-block clearfix">
                                <div class="courseimage hidden-sm-down ml-1 float-right d-block<?php echo $extraclass ?>" <?php echo $extra ?>>
                                </div>
                                <div class="summary p-1 d-block">
                                    <?php
                                        echo $news;
                                    ?>
                                </div>
                            </div>
                        </div>
                <?php
                    }
                }
                    echo $OUTPUT->course_content_header();
                    if ($PAGE->pagelayout == 'mydashboard' && $PAGE->theme->settings->coursebox == true) {
                        $courses = enrol_get_my_courses();
                        echo $OUTPUT->user_stats($courses);
                        echo $OUTPUT->dashboard_courses($courses);
                    }
                    echo $OUTPUT->main_content();
                    echo $OUTPUT->course_content_footer();
                    ?>
                </div>

                <?php
                    if ($knownregionlandingpage || $knownregionpost) {
                        if ($knownregionlandingpage && $hassidepost) {
                            echo html_writer::start_tag('aside', array('class'=>$regions['post']));
                            if ($knownregionlandingpage) {
                                if ($COURSE->format != 'landingpage') {
                                    echo html_writer::start_tag('div', array("class"=>'hidden'));
                                }
                                if ($PAGE->user_is_editing()) {
                                    echo html_writer::tag('h5', get_string('region-landingpage', 'theme_qmul'));
                                    echo html_writer::tag('small', get_string('region-landingpage_help', 'theme_qmul'));
                                }
                                echo $OUTPUT->blocks('landingpage');
                                if ($PAGE->user_is_editing()) {
                                    echo html_writer::tag('small', get_string('region-landingpage_end', 'theme_qmul'));
                                    echo html_writer::empty_tag('hr');
                                }
                                if ($COURSE->format != 'landingpage') {
                                    echo html_writer::end_tag('div');
                                }
                            }
                            if ($hassidepost) {
                                echo $OUTPUT->blocks('side-post');
                            }
                            echo html_writer::end_tag('aside');
                        }
                        if ($knownregionlandingpage && !$hassidepost) {
                            if ($COURSE->format != 'landingpage') {
                                echo html_writer::start_tag('aside', array('class'=>'hidden'));
                            } else {
                                echo html_writer::start_tag('aside', array('class'=>$regions['post']));
                            }
                            echo $OUTPUT->blocks('landingpage');
                            echo html_writer::end_tag('aside');
                        }
                    }
                ?>
            </div>
            <div class="row mt-2 boxlinks">
                <div class="col-12 col-md-6 col-lg-3 mb-1">
                    <div class="helpsupport footblock">
                        <?php
                            $background = $PAGE->theme->setting_file_url('helpsupportbg', 'helpsupportbg');
                            $background = "background-image: url('$background');";
                        ?>
                        <a style="<?php echo $background ?>" href="<?php echo $PAGE->theme->settings->helpsupportlink ?>">
                            <h4>
                                <i class="helpsupport"></i>
                                <?php echo get_string('helpsupport', 'theme_qmul') ?>
                            </h4>
                        </a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-1">
                    <div class="qmplusmedia footblock">
                        <?php
                            $background = $PAGE->theme->setting_file_url('qmplusmediabg', 'qmplusmediabg');
                            $background = "background-image: url('$background');";
                        ?>
                        <a style="<?php echo $background ?>" href="<?php echo $PAGE->theme->settings->qmplusmedialink ?>">
                            <h4>
                                <i class="qmplusmedia"></i>
                                <?php echo get_string('qmplusmedia', 'theme_qmul') ?>
                            </h4>
                        </a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-1">
                    <div class="qmplushub footblock">
                        <?php
                            $background = $PAGE->theme->setting_file_url('qmplushubbg', 'qmplushubbg');
                            $background = "background-image: url('$background');";
                        ?>
                        <a style="<?php echo $background ?>" href="<?php echo $PAGE->theme->settings->qmplushublink ?>">
                            <h4>
                                <i class="qmplushub"></i>
                                <?php echo get_string('qmplushub', 'theme_qmul') ?>
                            </h4>
                        </a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-1">
                    <div class="qmplusarchive footblock">
                        <?php
                            $background = $PAGE->theme->setting_file_url('qmplusarchivebg', 'qmplusarchivebg');
                            $background = "background-image: url('$background');";
                        ?>
                        <a style="<?php echo $background ?>" href="<?php echo $PAGE->theme->settings->qmplusarchivelink ?>">
                            <h4>
                                <i class="qmplusarchive"></i>
                                <?php echo get_string('qmplusarchive', 'theme_qmul') ?>
                            </h4>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <footer id="page-footer" class="footer">
            <?php require_once(dirname(__FILE__).'/includes/footer.php'); ?>
        </footer>
    </div>
    <?php if ($PAGE->pagelayout != 'login') { ?>
    <div class="searchcontainer closed">
        <div class="overlay"></div>
        <div class="searchbox container-fluid">
            <div class="input-group group">
                <input type="text" class="form-control coursesearch" name="coursesearch">
                <div class="input-group-addon bg-primary" id="basic-addon2">
                    <svg width="35px" height="35px" viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><title><?php echo get_string('search', 'theme_qmul') ?></title><g transform="translate(-1289.000000, -25.000000)" fill="#FFFFFF"><g><path d="M1302.84615,33.4615385 C1302.84615,31.9791593 1302.31932,30.7111431 1301.26562,29.6574519 C1300.21193,28.6037608 1298.94392,28.0769231 1297.46154,28.0769231 C1295.97916,28.0769231 1294.71114,28.6037608 1293.65745,29.6574519 C1292.60376,30.7111431 1292.07692,31.9791593 1292.07692,33.4615385 C1292.07692,34.9439177 1292.60376,36.2119338 1293.65745,37.265625 C1294.71114,38.3193162 1295.97916,38.8461538 1297.46154,38.8461538 C1298.94392,38.8461538 1300.21193,38.3193162 1301.26562,37.265625 C1302.31932,36.2119338 1302.84615,34.9439177 1302.84615,33.4615385 Z M1309,43.4615385 C1309,43.8782072 1308.84776,44.2387805 1308.54327,44.5432692 C1308.23878,44.8477579 1307.87821,45 1307.46154,45 C1307.02884,45 1306.66827,44.8477579 1306.37981,44.5432692 L1302.25721,40.4326923 C1300.82291,41.426287 1299.22437,41.9230769 1297.46154,41.9230769 C1296.3157,41.9230769 1295.21996,41.7007234 1294.17428,41.2560096 C1293.1286,40.8112959 1292.22717,40.2103403 1291.46995,39.453125 C1290.71274,38.6959097 1290.11178,37.7944764 1289.66707,36.7487981 C1289.22235,35.7031198 1289,34.6073775 1289,33.4615385 C1289,32.3156994 1289.22235,31.2199572 1289.66707,30.1742788 C1290.11178,29.1286005 1290.71274,28.2271672 1291.46995,27.4699519 C1292.22717,26.7127366 1293.1286,26.1117811 1294.17428,25.6670673 C1295.21996,25.2223535 1296.3157,25 1297.46154,25 C1298.60738,25 1299.70312,25.2223535 1300.7488,25.6670673 C1301.79448,26.1117811 1302.69591,26.7127366 1303.45312,27.4699519 C1304.21034,28.2271672 1304.8113,29.1286005 1305.25601,30.1742788 C1305.70072,31.2199572 1305.92308,32.3156994 1305.92308,33.4615385 C1305.92308,35.2243678 1305.42629,36.8229095 1304.43269,38.2572115 L1308.55529,42.3798077 C1308.85176,42.6762835 1309,43.0368569 1309,43.4615385 Z"></path></g></g></g></svg>
                </div>
                <?php
                    if (isloggedin() && !isguestuser()) {
                        echo html_writer::start_tag('div', array('class'=>'searchoptions'));
                        echo html_writer::tag('div', get_string('mymodules', 'theme_qmul'), array('class'=>'mymodules active', 'data-type'=>'my'));
                        echo html_writer::tag('div', get_string('allmodules', 'theme_qmul'), array('class'=>'allmodules', 'data-type'=>'all'));
                        echo html_writer::end_tag('div');
                    }
                ?>
            </div>
            <div class="searchresults p-1"></div>
        </div>
        <div id="userid" class="hidden" data-userid="<?php echo $USER->id ?>"></div>
    </div>
    <?php } ?>
</div> <!-- End outer or login wrap div -->

<?php echo $OUTPUT->standard_end_of_body_html() ?>

<!-- Start Google Analytics -->
<?php if ($hasanalytics) { ?>
    <?php require_once(dirname(__FILE__).'/includes/analytics.php'); ?>
<?php } ?>
<!-- End Google Analytics -->

</body>
</html>