<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Public API of the log report.
 *
 * Defines the APIs used by QM+ reports
 *
 * @package    report_qmplus
 * @copyright  2015 Queen Mary University of London
 * @author     Panagiotis Paralakis  <p.paralakis@qmul.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/coursecatlib.php');

/**
 * Return a list of page types
 *
 * @return array
 *
 */
function report_qmplus_getMimeType_list() {
    $array = array(
        'application' => 'application',
        'audio' => 'audio',
        'chemical' => 'chemical',
        'drawing' => 'drawing',
        'i-world' => 'i-world',
        'image' => 'image',
        'message' => 'message',
        'model' => 'model',
        'multipart' => 'multipart',
        'music' => 'music',
        'paleovu' => 'paleovu',
        'text' => 'text',
        'video' => 'video',
        'windows' => 'windows',
        'www' => 'www',
        'x-conference' => 'x-conference',
        'x-music' => 'x-music',
        'x-world' => 'x-world',
        'xgl' => 'xgl',
        'application/base64' => 'application/base64',
        'application/book' => 'application/book',
        'application/clariscad' => 'application/clariscad',
        'application/commonground' => 'application/commonground',
        'application/drafting' => 'application/drafting',
        'application/excel' => 'application/excel',
        'application/freeloader' => 'application/freeloader',
        'application/futuresplash' => 'application/futuresplash',
        'application/groupwise' => 'application/groupwise',
        'application/hta' => 'application/hta',
        'application/i-deas' => 'application/i-deas',
        'application/inf' => 'application/inf',
        'application/marc' => 'application/marc',
        'application/mbedlet' => 'application/mbedlet',
        'application/mime' => 'application/mime',
        'application/mspowerpoint' => 'application/mspowerpoint',
        'application/msword' => 'application/msword',
        'application/netmc' => 'application/netmc',
        'application/octet-stream' => 'application/octet-stream',
        'application/oda' => 'application/oda',
        'application/pdf' => 'application/pdf',
        'application/pkcs7-signature' => 'application/pkcs7-signature',
        'application/pkix-crl' => 'application/pkix-crl',
        'application/postscript' => 'application/postscript',
        'application/pro_eng' => 'application/pro_eng',
        'application/set' => 'application/set',
        'application/smil' => 'application/smil',
        'application/solids' => 'application/solids',
        'application/sounder' => 'application/sounder',
        'application/step' => 'application/step',
        'application/streamingmedia' => 'application/streamingmedia',
        'application/vda' => 'application/vda',
        'application/vnd.fdf' => 'application/vnd.fdf',
        'application/vnd.hp-hpgl' => 'application/vnd.hp-hpgl',
        'application/vnd.ms-pki.certstore' => 'application/vnd.ms-pki.certstore',
        'application/vnd.ms-pki.pko' => 'application/vnd.ms-pki.pko',
        'application/vnd.ms-pki.seccat' => 'application/vnd.ms-pki.seccat',
        'application/vnd.ms-powerpoint' => 'application/vnd.ms-powerpoint',
        'application/vnd.ms-project' => 'application/vnd.ms-project',
        'application/vnd.nokia.configuration-message' => 'application/vnd.nokia.configuration-message',
        'application/vnd.nokia.ringing-tone' => 'application/vnd.nokia.ringing-tone',
        'application/vnd.rn-realplayer' => 'application/vnd.rn-realplayer',
        'application/vnd.wap.wmlc' => 'application/vnd.wap.wmlc',
        'application/vnd.wap.wmlscriptc' => 'application/vnd.wap.wmlscriptc',
        'application/vnd.xara' => 'application/vnd.xara',
        'application/vocaltec-media-desc' => 'application/vocaltec-media-desc',
        'application/vocaltec-media-file' => 'application/vocaltec-media-file',
        'application/wordperfect' => 'application/wordperfect',
        'application/wordperfect6.0' => 'application/wordperfect6.0',
        'application/wordperfect6.1' => 'application/wordperfect6.1',
        'application/x-123' => 'application/x-123',
        'application/x-aim' => 'application/x-aim',
        'application/x-authorware-bin' => 'application/x-authorware-bin',
        'application/x-authorware-map' => 'application/x-authorware-map',
        'application/x-authorware-seg' => 'application/x-authorware-seg',
        'application/x-bcpio' => 'application/x-bcpio',
        'application/x-bsh' => 'application/x-bsh',
        'application/x-bytecode.python' => 'application/x-bytecode.python',
        'application/x-bzip' => 'application/x-bzip',
        'application/x-bzip2' => 'application/x-bzip2',
        'application/x-cdlink' => 'application/x-cdlink',
        'application/x-chat' => 'application/x-chat',
        'application/x-cocoa' => 'application/x-cocoa',
        'application/x-compressed' => 'application/x-compressed',
        'application/x-conference' => 'application/x-conference',
        'application/x-cpio' => 'application/x-cpio',
        'application/x-cpt' => 'application/x-cpt',
        'application/x-deepv' => 'application/x-deepv',
        'application/x-director' => 'application/x-director',
        'application/x-dvi' => 'application/x-dvi',
        'application/x-elc' => 'application/x-elc',
        'application/x-envoy' => 'application/x-envoy',
        'application/x-esrehber' => 'application/x-esrehber',
        'application/x-excel' => 'application/x-excel',
        'application/x-freelance' => 'application/x-freelance',
        'application/x-gsp' => 'application/x-gsp',
        'application/x-gss' => 'application/x-gss',
        'application/x-gtar' => 'application/x-gtar',
        'application/x-gzip' => 'application/x-gzip',
        'application/x-hdf' => 'application/x-hdf',
        'application/x-helpfile' => 'application/x-helpfile',
        'application/x-httpd-imap' => 'application/x-httpd-imap',
        'application/x-ima' => 'application/x-ima',
        'application/x-internett-signup' => 'application/x-internett-signup',
        'application/x-inventor' => 'application/x-inventor',
        'application/x-ip2' => 'application/x-ip2',
        'application/x-java-class' => 'application/x-java-class',
        'application/x-java-commerce' => 'application/x-java-commerce',
        'application/x-javascript' => 'application/x-javascript',
        'application/x-koan' => 'application/x-koan',
        'application/x-latex' => 'application/x-latex',
        'application/x-lha' => 'application/x-lha',
        'application/x-livescreen' => 'application/x-livescreen',
        'application/x-lotus' => 'application/x-lotus',
        'application/x-lzh' => 'application/x-lzh',
        'application/x-lzx' => 'application/x-lzx',
        'application/x-mac-binhex40' => 'application/x-mac-binhex40',
        'application/x-macbinary' => 'application/x-macbinary',
        'application/x-magic-cap-package-1.0' => 'application/x-magic-cap-package-1.0',
        'application/x-mathcad' => 'application/x-mathcad',
        'application/x-meme' => 'application/x-meme',
        'application/x-mif' => 'application/x-mif',
        'application/x-mix-transfer' => 'application/x-mix-transfer',
        'application/x-msexcel' => 'application/x-msexcel',
        'application/x-mspowerpoint' => 'application/x-mspowerpoint',
        'application/x-navi-animation' => 'application/x-navi-animation',
        'application/x-navidoc' => 'application/x-navidoc',
        'application/x-navimap' => 'application/x-navimap',
        'application/x-navistyle' => 'application/x-navistyle',
        'application/x-netcdf' => 'application/x-netcdf',
        'application/x-newton-compatible-pkg' => 'application/x-newton-compatible-pkg',
        'application/x-nokia-9000-communicator-add-on-software' => 'application/x-nokia-9000-communicator-add-on-software',
        'application/x-omc' => 'application/x-omc',
        'application/x-omcdatamaker' => 'application/x-omcdatamaker',
        'application/x-omcregerator' => 'application/x-omcregerator',
        'application/x-pagemaker' => 'application/x-pagemaker',
        'application/x-pcl' => 'application/x-pcl',
        'application/x-pixclscript' => 'application/x-pixclscript',
        'application/x-pkcs10' => 'application/x-pkcs10',
        'application/x-pkcs12' => 'application/x-pkcs12',
        'application/x-pkcs7-certreqresp' => 'application/x-pkcs7-certreqresp',
        'application/x-pkcs7-mime' => 'application/x-pkcs7-mime',
        'application/x-pkcs7-signature' => 'application/x-pkcs7-signature',
        'application/x-project' => 'application/x-project',
        'application/x-qpro' => 'application/x-qpro',
        'application/x-sdp' => 'application/x-sdp',
        'application/x-sea' => 'application/x-sea',
        'application/x-seelogo' => 'application/x-seelogo',
        'application/x-shar' => 'application/x-shar',
        'application/x-shockwave-flash' => 'application/x-shockwave-flash',
        'application/x-sprite' => 'application/x-sprite',
        'application/x-stuffit' => 'application/x-stuffit',
        'application/x-sv4cpio' => 'application/x-sv4cpio',
        'application/x-sv4crc' => 'application/x-sv4crc',
        'application/x-tar' => 'application/x-tar',
        'application/x-tbook' => 'application/x-tbook',
        'application/x-tex' => 'application/x-tex',
        'application/x-texinfo' => 'application/x-texinfo',
        'application/x-troff' => 'application/x-troff',
        'application/x-troff-man' => 'application/x-troff-man',
        'application/x-troff-me' => 'application/x-troff-me',
        'application/x-troff-ms' => 'application/x-troff-ms',
        'application/x-visio' => 'application/x-visio',
        'application/x-vnd.audioexplosion.mzz' => 'application/x-vnd.audioexplosion.mzz',
        'application/x-vnd.ls-xpix' => 'application/x-vnd.ls-xpix',
        'application/x-wais-source' => 'application/x-wais-source',
        'application/x-winhelp' => 'application/x-winhelp',
        'application/x-wintalk' => 'application/x-wintalk',
        'application/x-wpwin' => 'application/x-wpwin',
        'application/x-wri' => 'application/x-wri',
        'application/x-x509-ca-cert' => 'application/x-x509-ca-cert',
        'application/x-x509-user-cert' => 'application/x-x509-user-cert',
        'audio/it' => 'audio/it',
        'audio/make' => 'audio/make',
        'audio/mid' => 'audio/mid',
        'audio/mpeg' => 'audio/mpeg',
        'audio/s3m' => 'audio/s3m',
        'audio/tsp-audio' => 'audio/tsp-audio',
        'audio/tsplayer' => 'audio/tsplayer',
        'audio/vnd.qcelp' => 'audio/vnd.qcelp',
        'audio/voxware' => 'audio/voxware',
        'audio/x-adpcm' => 'audio/x-adpcm',
        'audio/x-aiff' => 'audio/x-aiff',
        'audio/x-au' => 'audio/x-au',
        'audio/x-gsm' => 'audio/x-gsm',
        'audio/x-jam' => 'audio/x-jam',
        'audio/x-liveaudio' => 'audio/x-liveaudio',
        'audio/x-mod' => 'audio/x-mod',
        'audio/x-mpequrl' => 'audio/x-mpequrl',
        'audio/x-nspaudio' => 'audio/x-nspaudio',
        'audio/x-pn-realaudio' => 'audio/x-pn-realaudio',
        'audio/x-pn-realaudio-plugin' => 'audio/x-pn-realaudio-plugin',
        'audio/x-psid' => 'audio/x-psid',
        'audio/x-realaudio' => 'audio/x-realaudio',
        'audio/x-twinvq' => 'audio/x-twinvq',
        'audio/x-twinvq-plugin' => 'audio/x-twinvq-plugin',
        'audio/x-vnd.audioexplosion.mjuicemediafile' => 'audio/x-vnd.audioexplosion.mjuicemediafile',
        'audio/x-voc' => 'audio/x-voc',
        'audio/x-wav' => 'audio/x-wav',
        'audio/xm' => 'audio/xm',
        'chemical/x-pdb' => 'chemical/x-pdb',
        'i-world/i-vrml' => 'i-world/i-vrml',
        'image/bmp' => 'image/bmp',
        'image/cmu-raster' => 'image/cmu-raster',
        'image/fif' => 'image/fif',
        'image/florian' => 'image/florian',
        'image/g3fax' => 'image/g3fax',
        'image/gif' => 'image/gif',
        'image/ief' => 'image/ief',
        'image/jpeg' => 'image/jpeg',
        'image/jutvision' => 'image/jutvision',
        'image/naplps' => 'image/naplps',
        'image/pict' => 'image/pict',
        'image/pjpeg' => 'image/pjpeg',
        'image/png' => 'image/png',
        'image/vnd.net-fpx' => 'image/vnd.net-fpx',
        'image/vnd.rn-realflash' => 'image/vnd.rn-realflash',
        'image/vnd.rn-realpix' => 'image/vnd.rn-realpix',
        'image/vnd.wap.wbmp' => 'image/vnd.wap.wbmp',
        'image/vnd.xiff' => 'image/vnd.xiff',
        'image/x-cmu-raster' => 'image/x-cmu-raster',
        'image/x-dwg' => 'image/x-dwg',
        'image/x-icon' => 'image/x-icon',
        'image/x-jg' => 'image/x-jg',
        'image/x-jps' => 'image/x-jps',
        'image/x-niff' => 'image/x-niff',
        'image/x-pcx' => 'image/x-pcx',
        'image/x-pict' => 'image/x-pict',
        'image/x-portable-anymap' => 'image/x-portable-anymap',
        'image/x-portable-bitmap' => 'image/x-portable-bitmap',
        'image/x-portable-greymap' => 'image/x-portable-greymap',
        'image/x-portable-pixmap' => 'image/x-portable-pixmap',
        'image/x-quicktime' => 'image/x-quicktime',
        'image/x-rgb' => 'image/x-rgb',
        'image/x-tiff' => 'image/x-tiff',
        'image/x-windows-bmp' => 'image/x-windows-bmp',
        'image/x-xwindowdump' => 'image/x-xwindowdump',
        'image/xbm' => 'image/xbm',
        'image/xpm' => 'image/xpm',
        'location/x-texinfo' => 'location/x-texinfo',
        'message/rfc822' => 'message/rfc822',
        'model/iges' => 'model/iges',
        'model/vnd.dwf' => 'model/vnd.dwf',
        'model/x-pov' => 'model/x-pov',
        'multipart/x-gzip' => 'multipart/x-gzip',
        'multipart/x-ustar' => 'multipart/x-ustar',
        'multipart/x-zip' => 'multipart/x-zip',
        'music/x-karaoke' => 'music/x-karaoke',
        'paleovu/x-pv' => 'paleovu/x-pv',
        'text/asp' => 'text/asp',
        'text/css' => 'text/css',
        'text/html' => 'text/html',
        'text/mcf' => 'text/mcf',
        'text/pascal' => 'text/pascal',
        'text/plain' => 'text/plain',
        'text/richtext' => 'text/richtext',
        'text/scriplet' => 'text/scriplet',
        'text/tab-separated-values' => 'text/tab-separated-values',
        'text/uri-list' => 'text/uri-list',
        'text/vnd.abc' => 'text/vnd.abc',
        'text/vnd.fmi.flexstor' => 'text/vnd.fmi.flexstor',
        'text/vnd.rn-realtext' => 'text/vnd.rn-realtext',
        'text/vnd.wap.wml' => 'text/vnd.wap.wml',
        'text/vnd.wap.wmlscript' => 'text/vnd.wap.wmlscript',
        'text/webviewhtml' => 'text/webviewhtml',
        'text/x-asm' => 'text/x-asm',
        'text/x-audiosoft-intra' => 'text/x-audiosoft-intra',
        'text/x-c' => 'text/x-c',
        'text/x-component' => 'text/x-component',
        'text/x-fortran' => 'text/x-fortran',
        'text/x-h' => 'text/x-h',
        'text/x-java-source' => 'text/x-java-source',
        'text/x-la-asf' => 'text/x-la-asf',
        'text/x-m' => 'text/x-m',
        'text/x-pascal' => 'text/x-pascal',
        'text/x-script' => 'text/x-script',
        'text/x-script.csh' => 'text/x-script.csh',
        'text/x-script.elisp' => 'text/x-script.elisp',
        'text/x-script.ksh' => 'text/x-script.ksh',
        'text/x-script.lisp' => 'text/x-script.lisp',
        'text/x-script.perl' => 'text/x-script.perl',
        'text/x-script.perl-module' => 'text/x-script.perl-module',
        'text/x-script.phyton' => 'text/x-script.phyton',
        'text/x-script.rexx' => 'text/x-script.rexx',
        'text/x-script.sh' => 'text/x-script.sh',
        'text/x-script.tcl' => 'text/x-script.tcl',
        'text/x-script.tcsh' => 'text/x-script.tcsh',
        'text/x-script.zsh' => 'text/x-script.zsh',
        'text/x-server-parsed-html' => 'text/x-server-parsed-html',
        'text/x-setext' => 'text/x-setext',
        'text/x-sgml' => 'text/x-sgml',
        'text/x-speech' => 'text/x-speech',
        'text/x-uil' => 'text/x-uil',
        'text/x-uuencode' => 'text/x-uuencode',
        'text/x-vcalendar' => 'text/x-vcalendar',
        'text/xml' => 'text/xml',
        'video/animaflex' => 'video/animaflex',
        'video/avs-video' => 'video/avs-video',
        'video/mpeg' => 'video/mpeg',
        'video/quicktime' => 'video/quicktime',
        'video/vdo' => 'video/vdo',
        'video/vnd.rn-realvideo' => 'video/vnd.rn-realvideo',
        'video/vnd.vivo' => 'video/vnd.vivo',
        'video/vosaic' => 'video/vosaic',
        'video/x-amt-demorun' => 'video/x-amt-demorun',
        'video/x-amt-showrun' => 'video/x-amt-showrun',
        'video/x-atomic3d-feature' => 'video/x-atomic3d-feature',
        'video/x-dl' => 'video/x-dl',
        'video/x-dv' => 'video/x-dv',
        'video/x-fli' => 'video/x-fli',
        'video/x-gl' => 'video/x-gl',
        'video/x-isvideo' => 'video/x-isvideo',
        'video/x-motion-jpeg' => 'video/x-motion-jpeg',
        'video/x-mpeg' => 'video/x-mpeg',
        'video/x-mpeq2a' => 'video/x-mpeq2a',
        'video/x-ms-asf' => 'video/x-ms-asf',
        'video/x-ms-asf-plugin' => 'video/x-ms-asf-plugin',
        'video/x-msvideo' => 'video/x-msvideo',
        'video/x-qtc' => 'video/x-qtc',
        'video/x-scm' => 'video/x-scm',
        'video/x-sgi-movie' => 'video/x-sgi-movie',
        'windows/metafile' => 'windows/metafile',
        'www/mime' => 'www/mime',
        'x-conference/x-cooltalk' => 'x-conference/x-cooltalk',
        'x-music/x-midi' => 'x-music/x-midi',
        'x-world/x-3dmf' => 'x-world/x-3dmf',
        'x-world/x-svr' => 'x-world/x-svr',
        'x-world/x-vrml' => 'x-world/x-vrml',
        'x-world/x-vrt' => 'x-world/x-vrt',
        'xgl/drawing' => 'xgl/drawing',
        'xgl/movie' => 'xgl/movie'
    );
    return $array;
}


