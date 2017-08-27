<?php
	/**
	 * This script is the bizData layer for the dashboard service.
	 * It pulls the required information to display the file's metadata.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the data layer	
	require_once('./bizDataLayer/library.php');
	require_once('./bizDataLayer/exception.php');
	require_once('./classes/Database.class.php');


	/**
	 * This method starts the dashboard service's interaction with
	 * the database. It pulls the file's metadata based on the user's
	 * ID, creates a JSON object and sends it back to the service.
	 *
	 * @param 	$user_id 	The user's ID
	 * @return 				The JSON object with a success or failure
	 */
	function start_dashboard_data($user_id) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = "SELECT
					mailbox_id, 
					file_name,
					file_type,
					file_size,
					num_messages,
					date_added,
					last_accessed
				FROM arachnid.mailbox
				WHERE user_user_id=$user_id";	// Must use double-quotes for this query

		// Prepare sql and decode the return
		try {
			if($stmt = $mysqli->prepare($sql)) {
				$data = returnJson($stmt);
				$json = json_decode($data);

				// Close db connection and throw an error if nothing comes back
				if(!$data) {
					$stmt->close();
					$db->close();

					throw new Exception('An error occurred while fetching data with start_dashboard_data!');
				}
				elseif($data === 'null') {
					$error = 'Click Upload to add a mailbox';
					return '[{"success":"false"},{"error":"'.$error.'"}]';
				}
				else {
					$mailboxes = array();
					$json_count = count($json);

					for($i = 0; $i < $json_count; $i++) {
						$date_added_format = date('M j, Y, g:i a', strtotime($json[$i]->date_added));
						$last_accessed_format = date('M j, Y, g:i a', strtotime($json[$i]->last_accessed));

						$mailbox = array(
							'mailbox_id' => $json[$i]->mailbox_id,
							'file_name' => $json[$i]->file_name,
							'file_type' => $json[$i]->file_type,
							'file_size' => $json[$i]->file_size,
							'num_messages' => $json[$i]->num_messages,
							'date_added' => $date_added_format,
							'last_accessed' => $last_accessed_format
						);
						$mailboxes[] = $mailbox;
					}

					$stmt->close();
					$db->close();

					return '[{"success":"true"},{"mailboxes":'.json_encode($mailboxes).'}]';
				}
			}
		}
		catch(Exception $e) {
			log_error($e, $sql, null, 'arachnid_dashboard');
			$error = 'This is not your dashboard!';

			return '[{"success":"false"},{"error":"'.$error.'"}]';
		}		
	}
?>