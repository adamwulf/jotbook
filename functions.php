<?

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function page_self_url(){
	$url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	if(strpos($url, "?") !== false){
		$url = substr($url, 0, strpos($url, "?"));
	}
	if(strrpos($url, "/") != strlen($url)-1){
		$url .= "/";
	}
	return $url;
}

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



function getLock($lockfile){
	global $fp;

	$dir = dirname($lockfile);
	if(!is_dir($dir)){
		mkdir($dir);
	}

	if(!file_exists($lockfile)){
		file_put_contents($lockfile, "");
	}
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