/**
 * Return a list of blacklisted file extensions
 *
 * @return array
 */
function report_qmplus_getBlacklistedTypes_list(){

    $array = array();

    $blacklistedExtensions = get_config('report_qmplus', 'blacklistedSettings'); // get from settings file
    if($blacklistedExtensions!=''){  // if external users exist, send them an email
        $blacklistedExtensions = preg_replace('/\s+/', '', $blacklistedExtensions);   //remove empty space
        $blacklistedExtensions = explode(',',$blacklistedExtensions);

        foreach ($blacklistedExtensions as $extension) {

            if($extension!=''){
                $array['.'.$extension] = $extension;
            }

        }

    }

   /* $array = array(
        '.exe' => 'exe',
        '.bat' => 'bat',
        '.com' => 'com',
        '.pif' => 'pif',
        '.scr' => 'scr',
        '.cmd' => 'cmd'
     );*/

    return $array;

}

/**
 * Return a list of courses
 *
 * @return array
 */
function report_qmplus_getCourse_list(){

    $courses = get_courses();

    $array = array();

    foreach ($courses as $course) {
        $array[$course->id] = $course->fullname;
    }

    natcasesort($array);

    return $array;

}


/**
 * Generate data for the CSV Mime reports
 *
 * @param $mime
 * @param $datefrom
 * @param $dateto
 * @return array
 */
