moodle-local_course_backup
==========================

A Local plugin that allows system administrator to create adhoc course backups.
this currently has to entry points:

 course_backup24hr 
 course_back7day

the purpose of the above entry points are to allow for the scripts to include and use different config.php files.

as this plugin has been developed to run on a utility server there is no way to interact with a UI to set up the required settings so the following must be in the config.php as a forced settings

// forced setting for the CLI ADHOC Course backup script because the database is refreshed.
 $CFG->forced_plugin_settings = array('qmul/course_backup'  => array(
 														"backup_general_users"=> 0,
													    "backup_general_users_locked" => 0,
													    "backup_general_users_anonymize" => 0,
													    "backup_general_users_anonymize_locked" => 0,
													    "backup_general_role_assignments" => 0,
													    "backup_general_role_assignments_locked" => 0,
													    "backup_general_activities" => 1,
													    "backup_general_activities_locked" => 0,
													    "backup_general_blocks" => 1,
													    "backup_general_blocks_locked" => 0,
													    "backup_general_filters" => 1,
													    "backup_general_filters_locked" => 0,
													    "backup_general_comments" => 0,
													    "backup_general_comments_locked" => 0,
													    "backup_general_userscompletion" => 0,
													    "backup_general_userscompletion_locked" => 0,
													    "backup_general_logs" => 0,
													    "backup_general_logs_locked" => 0,
													    "backup_general_histories" => 0,
													    "backup_general_histories_locked" => 0,
													    "backup_auto_weekdays" => 0000000,
													    "backup_auto_hour" => 0,
													    "backup_auto_minute" => 0,
													    "backup_auto_storage" => 1,
													    "backup_auto_keep" => 1,
													    "backup_auto_users" => 1,
														"backup_auto_user_files" => 1,
													    "backup_auto_role_assignments" => 1,
													    "backup_auto_activities" => 1,
													    "backup_auto_blocks" => 1,
													    "backup_auto_filters" => 1,
													    "backup_auto_comments" => 1,
													    "backup_auto_userscompletion" => 1,
													    "backup_auto_logs" => 1,
													    "backup_auto_histories" => 1,
													    "backup_auto_active" => 2,    
													    "backup_auto_destination" => "DIRECTORY/TO/SAVE/TO"
 																)
								);   

