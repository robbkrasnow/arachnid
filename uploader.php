<?php
	/**
	 * This script is meant to validate an uploaded file against the following conditions:
	 * 	- POST Request Method
	 * 	- Size of post at 4 GB
	 * 	- Empty $_FILES array
	 *	- Any PHP upload errors
	 * 	- Exceeding file size limitations
	 *	- If file already exists on the server
	 * 	- MIME Type of file
	 *
	 *	If all validation passes, file is moved to Arachnid's upload directory
	 * 
	 * @see http://php.net/manual/en/features.file-upload.php
	 */
	try {
		// Set default empty array so php errors don't show in HTML
		$result = json_encode(array());

		// Check if $_SERVER values are set properly. If so, move on to validate $_FILES.
		if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$content_length = $_SERVER['CONTENT_LENGTH'];
			$post_max_size_bytes = return_bytes(ini_get('post_max_size'));
			$post_max_size = $post_max_size_bytes / 1048576;	// use 1073741824 for GB

			// Check if $_FILES and $_POST are empty, and content length is not 0 or 
			// larger than the POST's max size to verify there is a file being uploaded
			if(empty($_FILES['file']) && empty($_POST) && 
				$content_length > 0 && $content_length > $post_max_size_bytes) {
				throw new RuntimeException("Exceeded file size limit of {$post_max_size} MB", 1);
			}

			// Make sure the is actually a file in the $_FILES superglobal
			if(!empty($_FILES['file'])) {
				// Grab some metadata from the file in order to validate
				$file_name = basename($_FILES['file']['name']);
				$file_size = $_FILES['file']['size'];
				$temp_name = $_FILES['file']['tmp_name'];
				$error = $_FILES['file']['error'];

				// If there are any errors, someone potentially messed with the POST. Display error.
				if(!isset($error) || is_array($error)) {
					throw new RuntimeException("Invalid parameters", 2);
				}

				// Check if any PHP errors occur against PHP known error codes
				switch($error) {
					case UPLOAD_ERR_OK:
						break;
					case UPLOAD_ERR_NO_FILE:
						throw new RuntimeException("No file selected", 3);
					case UPLOAD_ERR_INI_SIZE:
						throw new RuntimeException("Exceeded file size limit", 4);
					case UPLOAD_ERR_FORM_SIZE:
						throw new RuntimeException("Exceeded file size limit", 5);
					default:
						throw new RuntimeException("Unknown error", 6);
				}

				// Hash the temp name of the file
				$sha1_file_name = sha1_file($temp_name);

				// Check if the file already exists on the server
				if(file_exists("./uploads/" . $sha1_file_name)) {
					throw new RuntimeException("File already exists on the server", 7);
				}

				// Validate the file size in bytes is less than 4 GB
				// Use 134217728 (bytes) for 128 MB
				if($file_size > 4294967296) {
					throw new RuntimeException("Exceeded file size limit", 8);
				}

				// Set a list of allowed MIME types for file uploads
				$allowed_mimes = array(
					'text/plain',
					'text/html',
					'text/x-mailbox'
				);

				// Validate file's MIME type
				$file_info = new finfo(FILEINFO_MIME_TYPE);
				$file_type = $file_info->file($temp_name);

				// Compare file's MIME type to MIME types allowed above
				if(false === $mime = array_search($file_type, $allowed_mimes, true)) { 
					throw new RuntimeException("Invalid file format or MIME type", 9);
				}

				// Move uploaded file to the uploads directory in Arachnid's root
				if(!move_uploaded_file(
					$temp_name,
					sprintf('./uploads/%s', $sha1_file_name)
				)) {
					throw new RuntimeException("Failed to move uploaded file", 10);
				}

				// Return a json object with the file's metadata to be used for database insertion
				$result = json_encode(array(
					'success' => array(
						'file_hash' => $sha1_file_name,
						'file_name' => $file_name,
						'file_type' => $file_type,
						'file_size' => $file_size
					),
				));

				// Print result back to callback method from AJAX call
				echo $result;
			}
		}
	}
	catch(RuntimeException $e) {
		// If any exceptions occurred, create json object to be used for database insertion
		$result = json_encode(array(
			'error' => array(
				'msg' => $e->getMessage(),
				'code' => $e->getCode()
			),
		));

		// Print result back to AJAX callback
		echo $result;
	}


	/**
	 * Convert string to bytes
	 *
	 * @see http://php.net/manual/en/function.ini-get.php
	 */
	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}	
?>