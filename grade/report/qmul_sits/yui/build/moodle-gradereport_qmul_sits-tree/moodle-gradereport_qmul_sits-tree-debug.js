YUI.add('moodle-gradereport_qmul_sits-tree', function (Y, NAME) {

M.gradereport_qmul_sits = M.gradereport_qmul_sits || {};
var NS = M.gradereport_qmul_sits.tree = {};

NS.init = function(config) {
    Y.all('#region-main li.node').on('click', function (e) {
        var li = Y.one(e.target).ancestor('li', true);
        console.log(li);
        if (li.hasClass('expanded')) {
            li.removeClass('expanded');
        } else {
            li.addClass('expanded');
        }
        e.stopPropagation();
    });
};


}, '@VERSION@', {"requires": ["base", "node", "event"]});
