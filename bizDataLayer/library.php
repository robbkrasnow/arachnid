<?
	/**
	 * This file is intended to be a library of data layer methods that may
	 * be used in multiple other service or data layer methods. To get to this
	 * file, you must go through the service layer as well, while sanitizing
	 * and filtering all data.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the data layer
	require_once('./bizDataLayer/exception.php');
	require_once('./classes/Database.class.php');


	/********************* 
	 *****  HELPERS  *****
	 *********************/ 


	/**
	 * Method used to return a user's ID based on their username.
	 *
	 * @param 	$username 		The username of the player whose ID you need
	 * @return 	$user_id/bool  	The ID of the user you need or false if no result
	 */
	function get_user_id_from_username($username) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'SELECT user_id FROM arachnid.user WHERE username=?';
		$vars = array($username);
		$types = array('s');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		// If there were any affected rows, grab data
		if($num_rows == 1) {
			$row = $db->fetch_array();
			$user_id = $row['user_id'];
			
			// Return the user's ID
			return $user_id;
		}
		
		return false;
	}


	/**
	 * Method to return the username of the user based on their user_id
	 *
	 * @param 	$id 		The ID of the user you need the username of
	 * @return 	$username 	The username of the user you need from the user ID
	 */
	function get_username_from_user_id($user_id) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'SELECT username FROM arachnid.user WHERE user_id=?';
		$vars = array($id);
		$types = array('i');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		// If there were any affected rows, grab data
		if($num_rows == 1) {
			$row = $db->fetch_array();
			$username = $row['username'];
			
			// Return the username of the player
			return $username;
		}
		
		return false;
	}


	/**
	 * Method to update the room. Similar to updateRoomNum, except instead of updating
	 * both the challenger and challenged room numbers at once, this one will only update
	 * one user's room at a time based on their user id
	 *
	 * @param 	$user_id  	The user's ID
	 * @param 	$room_num 	The room number you want to update with
	 */
	function update_room_num($user_id, $room_num) {
		// Get a DB instance, set the query, and add the parameters
		$db	= Database::getInstance();
		$sql = 'UPDATE arachnid.user SET room_num=? WHERE user_id=?';
		$vars = array($room_num, $user_id);
		$types = array('i', 's');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		if($num_rows == 1) {
			return '[{"success":"true"}]';
		}
	}


	/**
	 * This method is used to grab the number of mailboxes based on 
	 * a particular user ID. 
	 *
	 * @param 	$user_id  				The user's ID
	 * @return 	$num_mailboxes/bool 	The user's number of mailboxes or false if no mailboxes
	 */
	function get_num_mailboxes_by_user_id($user_id) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'SELECT num_mailboxes FROM arachnid.user WHERE user_id=?';
		$vars = array($user_id);
		$types = array('i');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();

		// If there were any affected rows, grab data
		if($num_rows == 1) {
			$row = $db->fetch_array();
			$num_mailboxes = $row['num_mailboxes'];
			
			// Return the number of mailboxes of the user
			return $num_mailboxes;
		}
		
		$db->close();
		return false;
	}


	/**
	 * This method is used to get a mailbox id based on a user's ID
	 * and a specific file hash.
	 *
	 * @param 	$user_id  			The user's ID
	 * @param 	$file_hash 			The file's hash
	 * @return 	$mailbox_id/bool 	The mailbox ID or false if mailbox doesn't exist
	 */
	function get_mailbox_id_by_user_id_and_file_hash($user_id, $file_hash) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'SELECT mailbox_id FROM arachnid.mailbox WHERE user_user_id=? AND file_hash=?';
		$vars = array($user_id, $file_hash);
		$types = array('i', 's');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		// If there were any affected rows, grab data
		if($num_rows == 1) {
			$row = $db->fetch_array();
			$mailbox_id = $row['mailbox_id'];
			
			// Return the number of mailboxes of the user
			return $mailbox_id;
		}
		
		return false;
	}


	/**
	 * This method is used to get a mailbox's date it was added to
	 * the db based on the mailbox ID.
	 *
	 * @param 	$mailbox_id			The user's ID
	 * @return 	$date_added/bool 	The date the mailbox was added or false if no date
	 */
	function get_mailbox_date_added($mailbox_id) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'SELECT date_added FROM arachnid.mailbox WHERE mailbox_id=?';
		$vars = array($mailbox_id);
		$types = array('i');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		// If there were any affected rows, grab data
		if($num_rows == 1) {
			$row = $db->fetch_array();
			$date_added = $row['date_added'];
			
			// Return the number of mailboxes of the user
			return $date_added;
		}
		
		return false;
	}


	/*********************** 
	 *****  UTILITIES  *****
	 ***********************/ 


	/**
	 * Method used to sanitize and filter all data. Strip all tags and filter
	 * it to help prevent attacks.
	 *
	 * @param $val 		The value to be sanitized and filtered
	 * @return $result  The sanitized and filtered string
	 */
	function sanitizeAndFilter($val) {
		$result = (string)$val;
		$result = trim($result);
		$result = strip_tags($result);
		$result = htmlentities($result, ENT_QUOTES, "UTF-8");
		$result = stripslashes($result);
		$result = filter_var($result, FILTER_SANITIZE_STRING);
		
		return $result;
	}


	/**
	 * Method used to convert data into a JSON object.
	 *
	 * @author 			Dan Bogaard
	 * @param 	$stmt 	A prepared sql statement
	 * @return 			A JSON encoded multi-dimensional, associative array
	 */
	function returnJson($stmt) {
		$stmt->execute();
		$stmt->store_result();
		$meta = $stmt->result_metadata();
		$bindVarsArray = array();
		//using the stmt, get it's metadata (so we can get the name of the name=val pair for the associate array)!
		while ($column = $meta->fetch_field()) {
			$bindVarsArray[] = &$results[$column->name];
		}
		//bind it!
		call_user_func_array(array($stmt, 'bind_result'), $bindVarsArray);
		//now, go through each row returned,
		while($stmt->fetch()) {
			$clone = array();
			foreach ($results as $k => $v) {
				$clone[$k] = $v;
			}
			$data[] = $clone;
		}
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		//MUST change the content-type
		header("Content-Type:text/plain");
		// This will become the response value for the XMLHttpRequest object
		return json_encode($data);
	}


	/**
	 * UNUSED
	 *
	 * Method to UTF8 encode an entire object and all subobjects
	 *
	 * @param 	$input 	The object to encode
	 * @see 			http://php.net/manual/en/function.utf8-encode.php#109965
	 * @see 			https://gist.github.com/oscar-broman/3653399
	 */
	/*
	function utf8_encode_deep(&$input) {
		if (is_string($input)) {
			$input = utf8_encode($input);
		}
		else if (is_array($input)) {
			foreach ($input as &$value) {
				utf8_encode_deep($value);
			}

			unset($value);
		}
		else if (is_object($input)) {
			$vars = array_keys(get_object_vars($input));

			foreach ($vars as $var) {
				utf8_encode_deep($input->$var);
			}
		}
	}
	*/
?>