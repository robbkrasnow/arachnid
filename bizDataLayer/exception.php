<?php
	/**
	 * Used for logging errors from the server to dataerror.log.
	 * Used for debugging purposes.
	 *
	 * @author 		Dan Bogaard
	 * @author 		Robb Krasnow
	 * @version 	2.0
	 */


	/**
	 * Method used to log errors from the DB.
	 *
	 * @param 	$e 			The error from the DB
	 * @param 	$sqlst 		The sql statement used
	 * @param 	$params 	The parameters used
	 * @param 	$log_name 	The name of the log file to write to
	 */
	function log_error($e, $sqlst, $params, $log_name){
		$myFile = "./bizDataLayer/logs/".$log_name.".log";
		$fh = fopen($myFile, "a+") or die("can't open file");
		
		try {
			fwrite($fh, "Exception caught @".date("H:i:s m.d.y")."\n"); 
			fwrite($fh, "    Message: ".$e->getMessage()."\n");
			fwrite($fh, "    SQL: ".$sqlst."\n");
			
			if (is_array($params)) {
				fwrite($fh, "    Params: ".implode(",",$params)."\n");
			}

			fwrite($fh, "    File: ".$e->getFile()."\n");
			fwrite($fh, "    Line: ".$e->getLine()."\n");
			fwrite($fh, "    Trace: ".$e->getTraceAsString()."\n");
			fwrite($fh, "===========================================\n");
			fclose($fh);
		}
		catch (Exception $e) {
			echo "error";
		}
	}


	/**
	 * Method used to log Python errors from the DB.
	 *
	 * @param 	$e 			The error from the DB
	 * @param 	$log_name 	The name of the log file to write to
	 */
	function log_error_py($e, $log_name) {
		$myFile = "./bizDataLayer/logs/".$log_name.".log";
		$fh = fopen($myFile, "a+") or die("can't open file");
		
		try {
			fwrite($fh, "Exception caught @".date("H:i:s m.d.y")."\n"); 
			fwrite($fh, "    Message: ".$e->getMessage()."\n");
			fwrite($fh, "    File: ".$e->getFile()."\n");
			fwrite($fh, "    Line: ".$e->getLine()."\n");
			fwrite($fh, "    Trace: ".$e->getTraceAsString()."\n");
			fwrite($fh, "===========================================\n");
			fclose($fh);
		}
		catch (Exception $e) {
			echo "error";
		}
	}


	/**
	 * Method used to log Python dumps.
	 *
	 * @param 	$var		The dump
	 * @param 	$log_name 	The name of the log file to write to
	 */
	function log_error_py_dump($var, $log_name) {
		$myFile = "./bizDataLayer/logs/".$log_name.".log";
		$fh = fopen($myFile, "a+") or die("can't open file");
		
		try {
			fwrite($fh, "Dump @".date("H:i:s m.d.y")."\n");
			fwrite($fh, $var);
			fwrite($fh, "===========================================\n");
			fclose($fh);
		}
		catch (Exception $e) {
			echo "error";
		}
	}
?>