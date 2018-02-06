/*
 * @package    local_qmul_messaging
 * @copyright  2016 Damian Hippisley
 * @license    QMUL
 */


/**
 * @module local_qmul_messaging/messageview
 */

define(['jquery', 'core/log', 'jqueryui'], function($, log){
    "use strict";
    log.debug('messageview.js loaded');
    return {
        init: function(){
            $(".clickable-row").click(function() {
                window.document.location = $(this).data("href");
            });

            // message dialog
            var messagecontent = $('.qmul-message-list-dialog-html').html(),
                button = $(".dialog-opener"),
                mdialog = $(".message-dialog");
            $('.message-dialog').append(messagecontent);


            mdialog.css({height:"500px", overflow:"auto"}).dialog({
                autoOpen:false,
                resizable: false,
                height: 500,
                width: 400,
                title: "Messages",
                dialogClass: "no-close local_qmul_message_dialog",
                position: { my: "left top", at: "left bottom", of: button },
                buttons: [
                    {
                        text: "Mark all as read",
                        icons: {
                            primary: "ui-icon-heart"
                        },
                        click: function() {
                            $( this ).dialog( "close" );
                        }
                    },
                    {
                        text: "View all Messages ",
                        icons: {
                            primary: "ui-icon-heart"
                        },
                        click: function() {
                            $( this ).dialog( "close" );
                        }

                    }
                ]
            });

            button.click(function(){
                 if(mdialog.dialog("isOpen")){
                     mdialog.dialog("close");
                 }else{
                     mdialog.dialog("open");
                 }
            });


        },
        sent_actions:function(config){

            var hide, del;

            del = $('.delete-action');
            hide = $('.hide-action');


            var updatemessage = function (event) {
                event.preventDefault();
                log.debug('action : ' + event.data.action);

                var field, messageid, actionurl, data, wsfunction;
                field = $(this);
                messageid = field.children("a").attr('data-messageid');
                actionurl = '/webservice/rest/server.php?';

                switch (event.data.action){
                    case 'delete':
                          wsfunction = 'local_qmul_messaging_delete_message';
                          break;
                    case 'hide':
                          wsfunction = 'local_qmul_messaging_hide_message';
                          break;
                    default:
                          wsfunction = 'local_qmul_messaging_hide_message';
                          break;
                }

                data = {
                    'wstoken': config.token,
                    'wsfunction': wsfunction,
                    'moodlewsrestformat': 'json',
                    'messageid': messageid
                };

                $.ajax({url: actionurl, data: data}).done(function (data) {
                    log.debug(data.feedback);

                    switch (data.action){
                        case 'delete':
                            field.parent('tr').remove();
                            break;
                        case 'hide':
                            field.parent('tr').find('span.message-status div.text_to_html').text(config.status.hidden);
                            field.parent('tr').find('td.hide-action').unbind( "click" ).find('a').text(config.action.delete);
                            field.parent('tr').find('td.hide-action').on("click",null, {'action': 'delete'}, updatemessage);
                            break;
                        default:
                            break;
                    }
                    log.debug(config.status.hidden);

                });

            };

            del.on("click",null, {'action': 'delete'}, updatemessage);
            hide.on("click",null, {'action': 'hide'}, updatemessage);


            log.debug("You are running jQuery version: " + $.fn.jquery);
            log.debug(config);


        },
        init_feed: function(config){
            $.ajax({url: config.url, context: $('.local-qmul-messaging-feeditems'), success: function(result) {
                var list = this;
                var feed = $($.parseXML(result));
                feed.find('entry').each(function() {
                    var itemTitle = $(this).find('title').text();
                    var itemDescription = $(this).find('content').text();

                    var html = '<li>';
                    html += '<div class="item title">'+itemTitle+'</div>';
                    html += '<div class="item description">'+itemDescription+'</div>';
                    $(this).find('link').each(function() {
                        var rel = $(this).attr('rel');
                        var target = $(this).text();
                        if (rel == 'edit') {
                            html += '<a class="edit" href="'+target+'">Edit</a>';
                        }
                    });
                    html += '</li>';
                    $(list).append(html);
                });
                // console.log(list);
                // console.log(feed);
            }});
        },
        message_ticker: function(config){

            //D.H. code start here
            var has_ticker, data;

            //check if site ticker is on page
            has_ticker = $('.news-ticker').length;
            log.debug(has_ticker);


            if(has_ticker === 0){
                return false;
            }

            var messageurl;

            // messageurl =  '/webservice/rest/server.php?';
            // messageurl += 'wstoken=';
            // messageurl += config.token;
            // messageurl += '&wsfunction=local_qmul_messaging_get_user_messages';
            // messageurl += '&moodlewsrestformat=json';
            // messageurl += '&userid=';
            // messageurl += config.user;
            // messageurl += '&contextid=';
            // messageurl += config.context;

            messageurl =  '/webservice/rest/server.php';

            data = {
                'wstoken': config.token,
                'wsfunction': 'local_qmul_messaging_get_user_messages_syndicate',
                'moodlewsrestformat': 'json',
                'userid':  config.user,
                'contextid': config.context
            };


            $.ajax({
                url: messageurl,
                data: data
            }).done(function(data){
                log.debug('message ajax success');
                log.debug(data);

                $.each(data, function(index, element) {
                    var a, div1, div2;

                    a =  "<a onclick=\"this.target=&quot;_blank&quot;\"";
                    a += "href=\"";
                    a += element.link;
                    a += "\" ";
                    a += "data-hasqtip=\"13\"";
                    a += "aria-describedby=\"qtip-13\">";
                    a += element.title;
                    a += "</a>";

                    div1 = "<div class=\"link\">";
                    div1 += a;
                    div1 += "</div>";

                    div2 = "<div class=\"list\">";
                    div2 += div1;
                    div2 += "</div>";

                    $('.news-ticker > div').append( div2 );

                    //only return 3
                    return ( index !== 3 );
                });

            }).fail(function(){
                log.debug('message ajax fail');

            });



        }
      };

   }
);