<?php

/**
 * @author Petr Skoda
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle multiauth
 *
 * Authentication Plugin: No Authentication
 *
 * No authentication at all. This method approves everything!
 *
 * 2007-02-18  File created.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');

/**
 * Plugin for no authentication - disabled user.
 */
class auth_plugin_ulcc_sharedsecret_sso extends auth_plugin_base {


	
    /**
     * Constructor.
     */
    function auth_plugin_ulcc_sharedsecret_sso() {
        $this->authtype = 'ulcc_sharedsecret_sso';
    }

	
	function auth_get_record($table,$field,$param)	{
		global $DB;
	
		if (!isset($DB))	{
			return get_record($table,$field,$param);
		}	else	{
			return $DB->get_record($table,array($field=>$param));
		}
	}
	
	/*
	 * login hook to allow users with the correct client id and session key to perform a single sign on
	 */
	 function loginpage_hook(){

		global $CFG,$USER;


		$config	=	get_config('auth/ulcc_sharedsecret_sso');

         $sharedsecret		=	(!empty($config->sharedsecret))    ?        $config->sharedsecret   :   false;
         $useexpiration		=	(!empty($config->useexpiration))    ?       $config->useexpiration  :   false;
         $expirationtime    =	(!empty($config->expirationtime))   ?       $config->expirationtime :   false;
		$clientusername 	= optional_param('clientuname',null,PARAM_TEXT);
         $sshash 			= optional_param('sshash',null,PARAM_RAW);
         $page 			    = optional_param('ssopage',null,PARAM_RAW);
         $gentime 			= optional_param('gen',null,PARAM_RAW);
         $genhash 			    = optional_param('ghash',null,PARAM_RAW);



		if (!empty($clientusername) && !empty($sshash) && !empty($sharedsecret))  {

            $generatedssecret   =   md5($clientusername.$sharedsecret);

            if (!empty($useexpiration))     {
                //first check the hash of the generated time matches the given timestamp
                 if ($genhash != md5($gentime)) return false;

                //we are sure that the gentime and the ghash are of the same time - still could be a spoof though

                //check the expiration time has not been passed
                if (time() > $gentime+$expirationtime) return false;

                //lets make the hash again this time with the token generated time included
                $generatedssecret   =   md5($clientusername.$sharedsecret.$gentime);
            }

			//make sure the $sshash is equal to a md5 of the username supplied and the shared secret
			if ($generatedssecret	== $sshash) {
				$user = $this->auth_get_record("user","username",$clientusername);
				//make sure the user exists
				if (!empty($user)) {
					$USER	=	complete_user_login($user);
                    if(!empty($USER))   {
                        $new_page = $CFG->wwwroot.'/'.$page;
                        redirect($new_page);
                    }
				}
			}
		}
		return false;
	 }
	 
	 
	 /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        // set to defaults if undefined
        if (!isset ($config->sharedsecret)) {
            $config->sharedsecret = 'testmoodle';
        }

        // save settings
        
        set_config('sharedsecret', $config->sharedsecret, 'auth/ulcc_sharedsecret_sso');
        set_config('useexpiration', $config->useexpiration, 'auth/ulcc_sharedsecret_sso');
        set_config('expirationtime', $config->expirationtime, 'auth/ulcc_sharedsecret_sso');
        return true;
    }
	
	 /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    function config_form($config, $err, $user_fields) {
        include "config.html";
    }

}

?>
