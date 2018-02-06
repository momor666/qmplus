<?php

defined('MOODLE_INTERNAL') || die();

class local_qmul_messaging_messagelist {

    /** @var Iterator $_recordset The DB recordset being shadowed. */
    private $_recordset;

    /** @var context $_context The parent context being searched. */
    private $_context;

    private $_user;

    public $messagelist;
    public $contextlessusermessagelist;
    public $markasreadlist;

    public function __construct(context $context, $user) {
        global $DB, $USER;
        $this->_user = $user;
        $this->messagelist = [];
        $this->_context = $context;
        $this->markasreadlist = local_qmul_messaging_get_read_list($user->id);

    }

    /**
     * @param $userid
     * @return mixed
     */
    public function get_message_list_by_user($userid){
        $this->set_message_list_by_user($userid);
        return $this->messagelist;
    }


    /**
     * @param $userid
     * This function gets a list of messages that are relevant to a user.
     * It divines all contexts that are of interest to a user and then
     * gets the messages that they have the correct roles for
     *
     */
    public function set_message_list_by_user($userid){
        global $DB, $USER;


        //get a list of contexts based on courses and their parent categories
        $query = <<<END_SQL
        SELECT ctx.*, ra.*, r.*
         FROM
          {context} ctx,
          {role_assignments} ra,
		  {role} r
         WHERE  
			     ra.contextid = ctx.id
             AND ra.roleid = r.id
             AND ra.userid=:userid;  
END_SQL;

        $roleassigns = $DB->get_recordset_sql($query, array(
            'userid' => $userid,
        ));

        $usercontexts = [];
        $contextlist = [];
        foreach ($roleassigns as $roleassign){
            $contextpath = $roleassign->path;
            $contexts = preg_split('|/|', $contextpath, 0, PREG_SPLIT_NO_EMPTY);
            array_shift($contexts);
            foreach ($contexts as $context){
                $rassign = (object) ['context' => $context,
                                     'roleid' => $roleassign->roleid,
                                     'name' => $roleassign->name,
                                     'path' => $roleassign->path,
                                     'contextlevel' => $roleassign->contextlevel];
                 array_push($usercontexts, $rassign);
                 array_push($contextlist, $context);
            }
        }

        $contextlist = array_unique($contextlist);

        try{
        //get a list of messages from in the users course or category contexts
             list($insql, $params) = $DB->get_in_or_equal($contextlist);
        }
        catch (Exception $e){
            echo get_string('nomessages', 'local_qmul_messaging');
            $this->_recordset = null;
            return false;
        }


        $query = <<<END_SQL
        SELECT   lqm.id as messageid, lqm.context, lqmr.role
		  FROM 
			mdl_local_qmul_messaging lqm,
			mdl_local_qmul_messaging_roles lqmr
          WHERE
				lqmr.message = lqm.id
                AND lqm.context $insql;
END_SQL;
        //TODO: sanitise this variable
        $messages = $DB->get_recordset_sql($query, $params);


        $usermessages = [];
        foreach ($messages as $message){
            //TODO: if the message is

            $mcontext = $message->context;
            //get all role assigns from paths in $userassign that contain the above context.
            $messageincuserassign = false;
            foreach($usercontexts as $usercontext){
                $contextfamily = preg_split('|/|', $usercontext->path, 0, PREG_SPLIT_NO_EMPTY);
                array_shift($contextfamily);
                //we are only interested in categories and courses
                 if(in_array($mcontext, $contextfamily)){
                     if($message->role  == $usercontext->roleid) {
                         $messageincuserassign = true;
                         break;
                     }
                 }
            }
            if($messageincuserassign == true){
                array_push($usermessages, $message->messageid);
            }
        }



        unset($messages, $insql, $params);

        try{
            list($insql, $params) = $DB->get_in_or_equal(array_unique($usermessages), SQL_PARAMS_QM, 'param', true, true);
        }
        catch (Exception $e){
            echo get_string('nomessages', 'local_qmul_messaging');
            $this->messagelist = null;
            return false;
        }

        array_push($params, $userid);

        $query = <<<END_SQL
        SELECT lqm.id as messageid,  lqm.*, r.*, lqmr.role as messagerole, u.firstname, u.lastname, cc.name AS catname,
                CASE
                  WHEN lqm.validfrom = 0 THEN lqm.created
                  ELSE lqm.validfrom END AS postdate
               FROM
                {course_categories} cc,
                {context} ctx,
                {local_qmul_messaging} lqm,
                {local_qmul_messaging_roles} lqmr,
                {user} u,
                {role} r   
               WHERE 
               		ctx.instanceid = cc.id
                AND lqm.context = ctx.id  
                AND lqm.id = lqmr.message
                AND lqm.author = u.id    
			    AND lqm.id $insql
			    AND	lqmr.role = r.id
		UNION 
        SELECT  lqm.id as messageid,  lqm.*, r.*, lqmr.role as messagerole, u.firstname, u.lastname, 'Site wide' AS catname,
                CASE
                  WHEN lqm.validfrom = 0 THEN lqm.created
                  ELSE lqm.validfrom END AS postdate
		  FROM 
			{local_qmul_messaging} lqm,
			{local_qmul_messaging_roles} lqmr,
			{user} u,
            {role} r
          WHERE
				    lqmr.message = lqm.id
				AND lqm.author = u.id
				AND lqm.context = 1
			    AND lqmr.role IN 
                   (SELECT ra.roleid 
                           FROM {role_assignments} ra,
                           	    {role} r
                           WHERE ra.roleid = r.id 
                           AND ra.userid= ? 
                           GROUP BY roleid)
			    AND lqmr.role = r.id
		ORDER BY postdate DESC;  
            

END_SQL;

        $results =  $DB->get_recordset_sql($query, $params);





        //crate message list array
        $messages = [];
        foreach ($results as $result){
            array_push($messages, $result);
        }

        //add roles array to each message
        foreach($messages as $message){
            $id =  $message->messageid;
            $message->rolecodes = [];
            $message->rolenames = [];
            foreach ($messages as $mes){
                if($mes->messageid == $id){
                    array_push($message->rolecodes, $mes->messagerole);
                    array_push($message->rolenames, $mes->name);
                }
            }

        }

        //deduplicate messages now they roles have been rolled up
        $dedupedmessages = [];
        $messageidlist = [];
        foreach ($messages as $mes2){
            if(!in_array($mes2->messageid, $messageidlist)){
                array_push($messageidlist, $mes2->messageid);
                array_push($dedupedmessages, $mes2);
            }
        }

        unset ($messages, $message);

        $contextid = $this->_context->id;
        $issitecontext = (($contextid == 1) or ($contextid == 2)) ? true : false;

        //calculate various messages properties
        //AND remove messages not pertinent to category view
        $messages = [];
        foreach($dedupedmessages as $message){
            $message = $this->_get_message_properties($message);
            // also remove messages that are not in the context
            if(!$issitecontext){
                if($contextid != $message->context){
                    continue;
                }
            }
            array_push($messages, $message);
        }

        //set message list
        $this->messagelist = $messages;

    }


