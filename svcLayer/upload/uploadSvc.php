<?php
	/**
	 * This file is intended to be used as the service layer for uploading
	 * mbox files to the server for parsing.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the service layer
	require_once('./bizDataLayer/uploadBizData.php');
	require_once('./bizDataLayer/library.php');
	require_once('../../../dbInfo.inc');


	/**
	 * This method is used for sending an uploaded file's metadata to the
	 * server for parsing.
	 *
	 * @param  $d 	The data coming into the system (user_id|file_hash|file_name|file_type|file_size)
	 * @return 		The JSON of whether or not the login was successful or failed
	 */
	function start_upload($d) {
		$data = explode('|', $d);
		$user_id = sanitizeAndFilter(intval($data[0]));
		$file_hash = sanitizeAndFilter($data[1]);
		$file_name = sanitizeAndFilter($data[2]);
		$file_type = sanitizeAndFilter($data[3]);
		$file_size = sanitizeAndFilter(intval($data[4]));

		// Check if the mailbox already exists in the database based on the
		// user ID and the file hash
		$mailbox_exists = check_if_mailbox_exists_data($user_id, $file_hash);

		// If the mailbox hash doesn't already exist in the database, update
		// the number of mailboxes for that user
		if($mailbox_exists === false) {
			$num_mailboxes = get_num_mailboxes_by_user_id($user_id);
			update_num_mailboxes_data($user_id, $num_mailboxes);
		}
		
		// Start the parsing/insertion into the database
		return start_upload_data($user_id, $file_hash, $file_name, $file_type, $file_size, $mailbox_exists);
	}


	function start_get_parse_status($d) {
		$data = explode('|', $d);
		$user_id = sanitizeAndFilter(intval($data[0]));
		$file_hash = sanitizeAndFilter($data[1]);

		return start_get_parse_status_data($user_id, $file_hash);
	}
?>