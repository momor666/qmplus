<?php

class local_qmul_messaging_renderer extends core_renderer {

    private function addTextNode($parent, $type, $text) {

        $node = $parent->ownerDocument->createElement($type);
        $parent->appendChild($node);
        $node->appendChild($parent->ownerDocument->createTextNode($text));
        return $node;
    }

    /**
     * Generate an RSS feed containing the supplied messages
     */
    function rss_messages($messages) {
        global $CFG;

        $messagelist = $messages->messagelist;

        $feed = new DOMDocument("1.0", "UTF-8");
        $rssNode = $feed->createElement("rss");
        $feed->appendChild($rssNode);
        $rssNode->setAttribute("version", "2.0");
        $channelNode = $feed->createElement("channel");
        $rssNode->appendChild($channelNode);
        $this->addTextNode($channelNode, "title", $messages->title());
        $this->addTextNode($channelNode, "description", $messages->description());
        $itemdoc = new DOMDocument();
        foreach ($messagelist as $message) {
            if($message) {
                $itemNode = $feed->createElement("item");
                $channelNode->appendChild($itemNode);
                $this->addTextNode($itemNode, "title", strip_tags($message->subject));
                $this->addTextNode($itemNode, "link", "{$CFG->wwwroot}/local/qmul_messaging/view.php?message={$message->id}");
                $this->addTextNode($itemNode, "description", $message->message);
            }
        }
        return $feed->saveXML();
    }

    /**
     * Generate an ATOM feed containing the supplied messages
     */
    function atom_messages($messages) {
        global $CFG, $DB;

        $messagelist = $messages->messagelist;

        $doc = new DOMDocument("1.0", "UTF-8");
        $feedNode = $doc->createElement("feed");
        $doc->appendChild($feedNode);
        $feedNode->setAttribute("xmlns", "http://www.w3.org/2005/Atom");
        $this->addTextNode($feedNode, "title", $messages->title())->setAttribute("type", "html");
        $this->addTextNode($feedNode, "subtitle", $messages->description())->setAttribute("type", "html");
        $this->addTextNode($feedNode, "updated", date(DATE_ATOM));

        foreach ($messagelist as $message) {

            if($message && ($message->hidden == 0)) {
                $entryNode = $doc->createElement("entry");
                $feedNode->appendChild($entryNode);
                $this->addTextNode($entryNode, "title", $message->subject)->setAttribute("type", "html");
                $this->addTextNode($entryNode, "content", $message->message)->setAttribute("type", "html");
                $this->addTextNode($entryNode, "updated", date(DATE_ATOM, $message->created));
                $this->addTextNode($entryNode, "id", "{$CFG->wwwroot}/local/qmul_messaging/view.php?message={$message->id}&context={$message->context}");

                if ($message->editable) {
                    $this->addTextNode($entryNode, "link", "{$CFG->wwwroot}/local/qmul_messaging/compose.php?id={$message->id}&context={$message->context}&sesskey=" . sesskey())->setAttribute("rel", "edit");
                }

                $author = $DB->get_record('user', array('id' => $message->author));
                $authorNode = $doc->createElement("author");
                $entryNode->appendChild($authorNode);
                $this->addTextNode($authorNode, "name", fullname($author));
                $this->addTextNode($authorNode, "email", $author->email);
            }
        }
        return $doc->saveXML();
    }



    /**
     * Returns a table of messages for a standard page moodle view
     * @param $messages
     * @return string
     */
    function inbox_messagelist($messages) {

        $messagelist = $messages->messagelist;

        $output = '';
        $output .= html_writer::start_tag('table', array('class' => 'table table-hover'));
        $output .= html_writer::start_tag('tbody', array('class' => 'messagetablebody'));

        foreach ($messagelist as $message) {
            if($message && ($message->hidden == 0)){
                $output .= $this->render_inbox_message($message, true);
            }
        }

        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');

        return $output;
    }