function report_qmplus_generate_mime_report($mime,$datefrom,$dateto) {

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $records = report_qmplus_get_records_byMimeType($mime,$datefrom,$dateto);

    $files = report_qmplus_prepareFiles($records);

    $records->close();

    $mime = str_replace("/", "-",$mime);

    $files = array(
        array('filename' => "inCourse-$mime.csv", 'data' => $files['inCourse']),
        array('filename' => "other-$mime.csv", 'data' => $files['other'])
    );

    return $files;

}

/**
 * Calculate data for the blacklisted CSV reports
 *
 * @param $filetype
 * @param $datefrom
 * @param $dateto
 * @return array
 */
function report_qmplus_generate_blacklisted_report($filetype,$datefrom,$dateto){

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $records = report_qmplus_get_records_byfiletype($filetype,$datefrom,$dateto); // Context level 70 for in course files

    $files = report_qmplus_prepareFiles($records);

    $records->close();

    $filetype = str_replace(".", "",$filetype);

    $files = array(
        array('filename' => "inCourse-$filetype.csv", 'data' => $files['inCourse']),
        array('filename' => "other-$filetype.csv", 'data' => $files['other'])
    );

    return $files;

}

/**
 * Calculate data for the course back ups CSV reports
 *
 * @param $datefrom
 * @param $dateto
 * @return array
 */
