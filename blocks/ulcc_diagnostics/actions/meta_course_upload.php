<?php

/**
 * Moodle ULCC Admin page.
 *
 * @package    block_ulcc_diagnostic
 * @copyright  2011 onwards ULCC (http://www.ulcc.ac.uk)
 *
 * refactored 6th July 2011 for readability
 *
 */

require('../../../config.php');
global $CFG, $USER, $DB;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once $CFG->libdir . '/formslib.php';
require_once("$CFG->dirroot/enrol/meta/locallib.php");
require_once($CFG->dirroot . '/blocks/ulcc_diagnostics/actions/upload_helpers.php');
require_once($CFG->dirroot . '/blocks/ulcc_diagnostics/actions/admin_meta_course_forms.php');



@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', context_system::instance());

// array of all valid fields for validation
$STD_FIELDS = array('course_id_number', 'child_course_id_number');
$PRF_FIELDS = array();

$PAGE->set_url('/blocks/ulcc_diagnostics/actions/course_upload.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_course($SITE);
$PAGE->set_pagetype('course-upload');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add('Bulk Upload Meta Courses');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

$form_controller = new form_controller();
$form_controller->setAction('form1', 'admin_upload_meta_form', 'admin_upload_meta_intermediate');
$form_controller->setAction('form2', 'admin_upload_meta_intermediate', 'admin_upload_meta_form_final');
$form_controller->setAction('form3', 'admin_upload_meta_form_final', false);
$form_controller->Action($OUTPUT);

?>