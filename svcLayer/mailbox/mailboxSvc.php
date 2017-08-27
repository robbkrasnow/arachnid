<?php
	/**
	 * This script is intended to be used as the service layer for pulling
	 * visualization data from the parsed mbox files.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the service layer
	require_once('./bizDataLayer/mailboxBizData.php');
	require_once('./bizDataLayer/library.php');
	require_once('../../../dbInfo.inc');


	/**
	 * This method is used for sending the user_id and mailbox_id to
	 * the bizData layer after sanitizing and filtering.
	 *
	 * @param  $d 	The data coming into the system (user_id|mailbox_id)
	 * @return 		The JSON of whether or not the login was successful or failed
	 */
	function start_mailbox($d) {
		$data = explode('|', $d);
		$user_id = intval(sanitizeAndFilter($data[0]));
		$mailbox_id = intval(sanitizeAndFilter($data[1]));

		return start_mailbox_data($user_id, $mailbox_id);
	}
?>