function report_qmplus_generate_backup_report($datefrom,$dateto){

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $records = report_qmplus_get_records_byComponent('backup',$datefrom,$dateto);

    $files = report_qmplus_prepareFiles($records);

    $records->close();

    $files = array(
        array('filename' => "Course-Backups.csv", 'data' => $files['inCourse']),
        array('filename' => "other-Backups.csv", 'data' => $files['other'])
    );

    return $files;

}

/**
 * Calculate data for the course files CSV reports
 *
 * @param $course
 * @param $datefrom
 * @param $dateto
 * @return array
 */
function report_qmplus_generate_course_report($course,$datefrom,$dateto){

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $records = report_qmplus_get_records_byCourse($course,$datefrom,$dateto);

    $files = report_qmplus_prepareFiles($records);

    $records->close();

    $filetype = str_replace(".", "",$course);

    $files = array(
        array('filename' => "inCourse-$course.csv", 'data' => $files['inCourse']),
        array('filename' => "other-$course.csv", 'data' => $files['other'])
    );

    return $files;

}

/**
 * Calculate data for the course files CSV reports
 *
 * @param $course
 * @param $datefrom
 * @param $dateto
 * @return array
 */
function report_qmplus_generate_configlog_report($datefrom,$dateto)
{

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $records = report_qmplus_get_configlog_records($datefrom,$dateto);

    //$files = report_qmplus_prepareFiles($records);

    //$records->close();

    $files = array(
        array('filename' => "moodle-configlog.csv", 'data' => $records),
    );

    return $files;

}




/**
 * Get db records based on mime type
 *
 * @param $mime
 * @param $datefrom
 * @param $dateto
 * @return moodle_recordset
 */
function report_qmplus_get_records_byMimeType($mime,$datefrom,$dateto)
{
    global $DB;

    $where ="f.mimetype like '$mime%' AND f.timemodified BETWEEN $datefrom AND $dateto";
    $sql = report_qmplus_createQuery($where);

    $records = $DB->get_recordset_sql($sql);

    return $records;

}

/**
 * Get db records based on filename extension
 *
 * @param $filetype
 * @param $datefrom
 * @param $dateto
 * @return moodle_recordset
 */
function report_qmplus_get_records_byfiletype($filetype,$datefrom,$dateto)
{
    global $DB;

    $where = " ( f.filename like '%$filetype'OR f.filename like '%\.$filetype\.%' ) AND f.timemodified BETWEEN $datefrom AND $dateto";
    $sql = report_qmplus_createQuery($where);

    $records = $DB->get_recordset_sql($sql);

    return $records;
}