    /**
     * This renders a table row to fill the table output in list_messages()
     * @param $message
     * @param bool $preview
     * @return string
     */
    function render_inbox_message($message, $preview = true, $markedasread) {

        $output = '';
        if($message->hidden == 1){
            return $output;
        }

        $viewurl = new moodle_url('/local/qmul_messaging/view.php', array(
            'context' => $message->context,
            'message' => $message->messageid,
            'sesskey' => sesskey(),
        ));

        $class = "bold";
        if($message->readbyuser){
            $class = "";
        }

        $output .= html_writer::start_tag('tr', array('class' => 'clickable-row ' . $class,
                                                      'data-href' => $viewurl));

        $output .= html_writer::start_tag('td');
        $output .= html_writer::span($message->firstname .' '. $message->lastname, 'author-name');
        $output .= html_writer::end_tag('td');


        $output .= html_writer::start_tag('td');
        $output .= html_writer::span(format_text(strip_tags($message->subject), FORMAT_PLAIN), 'subject');
        $output .= " - ";
        $output .= html_writer::span(format_text($this->crop_message_text($message->message, 144), FORMAT_PLAIN), 'body');
        $output .= html_writer::end_tag('td');

        $output .= html_writer::start_tag('td');
        $output .= html_writer::start_tag('ul');
        foreach ($message->rolenames as $role){
            $output .= html_writer::start_tag('li');
            $output .= $role . 's';
            $output .= html_writer::end_tag('li');
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('td');

        $output .= html_writer::start_tag('td');
        $output .= $message->catname;
        $output .= html_writer::end_tag('td');

        $output .= html_writer::start_tag('td');
        $output .= $message->rolesrollup;
        $output .= html_writer::end_tag('td');


        $output .= html_writer::start_tag('td');
        $output .= html_writer::span(format_text($message->displaytime), 'time');
        $output .= html_writer::end_tag('td');


        $output .= html_writer::end_tag('tr');
        return $output;
    }



    /**
     * This renderer out puts an inpage table view for a Moodle page
     * but only outputs additional column data for the messaging management view
     * @param $messages
     * @return string
     */
    function sent_messagelist($messages) {

        $messagelist = $messages->messagelist;

        $output = '';
        $output .= html_writer::start_tag('table', array('class' => 'table table-hover'));
        $output .= html_writer::start_tag('tbody');

        $output .= html_writer::start_tag('thead');
        $output .= html_writer::start_tag('tr');


        $output .= html_writer::start_tag('th');
        $output .= html_writer::span(get_string('thead:subject', 'local_qmul_messaging'));
        $output .= html_writer::end_tag('th');

        $output .= html_writer::start_tag('th');
        $output .= html_writer::span(get_string('thead:messagebody', 'local_qmul_messaging'));
        $output .= html_writer::end_tag('th');

        $output .= html_writer::start_tag('th');
        $output .= html_writer::span(get_string('thead:status', 'local_qmul_messaging'));
        $output .= html_writer::end_tag('th');

        $output .= html_writer::start_tag('th');
        $output .= html_writer::span(format_text(get_string('thead:timesent', 'local_qmul_messaging')), 'message-time-sent');
        $output .= html_writer::end_tag('th');

        $output .= html_writer::start_tag('th');
        $output .= html_writer::span(format_text(get_string('thead:timeexpire', 'local_qmul_messaging')), 'message-time-expire');
        $output .= html_writer::end_tag('th');

        $output .= html_writer::start_tag('th', array('colspan' => '3'));
        $output .= html_writer::span(format_text(get_string('thead:actions', 'local_qmul_messaging')), 'message-actions');
        $output .= html_writer::end_tag('th');

        $output .= html_writer::end_tag('tr');
        $output .= html_writer::end_tag('thead');

        foreach ($messagelist as $message) {
            if($message){
                $output .= $this->render_sent_message($message);
            }
        }

        $output .= html_writer::end_tag('tbody');
        $output .= html_writer::end_tag('table');

        return $output;
    }



    /**
     * This generates message rows for the message management view
     * @param $message
     * @param bool $preview
     * @return string
     */
    function render_sent_message($message, $preview = true) {

        $output = '';

        $output .= html_writer::start_tag('tr');

        $output .= html_writer::start_tag('td');
        $output .= html_writer::span($message->subject, 'subject');
        $output .= html_writer::end_tag('td');

        $output .= html_writer::start_tag('td');
        $output .= html_writer::span(format_text($message->message), 'body');
        $output .= html_writer::end_tag('td');

        $viewurl = new moodle_url('/local/qmul_messaging/view.php', array(
            'context' => $message->context,
            'message' => $message->messageid,
            'sesskey' => sesskey(),
        ));

        $output .= html_writer::start_tag('td');
        $output .= html_writer::span(format_text($message->status), 'message-status');
        $output .= html_writer::end_tag('td');

        $output .= html_writer::start_tag('td');
        $output .= html_writer::span(format_text($message->displaytime), 'time');
        $output .= html_writer::end_tag('td');

        $output .= html_writer::start_tag('td');
        $output .= html_writer::span(format_text($message->expiretime), 'expiretime');
        $output .= html_writer::end_tag('td');

        $output .= html_writer::start_tag('td', array('class' => 'view-action'));
        $output .= html_writer::link($viewurl, get_string('view'), array('data-messageid' => $message->messageid));
        $output .= html_writer::end_tag('td');

        if ($message->editable) {
            //edit
            $editurl = new moodle_url('/local/qmul_messaging/compose.php', array(
                'context' => $message->context,
                'message' => $message->messageid,
                'sesskey' => sesskey(),
            ));
            $output .= html_writer::start_tag('td', array('class' => 'edit-action'));
            if($message->status == 'archived'){
                $output .= html_writer::span(format_text('--'), 'time');
            }
            else{
                $output .= html_writer::link($editurl, get_string('edit'), array('data-messageid' => $message->messageid));
            }
            $output .= html_writer::end_tag('td');

            if ($message->hidden == 0){
                //hide button
                $hideurl = new moodle_url('/local/qmul_messaging/compose.php', array(
                    'context' => $message->context,
                    'message' => $message->messageid,
                    'sesskey' => sesskey(),
                ));
                $output .= html_writer::start_tag('td', array('class' => 'hide-action'));
                $output .= html_writer::link($hideurl, get_string('hide'), array('data-messageid' => $message->messageid));
                $output .= html_writer::end_tag('td');
            }
            else {
                //delete
                $deleteurl = new moodle_url('/local/qmul_messaging/compose.php', array(
                    'context' => $message->context,
                    'message' => $message->messageid,
                    'sesskey' => sesskey(),
                ));
                $output .= html_writer::start_tag('td', array('class' => 'delete-action'));
                $output .= html_writer::link($deleteurl, get_string('delete'), array('data-messageid' => $message->messageid));
                $output .= html_writer::end_tag('td');

            }

        }


        if($debugging = false){
            $output .= html_writer::start_tag('td');
            $output .= $message->context;
            $output .= ' (';
            $output .= $message->name;
            $output .= ')';
            $output .= html_writer::end_tag('td');
        }


        $output .= html_writer::end_tag('tr');
        return $output;
    }


    /**
     * This out puts a Moodle page full message view with corresponding data
     * @param $message
     * @return string
     */
    function render_full_message($message) {
        global $OUTPUT;

        $output = '';

        $output .= html_writer::start_tag('h3', array('class' => 'message-title'));
        $output .= html_writer::span($message->subject);
        $output .= html_writer::end_tag('h3');

        $viewlisturl = new moodle_url('/local/qmul_messaging/index.php', array('context' => $message->context));
        $output .= $OUTPUT->single_button($viewlisturl, get_string('viewmessagelistbutton', 'local_qmul_messaging'), 'get');

        $picture = html_writer::div($OUTPUT->user_picture($message->authorrecord), 'message-picture left picture');


        $role_of_sender = false;

        $metadata = 'by '. html_writer::span($message->firstname .' '. $message->lastname, 'author-name');
        if($message->messagerolenames){
            $rolenames = implode($message->messagerolenames, ', ');
            $rolenames = html_writer::span($rolenames, 'message-role-names');
            if($message->context == 1){
                $metadata .= ' to all ' . $rolenames . ' roles in QMplus';
            }
            else{
                $metadata .= ' to all ' . $rolenames . ' roles in ' . $message->contextname;
            }
        }
        //TODO - get this
        $metadata .=  html_writer::div('Posted on ' .$message->displaytime, 'message-displaytime');

        $headsubject = html_writer::div($message->subject, 'message-subject subject');
        $metadata = html_writer::div($metadata, 'message-metadata');

        $posthead = html_writer::div($headsubject . $metadata);

        $header = $picture . $posthead;


        $output .= html_writer::start_tag('div', array('class' => 'message-container forumpost'));
        $output .= html_writer::div($header, 'message-header row header');
        $output .= html_writer::div($message->message, 'message-body row maincontent');
        $output .= html_writer::end_tag('div');


        return $output;
    }

    /**
     * Renders dialog friendly html for the message dialog in theme
     * UNIMPLEMENTED
     * @return string
     */
    function render_message_list_dialog_html($messages){

        $messagelist = $messages->messagelist;

        $output = '';
        $output .= html_writer::start_div('qmul-message-list-dialog-html');

        foreach ($messagelist as $message) {

            if($message){
                $output .= html_writer::start_div('qmul-message-dialog-html');
                $output .= $this->render_message_dialog_html($message);
                $output .= html_writer::end_div();
            }

        }

        $output .= html_writer::end_div();

        return $output;
    }

    /**
     *
     * @param $message
     * @return string
     *
     */
    function render_message_dialog_html($message){
        global $PAGE, $CFG;

        $output = '';
        if($message->hidden == 1){
            return $output;
        }

        $viewurl = new moodle_url('/local/qmul_messaging/view.php', array(
            'context' => $message->context,
            'message' => $message->messageid,
            'sesskey' => sesskey(),
        ));



        $output .= html_writer::img($CFG->wwwroot . '/local/qmul_messaging/pix/quotes-in-a-rounded-speech-bubble-icon26px.jpg',
            'ui-dialog-speech-icon', array('class'=>'ui-dialog-speech-icon'));

        $output .= html_writer::start_div('ui-dialog-time');
        $output .= html_writer::span(format_text($message->displaytime));
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('ui-dialog-author-message');
        $output .= html_writer::span($message->firstname .' '. $message->lastname);
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('ui-dialog-levelcat');
        $output .= 'To :';
        $output .= html_writer::span($message->catroles);
        $output .= ' - ';
        $output .= html_writer::span($message->catname);
        $output .= html_writer::end_div();

        $output .= html_writer::start_div('ui-dialog-subject');
        $linktext = html_writer::span(format_text(strip_tags($message->subject), FORMAT_PLAIN), 'ui-dialog-subject');
        $output .= html_writer::link($viewurl, $linktext);
        $output .= html_writer::end_div();



        $output .= html_writer::start_div('ui-dialog-body');
        $output .= html_writer::span(format_text($this->crop_message_text($message->message, 144), FORMAT_PLAIN), 'ui-dialog-body');
        $output .= html_writer::end_div();

        return $output;


    }


    /**
     * @param $contextofsender
     * @param $catname
     * @return string
     */
    function get_role_of_sender($contextofsender, $catname){
        if($contextofsender == 1){
            return 'Site Administrator';
        }
        else{
            return 'Adminstrator of ' . $catname;
        }

    }

    /**
     * @param $string
     * @param $num
     * @return string
     */
    function crop_message_text($string, $num){
        $string = strip_tags($string);
        $string = substr($string,0, $num);
        return $string;
    }


}
