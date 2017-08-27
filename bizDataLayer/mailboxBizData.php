<?php
	/**
	 * This script is used for pulling the correct JSON from the
	 * database for a particular user and mailbox ID.
	 *
	 * @author Robb Krasnow
	 * @version 1.0
	 */


	// Required files for the data layer	
	require_once('./bizDataLayer/library.php');
	require_once('./bizDataLayer/exception.php');
	require_once('./classes/Database.class.php');


	/**
	 * This method locats the proper JSON strings that were parsed with 
	 * the upload service, based on the user and mailbox ID.
	 *
	 * @param 	$user_id 		The user's ID
	 * @param 	$mailbox_id 	The mailbox ID the user is requesting
	 */
	function start_mailbox_data($user_id, $mailbox_id) {
		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = "SELECT
					m.file_name,
					m.file_type,
					m.file_size,
					m.num_messages,
					m.date_added,
					m.last_accessed,
					j.nodes_json,
					j.links_json,
					j.messages_json
				FROM arachnid.json j
				INNER JOIN arachnid.mailbox m
				ON j.mailbox_mailbox_id=m.mailbox_id
				WHERE m.mailbox_id=$mailbox_id
				AND m.user_user_id=$user_id";	// Must use double-quotes for this query

		// Prepare the sql and grab JSON output
		try {
			if($stmt = $mysqli->prepare($sql)) {
				$data = returnJson($stmt);
				$json = json_decode($data);		// Because the data is return as JSON, need to decode it to get the strings out

				if(!$json) {
					$stmt->close();
					$db->close();

					throw new Exception('An error occurred while fetching data with start_mailbox_data!');
				}
				else {
					update_last_accessed_data($user_id, $mailbox_id);
					$date_added_format = date('M j, Y, g:i a', strtotime($json[0]->date_added));
					$last_accessed_format = date('M j, Y, g:i a', strtotime($json[0]->last_accessed));

					$file_properties = array(
						'mailbox_id' => $mailbox_id,
						'file_name' => $json[0]->file_name,
						'file_type' => $json[0]->file_type,
						'file_size' => $json[0]->file_size,
						'num_messages' => $json[0]->num_messages,
						'date_added' => $date_added_format,
						'last_accessed' => $last_accessed_format
					);

					// Need to unserialize, then decode again for each section
					$nodes_json = json_decode(unserialize($json[0]->nodes_json));
					$links_json = json_decode(unserialize($json[0]->links_json));
					$messages_json = json_decode(unserialize($json[0]->messages_json));

					// DO NOT DELETE
					// USED FOR TESTING BECAUSE SOME STRINGS HAVE ENCODING ISSUES
					// $nodes_json = json_decode(unserialize(gzuncompress(base64_decode($json[0]->nodes_json))));
					// $links_json = json_decode(unserialize(gzuncompress(base64_decode($json[0]->links_json))));
					// $messages_json = unserialize(gzuncompress(base64_decode($json[0]->messages_json)));

					// Recreate the array needed for D3
					$json_d3 = array(
						'nodes' => $nodes_json,
						'links' => $links_json,
						'messages' => $messages_json
					);

					$stmt->close();
					$db->close();

					return '[{"success":"true"},{"file_properties":'.json_encode($file_properties).'},{"json":'.json_encode($json_d3).'}]';
				}
			}
		}
		catch(Exception $e) {
			log_error($e, $sql, null, 'arachnid_mailbox');
			$error = 'This is not your mailbox!';
			
			return '[{"success":"false"},{"error":"'.$error.'"}]';
		}
	}


	/**
	 * This method is used for updating the last accessed date for a
	 * user's mailbox 
	 *
	 * @param	$user_id 		The user's ID
	 * @param 	$mailbox_id 	The mailbox ID to update
	 * @return	bool 			Return false if Exception thrown, otherwise nothing
	 */
	function update_last_accessed_data($user_id, $mailbox_id) {
		// Set date format
		$date = date('Y-m-d H:i:s');

		// Get a DB instance, set the query, and add the parameters
		$db = Database::getInstance();
		$mysqli = $db->getMySQLi();
		$sql = 'UPDATE arachnid.mailbox SET last_accessed=? WHERE user_user_id=? AND mailbox_id=?';
		
		try {
			if($stmt = $mysqli->prepare($sql)) {
				$stmt->bind_param('sii', $date, $user_id, $mailbox_id);
				$stmt->execute();
				$stmt->close();
			}
			else {
				throw new Exception('An error occurred with update_last_accessed_data!');
			}
		}
		catch(Exception $e) {
			log_error($e, $sql, null);
			return false;
		}
	}
?>