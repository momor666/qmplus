<!-- Start Alerts -->
<?php if ($hasalert1 || $hasalert2 || $hasalert3) {
    $content = '';
    if ($hasalert1) {
        if (!isset($_COOKIE['theme_qmul_alert1']) || $_COOKIE['theme_qmul_alert1'] != 'closed') {
            $alert = new stdClass();
            $alert->alertnumber = 1;
            $alert->alerttype = $PAGE->theme->settings->alert1type;
            if ($PAGE->theme->settings->alert1type == 'info') {
                $alert->alerticon = $alertinfo;
            } else if ($PAGE->theme->settings->alert1type == 'warning') {
                $alert->alerticon = $alertwarning;
            } else {
                $alert->alerticon = $alertsuccess;
            }
            $alert->alerttitle = $PAGE->theme->settings->alert1title;
            $alert->alerttext = format_text($PAGE->theme->settings->alert1text);

            $content .= $OUTPUT->render_from_template('theme_qmul/alert', $alert);
        }
    }

    if ($hasalert2) {
        if (!isset($_COOKIE['theme_qmul_alert2']) || $_COOKIE['theme_qmul_alert2'] != 'closed') {
            $alert = new stdClass();
            $alert->alertnumber = 2;
            $alert->alerttype = $PAGE->theme->settings->alert2type;
            if ($PAGE->theme->settings->alert2type == 'info') {
                $alert->alerticon = $alertinfo;
            } else if ($PAGE->theme->settings->alert2type == 'warning') {
                $alert->alerticon = $alertwarning;
            } else {
                $alert->alerticon = $alertsuccess;
            }
            $alert->alerttitle = $PAGE->theme->settings->alert2title;
            $alert->alerttext = format_text($PAGE->theme->settings->alert2text);

            $content .= $OUTPUT->render_from_template('theme_qmul/alert', $alert);
        }
    }

    if ($hasalert3) {
        if (!isset($_COOKIE['theme_qmul_alert3']) || $_COOKIE['theme_qmul_alert3'] != 'closed') {
            $alert = new stdClass();
            $alert->alertnumber = 3;
            $alert->alerttype = $PAGE->theme->settings->alert3type;
            if ($PAGE->theme->settings->alert3type == 'info') {
                $alert->alerticon = $alertinfo;
            } else if ($PAGE->theme->settings->alert3type == 'warning') {
                $alert->alerticon = $alertwarning;
            } else {
                $alert->alerticon = $alertsuccess;
            }
            $alert->alerttitle = $PAGE->theme->settings->alert3title;
            $alert->alerttext = format_text($PAGE->theme->settings->alert3text);

            $content .= $OUTPUT->render_from_template('theme_qmul/alert', $alert);
        }
    }

    if (!empty($content)) {
        echo html_writer::tag('div', $content, array('class'=>'container-fluid alerts'));
    }

} ?>
<!-- End Alerts -->