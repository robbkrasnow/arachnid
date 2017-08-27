<?php
	/**
	 * This is a helper class to any mysql database
	 *
	 * @author Bryan French
	 */
	require_once('../../../dbInfo.inc');
	
	class Database {
		// Store the single instance of Database
		private static $m_pInstance; 
		
		private $hostname = ""; //database server
		private $username = ""; //database login name
		private $password = ""; //database login password
		private $database = ""; //database name
		private $pre      = ""; //table prefix
		private $mysqli;  		//mysqli object
		private $error;    		//error
		
		//number of rows affected by SQL query
		private $affected_rows = 0;
		//last insert id
		private $insert_id;
		private $results = array(); //results of query
		private $column_info= array();
		
		
		#-#############################################
		# desc: constructor
		# usage: $db = new Database(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
		#             or $db = new Database(); and will using info from config file
		private function __construct($hostname=DB_HOSTNAME, $username=DB_USERNAME, $password=DB_PASSWORD, $database=DB_DATABASE){
			$this->hostname = $hostname;
			$this->username = $username;
			$this->password = $password;
			$this->database = $database;
			//connect
			$this->mysqli = @new mysqli($hostname, $username, $password, $database);
		
			if (mysqli_connect_errno()) 
			{
				$this->error = "Connect failed: ".mysqli_connect_error();
				echo $this->error;
				die();
			}
			else $this->error = "";
		}#-#constructor()
		
		#-#############################################
		# desc: close the connection
		function close() {
			if(!@mysqli_close($this->mysqli)){
				$this->error="Connection close failed.";
			}
			else $this->error="";
		}#-#close()
		
		#-############################################
		# Desc: return some private attributes
		#  returns last error
		function getError() { return $this->error;}
		#returns the number of rows for select or affected rows for update/delete
		function getAffectedRows() {return $this->affected_rows;}
		#returns last insert ID
		function getInsertId() {return $this->insert_id;}
		
		# ADDED BY ROBB
		function getMySQLi() { return $this->mysqli; }
		
		#-#############################################
		# Desc: executes SQL query to an open connection
		# Param: (MySQL query) to execute: $query is query string with ?'s for variable replacement
		# Param: $vars is an array whose elements are the values for the query, with one element for each ? 
		#              in $query
		# Param: $types is an array whose elements are the types for each value in $vars, one element for 
		#             each element in $vars - Possible values are: "i" for All INT types, "d" for DOUBLE and FLOAT,
		#             "b" for BLOBs andn "s" for All other types
		#
		# Sample usage:
		#		$query = "select * from phonenumbers WHERE PersonID= ? ";
		#      $results = $db->doQuery($query,array($id),array("i"));
		#
		#
		#		$data = array("fax","222-1234","555",1,"fax","222-4321","555");
		#		$db->doQuery("UPDATE  phonenumbers SET PhoneType = ?, PhoneNum= ?, AreaCode = ? Where ".
		#						"PersonID = ? AND PhoneType = ? AND PhoneNum = ? AND AreaCode = ?",
		#						$data,array("s","s","s","i","s","s","s"));		
		#
		# returns: error 
		function doQuery($query,$vars=array(),$types=array())
		{
					//determine which type of query: select, insert, update, delete
					$select = false;
					$delete = false;
					$insert = false;
					$update = false;
					$this->results = null;
					//first get the command and convert to lower case
					$command = strtolower(substr(trim($query),0,strpos($query," ")));
					switch ($command) {
						case "select": $select = true;
								break;
						case "insert": $insert = true;
								break;
						case "update": $update = true;
								break;
						case "delete": $delete = true;
								break;			
					}
					
					$this->results = array();
					$this->error = "";
					
					if (substr_count($query,"?") != count($vars) || count($vars) != count($types))
					{
						$this->error = "Wrong number of parameters for query";
						return $this->error;
					}
					else if ($stmt = @$this->mysqli->prepare($query) )
					{
						if($select)
						{
							//get the column information
							$meta = $stmt->result_metadata();
							$field_cnt = $meta->field_count;
							$col_names = array();
							while ($colinfo=$meta->fetch_field())
							{
								array_push($col_names,$colinfo->name);
							}
						}
						
						//call the bind_param function ?
						if (count($vars)>0) 
						{
							//declare and bind the parameters
							$list = array();
							//create the datatypes and array of values for query and binding params
							$i=0;
							$bindtypes="";
							foreach($types as $type)
							{
								$bindtypes.=$type;
							}
							foreach($vars as $val)
							{
								$bind_name = 'bind' . $i;       //give them an arbitrary name
								$$bind_name = $val;            //add the parameter to the variable variable
								$list[] =&$$bind_name;
								$i++;
							}
							array_unshift($list,$bindtypes);
							//call the function bind_param with dynamic params
							//var_dump("<hr />",$query,$list,"<hr />");
							call_user_func_array(array($stmt,'bind_param'),$list);
						} //bind params?
						if ($select)
						{
							//declare and bind the results
							$res = array_fill(0,$field_cnt,'');
							$bind_res[0] = $stmt;  //make the statement first element 
							//add references to columns array to the parameter list
							for ($i=0; $i<$field_cnt; $i++)
							{
								$bind_res[]=&$res[$i];
							}
		
							//pass the array to the bind results function
						call_user_func_array("mysqli_stmt_bind_result",$bind_res);
						}
						
						//execute the statement
						$good = @$stmt->execute();
			
						$this->column_info = array();
						if ($select) {
								/* get resultset for metadata */
								$metadata = $stmt->result_metadata();
							
								/* retrieve field information from metadata result set */
								$field = $metadata->fetch_fields();
							
								foreach ($field as $val) {
									$this->column_info[$val->name] = array("length"=>$val->length,
											  "type"=>$this->get_type_name($val->type, $val->length, $val->decimals, $val->charsetnr, $val->flags),
											  "flags"=>$val->flags);
								}
						}
										
						@$stmt->store_result();
						//get the affected number of rows
						if ($insert || $update || $delete) {
								$this->affected_rows = @$stmt->affected_rows;
						}
						else {
							$this->affected_rows = @$stmt->num_rows;				
						}
		
						if ($insert)
						{
							$this->insert_id = $stmt->insert_id;
						}
						else
						{
							$this->insert_id = null;	
						}
						
						if ($select)
						{
							/* fetch values and make associative array */
							while ($stmt->fetch()) 
							{
								$row = array();
								for($i=0; $i<$field_cnt; $i++)
								{
									$row[$col_names[$i]] = $res[$i];
								}
								$this->results[] = $row;
							}
						}
						
						if ($good) 
							$this->error="";
						else if (isset($this->mysqli)) {
							$this->error = $this->mysqli->error;
						}
						else
							$this->error="Error with last statement execution *".mysql_error()."*";
							
						/* close statement */
						$stmt->close();
					}//prepare query
					else
					{
						if (isset($this->mysqli))
							$this->error = $this->mysqli->error;
						else
							$this->error="Error with last statement execution <".mysql_error().">";    			
					}
		   
					return $this->error;
				} //doQuery
		
		
		#-#############################################
		# desc: fetches and returns results one line at a time from last query
		# param: $row is the row number to retrieve, defaults to the first row
		# return: (array) fetched record(s)
		function fetch_array($row=0) {
			// retrieve row 
			if (!is_null($this->results) && $row >=0 && $row < count($this->results)) {
				$record = $this->results[$row];
			}else{
				$record = null;
			}
		
			return $record;
		}#-#fetch_array()
		
		#-#############################################
		# desc: returns all the results (not one row) for the last query
		# returns: assoc array of ALL fetched results
		function fetch_all_array() {
			return $this->results;
		}#-#fetch_all_array()
		
		
		#-#############################################
		# desc: frees the resultset from last query
		# 
		function free_result() {
			$this->error = "";
			
			if(!@$this->mysqli->free_result()) {
				$this->error("Results set could not be freed.");
			}
			return $this->error;
		}#-#free_result()
		
		//returns column info for last query, column name is the key, with type and length
		//info. 
		function getColumnInfo() {
			return $this->column_info;
		}
		
		//get column names for a particular table
		function getColNames($tableName) {
				$cols_return = array();
				$cols = $this->mysqli->query("SHOW COLUMNS FROM $tableName");
				if($cols)
				{
					while($col = $cols->fetch_assoc())
					{
						$cols_return[] = $col['Field'];
					}
				}
				else {
					$this->error = $this->mysqli->error;
				}
				return $cols_return;
		  }
			 
		//return column info for a particular table 
		function getColInfo($tableName) {
				$cols_return = array();
				$cols = $this->mysqli->query("SHOW COLUMNS FROM $tableName");
				if($cols)
				{
					while($col = $cols->fetch_assoc())
					{
						$cols_return[$col['Field']] = array("Type"=>$col['Type'],"Null"=>$col['Null'],
												  "Key"=>$col['Key'],"Default"=>$col['Default'],"Extra"=>$col['Extra']);
					}
				}
				else {
					$this->error = $this->mysqli->error;
				}
				return $cols_return;
		  } //getColInfo
		  
		//return number of records info for a particular table 
		function getNumRecords($tableName) {
				$numRecords = 0;
				$res = $this->mysqli->query("SELECT count(*) FROM $tableName");
				if($res)
				{
					$row = $res->fetch_array();
					$numRecords = $row[0];
				}
				else {
					$this->error = $this->mysqli->error;
				}
				return $numRecords;
		  }
		
		
		
		function getPrimaryKey($tableName) {
				$cols_return = array();
				$cols = $this->mysqli->query("SHOW INDEXES FROM $tableName WHERE Key_name = 'PRIMARY'");
		
				if($cols)
				{
					while($col = $cols->fetch_assoc())
					{
							$cols_return[] = $col['Column_name'];
					}
				}
				else {
					$this->error = $this->mysqli->error;
				}
				return $cols_return;
		  }
		
		//return valid table names for the database given a pattern 
		function getValidTableNames($pattern = "") {
			$like = ($pattern == "") ? "" : " like '$pattern' ";
				$tables_return = array();
				$tables = $this->mysqli->query("SHOW TABLES $like");
		
				if($tables)
				{
					while($table = $tables->fetch_array(MYSQLI_NUM))
					{
							$tables_return[] = $table[0];
					}
				}  else {
					$this->error = $this->mysqli->error;
				}
				return $tables_return;
			
		}
		
		private function get_type_name($code, $size, $decimals, $charset, $flags)
		{
			switch ($code) {
				case 1    : return "TINYINT($size)";
				case 2    : return "SMALLINT($size)";
				case 3    : return "INT($size)";
				case 4    : return "FLOAT($size, $decimals)";
				case 5    : return "DOUBLE($size, $decimals)";
				case 6    : return "NULL";
				case 7    : return "TIMESTAMP($size)";
				case 8    : return "BIGINT($size)";
				case 9    : return "MEDIUMINT($size)";
				case 10   : return "DATE";
				case 11   : return "TIME($size)";
				case 12   : return "DATETIME($size)";
				case 13   : return "YEAR($size)";
				case 14   : return "NEWDATE";   // according to http://www.redferni.uklinux.net/mysql/MySQL-Protocol.html
				case 16   : return "BIT($size)";
				case 246  : return "DECIMAL($size, $decimals)";
				case 247  : return "ENUM";      // according to http://www.redferni.uklinux.net/mysql/MySQL-Protocol.html
				case 248  : return "SET";       // according to http://www.redferni.uklinux.net/mysql/MySQL-Protocol.html
				case 252  : if ($charset==63) { // 63 is binary pseudocollation, used for non-string types
											if ($size==255)       return 'TINYBLOB / TINYTEXT BINARY';
											if ($size==65535)     return 'BLOB / TEXT BINARY';
											if ($size==16777215)  return 'MEDIUMBLOB / MEDIUMTEXT BINARY';
											if ($size==-1)        return 'LONGBLOB / LONGTEXT BINARY';
											} else {
											if ($size==255)       return 'TINYTEXT';
											if ($size==65535)     return 'TEXT';
											if ($size==16777215)  return 'MEDIUMTEXT';
											if ($size==-1)        return 'LONGTEXT';
								}
				case 253  : return "VARCHAR($size)";
				case 254  : if ($flags==4481) return "ENUM";                    // is this reliable?
								   elseif ($flags==6273) return "SET";
								   else return "CHAR($size)";
				case 255  : return "GEOMETRY";
				default   : return "?";
			}
		}
		
		//singleton function
		public static function getInstance()
		{
			if (!self::$m_pInstance)
			{
				self::$m_pInstance = new Database();
			}
			
			return self::$m_pInstance;
		} 
		
		
		/*
		 *NEW by Rick
		 *
		 */
		function removeRows($table, $wherePKs = array()){
			$pk = $this->getPrimaryKey($table);
			$questionMarks = '?';
			$types = array('s');
			for($i=0; $i<count($wherePKs)-1; $i++){
			$questionMarks .= ', ?';
			$types[] = 's';
			}
			$query = 'DELETE FROM '. $table .' WHERE '. $pk[0] .' IN('. $questionMarks .')';
			$error = $this->doQuery($query, $wherePKs, $types);
			echo $error;
		}
		
		function addRow($table, $postValues = array()){
			$insertCols = '';
			$questionMarks = '';
			$insertValues = array();
			$colInfo = $this->getColInfo($table);
			$types = array();
			//var_dump($colInfo);
			$cols = $this->getColNames($table);
			unset($cols[0]);
			$cols = array_values($cols);
			$colCount = count($cols);
			
			for($i=0; $i<$colCount; $i++){
			$insertCols .= ', ' . $cols[$i];
			$questionMarks .= ', ?';
			$insertValues[] = $postValues[$cols[$i]];
			$dataType = $colInfo[$cols[$i]]['Type'];
			if(substr_count($dataType, 'int') > 0){
				$types[] = 'i';
			}elseif(substr_count($dataType, 'date') > 0){
				$types[] = 'd';
			}elseif(substr_count($dataType, 'decimal') > 0){
				$types[] = 'i';
			}else{ $types[] = 's';}
			}
			$pk = $this->getPrimaryKey($table);
			$query = 'INSERT INTO '. $table .'('. $pk[0] . $insertCols .') VALUES( NULL'. $questionMarks . ')';
			var_dump($insertValues);
			$error = $this->doQuery($query, $insertValues, $types);
			echo $error;
		}
	}//CLASS Database
	###################################################################################################

?>