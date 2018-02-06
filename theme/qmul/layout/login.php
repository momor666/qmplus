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
<div class="outer">
    <div class="moodlecontent">
        <div id="page">
            <div id="page-content" class="row">
                <div id="region-main" class="<?php echo $regions['content']; ?>">
                    <?php
                    echo $OUTPUT->course_content_header();
                    echo $OUTPUT->main_content();
                    echo $OUTPUT->course_content_footer();
                    ?>
                </div>
            </div>
        </div>
        <footer id="page-footer" class="footer">
            <?php require_once(dirname(__FILE__).'/includes/footer.php'); ?>
        </footer>
    </div>
</div> <!-- End outer or login wrap div -->

<?php echo $OUTPUT->standard_end_of_body_html() ?>

<!-- Start Google Analytics -->
<?php if ($hasanalytics) { ?>
    <?php require_once(dirname(__FILE__).'/includes/analytics.php'); ?>
<?php } ?>
<!-- End Google Analytics -->

</body>
</html>
