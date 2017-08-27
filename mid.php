<?php
	/**
	 * Middle layer of SOA architecture. Everything from service layer to bizData
	 * layer goes through here.
	 *
	 * @author Dan Bogaard
	 */
	if(isset($_REQUEST['method'])){
		//include all files for needed area (a)
		foreach (glob("./svcLayer/".$_REQUEST['a']."/*.php") as $filename){
			include $filename;
		}
		$serviceMethod=$_REQUEST['method'];
		$data=$_REQUEST['data'];
		$result=@call_user_func($serviceMethod,$data,$_SERVER['REMOTE_ADDR'],$_COOKIE['token']);
		if($result){
			//might need the header cache stuff
			header("Content-Type:text/plain");
			echo $result;
		}
	}
?>