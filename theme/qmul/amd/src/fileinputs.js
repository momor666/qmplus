/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {
    return {
        init: function() {
            log.debug('QMUL File Input JavaScript initialised');
            $(document).ready(function() {

                $("body").on('change', '.inputfile', function(e) {
                    var input = this;

                    input.addEventListener('focus', function() { input.classList.add('has-focus'); });
                    input.addEventListener('blur', function() { input.classList.remove('has-focus'); });

                    var label = input.nextElementSibling,
                        label2 = label.nextElementSibling,
                        labelVal = label.innerHTML,
                        labelVal2 = label2.innerHTML;

                    var fileName = '';
                    if (this.files && this.files.length > 1) {
                        fileName = (this.getAttribute('data-multiple-caption') || '').replace('{count}', this.files.length);
                    } else {
                        fileName = e.target.value.split('\\').pop();
                    }

                    if (fileName) {
                        label.querySelector('span').innerHTML = fileName;
                        label2.innerHTML = fileName;
                    } else {
                        label.innerHTML = labelVal;
                        label2.innerHTML = labelVal2;
                    }
                });

                $(".fdescription").each(function() {
                    if ($(this).length) {
                        var divclass = $(this).attr('class');
                        $(this).replaceWith(function() {
                            return $('<span/>', {
                                html: this.innerHTML
                            }).addClass(divclass);
                        });
                    }
                });
            });
        }
    }
});
/* jshint ignore:end */
