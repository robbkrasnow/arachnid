<?php
	/**
	 * This file is intended to be used as the service layer for any new
	 * user's registration.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the service layer
	require_once('./bizDataLayer/registerBizData.php');
	require_once('./bizDataLayer/library.php');
	require_once('../../../dbInfo.inc');


	/**
	 * This method is used to start the registration process. Will take in new
	 * username and password, sanitize and filter them, and send them to the
	 * data layer to be processed. Hash and salt the password.
	 *
	 * @param 	$d  The data coming in from the user (new_username|new_password)
	 * @return   	The JSON object of success or failure
	 */
	function start_register($d) {
		$data = explode("|", $d);
		$new_username = sanitizeAndFilter($data[0]);
		$new_password = sha1(sanitizeAndFilter($data[1]).$new_username);
		
		return start_register_data($new_username, $new_password);
	}
?>