    public function get_authored_messages($userid){
        $this->set_authored_messages($userid);
        return $this->messagelist;
    }


    private function set_authored_messages($userid){
        global $DB;

        $query = <<<END_SQL
        SELECT lqm.id as messageid,  lqm.*, r.*, lqmr.role as messagerole, u.firstname, u.lastname,
            CASE
                  WHEN lqm.validfrom = 0 THEN lqm.created
                  ELSE lqm.validfrom END AS postdate
            FROM
                {local_qmul_messaging} lqm,
                {local_qmul_messaging_roles} lqmr,
                {user} u,
                {role} r
            WHERE
                u.id = lqm.author
                AND lqmr.message = lqm.id
                AND lqm.author = ?
                AND lqmr.role = r.id
         ORDER BY postdate DESC;  

END_SQL;

        $results = $DB->get_recordset_sql($query, array($userid));

        //crate message list array
        $messages = [];
        foreach ($results as $result){
            array_push($messages, $result);
        }

        //add roles array to each message
        foreach($messages as $message){
            $id =  $message->messageid;
            $message->rolecodes = [];
            $message->rolenames = [];
            foreach ($messages as $mes){
                if($mes->messageid == $id){
                    array_push($message->rolecodes, $mes->messagerole);
                    array_push($message->rolenames, $mes->name);
                }
            }

        }

        //deduplicate messages now they roles have been rolled up
        $dedupedmessages = [];
        $messageidlist = [];
        foreach ($messages as $mes2){
            if(!in_array($mes2->messageid, $messageidlist)){
                array_push($messageidlist, $mes2->messageid);
                array_push($dedupedmessages, $mes2);
            }
        }

        unset ($messages, $message);

        //calculate various messages properties
        $messages = [];
        foreach($dedupedmessages as $message){
            $message = $this->_get_message_properties($message);
            array_push($messages, $message);
        }

        $this->messagelist = $messages;
    }





    // Custom functions
    function title() {

        if ($this->_context == context_system::instance()) {
            return get_site()->fullname;
        }
        if ($cc = $this->_context->get_course_context(false)) {
            $course = get_course($cc->instanceid);
            return format_string($course->fullname);
        }
    }

