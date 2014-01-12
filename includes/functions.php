<?
// run a query on mysql,
// and throw an exception if there's
// a problem
function query($sql){
	global $mysql;
	$result = mysql_query($sql, $mysql);
	if(mysql_error($mysql)){
		throw new Exception("mysql error");
	}
	return $result;
}


function readFormValue($var, $data){
	if(isset($data[$var])){
		$val = $data[$var];
		if(is_array($val) && get_magic_quotes_gpc()){
			$arr = $val;
			$ret = array();
			foreach($arr as $var => $val){
				$ret[$var] = stripslashes($val);
			}
			$val = $ret;
		}else if(get_magic_quotes_gpc()){
			$val = stripslashes($val);
		}
		return $val;
	}else{
		return false;
	}
}



function getLock(){
	global $fp;

	$lockfile = dirname(__FILE__) . "/../list.data.lock";
	$fp = @fopen($lockfile,'a');
	$retries = 0; 
	$max_retries = 100; 

	if (!$fp) { 
		throw new Exception("cannot lock file");
	} 

	// keep trying to get a lock as long as possible 
	while(!flock($fp, LOCK_EX) and $retries <= $max_retries){ 
		if($retries > 0) { 
			usleep(rand(1, 10000)); 
		} 
		$retries ++;
	}
	// couldn't get the lock, give up 
	if ($retries == $max_retries) { 
		throw new Exception("cannot lock file");
	}

}

function releaseLock(){
	global $fp;
	flock($fp, LOCK_UN); 
	fclose($fp); 
}




	function array_copy($arr){
		$ret = array();
		foreach($arr as $var => $val){
			$ret[$var] = $val;
		}
		return $ret;
	}
	
function getmicrotime(){ 
    return microtime(true); 
//    return ((float)$usec + (float)$sec); 
}

?>