/**
 * Get db records based on filename extension
 *
 * @param $type
 * @param $datefrom
 * @param $dateto
 * @return moodle_recordset
 */
function report_qmplus_get_records_byComponent($type,$datefrom,$dateto)
{
    global $DB;

    $where ="f.component like '$type' AND f.timemodified BETWEEN $datefrom AND $dateto";
    $sql = report_qmplus_createQuery($where);

    $records = $DB->get_recordset_sql($sql);

    return $records;
}




/**
 * Get db records based on filename extension
 *
 * @param $type
 * @param $datefrom
 * @param $dateto
 * @return moodle_recordset
 */
function report_qmplus_get_records_byCourse($course,$datefrom,$dateto)
{
    global $DB;

    $sql = "
        SELECT f1.* FROM (
            SELECT f.*,ctx.contextlevel,ctx.instanceid,u.firstname,u.lastname,u.email, md.id as moduleid,
            md.instance as modinstance, md.course as modulecourse,md.section as modulesection,
            md.sectionname, md.courseid, md.fullname, md.idnumber
            FROM {files} f
            INNER JOIN {context} ctx ON ctx.id=f.contextid AND ctx.contextlevel = 50 AND  ctx.instanceid = $course
            INNER JOIN {user} u ON f.userid=u.id
            LEFT JOIN (SELECT m.id,m.instance,m.course,s.name as sectionname,s.section, c.id as courseid, c.fullname, c.idnumber
                    FROM {course_modules} m
                    INNER JOIN {course_sections} s ON m.section=s.id
                    INNER JOIN {course} c ON c.id=m.course
              ) md ON (md.course = ctx.instanceid AND ctx.contextlevel = 50)
            WHERE f.timemodified BETWEEN $datefrom AND $dateto AND f.filename <> '.'
            GROUP BY f.id , md.id /* VS 20/oct/17 MySQL 5.7  compliance */
            ORDER BY ctx.path
        ) f1
        UNION
        SELECT f2.* FROM (
            SELECT f.*,ctx.contextlevel,ctx.instanceid,u.firstname,u.lastname,u.email, md.id as moduleid,
            md.instance as modinstance, md.course as modulecourse,md.section as modulesection,
            md.sectionname, md.courseid, md.fullname, md.idnumber
            FROM {files} f
            INNER JOIN {context} ctx ON ctx.id=f.contextid AND ctx.contextlevel = 70
            INNER JOIN {user} u ON f.userid=u.id
            INNER JOIN (SELECT m.id,m.instance,m.course,s.name as sectionname,s.section, c.id as courseid, c.fullname, c.idnumber
                    FROM {course_modules} m
                    INNER JOIN {course_sections} s ON m.section=s.id
                    INNER JOIN {course} c ON c.id=m.course AND c.id = $course
              ) md ON (md.id = ctx.instanceid AND ctx.contextlevel = 70)
            WHERE f.timemodified BETWEEN $datefrom AND $dateto AND f.filename <> '.'
            GROUP BY f.id , md.id /* VS 20/oct/17 MySQL 5.7  compliance */
            ORDER BY ctx.path
        ) f2
        ";

    $records = $DB->get_recordset_sql($sql);

    return $records;
}


/**
 * Get db config log records bettween timestamps
 *
 * @param $datefrom
 * @param $dateto
 * @return moodle_recordset
 */
function report_qmplus_get_configlog_records($datefrom = null, $dateto = null)
{
    global $DB;

    $where = '';

    if(!is_null($datefrom) && !is_null($dateto)){
        $where = "WHERE cl.timemodified BETWEEN $datefrom AND $dateto";
    } elseif (!is_null($datefrom)) {
        $where = "WHERE cl.timemodified >= $datefrom";
    }


    $sql = "SELECT cl.timemodified, cl.plugin, cl.name, cl.value, cl.oldvalue, u.lastname,u.firstname
              FROM {config_log} cl
              JOIN {user} u ON u.id = cl.userid
              $where
          ORDER BY cl.timemodified DESC";

    $rs = $DB->get_recordset_sql($sql);

    $data = array();
    $data[] =  array('Date','User','Plugin','Setting','New value','Original value');

    foreach ($rs as $log) {

        $row = array();
        $row[] = userdate($log->timemodified);
        $row[] =$log->firstname .' '. $log->lastname;
        if (is_null($log->plugin)) {
            $row[] = 'core';
        } else {
            $row[] = $log->plugin;
        }
        $row[] = $log->name;
        $row[] = s($log->value);
        $row[] = s($log->oldvalue);

        $data[] = $row;
    }

    $rs->close();

    return $data;
}

/**
 * Create and compress csv files
 *
 * @param $filename
 * @param $csvFiles
 * @return string
 */
function report_qmplus_createZIP($filename,$csvFiles) {

    global $CFG;

    $zipname = $CFG->dataroot . '/reports/' . $filename . '.zip';
    $zip = new ZipArchive;
    $zip->open($zipname, ZipArchive::CREATE);

    foreach ($csvFiles as $name => $file) {

        $csv = $CFG->dataroot . '/reports/'.$name;
        $f = fopen($csv, 'w');

        foreach ($file as $fields) {
            fputcsv($f, $fields);
        }

        fclose($csv, 'w');

        //close the file
        $zip->addFile($csv, $name);
    }

    // close the archive
    $zip->close();

    return $zipname;

}


/**
 * This method is not used at the moment. It was originally created for ajax requests.
 *
 * @param $filename
 * @param $data
 */
function report_qmplus_createCSV($filename, $data) {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    $f = fopen('php://output', 'w');

    foreach ($data as $fields) {
        fputcsv($f, $fields);
    }

    fclose('php://output', 'w');

}



/**
 * Get db records based on time modified
 *
 * @param $timestamp
 * @return moodle_recordset
 */
function report_qmplus_getNewFiles($timestamp) {
    global $DB;

    $where ="f.timemodified >= '$timestamp'";
    $sql = report_qmplus_createQuery($where);

    $records = $DB->get_recordset_sql($sql);

    return $records;

}

/**
 * Get db records based on time modified
 *
 * @param $timestamp
 * @return moodle_recordset
 */
