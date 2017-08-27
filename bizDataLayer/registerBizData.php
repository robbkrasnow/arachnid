<?php
	/**
	 * This file is intended to be used as the data layer for any new registrations
	 * into the system.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the data layer
	require_once('./bizDataLayer/exception.php');
	require_once('./bizDataLayer/library.php');
	require_once('./classes/Database.class.php');
	require_once('../../../Token.class.php');


	/**
	 * Method used to register a new user based on the input they added
	 * to the registration form.
	 *
	 * @param 	$new_username 	The user's new username
	 * @param 	$new_password 	The user's new hashed password
	 *
	 */
	function start_register_data($new_username, $new_password) {
		// Set default timezone to central
		date_default_timezone_set('America/Chicago');
		
		// Set the date to be inserted to the "account_created" time column
		$date = date("Y-m-d H:i:s");
		
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'INSERT INTO arachnid.user (username, password, last_accessed, account_created, num_mailboxes, room_num) VALUES (?, ?, ?, ?, ?, ?)';
		$vars = array($new_username, $new_password, $date, $date, 0, -1);
		$types = array('s', 's', 's', 's', 'i', 'i');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		// If there was an affected row, grab data from row
		if($num_rows == 1) {
			return '[{"success":"true"}]';
		}
		
		// Return false and the DB error if failure
		return '[{"success":"false"},{"error":'.json_encode($db->getError()).'}]';
	}
?>