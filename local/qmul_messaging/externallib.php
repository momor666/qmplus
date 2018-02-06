<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * External functions.
 *
 * @package    local_qmul_messaging
 * @copyright  2015 Queen Mary University of London
 * @author     Damian Hippisley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once(__DIR__.'/lib.php');

class local_qmul_messaging_external extends external_api{

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_messages_syndicate_parameters() {
        return new external_function_parameters(
            //TODO: maybe ws will not like optional param at top level
            array('userid' => new external_value(PARAM_INT, 'Moodle user id', VALUE_REQUIRED),
                  'contextid' => new external_value(PARAM_INT, 'Context ID', VALUE_DEFAULT, 1)
            )
        );
    }

    /**
     * Get user messages
     * @return string welcome message
     */
    public static function get_user_messages_syndicate($userid, $contextid) {
        global $DB, $CFG, $USER;

        //Parameters validation
        $params = self::validate_parameters(self::get_user_messages_syndicate_parameters(),
            array('userid' => $userid, 'contextid' => $contextid ));



        $syndicatelist = [];
        $user = $DB->get_record('user', array('id' => $params['userid']), '*', MUST_EXIST);
        if(!$user){
            return $syndicatelist = [];
        }
        $context = context::instance_by_id($params['contextid']);

        $messages = new local_qmul_messaging_messagelist($context, $user);
        $messages->get_message_list_by_user($params['userid']);
        $messagelist = $messages->messagelist;

        //rss like format (but delivered in ws formats
        foreach ($messagelist as $message){

            if($message && ($message->hidden == 0) && (!$message->readbyuser)) {
                $syndicate = new stdClass();
                $syndicate->title = format_text(strip_tags($message->subject), FORMAT_PLAIN);
                $syndicate->link = "{$CFG->wwwroot}/local/qmul_messaging/view.php?message={$message->messageid}";
                $syndicate->description = format_text(strip_tags($message->message), FORMAT_PLAIN);
                array_push($syndicatelist, $syndicate);
            }
        }

        return $syndicatelist;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_messages_syndicate_returns() {
        return new external_multiple_structure(
             new external_single_structure(
                array(
                    'title' => new external_value(PARAM_RAW, 'title of message'),
                    'link' => new external_value(PARAM_LOCALURL, 'link of message'),
                    'description' => new external_value(PARAM_RAW, 'description of message'),
                )
             )
        );
    }


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hide_message_parameters() {
        return new external_function_parameters(
        //TODO: maybe ws will not like optional param at top level
            array('messageid' => new external_value(PARAM_INT, 'Local qmul messageid', VALUE_REQUIRED)
            )
        );
    }

    /**
     * Get user messages
     * @return string welcome message
     */
    public static function hide_message($messageid){
        global $DB, $CFG;

        $return = new stdClass();

        if($messagerow = $DB->get_record('local_qmul_messaging', array('id' => $messageid))){
            $messagerow->hidden = 1;
            $DB->update_record('local_qmul_messaging', $messagerow, $bulk=false);
            $return->feedback = "Message $messageid hidden.";

        }
        else {
            $return->feedback = "Message not $messageid hidden.";
        }

        $return->action = 'hide';

        return $return;

    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hide_message_returns() {
        return new external_single_structure(
                array(
                    'feedback' => new external_value(PARAM_TEXT, 'feedback'),
                    'action' => new external_value(PARAM_TEXT, 'action')
                )
        );
    }




    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function delete_message_parameters() {
        return new external_function_parameters(
        //TODO: maybe ws will not like optional param at top level
            array('messageid' => new external_value(PARAM_INT, 'Local qmul messageid', VALUE_REQUIRED)
            )
        );
    }

    /**
     * Get user messages
     * @return string welcome message
     */
    public static function delete_message($messageid){
        global $DB, $CFG;

        $messagerow = $DB->get_record('local_qmul_messaging', array('id' => $messageid));

        $return = new stdClass();
        $return->action = 'delete';

        if (local_qmul_messaging_delete_message($messagerow)){
            $return->feedback = "Message $messageid deleted.";
            return $return;
        }
        else{
            $return->feedback = "Message not $messageid deleted.";
            return $return;
        }

    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function delete_message_returns() {
        return
            new external_single_structure(
                array(
                    'feedback' => new external_value(PARAM_TEXT, 'feedback-message'),
                    'action' => new external_value(PARAM_TEXT, 'action')
                )
            );
    }



}