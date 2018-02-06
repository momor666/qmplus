/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   theme_qmul
 * @copyright Copyright Andrew Davidson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/log', 'core/ajax', 'core/notification'],
    function($, log, ajax, notification) {

        return {
            addcourseimageAlert: function(id, msg) {
                var closestr =  M.util.get_string('close', 'theme_qmul');
                if (!$(id).length) {
                    $('#courseimagecontrol').after(
                        '<div id="'+id+'" class="alert alert-warning" role="alert">' +
                        msg +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="'+closestr+'">' +
                        '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                        '</div>'
                    );
                }
            },
            humanFileSize: function(size) {
                var i = Math.floor( Math.log(size) / Math.log(1024) );
                return ( size / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
            },
            init: function(courseConfig, siteMaxBytes) {
                var plugin = this;
                var courseshortname = courseConfig.shortname
                // Take a backup of what the current background image url is (if any).
                $('.path-course-view .courseheader').data('servercoverfile', $('.path-course-view .courseheader').css('background-image'));

                var file, filedata;
                $('#changecourseimage').click(function(e) {
                    e.preventDefault();
                    $(this).removeClass('state-visible');
                    $('label[for="coverfiles"]').addClass('state-visible');
                });

                /**
                 * First state - image selection button visible.
                 */
                var state1 = function() {
                    $('#alert-cover-image-size').remove();
                    $('#alert-cover-image-bytes').remove();
                    $('label[for="coverfiles"] .loadingstat').remove();
                    $('#changecourseimageconfirmation').removeClass('state-visible');
                    $('label[for="coverfiles"]').addClass('state-visible');
                    $('#coverfiles').val('');
                };

                /**
                 * Second state - confirm / cancel buttons visible.
                 */
                var state2 = function() {
                    //$('#alert-cover-image-upload-failed').remove();
                    $('#changecourseimageconfirmation').removeClass('disabled');
                    $('label[for="coverfiles"]').removeClass('state-visible');
                    $('#changecourseimageconfirmation').addClass('state-visible');
                    $('body').removeClass('cover-image-change');
                };

                $('#coverfiles').on('change', function(e) {
                    $('body').addClass('cover-image-change');
                    var files = e.target.files; // FileList object
                    if (!files.length) {
                        return;
                    }

                    file = files[0];

                    // Only process image files.
                    if (!file.type.match('image.*')) {
                        return;
                    }

                    var reader = new FileReader();

                    $('label[for="coverfiles"]').append(
                        '<span class="loadingstat spinner-three-quarters">' +
                        M.util.get_string('loading', 'theme_qmul') +
                        '</span>'
                    );

                    // Closure to capture the file information.
                    reader.onload = (function(theFile) {
                        return function(e) {

                            // Set page header to use local version for now.
                            filedata = e.target.result;

                            // Ensure that the page-header in courses has the mast-image class.
                            $('.path-course-view .courseheader').addClass('hasimage');

                            // Warn if image resolution is too small.
                            var img = $('<img />');
                            img = img.get(0);
                            img.src = filedata;
                            if (img.width < 1024) {
                                plugin.addcourseimageAlert('alert-cover-image-size',
                                    M.util.get_string('error:courseimageresolutionlow', 'theme_qmul')
                                );
                            } else {
                                $('#alert-cover-image-size').remove();
                            }

                            // Warn if image file size exceeds max upload size.
                            // Note: The site max bytes is intentional, as the person who can do the upload would be able to
                            // override the course upload limit anyway.
                            var maxbytes = siteMaxBytes;
                            if (theFile.size > maxbytes) {
                                // Go back to initial state and show warning about image file size.
                                state1();
                                var maxbytesstr = humanFileSize(maxbytes);
                                var message = M.util.get_string('error:courseimageexceedsmaxbytes', 'theme_qmul', maxbytesstr);
                                plugin.addcourseimageAlert('alert-cover-image-bytes', message);
                                return;
                            } else {
                                $('#alert-cover-image-bytes').remove();
                            }

                            $('.path-course-view .courseheader').css('background-image', 'url(' + filedata + ')');
                            $('.path-course-view .courseheader').data('localcoverfile', theFile.name);

                            state2();
                        };
                    })(file);

                    // Read in the image file as a data URL.
                    reader.readAsDataURL(file);

                });
                $('#changecourseimageconfirmation .ok').click(function(){

                    if ($(this).parent().hasClass('disabled')) {
                        return;
                    }

                    $('#alert-cover-image-size').remove();
                    $('#alert-cover-image-bytes').remove();

                    $('#changecourseimageconfirmation .ok').append(
                        '<span class="loadingstat spinner-three-quarters">' +
                        M.util.get_string('loading', 'theme_qmul') +
                        '</span>'
                    );
                    $('#changecourseimageconfirmation').addClass('disabled');

                    var imagedata = filedata.split('base64,')[1];
                    $.ajax({
                        url: M.cfg.wwwroot+"/theme/qmul/lib/courseimage.php",
                        context: document.body,
                        contentType: "application/x-www-form-urlencoded",
                        data: {imagefilename: file.name, imagedata:imagedata, courseshortname:courseshortname},
                        method: 'POST'
                    }).done(function(response) {
                        state1();
                        $('#changecourseimageconfirmation .ok .loadingstat').remove();
                        if (!response.success && response.warning) {
                            plugin.addcourseimageAlert('alert-cover-image-upload-failed', response.warning);
                            return;
                        }
                        $('#alert-cover-image-upload-failed').remove();
                    }).fail(function( xhr, textStatus, errorThrown) {
                        $('.path-course-view .courseheader').css('background-image', $('.path-course-view .courseheader').data('servercoverfile'));
                        if ($('.path-course-view .courseheader').data('servercoverfile') == 'none') {
                            $('.path-course-view .courseheader').removeClass('hasimage');
                        }
                        state1();
                        plugin.addcourseimageAlert('alert-cover-image-upload-failed', errorThrown);
                        $('#changecourseimageconfirmation .ok .loadingstat').remove();
                    });
                });
                $('#changecourseimageconfirmation .cancel').click(function(){

                    if ($(this).parent().hasClass('disabled')) {
                        return;
                    }

                    $('.path-course-view .courseheader').css('background-image', $('.path-course-view .courseheader').data('servercoverfile'));

                    if ($('.path-course-view .courseheader').data('servercoverfile') == 'none') {
                        $('.path-course-view .courseheader').removeClass('hasimage');
                    }
                    state1();
                });
                $('#courseimagecontrol').addClass('js-enabled');
            },
            showError : function(response) {
                if (typeof response !== 'object') {
                    try {
                        var jsonObj = JSON.parse(response);
                        response = jsonObj;
                    } catch (e) {}
                }

                if (typeof response  === 'undefined') {
                    // Assume error.
                    response = {error: M.util.get_string('unknownerror', 'core')};
                }

                if (response.error || response.errorcode) {
                    if (response.backtrace) {
                        notification.exception(response);
                    } else {
                        var errorstr;
                        if (response.error) {
                            errorstr = response.error;
                            if (response.stacktrace) {
                                errorstr = '<div>' + errorstr + '<pre>' + response.stacktrace + '</pre></div>';
                            }

                        } else {
                            if (response.errorcode && response.message) {
                                errorstr = response.message;
                            } else {
                                errorstr = M.util.get_string('unknownerror', 'moodle');
                            }
                        }
                        notification.alert(M.util.get_string('error', 'moodle'),
                                errorstr, M.util.get_string('ok', 'moodle'));
                    }
                }

                return false;
            }
        }
    }
);
