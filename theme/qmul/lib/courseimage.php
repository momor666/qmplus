<?php
define('AJAX_SCRIPT', true);

require_once('../../../config.php');

$courseshortname = $_REQUEST['courseshortname'];
$imagedata = $_REQUEST['imagedata'];
$imagefilename = $_REQUEST['imagefilename'];

$course = coursebyshortname($courseshortname, 'id');
$context = context_course::instance($course->id);

$courseimage = setcourseimage($courseshortname, $imagedata, $imagefilename);
echo json_encode($courseimage);

function setcourseimage($courseshortname, $data, $filename) {
    global $CFG;

    $course = coursebyshortname($courseshortname);
    if ($course->id != SITEID) {
        // Course cover images.
        $context = context_course::instance($course->id);
    } else {
        // Site cover images.
        $context = context_system::instance();
    }

    require_capability('moodle/course:changesummary', $context);

    $fs = get_file_storage();
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $ext = $ext === 'jpeg' ? 'jpg' : $ext;

    if (!in_array($ext, supported_coverimage_types())) {
        return ['success' => false, 'warning' => get_string('unsupportedcoverimagetype', 'theme_snap', $ext)];
    }

    $newfilename = 'rawcoverimage.'.$ext;

    $binary =  base64_decode($data);
    if (strlen($binary) > get_max_upload_file_size($CFG->maxbytes)) {
        throw new moodle_exception('error:coverimageexceedsmaxbytes', 'theme_snap');
    }

    if ($course->id != SITEID) {
        // Course cover images.
        $context = context_course::instance($course->id);
        // Check suitability of course summary files area for use with cover images.
        if (!check_summary_files_for_image_suitability($context)) {
            return ['success' => false, 'warning' => get_string('coursesummaryfilesunsuitable', 'theme_snap')];
        }

        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $newfilename);

        // Remove any old course summary image files.
        $fs->delete_area_files($context->id, $fileinfo['component'], $fileinfo['filearea']);
    } else {
        // Site cover images.
        $context = context_system::instance();
        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'theme_snap',
            'filearea' => 'poster',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $newfilename);

        // Remove everything from poster area.
        $fs->delete_area_files($context->id, 'theme_snap', 'poster');
    }

    // Create new cover image file and process it.
    $storedfile = $fs->create_file_from_string($fileinfo, $binary);
    $success = $storedfile instanceof stored_file;
    if ($course->id != SITEID) {
        process_courseimage($context, $storedfile);
    } else {
        set_config('poster', $newfilename, 'theme_snap');
        process_courseimage($context);
    }
    return ['success' => $success];
}

function coursebyshortname($shortname, $fields = '*') {
    global $DB;
    $course = $DB->get_record('course', ['shortname' => $shortname], $fields, MUST_EXIST);
    return $course;
}

function check_summary_files_for_image_suitability($context) {

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'course', 'overviewfiles',0);
    $tmparr = [];
    // Remove '.' file from files array.
    foreach ($files as $file) {
        if ($file->get_filename() !== '.') {
            $tmparr[] = $file;
        }
    }
    $files = $tmparr;

    if (empty($files)) {
        // If the course summary files area is empty then its fine to upload an image.
        return true;
    }

    if (count($files) > 1) {
        // We have more than one file in the course summary files area, which is bad.
        return false;
    }

    /* @var \stored_file $file*/
    $file = end($files);
    $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
    if (!in_array($ext, supported_coverimage_types())) {
        // Unsupported file type.
        return false;
    }

    return true;
}

function supported_coverimage_types() {
    global $CFG;
    $extsstr = strtolower($CFG->courseoverviewfilesext);

    // Supported file extensions.
    $extensions = explode(',', str_replace('.', '', $extsstr));
    array_walk($extensions, function($s) {trim($s); });
    // Filter out any extensions that might be in the config but not image extensions.
    $imgextensions = ['jpg', 'png', 'gif', 'svg', 'webp'];
    return array_intersect ($extensions, $imgextensions);
}

function site_coverimage_original() {
    $theme = theme_config::load('snap');
    $filename = $theme->settings->poster;
    if ($filename) {
        if (substr($filename, 0, 1) != '/') {
            $filename = '/'.$filename;
        }
        $syscontextid = \context_system::instance()->id;
        $fullpath = '/'.$syscontextid.'/theme_snap/poster/0'.$filename;
        $fs = get_file_storage();
        return $fs->get_file_by_hash(sha1($fullpath));
    } else {
        return false;
    }
}

function get_course_firstimage($courseid) {
    $fs      = get_file_storage();
    $context = context_course::instance($courseid);
    $files   = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);

    if (count($files) > 0) {
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return $file;
            }
        }
    }

    return false;
}

function process_courseimage($context, $originalfile = false) {
    $contextlevel = $context->contextlevel;
    if ($contextlevel != CONTEXT_SYSTEM && $contextlevel != CONTEXT_COURSE) {
        throw new coding_exception('Invalid context passed to process_coverimage');
    }
    $newfilename = $contextlevel == CONTEXT_SYSTEM ? 'site-image' : 'course-image';

    if (!$originalfile) {
        if ($contextlevel == CONTEXT_SYSTEM) {
            $originalfile = site_coverimage_original($context);
        } else {
            $originalfile = get_course_firstimage($context->instanceid);
        }
    }

    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'theme_snap', 'coverimage');

    if (!$originalfile) {
        return false;
    }

    $filename = $originalfile->get_filename();
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $newfilename .= '.'.$extension;

    $filespec = array(
        'contextid' => $context->id,
        'component' => 'theme_snap',
        'filearea' => 'coverimage',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $newfilename,
    );

    $newfile = $fs->create_file_from_storedfile($filespec, $originalfile);
    $finfo = $newfile->get_imageinfo();

    if ($finfo['mimetype'] == 'image/jpeg' && $finfo['width'] > 1380) {
        return image::resize($newfile, false, 1280);
    } else {
        return $newfile;
    }
}