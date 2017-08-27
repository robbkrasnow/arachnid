<?php
	/**
	 * This script is meant to be used for everything related to uploading
	 * a mailbox to the system and parsing it.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the data layer	
	require_once('./bizDataLayer/library.php');
	require_once('./bizDataLayer/exception.php');
	require_once('./classes/Database.class.php');

	
	/**
	 * This method is used to check if the mailbox already exists
	 * in the databased based on file hash.
	 *
	 * @param 	$user_id 	The user's ID
	 * @param 	$file_hash 	The SHA1 hash of the file
	 * @return 	$flag 		True/False whether or not the mailbox exists
	 */
	function check_if_mailbox_exists_data($user_id, $file_hash) {
		// Set a flag to indicate if the mailbox exists or not
		$flag = false;

		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = 'SELECT file_hash FROM arachnid.mailbox WHERE user_user_id=?';
		
		try {
			if($stmt = $mysqli->prepare($sql)) {
				$stmt->bind_param('i', $user_id);
				$stmt->execute();
				$json = returnJson($stmt);
				
				if($json != 'null') {
					$output = json_decode($json, true);
					
					if(array_search($file_hash, array_column($output, 'file_hash')) !== false) {
						$flag = true;
					}
				}
				
				$stmt->close();
			}
			else {
				throw new Exception('An error occurred with check_if_mailbox_exists_data!');
			}
		}
		catch(Exception $e) {
			log_error($e, $sql, null, 'arachnid_upload');
		}

		return $flag;
	}


	/**
	 * Method used to update the user's number of mailboxes
	 *
	 * @param 	$userId		The user's ID
	 * @return	bool 		Return false if Exception thrown, otherwise nothing
	 */
	function update_num_mailboxes_data($user_id, $num_mailboxes) {
		// Increase the number of mailboxes
		$num_mailboxes++;

		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = 'UPDATE arachnid.user SET num_mailboxes=? WHERE user_id=?';
		
		try {
			if($stmt = $mysqli->prepare($sql)) {
				$stmt->bind_param('ii', $num_mailboxes, $user_id);
				$stmt->execute();
				$stmt->close();
			}
			else {
				throw new Exception('An error occurred with update_num_mailboxes_data!');
			}
		}
		catch(Exception $e) {
			log_error($e, $sql, null, 'arachnid_upload');

			return false;
		}
	}


	/**
	 * Method used to submit mailbox details to the mailbox table.
	 *
	 * @param 	$user_id	The user's ID
	 * @return				The JSON of a successful or failed connection to the DB 
	 */
	function start_upload_data($user_id, $file_hash, $file_name, $file_type, $file_size, $mailbox_exists) {
		date_default_timezone_set('America/Chicago');

		if($mailbox_exists === false) {
			// Set date format for DB
			$date = date('Y-m-d H:i:s');

			// Get a DB instance, set the query, and add the parameters
			$db = Database::getInstance();
			$sql = 'INSERT INTO arachnid.mailbox (file_hash, file_name, file_type, file_size, date_added, last_accessed, user_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)';
			$vars = array($file_hash, $file_name, $file_type, $file_size, $date, $date, $user_id);
			$types = array('s', 's', 's', 'i', 's', 's', 'i');
			
			// Run the query
			$db->doQuery($sql, $vars, $types);
			$num_rows = $db->getAffectedRows();

			if($num_rows == 1) {
				$mailbox_id = $db->getInsertId();
				$num_messages = parse_mbox_data($file_hash, $mailbox_id);
				
				if($num_messages > 0) {
					update_num_messages_data($num_messages, $mailbox_id);
					$message = 'File was uploaded and parsed successfully!';

					return '[{"success":"true"},{"message":"'.$message.'"},{"mailbox_id":'.$mailbox_id.'}]';
				}
				else {
					$error = "Error parsing Mbox file. Please try again.";
			
					return '[{"success":"false"},{"error":"'.$error.'"}]';
				}
			}
		}
		else {
			$mailbox_id = get_mailbox_id_by_user_id_and_file_hash($user_id, $file_hash);
			$date_added = get_mailbox_date_added($mailbox_id);
			$date_added_format = date('l, M j, Y, \a\\t g:i a T', strtotime($date_added));
			$error = "You've already uploaded this file on {$date_added_format}.";	// NOTE: Must be in double quotes
			
			return '[{"success":"false"},{"error":"'.$error.'"}]';
		}
	}


	/**
	 * Method used to parse a mailbox in .mbox format
	 *
	 * @param 	$file_hash 		The file's hash
	 * @param 	$mailbox_id 	The mailbox's ID
	 * @return 	$num_messages 	Either return the number of messages in the mailbox or 0 if empty
	 */
	function parse_mbox_data($file_hash, $mailbox_id) {
		try {
			// Path to python and parse_mbox python script
			// CHANGE THIS IF MOVED OFF LOCALHOST!!!
			$parse_py = '/usr/bin/python /Applications/XAMPP/htdocs/arachnid_v1.0/bizDataLayer/parse_mbox.py ./uploads/'.$file_hash;
			$command = escapeshellcmd($parse_py);
			$output = shell_exec($command);
			$json = json_decode($output);

			// CAN BE USED INSTEAD OF shell_exec IF STATUS NEEDED
			// $ouput comes back as array, need to implode to string
			// exec($command, $output, $status);
			// $imploded_output = implode('', $output);
			// $json = json_decode($imploded_output);

			// Store nodes, links, and messages
			$nodes = $json->nodes;
			$links = $json->links;
			$messages = $json->messages;

			$num_messages = count($messages);

			// Serialize all the JSON output to send to the DB
			$nodes_json = serialize(json_encode($nodes));
			$links_json = serialize(json_encode($links));
			$messages_json = serialize(json_encode($messages));

			// DO NOT DELETE
			// USED FOR TESTING BECAUSE SOME STRINGS HAVE ENCODING ISSUES
			// $nodes_json = base64_encode(gzcompress(serialize(json_encode($nodes))));
			// $links_json = base64_encode(gzcompress(serialize(json_encode($links))));
			// $messages_json = base64_encode(gzcompress(serialize($messages)));

			// Send the JSON strings to the JSON table in the DB
			send_json_to_db($nodes_json, $links_json, $messages_json, $mailbox_id);

			// Send every message to the message table
			send_messages_to_db($messages, $num_messages, $mailbox_id);

			// Return the count of messages in the mailbox
			return $num_messages;
		}
		catch(Exception $e) {
			log_error_py($e->getMessage(), 'arachnid_parse_mbox');

			return 0;
		}
	}


	/**
	 * Method used as a helper to keep an up-to-date status of when parsing
	 * has completed.
	 *
	 * @param  	$user_id 		The user's ID
	 * @param  	$file_hash 		The file's hash value
	 * @return		 			The JSON object with number of messages for that user based on that file
	 */
	function start_get_parse_status_data($user_id, $file_hash) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'SELECT num_messages
				FROM arachnid.mailbox
				WHERE file_hash=?
				AND user_user_id=?';

		$vars = array($file_hash, $user_id);
		$types = array('s', 'i');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);
		$num_rows = $db->getAffectedRows();

		if($num_rows === 0) {
			return '[{"success":"false"},{"num_messages":0}]';
		}
		elseif($num_rows === 1) {
			$row = $db->fetch_array();
			$num_messages = $row['num_messages'];

			// Return success with all the online user data
			return '[{"success":"true"},{"num_messages":'.$num_messages.'}]';
		}
	}


	/**
	 * This method is used to store all the JSON strings in the DB in order
	 * to use them in the future for revisiting previous visualizations.
	 *
	 * @param  	$nodes_json 		The JSON string containing all the nodes for the viz
	 * @param  	$links_json 		The JSON string containing all the links for the viz
	 * @param  	$messagess_json 	The JSON string containing all the messages for the viz
	 * @param  	$mailbox_id 		The mailbox ID
	 * @return	bool 				Return false if Exception thrown, otherwise nothing
	 */
	function send_json_to_db($nodes_json, $links_json, $messages_json, $mailbox_id) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = 'INSERT INTO arachnid.json (nodes_json, links_json, messages_json, mailbox_mailbox_id) VALUES (?, ?, ?, ?)';

		// Prepare sql and pass in the serialized JSON strings as 'binary' instead of 'string'
		try {
			if($stmt = $mysqli->prepare($sql)) {				
				$stmt->bind_param('bbbi', $nodes_json, $links_json, $messages_json, $mailbox_id);	// NOTE: use b for binary instead of s for string
				$stmt->send_long_data(0, $nodes_json);	// NOTE: Need to send long data because strings can be quite lengthy
				$stmt->send_long_data(1, $links_json);
				$stmt->send_long_data(2, $messages_json);
				$stmt->execute();
				$stmt->store_result(); 	// NOTE: Must store result for large data objects to work properly
				$stmt->close();
			}
			else {
				throw new Exception('An error occurred with send_json_to_db!');
			}
		}
		catch(Exception $e) {
			log_error($e, $sql, null, 'arachnid_upload');
			return false;
		}
	}


	/**
	 * This method is used to store all the messages in the DB.
	 *
	 * @param  	$messages 		The JSON string containing all the nodes for the viz
	 * @param 	$num_messages 	The number of messages in the mailbox
	 * @param  	$mailbox_id 	The mailbox ID
	 * @return	bool 			Return false if Exception thrown, otherwise nothing
	 */
	function send_messages_to_db($messages, $num_messages, $mailbox_id) {	
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = 'INSERT INTO arachnid.message (sender, receivers, date_sent, subject, content, mailbox_mailbox_id) VALUES (?, ?, ?, ?, ?, ?)';

		// For every message, grab necessary data for the message table
		// then send to the DB
		for($i = 0; $i < $num_messages; $i++) {
			$sender = $messages[$i]->sender;
			$receivers = serialize($messages[$i]->receivers);	# Store in DB as string because it could be an array with multiple email addresses
			$date_sent = $messages[$i]->date_sent;
			$subject = $messages[$i]->subject;
			$content = $messages[$i]->content;

			try {
				if($stmt = $mysqli->prepare($sql)) {
					$stmt->bind_param('sssssi', $sender, $receivers, $date_sent, $subject, $content, $mailbox_id);
					$stmt->execute();
					$stmt->close();
				}
				else {
					throw new Exception('An error occurred with send_messages_to_db!');
				}
			}
			catch(Exception $e) {
				log_error($e, $sql, null, 'arachnid_upload');
				return false;
			}
		}
	}


	/**
	 * This method is used to update the number of messages for the mailbox
	 * in the mailbox table once parsing is complete.
	 *
	 * @param 	$num_messages 	The number of messages in the mailbox
	 * @param  	$mailbox_id 	The mailbox ID
	 * @return	bool 			Return false if Exception thrown, otherwise nothing
	 */
	function update_num_messages_data($num_messages, $mailbox_id) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = 'UPDATE arachnid.mailbox SET num_messages=? WHERE mailbox_id=?';

		try {
			if($stmt = $mysqli->prepare($sql)) {
				$stmt->bind_param('ii', $num_messages, $mailbox_id);
				$stmt->execute();
				$stmt->close();
			}
			else {
				throw new Exception('An error occurred with update_num_messages_data');
			}
		}
		catch(Exception $e) {
			log_error($e, $sql, null, 'arachnid_upload');

			return false;
		}
	}
?>