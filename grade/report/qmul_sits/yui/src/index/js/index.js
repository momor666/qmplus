M.gradereport_qmul_sits = M.gradereport_qmul_sits || {};
var NS = M.gradereport_qmul_sits.index = {};

NS.update_summary = function(form) {
    var total_students      = form.all('input[type=checkbox].user').size();
    var total_gradeitems    = form.all('input[type=checkbox].gradeitem').size();
    var selected_students   = form.all('input[type=checkbox].user:checked').size();
    var selected_gradeitems = form.all('input[type=checkbox].gradeitem:checked').size();
    var summary = selected_students + ' of ' + total_students + ' students and ' + selected_gradeitems + ' of ' + total_gradeitems + ' grade items selected.';
    form.one('div.summary').setHTML(summary);

    if (selected_students == 0 || selected_gradeitems == 0) {
        form.one('input[type=submit]').setAttribute('disabled', 'disabled');
    } else {
        form.one('input[type=submit]').removeAttribute('disabled');
    };
};

NS.init = function(config) {
    // Copy error messages up to the top of the form
    Y.all('form.sits').each(function (form) {
        form.all('input[type=checkbox].error').each(function (node) {
            var error = node.ancestor('span').one('label').getHTML();
            form.one('div.errors ul').append("<li>"+error+"</li>");
        });
    });
    // Change the summary when selections change
    Y.all('form.sits input[type=checkbox]').on('change', function (e) {
        var form = Y.one(e.target).ancestor('form');
        NS.update_summary(form);
    });
    Y.all('form.sits').each(function () {
        NS.update_summary(this);
    });
    // Show the correct form when a module is chosen
    Y.one('select[name=sitsmodule]').on('change', function (e) {
        var module = Y.one(e.target).get('value');
        Y.all('div.sitsmodule').hide();
        Y.one('div.sitsmodule-'+module).show();
    });
    // Show the first module
    Y.all('div.sitsmodule').hide();
    Y.one('div.sitsmodule').show();
    // Activate the select-all/select-none handlers
    Y.all('form.sits a.select-all').on('click', function (e) {
        var fieldset = Y.one(e.target).ancestor('fieldset');
        var form = Y.one(e.target).ancestor('form');
        fieldset.all('input[type=checkbox]').each(function (checkbox) {
            if (!checkbox.hasAttribute('disabled')) {
                checkbox.set('checked', true);
            }
        });
        NS.update_summary(form);
    });
    Y.all('form.sits a.select-none').on('click', function (e) {
        var fieldset = Y.one(e.target).ancestor('fieldset');
        var form = Y.one(e.target).ancestor('form');
        fieldset.all('input[type=checkbox]').set('checked', false);
        NS.update_summary(form);
    });
};
