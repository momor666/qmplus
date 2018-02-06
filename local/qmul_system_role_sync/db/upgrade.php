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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    upgrade
 * @category   '$PWD'
 * @copyright  2013 Queen Mary University Gerry Hall
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 function xmldb_local_qmul_system_role_sync_upgrade($oldversion =0) {
			$config = new stdClass();
	        $config->filepath = '//u//web//qmulmis//mis_uploads';      //location pointed to QMUL by ULCC to upload MIS and other files
	        $config->filename = 'BUPT_accounts.csv';                    // filename containing all JP Students usernames
	        $config->rolename = 'JP Student';                           // role name to assign on the system level
			foreach ($config as $property => $property_value) {
	            set_config($property, $property_value, 'qmul_system_role_sync');
			}

  return true;
}
