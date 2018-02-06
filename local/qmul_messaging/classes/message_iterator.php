<?php

//defunctus est

defined('MOODLE_INTERNAL') || die();

class local_qmul_messaging_message_iterator implements Iterator {

    /** @var Iterator $_recordset The DB recordset being shadowed. */
    private $_recordset;

    /** @var context $_context The parent context being searched. */
    private $_context;

    private $_user;

    private $_messagelist;
    public $contextlessusermessagelist;
    public $markasreadlist;

    public function __construct(context $context, $recursive = true, $user = false) {
        global $DB, $USER;

        if ($user === false) {
            $user = $USER;
        }
        $this->_user = $user;
        $this->_messagelist = [];
        $this->_context = $context;
        $this->markasreadlist = local_qmul_messaging_get_read_list($USER->id);


//        $this->set_message_recordset_context($context->id, $user->id);

    }

    /**
     * @param $userid
     * @return mixed
     */
    public function get_message_recordset_by_user($userid){
        $this->set_message_recordset_by_user($userid);
        return $this->_recordset;
    }


    /**
     * @param $userid
     * This function gets a list of messages that are relevant to a user.
     * It divines all contexts that are of interest to a user and then
     * gets the messages that they have the correct roles for
     *
     */
    public function set_message_recordset_by_user($userid){
        global $DB, $USER;

        if(is_null($userid)){
            $userid = $USER->id;
        }


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
            list($insql, $params) = $DB->get_in_or_equal(array_unique($usermessages));
        }
        catch (Exception $e){
            echo get_string('nomessages', 'local_qmul_messaging');
            $this->_recordset = null;
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


        $messages = [];
        foreach ($results as $result){
            array_push($messages, $result);
        }

        $dedupedmessages = [];
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

        $messageidlist = [];
        foreach ($messages as $mes2){
            if(!in_array($mes2->messageid, $messageidlist)){
                array_push($messageidlist, $mes2->messageid);
                array_push($dedupedmessages, $mes2);
            }
        }

        unset ($messages);
        $messages = $dedupedmessages;

        $this->_recordset = $DB->get_recordset_sql($query, $params);

    }

    /**
     * @param $userid
     * @return mixed
     */
    public function get_message_recordset_context($context, $userid){
        $this->set_message_recordset_context($context, $userid);
        return $this->_recordset;
    }

    /**
     * Phil's original function that used context to fetch messages.
     *
     * @param $contextid
     * @param $userid
     */
    private function set_message_recordset_context($contextid, $userid){
        global $DB;

        $query = <<<END_SQL
        SELECT lqm.id as messageid,  lqm.*, r.*, lqmr.role as messagerole, u.firstname, u.lastname
            FROM
                {context} m_ctx,
                {context} u_ctx,
                {local_qmul_messaging} lqm,
                {local_qmul_messaging_roles} lqmr,
                {user} u,
                {role_assignments} ra,
                {role} r
            WHERE
                    lqm.context=m_ctx.id
                AND lqmr.message = lqm.id
                AND lqm.author = u.id
                AND (m_ctx.path LIKE concat(u_ctx.path, '/%') OR m_ctx.id=u_ctx.id)
                AND (m_ctx.id = :contextid1 OR m_ctx.path LIKE concat('%/', :contextid2, '/%'))
                AND ra.contextid=u_ctx.id
                AND lqmr.role = ra.roleid
                AND ra.roleid = r.id        
                AND ra.userid=:userid1 
         UNION 
         SELECT  lqm.id as messageid,  lqm.*, r.*, lqmr.role as messagerole, u.firstname, u.lastname 
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
                           AND ra.userid= :userid2 
                           GROUP BY roleid)
			    AND lqmr.role = r.id;  
END_SQL;

        $this->_recordset = $DB->get_recordset_sql($query, array(
            'contextid1' =>$contextid,
            'contextid2' =>$contextid,
            'userid1' => $userid,
            'userid2' => $userid,
        ));

    }

    public function get_authored_messages($userid){
        $this->set_authored_messages($userid);
        return $this->_recordset;
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

        $this->_recordset = $DB->get_recordset_sql($query, array($userid));
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

    // Standard iterator cruft.
    function rewind() {

        return $this->_recordset->rewind();
    }

    function key() {

        return $this->_recordset->key();
    }

    function current() {

        $record = $this->_recordset->current();

        $record->status = get_string('activestatus', 'local_qmul_messaging');

            // are scheduled date valid for display
        $validfrom = $record->validfrom;
        $validto = $this->get_validto_time($validfrom, $record->validfor);
        if($record->validfrom or $validto){
          //  $is_valid_from_to_date = $this->is_valid_from_to_date($validfrom, $validto);
            $is_valid_date = $this->is_valid_date($validfrom, $validto);
            $is_expired = $this->is_expired($validto);

            if($is_expired){
                //Do some things based on hidedelete value, hide = 1, delete = 0
                if($record->hideordelete){
                    $record->status = get_string('archivedstatus', 'local_qmul_messaging');
                    $record->hidden = 1;
                }
                else{
                    local_qmul_messaging_delete_message($record);
                }
                $record->hidden = 1;
            }
            if(!$is_valid_date){
                $record->status = get_string('scheduledstatus', 'local_qmul_messaging');
                $record->hidden = 1;
            }

        }


        if(!in_array($record->messageid, $this->_messagelist)){
            array_push($this->_messagelist, $record->messageid);
        }
        else{
            return false;
        }


        $record->editable = has_capability('local/qmul_messaging:send', context::instance_by_id($record->context), $this->_user);

        //TODO: why does this kill webservice and not others?
        if ($record) {
//            $record->subject = file_rewrite_pluginfile_urls($record->subject, 'pluginfile.php', $record->context, 'local_qmul_messaging', 'subject', $record->id);
//            $record->message = file_rewrite_pluginfile_urls($record->message, 'pluginfile.php', $record->context, 'local_qmul_messaging', 'message', $record->id);

        }


        $record->displaytime = local_qmul_messaging_get_record_display_time($record->postdate);

        return $record;
    }

    function next() {

        return $this->_recordset->next();
    }

    function valid() {
        return $this->_recordset->valid();
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


    //TODO: delete, moved to lib
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





}
