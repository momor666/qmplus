<?php

/**
 * Moodle ULCC Admin page.
 *
 * @package    block_ulcc_diagnostic
 * @copyright  2011 onwards ULCC (http://www.ulcc.ac.uk)
 * 
 */
 
require('../../../config.php');
global $CFG,$USER,$DB;
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->libdir.'/ldaplib.php');

@set_time_limit(3600); // 1 hour should be enough
raise_memory_limit(MEMORY_EXTRA);

require_login();
require_capability('moodle/site:config', context_system::instance());

$testuser = optional_param('user',NULL,PARAM_TEXT);

$PAGE->set_url('/blocks/ulcc_diagnostics/views/ldap_connect.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_course($SITE);
$PAGE->set_pagetype('ldap-connect');
$PAGE->set_docs_path('');
$PAGE->set_pagelayout('report');
$PAGE->navbar->add('ULCC Diagnosis');
$PAGE->navbar->add('LDAP Connection');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('ldap','block_ulcc_diagnostics'));
echo $OUTPUT->box_start('generalbox ldapdiag');
echo $OUTPUT->heading(get_string('settings','block_ulcc_diagnostics'));

$config = get_config('auth/ldap');

echo get_string('dbhost','block_ulcc_diagnostics'); 
if (empty($config->host_url)) {
	echo '<a href="'.$CFG->wwwroot.'/admin/auth_config.php?auth=ldap" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
}else{
	echo '<span style="color:#006600">'.$config->host_url.'</span>';
	echo '<br />';

	$urls = explode(';', $config->host_url);
	foreach ($urls as $server) {
		$server = trim($server);
		if (empty($server)) {
			continue;
		}
		echo '<div style="border: 1px solid #999"><p>'.$server.'</p>';
		if($connresult = ldap_connect($server)) {
			echo '<span style="color:#006600">Connection: '.$connresult.'</span>';
		}else{
			echo '<a href="'.$CFG->wwwroot.'/admin/auth_config.php?auth=ldap" style="color:#ff0000">'.get_string('noconnection','block_ulcc_diagnostics').'</a>'; 
		}
		echo '<br />';
		
		echo get_string('ldapversion','block_ulcc_diagnostics'); 
		if (!empty($config->ldap_version)) {
			echo '<span style="color:#006600">'.$config->ldap_version.'</span>';
			ldap_set_option($connresult, LDAP_OPT_PROTOCOL_VERSION, $config->ldap_version);
		}else{
			echo '<a href="'.$CFG->wwwroot.'/admin/auth_config.php?auth=ldap" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';
		}
		echo '<br />';
	
		// Fix MDL-10921
		if ($config->user_type === 'ad') {
			ldap_set_option($connresult, LDAP_OPT_REFERRALS, 0);
		}
	
		if (!empty($config->opt_deref)) {
			ldap_set_option($connresult, LDAP_OPT_DEREF, $config->opt_deref);
		}
		
		echo get_string('dbuser','block_ulcc_diagnostics');	 
		if (!empty($config->bind_dn)) {
			echo '<span style="color:#006600">'.$config->bind_dn.'</span>';
			echo '<br />';
			echo 'Bind:';
			if($bindresult = @ldap_bind($connresult, $config->bind_dn, $config->bind_pw)) {
				echo '<span style="color:#006600">'.get_string('success','block_ulcc_diagnostics').'</span>';
				echo '<br />';
				echo $OUTPUT->heading(get_string('contexts','block_ulcc_diagnostics')); 
				echo '<br />';	
				if(!empty($config->contexts)){
					$dns = explode(';', $config->contexts);
					foreach ($dns as $dn) {
						echo '<p>';
						echo '<span style="color:#006600">'.$dn.'</span>';
						echo '<br />';
						if(!empty($config->user_attribute)){
							echo 'Search <span style="color:#006600">'.$config->user_attribute.' - if no results try a different attribute (e.g. cn or samaccountname)</span>';
							echo '<br />';
							if($result = ldap_search($connresult,$dn,"($config->user_attribute=*)")) {
								echo '<span style="color:#006600">'.ldap_count_entries($connresult,$result).'</span> Records';
								echo '<br />';
								echo '<span style="color:#ff0000">'.ldap_error($connresult).'</span>';
								echo '<br />';
								
								if($testuser) {
									echo 'Using user: '.$testuser;
									$result = ldap_search($connresult,$dn,"($config->user_attribute=$testuser)");
								}else{
									echo 'Using bind user: '.$config->bind_dn.' (add ?user=TESTUSERNAME to try a different user)';
									$result = ldap_search($connresult,$dn,"($config->user_attribute=$config->bind_dn)");
								}
								
								echo '<br />';
								$entry = ldap_first_entry($connresult, $result);
                                $attrs = ldap_get_attributes($connresult, $entry);
                                
                                echo '<p>'.$attrs["count"] . " attributes held for this entry:</p>";
                                echo '<ul>';
							    for ($j = 0; $j < $attrs["count"]; $j++){
                                  $attr_name = $attrs[$j];
                                  $attrs["$attr_name"]["count"] . "\n";
                                  for ($k = 0; $k < $attrs["$attr_name"]["count"]; $k++) {
                                         echo '<li>'.$attr_name.": ".$attrs["$attr_name"][$k]. '</li>';
                                  }
                                }
                                echo '</ul>';
							}
						}else{
							echo 'Search <a href="'.$CFG->wwwroot.'/admin/auth_config.php?auth=ldap" style="color:#006600">Default user attribute</a> - if no results try a different attribute (e.g. samaccountname)';
							echo '<br />';
							if($result = ldap_search($connresult,$dn,"(cn=".$config->bind_dn.")")) {
								echo '<span style="color:#006600">'.ldap_count_entries($connresult,$result).'</span> Records';
								echo '<br />';
								echo '<span style="color:#ff0000">'.ldap_error($connresult).'</span>';
								
								if($testuser) {
									echo 'Using user: '.$testuser;
									$result = ldap_search($connresult,$dn,"($config->user_attribute=$testuser)");
								}else{
									echo 'Using bind user: '.$config->bind_dn.' (add ?user=TESTUSERNAME to try a different user)';
									$result = ldap_search($connresult,$dn,"($config->user_attribute=$config->bind_dn)");
								}
								
								$entry = ldap_first_entry($connresult, $result);
                                $attrs = ldap_get_attributes($connresult, $entry);
                                
                                echo '<p>'.$attrs["count"] . " attributes held for this entry:</p>";
                                echo '<ul>';
							    for ($j = 0; $j < $attrs["count"]; $j++){
                                  $attr_name = $attrs[$j];
                                  $attrs["$attr_name"]["count"] . "\n";
                                  for ($k = 0; $k < $attrs["$attr_name"]["count"]; $k++) {
                                         echo '<li>'.$attr_name.": ".$attrs["$attr_name"][$k]. '</li>';
                                  }
                                }
                                echo '</ul>';
							}							
						}
						echo '</p>';
					}
				}
			}else{
				echo '<a href="'.$CFG->wwwroot.'/admin/auth_config.php?auth=ldap" style="color:#ff0000">'.get_string('nobind','block_ulcc_diagnostics').'</a>';
			}
		}elseif($bindresult = @ldap_bind($connresult)){
            echo '<span style="color:#006600">'.get_string('anonbind','block_ulcc_diagnostics').'</span>';
            echo '<br />';
            echo 'Bind:';
            echo '<span style="color:#006600">' . get_string('success', 'block_ulcc_diagnostics') . '</span>';
            echo '<br />';
            echo $OUTPUT->heading(get_string('contexts', 'block_ulcc_diagnostics'));
            echo '<br />';
            if (!empty($config->contexts)) {
                $dns = explode(';', $config->contexts);
                foreach ($dns as $dn) {
                    echo '<p>';
                    echo '<span style="color:#006600">' . $dn . '</span>';
                    echo '<br />';
                    if (!empty($config->user_attribute)) {
                        echo 'Search <span style="color:#006600">' . $config->user_attribute . ' - if no results try a different attribute (e.g. cn or samaccountname)</span>';
                        echo '<br />';
                        if ($result = ldap_search($connresult, $dn, "($config->user_attribute=*)")) {
                            echo '<span style="color:#006600">' . ldap_count_entries($connresult, $result) . '</span> Records';
                            echo '<br />';
                            echo '<span style="color:#ff0000">' . ldap_error($connresult) . '</span>';
                            echo '<br />';

                            if ($testuser) {
                                echo 'Using user: ' . $testuser;
                                $result = ldap_search($connresult, $dn, "($config->user_attribute=$testuser)");
                            } else {
                                echo 'Using bind user: ' . $config->bind_dn . ' (add ?user=TESTUSERNAME to try a different user)';
                                $result = ldap_search($connresult, $dn, "($config->user_attribute=$config->bind_dn)");
                            }

                            echo '<br />';
                            $entry = ldap_first_entry($connresult, $result);
                            $attrs = ldap_get_attributes($connresult, $entry);

                            echo '<p>' . $attrs["count"] . " attributes held for this entry:</p>";
                            echo '<ul>';
                            for ($j = 0; $j < $attrs["count"]; $j++) {
                                $attr_name = $attrs[$j];
                                $attrs["$attr_name"]["count"] . "\n";
                                for ($k = 0; $k < $attrs["$attr_name"]["count"]; $k++) {
                                    echo '<li>' . $attr_name . ": " . $attrs["$attr_name"][$k] . '</li>';
                                }
                            }
                            echo '</ul>';
                        }
                    } else {
                        echo 'Search <a href="' . $CFG->wwwroot . '/admin/auth_config.php?auth=ldap" style="color:#006600">Default user attribute</a> - if no results try a different attribute (e.g. samaccountname)';
                        echo '<br />';
                        if ($result = ldap_search($connresult, $dn, "(cn=" . $config->bind_dn . ")")) {
                            echo '<span style="color:#006600">' . ldap_count_entries($connresult, $result) . '</span> Records';
                            echo '<br />';
                            echo '<span style="color:#ff0000">' . ldap_error($connresult) . '</span>';

                            if ($testuser) {
                                echo 'Using user: ' . $testuser;
                                $result = ldap_search($connresult, $dn, "($config->user_attribute=$testuser)");
                            } else {
                                echo 'Using bind user: ' . $config->bind_dn . ' (add ?user=TESTUSERNAME to try a different user)';
                                $result = ldap_search($connresult, $dn, "($config->user_attribute=$config->bind_dn)");
                            }

                            $entry = ldap_first_entry($connresult, $result);
                            $attrs = ldap_get_attributes($connresult, $entry);

                            echo '<p>' . $attrs["count"] . " attributes held for this entry:</p>";
                            echo '<ul>';
                            for ($j = 0; $j < $attrs["count"]; $j++) {
                                $attr_name = $attrs[$j];
                                $attrs["$attr_name"]["count"] . "\n";
                                for ($k = 0; $k < $attrs["$attr_name"]["count"]; $k++) {
                                    echo '<li>' . $attr_name . ": " . $attrs["$attr_name"][$k] . '</li>';
                                }
                            }
                            echo '</ul>';
                        }
                    }
                    echo '</p>';
                }
            }else{
            	echo '<a href="'.$CFG->wwwroot.'/admin/auth_config.php?auth=ldap" style="color:#ff0000">'.get_string('nobind','block_ulcc_diagnostics').'</a>';
            }
        }else{
			echo '<a href="'.$CFG->wwwroot.'/admin/settings.php?section=enrolsettingsdatabase" style="color:#ff0000">'.get_string('noentry','block_ulcc_diagnostics').'</a>';	
		}
		echo '</div>';
	}
}
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
