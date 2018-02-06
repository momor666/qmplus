<?php

/**
 * Moodle ULCC Admin page.
 *
 * @package    block_ulcc_diagnostic
 * @copyright  2011 onwards ULCC (http://www.ulcc.ac.uk)
 *
 * Refactored for readability 5-7-2011
 */

require('../../../config.php');
global $CFG, $USER, $DB, $OUTPUT;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once $CFG->libdir . '/formslib.php';
require_once($CFG->dirroot . '/blocks/ulcc_diagnostics/actions/upload_helpers.php');
require_once($CFG->dirroot . '/blocks/ulcc_diagnostics/actions/admin_category_merge_form.php');

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$text_library = textlib_get_instance();
$system_context = get_context_instance(CONTEXT_SYSTEM);

// array of all valid fields for validation
$STD_FIELDS = array('name', 'idnumber', 'description', 'parent');
$PRF_FIELDS = array();


$PAGE->set_url('/blocks/ulcc_diagnostics/actions/category_merge.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_course($SITE);
$PAGE->set_pagetype('category-upload');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Bulk Upload Categories');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

$form_controller = new form_controller();
$form_controller->setAction('form1', 'admin_category_merge_form', 'admin_category_merge_form_intermediate');
$form_controller->setAction('form2', 'admin_category_merge_form_intermediate', 'admin_category_merge_form_final');
$form_controller->setAction('form3', 'admin_category_merge_form_final', false);
$form_controller->Action($OUTPUT);



?>