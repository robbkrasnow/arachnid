<?php
	/**
	 * This script is intended to be used as the service layer for the
	 * dashboard.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */
	

	// Required files for the service layer
	require_once('./bizDataLayer/dashboardBizData.php');	
	require_once('./bizDataLayer/library.php');
	require_once('../../../dbInfo.inc');
	

	/**
	 * This method is used for sending the user ID through to the bizData
	 * layer to pull necessary information for creating the dashboard.
	 *
	 * @param  $d	The data coming into the system (user_id)
	 * @return 		The JSON of whether or not the login was successful or failed
	 */
	function start_dashboard($d) {
		return start_dashboard_data(sanitizeAndFilter($d));
	}
?>