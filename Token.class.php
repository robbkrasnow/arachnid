<?php
	// Include dbInfo.inc because IP_ADDRESS and REQUEST_TIME are stored there for base converting
	require_once('dbInfo.inc');
	
	/**
	 * This class is to be used for all construction and destruction of tokens for the user. It will
	 * take into account their userId, IP address, page request time, and some random characters for
	 * added security.  The goal is to create a long string of characters that are randomly shuffled,
	 * set that as a cookie on the user's machine, then on each page load, deconstruct the token to
	 * see if anything has been tampered with.
	 *
	 * NOTE: THIS CURRENTLY ONLY WORKS WITH IPv4 ADDRESS THAT ARE 10 OR MORE CHARACTERS.
	 *			- IPv4 addresses 10+ characters
	 *			- Localhost IPv6 address (::1)
	 * 
	 * @author Robb Krasnow
	 * @version 2.0
	 */
	class Token {
		// All variables are to be private because of security
		private $alphaNum 			= "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		private $nonAlpha 			= "`~!@#$%^&*()_+-={}[]\:\";'<>?,./";
		private $id 				= "";
		private $userId 			= "";
		private $randomShuffle 		= "";
		private $userIpAddr 		= "";
		private $userIpAddrImp 		= "";
		private $userRequestTime 	= "";
		private $fullToken 			= "";
		private $tokenFromCookie 	= "";
		
		/***********************************
		 *****  CONSTRUCTION OF TOKEN  *****
		 ***********************************/
		
		/**
		 * Sets the user's token for their game session.  It takes into account the users's id,
		 * IP address, and page request time.  
		 */	
		function setToken($id, $userIpAddr, $userRequestTime) {
			$this->id = $id;
			$this->userIpAddr = $userIpAddr;
			$this->userRequestTime = $userRequestTime;
			
			// IP ADDRESS
			// Remove periods/semicolons from IP address so original IP can be compared to un-converted IP
			// Convert IP to base defined in dbInfo.inc and pad with 0s until it's 10 chars long
			if(strpos($this->userIpAddr, ":") !== false) {
				$this->userIpAddrImp = implode(explode(":", $this->userIpAddr));
			}
			elseif(strpos($this->userIpAddr, ".") !== false) {
				$this->userIpAddrImp = implode(explode(".", $this->userIpAddr));
			}
			
			$userIpAddrPad = $this->number_pad(base_convert($this->userIpAddrImp, 10, IP_ADDRESS), 10);

			// REQUEST TIME
			// Convert request time to base defined in dbInfo.inc and pad with 0's until its 10 chars long
			$userRequestTimePad = $this->number_pad(base_convert($this->userRequestTime, 10, REQUEST_TIME), 10);
			
			// USER ID'S & TOKENS
			// Take temporary User ID's and pad them to 10 chars long to match other 10 char strings
			$this->userId = $this->number_pad($this->id, 10);
			
			// Shuffle both strings and take 5 chars from each shuffled string and concatenate them to make
			// a 10 char string to match other 10 char strings
			$this->randomShuffle = str_shuffle(substr(str_shuffle($this->alphaNum), 0, 5).
											   substr(str_shuffle($this->nonAlpha), 0, 5));
			
			// Split all 10 char strings into string arrays to be used with zip function for interlacing
			$ipArray     = str_split($userIpAddrPad);
			$timeArray   = str_split($userRequestTimePad);
			$userIdArray = str_split($this->userId);
			$randomArray = str_split($this->randomShuffle);
			
			// Return string of all zipped arrays (userIP, userRequestTime, userId, and random)
			$zipped = $this->zip($ipArray, $timeArray, $userIdArray, $randomArray);

			// Create actual token by taking a sha of the zipped up string
			$token = sha1($zipped);
			
			// Combine both sha'd string and non-sha'd string
			$this->tokenFull = $token.$zipped;
		}
		
		/**
		 * Accessor to grab the fully generated token after its created.  Used by the bizDataLayer
		 * to set the token in the cookie
		 *
		 * @return $this->tokenFull The fully generated token
		 */
		function getTokenFull() {
			return $this->tokenFull;
		}
		
		/*
		 * Pads a integer into a string based on the number ($num)
		 * 
		 * @param $data The data to be padded
		 * @param $num The amount of padding needed with 0's
		 * @return $padded The padded number in string format
		 * @see http://php.net/manual/en/function.str-pad.php
		 */
		function number_pad($data, $num) {
			$padded = str_pad($data, $num, '0', STR_PAD_LEFT);
			return $padded;
		}
		
		/**
		 * This method is used to interlace arrays by pulling one character from each
		 * array passed in with the next array passed in.
		 *
		 * @param array(s) Any amount of arrays you would like to zip together
		 * @return $result The fully zipped string
		 * @see http://stackoverflow.com/questions/11082461/intersect-2-arrays-in-php
		 *
		 * @example 1a2a3a4a 1b2b3b4b 1c2c3c4c 1d2d3d4d
		 * 			|								  |
		 * 			-----------------------------------
		 * 							 |
		 * 							\/
		 * 			1a1b1c1d 2a2b2c2d 3a3b3c3d 4a4b4c4d
		 * 			
		 * @example 0000 1111 2222 3333
		 * 			|				  |
		 * 			-------------------
		 * 					 |
		 * 					\/
		 * 			0123 0123 0123 0123
		 */
		function zip() {
			// Grab all arrays from the parameters
			$arrays = func_get_args();
			$result = array();
		  
			// Count the length of the arrays to get the length of the longest
			$longest = array_reduce($arrays, function($old, $e) {
				return max($old, count($e));
			}, 0);
		  
			// Traverse the arrays, one element at a time
			for ($i = 0; $i < $longest; $i++) {
				foreach($arrays as $a) {
					if(isset($a[$i])) {
						$result[] = $a[$i];
					}
				}
			}
			
			// Turn string array into a full string (remove if string array needed) and return
			return implode($result);
		}
		
		
		/**********************************
		 *****  DESTRUCTION OF TOKEN  *****
		 **********************************/
		
		/**
		 * Default destructor used to destory a token object after it's made.  Used in the
		 * bizDataLayer primarily for added security in case a hacker can somehow print the
		 * Token object generated.
		 */
		public function __destruct() {
            $this->this = null;
        }
		
		/**
		 * Used to set a new variable for the token set in the cookie (it could have been tampered with...)
		 *
		 * @param $token The user's token from the cookie
		 */
		function setTokenFromCookie($token) {
			$this->tokenFromCookie = $token;
		}
		
		/**
		 * Checks if token is accurate by pulling it apart and comparing hashes and the users
		 * current IP address for a double check against the values pulled apart.
		 *
		 * @return true/false True if everything is valid, otherwise false
		 */
		function checkToken() {
			// Split the token up again to make sure the first 40 chars are the same (TOKEN)
			$splitToken = substr($this->tokenFromCookie, 0, 40);
			
			// Split the token up again to make sure the second 40 chars are the same (ZIPPED)
			$splitZipped = substr($this->tokenFromCookie, 40, 40);
			
			// Unzip the second 40 chars
			$result = $this->unzip($splitZipped);

			// Check if the sha of the zipped portion matches the token.
			// If so, no one has messed with the token
			if(strcmp($splitToken, sha1($splitZipped) == 0)) {
				// Grab all data from unzip
				$ip      = base_convert($result[0], IP_ADDRESS, 10);
				$time    = base_convert($result[1], REQUEST_TIME, 10);
				$user    = $result[2];
				$random  = $result[3];
				$expires = substr($this->tokenFromCookie, 80, strlen($this->tokenFromCookie));

				// Check if token is expired after one hour
				if(time() - $expires <= 3600) {
					if(strpos($_SERVER['REMOTE_ADDR'], ":") !== false) {
						$userIpAddImp = implode(explode(":", $_SERVER['REMOTE_ADDR']));
					}
					elseif(strpos($_SERVER['REMOTE_ADDR'], ".") !== false) {
						$userIpAddImp = implode(explode(".", $_SERVER['REMOTE_ADDR']));
					}
					
					// Check if userIp is the same as when s/he originally logged in
					$validity = (strcmp($ip, $userIpAddImp) == 0) ? true : false;					
				} 
				else {
					$validity = false;
				}
			}
			else {
				$validity = false;
			}
			
			return $validity;
		}
		
		/**
		 * This method is used to de-interlace strings by pulling out the pattern used by the
		 * zip function.
		 *
		 * @param $string The string you want to unzip
		 * @return array The array of strings unzipped
		 * @see http://stackoverflow.com/questions/11082461/intersect-2-arrays-in-php
		 *
		 * @example 1a1b1c1d 2a2b2c2d 3a3b3c3d 4a4b4c4d
		 * 			|								  |
		 * 			-----------------------------------
		 * 							 |
		 * 							\/
		 * 			1a2a3a4a 1b2b3b4b 1c2c3c4c 1d2d3d4d
		 * 			
		 * @example 0123 0123 0123 0123
		 * 			|				  |
		 * 			-------------------
		 * 					 |
		 * 					\/
		 * 			0000 1111 2222 3333
		 */
		function unzip($string) {
			$ip     = '';
			$time   = '';
			$user   = '';
			$random = '';
			$which  = 0;
			$values = array();
			
			// Run through the entire string and every 4th string, reset $which to 0
			// which starts over the loop for every 4th string
			for($i = 0; $i < strlen($string); $i++) {
				// Surpress "Undefined offset" PHP errors with @ symbol
				@$values[$which] .= substr($string, $i, 1);
				$which = ($which == 3) ? 0 : $which + 1;
			}

			// Return an array of all unzipped strings
			return list($ip, $time, $user, $random) = $values;
		}
	}
?>