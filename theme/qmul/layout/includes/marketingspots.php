<?php
$spots = 3;
$no = 0;

for ($i=1; $i <= $spots; $i++) {
    $name = 'marketing'.$i;
    if (isset($PAGE->theme->settings->{"title_{$name}"}) && !empty($PAGE->theme->settings->{"title_{$name}"})) {
        $no++;
    }
}

if ($no == 0) {
    return;
}

$split = false;
if ($no == 2) {
    $split = true;
}

echo '<div class="marketingspots card-deck">';
    for ($i=1; $i <= $spots; $i++) {

        $name = 'marketing'.$i;

        if (isset($PAGE->theme->settings->{"title_{$name}"}) && !empty($PAGE->theme->settings->{"title_{$name}"})) {
            $spot = new stdClass();

            $class = '';
            if ($split == true) {
                if ($i == 1) {
                    $class = 'col-md-8';
                }
                if ($i == 2) {
                    $class = 'col-md-4';
                }
            }
            $spot->class = $class;
            $spot->title = $PAGE->theme->settings->{"title_{$name}"};
            $spot->content = $PAGE->theme->settings->{"content_{$name}"};
            $spot->hasimage = false;
            $spot->hascontent = false;
            if (!empty($PAGE->theme->settings->{"content_{$name}"})) {
                $spot->hascontent = true;
            }
            if (!empty($PAGE->theme->settings->{"image_{$name}"})) {
                $spot->hasimage = true;
                $spot->image =  $PAGE->theme->setting_file_url('image_'.$name, 'image_'.$name);
            }
            $spot->hasbutton = false;
            if (!empty($PAGE->theme->settings->{"buttontext_{$name}"}) && !empty($PAGE->theme->settings->{"buttonurl_{$name}"})) {
                $spot->hasbutton = true;
                $spot->buttontext = $PAGE->theme->settings->{"buttontext_{$name}"};
                $spot->url = $PAGE->theme->settings->{"buttonurl_{$name}"};
            }

            echo $OUTPUT->render_from_template('theme_qmul/marketingspot', $spot);
        }
    }
echo '</div>';
?>