function report_qmplus_getBlacklistedFiles($timestamp) {
    global $DB;

    $extensions = report_qmplus_getBlacklistedTypes_list();
    $cases='';
    foreach ($extensions as $extension) {
        if($cases!=='') $cases .= ' OR ';
        $cases .= "f.filename LIKE '%.$extension'";
    }
    $where = $cases." AND f.timemodified >= '$timestamp'";
    $sql = report_qmplus_createQuery($where);

    $records = $DB->get_recordset_sql($sql);

    return $records;

}

/**
 * Prepare sql query by adding the where paremeters
 *
 * @param $where string
 * @return $sql string
 */
function report_qmplus_createQuery($where) {

    $sql = "
        SELECT f1.* FROM (
            SELECT f.*,ctx.contextlevel,ctx.instanceid,u.firstname,u.lastname,u.email,u.department, md.id as moduleid,
            md.instance as modinstance, md.course as modulecourse,md.section as modulesection,
            md.sectionname, md.courseid, md.fullname, md.idnumber
            FROM {files} f
            INNER JOIN {context} ctx ON ctx.id=f.contextid AND ctx.contextlevel = 50
            INNER JOIN {user} u ON f.userid=u.id
            LEFT JOIN (SELECT m.id,m.instance,m.course,s.name as sectionname,s.section, c.id as courseid, c.fullname, c.idnumber
                    FROM {course_modules} m
                    INNER JOIN {course_sections} s ON m.section=s.id
                    INNER JOIN {course} c ON c.id=m.course
              ) md ON (md.course = ctx.instanceid AND ctx.contextlevel = 50)
            WHERE $where AND f.filename <> '.'
            GROUP BY f.id , md.id /* , md.id added for grouping VS 20 oct 2017 */
            ORDER BY ctx.path
        ) f1
        UNION
        SELECT f2.* FROM (
            SELECT f.*,ctx.contextlevel,ctx.instanceid,u.firstname,u.lastname,u.email,u.department, md.id as moduleid,
            md.instance as modinstance, md.course as modulecourse,md.section as modulesection,
            md.sectionname, md.courseid, md.fullname, md.idnumber
            FROM {files} f
            INNER JOIN {context} ctx ON ctx.id=f.contextid AND ctx.contextlevel = 70
            INNER JOIN {user} u ON f.userid=u.id
            LEFT JOIN (SELECT m.id,m.instance,m.course,s.name as sectionname,s.section, c.id as courseid, c.fullname, c.idnumber
                    FROM {course_modules} m
                    INNER JOIN {course_sections} s ON m.section=s.id
                    INNER JOIN {course} c ON c.id=m.course
              ) md ON (md.id = ctx.instanceid AND ctx.contextlevel = 70)
            WHERE $where AND f.filename <> '.'
            GROUP BY f.id , md.id /* VS MySQL 5.7 compliance */
            ORDER BY ctx.path
        ) f2
        UNION
        SELECT f3.* FROM (
            SELECT f.*,ctx.contextlevel,ctx.instanceid,u.firstname,u.lastname,u.email,u.department, null as  moduleid,
             null as  modinstance, null as modulecourse,null as modulesection,
              null as sectionname, null as courseid, null as fullname, null as idnumber
            FROM {files} f
            INNER JOIN {context} ctx ON ctx.id=f.contextid AND  ctx.contextlevel NOT IN (50,70)
            INNER JOIN {user} u ON f.userid=u.id
            WHERE $where AND f.filename <> '.'
            GROUP BY f.id
            ORDER BY ctx.path
        ) f3
        ";

    return $sql;
}





/**
 *  Get site admins
 *
 * @return moodle_recordset
 */
function report_qmplus_getSiteAdmins() {
    global $DB;

    $sql = "SELECT *
        FROM {user} as u, {config} as c
        WHERE c.name = 'siteadmins'
        AND FIND_IN_SET(u.id, c.value) > 0";

    $records = $DB->get_recordset_sql($sql);

    return $records;

}


/**
 * Create and store zip file that is added as attatchment on email reports on task job
 *
 * @param $data
 * @return stdClass|stored_file
 * @throws Exception
 * @throws coding_exception
 * @throws dml_exception
 * @throws dml_missing_record_exception
 * @throws dml_multiple_records_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function report_qmplus_createEmailReportFile($data)
{

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    global $CFG;

    $files = report_qmplus_prepareFiles($data);

    $files = array(
        array('filename' => "inCourse-newFiles.csv", 'data' => $files['inCourse']),
        array('filename' => "other-newFiles.csv", 'data' => $files['other'])
    );


    $unlinkFiles = array();

    // Create dir to store temp  report files.
    $myDir = $CFG->dataroot . '/qmplus_reports';
    if (!is_dir($myDir)) {
        mkdir($myDir, 0777, true); // true for recursive create
    }

    $zipname = $myDir.'/newFiles.zip';

    $zip = new ZipArchive;
    $zip->open($zipname, ZipArchive::CREATE);

    foreach ($files as $file) {

        $csv = $myDir.'/'.$file['filename'];
        //$f = fopen($csv, 'w');
        $f = fopen($csv, 'w');

        foreach ($file['data'] as $fields) {
            fputcsv($f, $fields);
        }

        fclose($csv, 'w');
        //close the file
        $zip->addFile($csv, $file['filename']);

        $unlinkFiles[] = $csv;

    }

    // close the archive
    $zip->close();


    $context = context_system::instance();

    $file = new stdClass;
    $file->contextid = $context->id;
    $file->component = 'report_qmplus';
    $file->filearea  = 'public';
    $file->itemid    = 0;
    $file->filepath  = '/';
    $file->filename  = 'newfiles-report.zip';
    $file->source    = 'newfiles-report.zip';

    $fs = get_file_storage();

    // Get file if exist
    $oldfile = $fs->get_file($context->id, $file->component, $file->filearea,
        $file->itemid, $file->filepath,  $file->filename);

    if ($oldfile) {
        $oldfile->delete();
    }

    $file = $fs->create_file_from_pathname($file, $zipname);

    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename());

    return array('file'=>$file, 'total'=>$files['totalRecords'], 'url'=>$url);

}



/**
 *
 * Send email
 *
 * @param $sender
 * @param $user
 * @param $data
 * @return mixed
 * @throws coding_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function report_qmplus_sendEmail($sender, $user, $data)
{

    $file = $data['file'];
    $totalfiles = $data['total'];

    $url = $data['url'];

    $message = new stdClass();
    $message->component         = 'report_qmplus'; //your component name
    $message->name              = 'newfiles'; //this is the message name from messages.php
    $message->userfrom          = $sender;
    $message->userto            = $user;
    $message->subject           = get_string('taskemailsubject', 'report_qmplus');
    $message->fullmessage       = get_string('taskemailmessagehtml', 'report_qmplus');
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml   = get_string('taskemailmessagehtmlheader', 'report_qmplus').
                                  get_string('taskemailmessagehtmltotal', 'report_qmplus').
                                  $totalfiles.
                                  get_string('taskemailmessagehtmlfooter', 'report_qmplus').
                                 '<br>'.html_writer::link($url,  $file->get_filename());
    $message->smallmessage      = '';
    $message->notification      = 1; //this is only set to 0 for personal messages between users
    $message->attachment = $file;  // it is not supported on 2.8 OMG!!!!!!!!!!!!!!!


    return message_send($message);


}

/**
 * Moodle required method to locate file
 *
 *
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return bool
 * @throws coding_exception
 * @throws require_login_exception
 */
