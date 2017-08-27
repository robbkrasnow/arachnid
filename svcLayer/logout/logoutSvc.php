<?php
	/**
	 * This file is intended to be used as the service layer for logging
	 * out of the system.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the service layer
	require_once('./bizDataLayer/logoutBizData.php');
	require_once('./bizDataLayer/library.php');
	require_once('../../../dbInfo.inc');

	
	/**
	 * This method is used for logging the user out of the system. Based
	 * on the user's ID, sanitize and filter it, then send to the data
	 * layer for processing.
	 * 
	 * @param 	$d	The userId of the person logging out (user_id)
	 * @return   	The JSON object of success or failure
	 */
	function start_logout($d) {	
		$user_id = sanitizeAndFilter($d);

		return start_logout_data($user_id);
	}
?>