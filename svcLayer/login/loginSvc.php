<?php
	/**
	 * This file is intended to be used as the service layer for logging
	 * into the system.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the service layer
	require_once('./bizDataLayer/loginBizData.php');	
	require_once('./bizDataLayer/library.php');
	require_once('../../../dbInfo.inc');

	
	/**
	 * This method is intended for logging a user into the system. Sanitize
	 * and filter all data. Hash and salt the password, and send all data to
	 * the data layer where the user's room will be updated to 0 (lobby).
	 *
	 * @param  $d 			The data coming into the system (username|password)
	 * @return $loginData	The JSON of whether or not the login was successful or failed
	 */
	function start_login($d) {
		// Explode, sanitize, filter, and store username and password
		$data = explode('|', $d);
		$username = sanitizeAndFilter($data[0]);
		$password = sha1(sanitizeAndFilter($data[1]).$username);

		// Acquire success/failure JSON object from bizData				
		$login_data = start_login_data($username, $password);

		// Grab user ID based on username to update the user's room number to the lobby
		$user_id = get_user_id_from_username($username);
		
		// Update user's room to 0 indicating the lobby
		update_room_num($user_id, 0);
		
		// Return JSON object with success or failure
		return $login_data;
	}
?>