<?php
	/**
	 * This file is intended to be used as the data layer for any logins
	 * into the system.
	 *
	 * if we have gotten here - we know:
	 * 		-they have permissions to be here
	 * 		-we are ready to do something with the database
	 * 		-method calling these are in the svcLayer
	 * 		-method calling specific method has same name droping 'Data' at end
	 * 		checkTurnData() here is called by checkTurn() in svcLayer
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the data layer
	require_once('./bizDataLayer/library.php');
	require_once('./bizDataLayer/exception.php');
	require_once('./classes/Database.class.php');
	require_once('../../../Token.class.php');
	
	// Start session data to store username and userId
	session_start();
	

	/**
	 * Method used to grab user from the DB and must match the username and hashed
	 * password coming in.
	 *
	 * @param 	$username 	The user's login username
	 * @param 	$password 	The user's login password
	 */
	function start_login_data($username, $password) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$sql = 'SELECT * FROM arachnid.user WHERE username=? AND password=? AND room_num=?';
		$vars = array($username, $password, -1);
		$types = array('s', 's', 'i');
		
		// Run the query
		$db->doQuery($sql, $vars, $types);  	  
		$num_rows = $db->getAffectedRows();
		
		// If there were any affected rows, grab data
		if($num_rows == 1) {
			$row = $db->fetch_array();
			$user_id = $row['user_id'];
			$room_num = $row['room_num'];
			$num_mailboxes = $row['num_mailboxes'];
			
			// Params used for seeting the cookie
			$expire   = time() + 3600; // 1 hour from now
			$path = '/';
			$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
			$secure = false;
			$http_only = false;
			
			// Set the session for the username and the user_id
			$_SESSION['username'] = $username;
			$_SESSION['user_id'] = $user_id;
			
			// Create a new token and set it with the user's ID, IP address, and request time
			$tokenObj = new Token();
			$tokenObj->setToken($user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_TIME']);
			$token = $tokenObj->getTokenFull();
			
			// Set the token in the cookie
			setcookie('token', $token.$expire, $expire, $path, $domain, $secure, $http_only);
			
			// Return success with the row of the user
			return '[{"success":"true"},{"username":"'.$username.'"}]';
		}
		else {
			$error = 'Invalid username and/or password. Please try again.';
		}
		
		// Close DB connection and return false with the error if sql call fails
		$db->close();
		return '[{"success":"false"},{"error":"'.$error.'"}]';
	}
?>