function report_qmplus_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    require_login($course, true, $cm);


    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'report_qmplus', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_file($file, $filename, 0, true);
}

/**
 *  Preparing data for generating the csv files
 *
 * @param $records
 * @return array
 */
function report_qmplus_prepareFiles($records)
{
    global $CFG;

    //Disable activity
    //$inCourseMedia[] = array('Course', 'Course ID', 'Course IdNumber', 'Media Title','Activity Name', 'Content Hash', 'Filesize (MB)', 'Media Type','Section', 'Section link','Item', 'Author', 'E-mail', 'Department');
    $inCourseMedia[] = array('Course', 'Date Modified', 'Course ID', 'Course IdNumber', 'Media Title', 'Content Hash', 'Filesize (MB)', 'Media Type', 'Section', 'Section link', 'Item', 'Author', 'E-mail', 'Department');
    $otherMedia[] = array('Media Title', 'Date Modified', 'Content Hash', 'Filesize (MB)', 'Media Type', 'Component', 'Filearea', 'Author', 'E-mail', 'Department');

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    $totalFiles = 0;
    foreach ($records as $record) {

        ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

        $record->filesize /= 1048576;  // Megabyte = 1,048,576 Bytes. Size is stored in bytes.
        $record->filesize = round($record->filesize, 2);

        $date = new DateTime();
        $date->setTimestamp($record->timemodified);
        $record->date = $date->format('M d,Y H:i');
        //$record->$date = date_format($date, 'U = M-d-Y H:i');//$date->format('U = Y-m-d H:i:s');

        $contextLevel = (int)$record->contextlevel;
        if ($contextLevel == 70 || $contextLevel == 50) {

            if ($record->itemid){
              $linkToResource = "$CFG->wwwroot/pluginfile.php/$record->contextid/$record->component/$record->filearea/$record->itemid/$record->filename";
            }
            else{
                $linkToResource = "$CFG->wwwroot/pluginfile.php/$record->contextid/$record->component/$record->filearea/$record->filename";
            }

            $pos = strpos($record->component, 'mod_');
            $activityname = '';

            if ($pos !== false) {
                $module = str_replace('mod_', '', $record->component);
                $linkToResource = "$CFG->wwwroot/mod/$module/view.php?id=$record->moduleid"; //link to resource
                /*
                ////////////  Disable activity name to increase performance  ///////////////////////////////////////////////
                if($record->modinstance!== null){
                    $activity = $DB->get_record($module, array('id' => $record->modinstance));
                    $activityname = $activity->name;
                }
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////
                */
            }

            //Create link
            if ($contextLevel == 50) {
                //course
                $linkToSection = $CFG->wwwroot . '/course/view.php?id='.$record->courseid;
            } else {
                $linkToSection = "$CFG->wwwroot/course/view.php?id=$record->courseid#section-$record->modulesection"; // link to course/section
            }




            $inCourseMedia[] = array(
                //$record->category,
                $record->fullname,
                $record->date,
                $record->courseid,
                $record->idnumber,
                $record->filename,
                //$activityname,  // remove activity to increase performance
                $record->contenthash,
                $record->filesize,
                $record->mimetype,
                $record->sectionname,
                $linkToSection,
                $linkToResource,
                $record->firstname . ' ' . $record->lastname,
                $record->email,
                $record->department
            );

        } else {

            $otherMedia[] = array(
                $record->filename,
                $record->date,
                $record->contenthash,
                $record->filesize,
                $record->mimetype,
                $record->component,
                $record->filearea,
                $record->firstname . ' ' . $record->lastname,
                $record->email,
                $record->department);

        }

        $totalFiles++;
    }

    return array('inCourse' => $inCourseMedia, 'other' => $otherMedia, 'totalRecords' => $totalFiles);

}




