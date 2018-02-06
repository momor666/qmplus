<?php

class local_qmul_messaging_cron extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('pluginname', 'local_qmul_messaging');
    }

    public function execute() {
        print_object(__CLASS__.'::'.__FUNCTION__);
        $this->delete_expired();
        $this->send_emails();
    }

    private function delete_expired() {
    }

    private function send_emails() {
        global $DB;

        $sql = 'SELECT * FROM {local_qmul_messaging} WHERE sendbyemail=1 AND emailsent = 0 AND validfrom <= ?';
        $params = array(
            time()
        );
        $messages = $DB->get_recordset_sql($sql, $params);
        foreach ($messages as $message) {

            // Prepare the emailsent time.
            $message->emailsent = time();

            // Grab the author.
            $author = $DB->get_record('user', array('id' => $message->author));

            // Build the common part of the per-user message.
            $moodlemsg = new stdClass();
            $moodlemsg->component = 'local_qmul_messaging';
            $moodlemsg->name = 'announcement';
            $moodlemsg->userfrom = $author;
            $moodlemsg->subject = format_string($message->subject);
            $moodlemsg->fullmessage = $message->message;
            $moodlemsg->fullmessageformat = FORMAT_HTML;
            $moodlemsg->fullmessagehtml = $message->message;
            $moodlemsg->smallmessage = '';
            $moodlemsg->notification = 1;
            $moodlemsg->contexturl = "$CFG->wwwroot/local/qmul_messaging/view.php?message={$message->id}";

            // Get a list of roles which receive this message.
            $roleids = $DB->get_fieldset_sql('SELECT role FROM {local_qmul_messaging_roles} WHERE message=?', array($message->id));

            // Loop through all the users....
            $users = $DB->get_recordset('user');
            foreach ($users as $user) {
                $sendmail = false;
                foreach ($roleids as $roleid) {
                    if ($sendmail = user_has_role_assignment($user->id, $roleid, $message->context)) {
                        break;
                    }
                }
                if ($sendmail) {
                    $moodlemsg->userto = $user;
                    message_send($moodlemsg);
                }
            }
            $users->close();

            // Save the emailsent timestamp grabbed before iterating users.
            $DB->update_record('local_qmul_messaging', $message);
        }
        $messages->close();
    }
}

