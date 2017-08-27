<?php
	/**
	 * This file is intended to be used as the data layer for any logouts
	 * out of the system.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the data layer	
	require_once('./bizDataLayer/library.php');
	require_once('./bizDataLayer/exception.php');
	require_once('./classes/Database.class.php');


	/**
	 * Method used to start the logout process from the DB for a specified user.
	 *
	 * @param 	$user_id	The user's ID
	 * @return				The JSON of a successful or failed connection to the DB 
	 */
	function start_logout_data($user_id) {
		// Make sure the userId is an integer, not a string
		$user_id = intval($user_id);
		
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'UPDATE arachnid.user SET room_num=? WHERE user_id=?';
		$vars = array(-1, $user_id);
		$types = array('i', 'i');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		if($num_rows == 1) {
			return '[{"success":"true"}]';
		}
		
		return '[{"success":"false"}]';
	}
?>