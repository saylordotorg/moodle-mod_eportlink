<?php
	
/**
 * This file updates the users auth in moodle so as to handle cas
 * auth for students is changed to 'cas'
 * auth for admins is changed t0 'manual'
 *
 * url for this script - /mod/quiz/update_users_info_cas.php
 *
 */
	require_once("../../config.php");

	function update_users() {
		global $CFG;
		
		$sql_change_students_auth = "UPDATE {$CFG->prefix}user set auth = 'cas' 
						WHERE id IN (SELECT ra.userid 
						FROM {$CFG->prefix}role_assignments ra, {$CFG->prefix}role r 
						WHERE r.name = 'Student' 
						AND r.id = ra.roleid);";
		
		execute_sql($sql_change_students_auth, false);

		$sql_change_admins_auth ="UPDATE {$CFG->prefix}user set auth = 'manual' 
					WHERE id IN (select ra.userid from {$CFG->prefix}role_assignments ra, {$CFG->prefix}role r 
					WHERE r.name = 'Administrator' 
					AND r.id = ra.roleid);";
		
		execute_sql($sql_change_admins_auth, false);
	}
	echo(update_users());
?>