<?php

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once('../lib.php');
require_once('../classes/output/core_renderer.php');

$icons = $_REQUEST['icons'];
$return = array();

foreach ($icons as $icon) {
	$classes = $icon['classes'];
	$classes = str_replace('hidden', '', $classes);
	$iconname = $icon['iconname'];
	if (!$replacement = theme_qmul_core_renderer::replace_moodle_icon($iconname, $classes)) {
		$replacement = '';
	}
	$return[$iconname] = $replacement;
}

print json_encode($return);