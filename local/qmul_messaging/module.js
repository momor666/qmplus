function init_feed(config) {
    jQuery.ajax({url: config.url, context: jQuery('.local-qmul-messaging-feeditems'), success: function(result) {
        var list = this;
        var feed = jQuery(jQuery.parseXML(result));
        feed.find('entry').each(function() {
            var itemTitle = jQuery(this).find('title').text();
            var itemDescription = jQuery(this).find('content').text();

            var html = '<li>';
            html += '<div class="item title">'+itemTitle+'</div>';
            html += '<div class="item description">'+itemDescription+'</div>';
            jQuery(this).find('link').each(function() {
                var rel = jQuery(this).attr('rel');
                var target = jQuery(this).text();
                if (rel == 'edit') {
                    html += '<a class="edit" href="'+target+'">Edit</a>';
                }
            });
            html += '</li>';
            jQuery(list).append(html);
        });
        console.log(list);
        console.log(feed);
    }});
}
