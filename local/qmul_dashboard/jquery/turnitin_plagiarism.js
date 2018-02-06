$(window).ready(function(){
//stuff from TII - copied from plagiarism_turnitin/jquery/turnitin/*

    $.each($('.pp_origreport_open'), function() {
        $(this).on('click', function (e) {
            var classList = $(this).attr('class').replace(/\s+/,' ').split(' ');

            for (var i = 0; i < classList.length; i++) {
                if (classList[i].indexOf('origreport_') !== -1 && classList[i] != 'pp_origreport_open') {
                    var classStr = classList[i].split("_");
                    var url = "";
                    // URL must be stored in separate div on forums
                    if ($('.origreport_forum_launch_'+classStr[1]).length > 0) {
                        url = $('.origreport_forum_launch_'+classStr[1]).html();
                    } else {
                        url = $(this).attr("id");
                    }
                    openDV("origreport", classStr[1], classStr[2], url);
                }
            }

        });
        $(this).hover(
           function() {
              // $(this).find('.score_colour').css("filter", "brightness(%60)");
              $(this).find('.score_colour').addClass("darken");
           },
           function(){
               $(this).find('.score_colour').removeClass("darken");
           }
        );
    });


    $.each($('.pp_grademark_open'), function() {
        $(this).on('click', function (e) {
            var classList = $(this).attr('class').replace(/\s+/, ' ').split(' ');

            for (var i = 0; i < classList.length; i++) {
                if (classList[i].indexOf('grademark_') !== -1 && classList[i] != 'pp_grademark_open') {
                    var classStr = classList[i].split("_");
                    var url = "";
                    // URL must be stored in separate div on forums
                    if ($('.grademark_forum_launch_'+classStr[1]).length > 0) {
                        url = $('.grademark_forum_launch_'+classStr[1]).html();
                    } else {
                        url = $(this).attr("id");
                    }
                    openDV("grademark", classStr[1], classStr[2], url);
                }
            }
        });
        $(this).hover(
            function() {
                // $(this).find('.score_colour').css("filter", "brightness(%60)");
                $(this).attr("style", "color:#002d4e");
            },
            function(){
                $(this).attr("style", "color:#005A9A");
            }
        );
    });

    // Open the DV in a new window in such a way as to not be blocked by popups.
    function openDV(dvtype, submissionid, coursemoduleid, url) {
        dvWindow = window.open('', '_blank');
        var loading = '<div class="tii_dv_loading" style="text-align:center;">';
        loading += '<img src="' + M.cfg.wwwroot + '/plagiarism/turnitin/pix/tiiIcon.svg" style="width:100px; height: 100px">';
        loading += '<p style="font-family: Arial, Helvetica, sans-serif;">' + M.str.plagiarism_turnitin.loadingdv + '</p>';
        loading += '</div>';
        $(dvWindow.document.body).html(loading);

        // Get html to launch DV.
        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: "get_dv_html", submissionid: submissionid, dvtype: dvtype,
                cmid: coursemoduleid, sesskey: M.cfg.sesskey},
            success: function(data) {
                $(dvWindow.document.body).html(loading + data);
                dvWindow.document.forms[0].submit();
                dvWindow.document.close();

                checkDVClosed(submissionid, coursemoduleid);
            }
        });
    }


    // Check whether the DV is still open, refresh the opening window when it closes.
    function checkDVClosed(submissionid, coursemoduleid) {
        if (window.dvWindow.closed) {
            refreshScores(submissionid, coursemoduleid);
        } else {
            setTimeout( function(){
                checkDVClosed(submissionid, coursemoduleid);
            }, 500);
        }
    }

    function refreshScores(submission_id, coursemoduleid) {
        $.ajax({
            type: "POST",
            url: M.cfg.wwwroot + "/plagiarism/turnitin/ajax.php",
            dataType: "json",
            data: {action: "update_grade", submission: submission_id, cmid: coursemoduleid, sesskey: M.cfg.sesskey},
            success: function(data) {
                window.location = window.location;
            }
        });
    }

    //add to object
    window.M.str.plagiarism_turnitin  = {
        closebutton: "Close",
        loadingdv: "Loading Turnitin Document Viewer...",
    };




});