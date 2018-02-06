<?php

$slides = 6;
$no = 0;

for ($i=1; $i <= $slides; $i++) {
    $name = 'slide'.$i;

    if (isset($PAGE->theme->settings->{"image_{$name}"}) && !empty($PAGE->theme->settings->{"image_{$name}"})) {
        $no++;
    }
}

$fullwidth = false;
$fullclass = '';
if ($PAGE->theme->settings->usefullwidthslider) {
    $fullwidth = true;
    $fullclass = 'mt-0';
}

if ($no == 0) {
    return;
}

$slideroutput = '';
$slideroutput .= html_writer::start_tag('div', array('class' => 'slidercontainer mb-2'.$fullclass));
$slideroutput .= html_writer::start_tag('div', array('class' => 'synergyslider' ));
for ($i=1; $i <= $slides; $i++) {
    $name = 'slide'.$i;

    if (isset($PAGE->theme->settings->{"image_{$name}"}) && !empty($PAGE->theme->settings->{"image_{$name}"})) {
        $slider = new stdClass();
        $slider->isparallax = $fullwidth;
        $slider->image = $PAGE->theme->setting_file_url('image_'.$name, 'image_'.$name);
        if (!empty($PAGE->theme->settings->{"url_{$name}"})) {
            $slider->hasurl = true;
            $slider->url =  $PAGE->theme->settings->{"url_$name"};
            $slider->slidebutton = get_string('slidebutton','theme_qmul');
        }
        if (!empty($PAGE->theme->settings->{"caption_{$name}"})) {
            $slider->hascaption = true;
            $slider->caption =  $PAGE->theme->settings->{"caption_$name"};
        }
        $slider->number = $i-1;
        $slider->first = false;
        if ($i == 1) {
            $slider->first = true;
        }
        $slideroutput .= $OUTPUT->render_from_template('theme_qmul/slide', $slider);

    }
}
$slideroutput .= html_writer::end_tag('div');
$slideroutput .= html_writer::end_tag('div');

echo $slideroutput;

$PAGE->requires->js_call_amd('theme_qmul/slider', 'init');