    function description() {

        if ($this->_context == context_system::instance()) {
            return get_site()->summary;
        }
        if ($cc = $this->_context->get_course_context(false)) {
            $course = get_course($cc->instanceid);
            return format_text($course->summary);
        }
    }


    /**
     * @param $message
     * @return mixed
     * adds various properties to the message
     * including
     * message
     * - status
     * - validfrom
     * - hideordelete
     * - editable
     * - displaytime
     */
    private function _get_message_properties($message) {

        $message->status = get_string('activestatus', 'local_qmul_messaging');

        if($message->hidden){
            $message->status = get_string('hiddenstatus', 'local_qmul_messaging');
        }

            // are scheduled date valid for display
        $validfrom = $message->validfrom;
        $validto = $this->get_validto_time($validfrom, $message->validfor);
        if($message->validfrom or $validto){
          //  $is_valid_from_to_date = $this->is_valid_from_to_date($validfrom, $validto);
            $is_valid_date = $this->is_valid_date($validfrom, $validto);
            $is_almost_expired = $this->is_almost_expired($validto, 3);
            $is_expired = $this->is_expired($validto);

            if ($is_almost_expired){
                $message->status = get_string('almostexpiredstatus', 'local_qmul_messaging');
            }
            else if ($is_expired){
                //Do some things based on hidedelete value, hide = 1, delete = 2
                if($message->hideordelete == 1){
                    $message->status = get_string('archivedstatus', 'local_qmul_messaging');
                    $message->hidden = 1;
                }
                else if ($message->hideordelete == 2){
                    local_qmul_messaging_delete_message($message);
                }
                $message->hidden = 1;
            }
            if(!$is_valid_date){
                $message->status = get_string('scheduledstatus', 'local_qmul_messaging');
                $message->hidden = 1;
            }

        }


        $message->readbyuser = false;
        if(in_array($message->messageid, $this->markasreadlist)){
            $message->readbyuser = true;
        }

        $message->catroles = '';
        $count = count($message->rolenames);
        $i = 0;
        foreach($message->rolenames as $role){
            $message->catroles .= $role;
            $i++;
            if($i < $count){
                $message->catroles .= 's, ';
            }else{
                $message->catroles .= 's';
            }
        }


        $message->editable = has_capability('local/qmul_messaging:send', context::instance_by_id($message->context), $this->_user);

        //TODO: why does this kill webservice and not others?
        if ($message) {
//            $message->message = file_rewrite_pluginfile_urls($message->message, 'pluginfile.php', $message->context, 'local_qmul_messaging', 'message', $message->id);
        }

        $message->displaytime = local_qmul_messaging_get_record_display_time($message->postdate);
        $message->expiretime = "--";
        if ($validto){
            $message->expiretime = $this->get_record_expiry_time($validto);
        }

        return $message;
    }


    function is_valid_from_to_date($validfrom, $validto){
        if(!$validfrom){
            return false;
        }
        $time = time();
        if($validto) {
            if (($validfrom < $time) && ($validto > $time)) {
                return true;
            }
        }
        return false;

    }

    function is_expired($validto){
        $time = time();
        if($validto) {
            if ($validto < $time) {
                return true;
            }
        }
        return false;
    }

    function is_valid_date($validfrom, $validto){
        if(!$validfrom){
            return false;
        }
        $time = time();
        if($validto) {
            if (($validfrom < $time) && ($validto > $time)) {
                return true;
            }
        }
        if ($validfrom < $time) {
            return true;
        }
        return false;
    }

    function get_validto_time($validfrom, $validfor){
        if(!$validfrom){
            return false;
        }
        if($validfor) {
            $validto = $validfrom + $validfor;
            return $validto;
        }
        else{
            return false;
        }
    }

    function is_almost_expired($validto, $offset=3){

        $time = time();
        $almost = time() - ($offset * 24 * 60 * 60);

        if($validto) {
            if (($offset < $time) && ($validto > $time)) {
                return true;
            }
        }
        return false;

    }

    public function get_record_display_time($postdate){

        date_default_timezone_set('UTC');
        $displaydate = '';

        $today = date('j M Y');

        $year = date('Y', $postdate);
        $date = date('j M Y', $postdate);

        if($today == $date){
            $time = date('H:i', $postdate);
            $displaydate = $time;
        }
        else{
            $displaydate = $date;
        }

        return $displaydate;
    }

    public function get_record_expiry_time($expiredate){

        date_default_timezone_set('UTC');

        $today = date('j M Y');

        $date = date('j M Y', $expiredate);

        if($today == $date){
            $time = date('H:i', $expiredate);
            $displaydate = $time;
        }
        else{
            $displaydate = $date;
        }

        return $displaydate;
    }




}
