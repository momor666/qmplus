qmul_system_role_sync
=====================

qmul_system_role_sync enrolls all student from chosen file to JP Student role on the system context

Installing

Install this plugin like any normal QMUL/Moodle Plugin
Subtree Merge this repository into <root>/local/qmul_system_role_sync
	
The Install.php File sets the values the default values for the plugin and they are listed below.

$config->filepath = '/u/web/qmulmis/mis_uploads';      		//location pointed to QMUL by ULCC to upload MIS and other files

$config->filename = 'BUPT_accounts.csv';                    // filename containing all JP Students usernames

$config->rolename = 'JP Student';                           // role name to assign on the system level


To override these setting you can use moodle Forced settings in the site config.php file like so.

Example:

<pre><code>
$CFG->forced_plugin_settings = 
	array(
		'qmul_system_role_sync'  =>
				array(
					'filepath' => '/u/web/qmulmis/mis_uploads', 
					'filename' => 'BUPT_accounts.csv',
					'rolename' => 'JP Student'
				)
	
	);
</code></pre>
