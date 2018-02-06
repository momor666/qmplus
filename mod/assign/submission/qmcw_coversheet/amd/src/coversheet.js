// Put this file in path/to/plugin/amd/src
// You can call it anything you like

define(['jquery'], function($) {

    return {
        status_view: function() {

            $(".submissionaction input").attr('disabled', 'disabled').click(function(e) {
                e.preventDefault();
                return false;
            });
            $(".submissionaction .submithelp").fadeIn( function() {
                $(this).html("<div class='message warn'>You cannot add, edit or submit a submission via QMplus. Please " +
                    "print the coversheet and submit as instructed.</div>");
            });
            // may need to grey out instead
        }
    };
});