/**
 * Create and store zip file that is added as attatchment on email reports on task job
 *
 * @param $data
 * @return stdClass|stored_file
 * @throws Exception
 * @throws coding_exception
 * @throws dml_exception
 * @throws dml_missing_record_exception
 * @throws dml_multiple_records_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function report_qmplus_createEmailtFileForNewSettings($data)
{

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    global $CFG;

    $files = report_qmplus_prepareFiles($data);

    $files = array(
        array('filename' => "QMPLus-newSettings.csv", 'data' => $data)
    );


    $unlinkFiles = array();

    // Create dir to store temp  report files.
    $myDir = $CFG->dataroot . '/qmplus_reports';
    if (!is_dir($myDir)) {
        mkdir($myDir, 0777, true); // true for recursive create
    }

    $zipname = $myDir.'/newSettings.zip';

    $zip = new ZipArchive;
    $zip->open($zipname, ZipArchive::CREATE);

    foreach ($files as $file) {

        $csv = $myDir.'/'.$file['filename'];
        //$f = fopen($csv, 'w');
        $f = fopen($csv, 'w');

        foreach ($file['data'] as $fields) {
            fputcsv($f, $fields);
        }

        fclose($csv, 'w');
        //close the file
        $zip->addFile($csv, $file['filename']);

        $unlinkFiles[] = $csv;

    }

    // close the archive
    $zip->close();


    $context = context_system::instance();

    $file = new stdClass;
    $file->contextid = $context->id;
    $file->component = 'report_qmplus';
    $file->filearea  = 'public';
    $file->itemid    = 0;
    $file->filepath  = '/';
    $file->filename  = 'QMPLus-newSettings.zip';
    $file->source    = 'QMPLus-newSettings.zip';

    $fs = get_file_storage();

    // Get file if exist
    $oldfile = $fs->get_file($context->id, $file->component, $file->filearea,
        $file->itemid, $file->filepath,  $file->filename);

    if ($oldfile) {
        $oldfile->delete();
    }

    $file = $fs->create_file_from_pathname($file, $zipname);

    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename());


    return array('file'=>$file, 'total'=>((int)count($data)-1), 'url'=>$url);

}


/**
 *
 * Send email
 *
 * @param $sender
 * @param $user
 * @param $data
 * @return mixed
 * @throws coding_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function report_qmplus_sendNewSettingsEmail($sender, $user, $data)
{

    $file = $data['file'];
    $totalfiles = $data['total'];
    $url =  $data['url'];

    $message = new stdClass();
    $message->component         = 'report_qmplus'; //your component name
    $message->name              = 'newsettings'; //this is the message name from messages.php
    $message->userfrom          = $sender;
    $message->userto            = $user;
    $message->subject           = get_string('tasknewsettingsemailsubject', 'report_qmplus');
    $message->fullmessage       = get_string('tasknewsettingsemailmessagehtml', 'report_qmplus');
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml   = get_string('tasknewsettingsemailmessagehtmlheader', 'report_qmplus').
        get_string('tasknewsettingsemailmessagehtmltotal', 'report_qmplus').
        $totalfiles.
        get_string('tasknewsettingsemailmessagehtmlfooter', 'report_qmplus').
        '<br>'.html_writer::link($url,  $file->get_filename());
    $message->smallmessage      = '';
    $message->notification      = 1; //this is only set to 0 for personal messages between users
    $message->attachment = $file;  // it is not supported on 2.8 OMG!!!!!!!!!!!!!!!

    return message_send($message);

}



/**
 * Create and store zip file that is added as attatchment on email reports on task job
 *
 * @param $data
 * @return stdClass|stored_file
 * @throws Exception
 * @throws coding_exception
 * @throws dml_exception
 * @throws dml_missing_record_exception
 * @throws dml_multiple_records_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function report_qmplus_createEmailReportBlacklisted($data)
{

    ini_set('max_execution_time', 0); // php safe mode needs to be off. Moodle requires this setting to off as well.

    global $CFG;

    $files = report_qmplus_prepareFiles($data);
    $totalFiles = $files['totalRecords'];

    $files = array(
        array('filename' => "inCourse-Blacklisted-Files.csv", 'data' => $files['inCourse']),
        array('filename' => "other-Blacklisted-Files.csv", 'data' => $files['other'])
    );


    $unlinkFiles = array();

    // Create dir to store temp  report files.
    $myDir = $CFG->dataroot . '/qmplus_reports/blacklisted';
    if (!is_dir($myDir)) {
        mkdir($myDir, 0777, true); // true for recursive create
    }

    $zipname = $myDir.'/blackListed.zip';

    $zip = new ZipArchive;
    $zip->open($zipname, ZipArchive::CREATE);

    foreach ($files as $file) {

        $csv = $myDir.'/'.$file['filename'];
        //$f = fopen($csv, 'w');
        $f = fopen($csv, 'w');

        foreach ($file['data'] as $fields) {
            fputcsv($f, $fields);
        }

        fclose($csv);
        //close the file
        $zip->addFile($csv, $file['filename']);

        $unlinkFiles[] = $csv;

    }

    // close the archive
    $zip->close();


    $context = context_system::instance();

    $file = new stdClass;
    $file->contextid = $context->id;
    $file->component = 'report_qmplus';
    $file->filearea  = 'public';
    $file->itemid    = 0;
    $file->filepath  = '/';
    $file->filename  = 'blacklisted-report.zip';
    $file->source    = 'blacklisted-report.zip';

    $fs = get_file_storage();

    // Get file if exist
    $oldfile = $fs->get_file($context->id, $file->component, $file->filearea,
        $file->itemid, $file->filepath,  $file->filename);

    if ($oldfile) {
        $oldfile->delete();
    }

    $file = $fs->create_file_from_pathname($file, $zipname);

    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename());

    return array('file'=>$file, 'total'=>$totalFiles, 'url'=>$url);

}

/**
 *
 * Send blacklisted email
 *
 * @param $sender
 * @param $user
 * @param $data
 * @return mixed
 * @throws coding_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function report_qmplus_sendBlackListedEmail($sender, $user, $data)
{

    $file = $data['file'];
    $totalfiles = $data['total'];

    $url = $data['url'];

    $message = new stdClass();
    $message->component         = 'report_qmplus'; //your component name
    $message->name              = 'newblacklisted'; //this is the message name from messages.php
    $message->userfrom          = $sender;
    $message->userto            = $user;
    $message->subject           = get_string('tasknewblacklistedemailsubject', 'report_qmplus');
    $message->fullmessage       = get_string('tasknewblacklistedemailmessage', 'report_qmplus');
    $message->fullmessageformat = FORMAT_HTML;
    $message->fullmessagehtml   = get_string('tasknewblacklistedemailmessagehtmlheader', 'report_qmplus').
        get_string('tasknewblacklistedemailmessagehtmltotal', 'report_qmplus').
        $totalfiles.
        get_string('tasknewblacklistedemailmessagehtmlfooter', 'report_qmplus').
        '<br>'.html_writer::link($url,  $file->get_filename());
    $message->smallmessage      = '';
    $message->notification      = 1; //this is only set to 0 for personal messages between users
    $message->attachment = $file;  // it is not supported on 2.8 OMG!!!!!!!!!!!!!!!


    return message_send($message);


}