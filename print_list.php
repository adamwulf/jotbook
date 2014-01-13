<?
//
//
// a debug script




include "config.php";

$mysql = mysql_connect(DB_HOST, DB_USER , DB_PASSWORD);
mysql_select_db(DB_NAME, $mysql);

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


$thread_id = rand(1000,9999);


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

try{


	//
	// get the JSON input and parse
	$json_in = "";
	if(get_magic_quotes_gpc()){
		$json_in = stripslashes($_REQUEST["data"]);
	}else{
		$json_in = $_REQUEST["data"];
	}
	$data = json_decode($json_in);
	$input = $data;
	// prep the response
	$ret = array();

	$sql = "INSERT INTO listotron2 VALUES ('','" . $thread_id . "','asking for lock','')";
	query($sql);

	////////////////////////////////////////////
	////////////////////////////////////////////
	//
	// get the list data
	// and lock the file
	
	$lockfile = dirname(__FILE__) . "/list.data.lock";
	$fp = fopen($lockfile,'a');
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
	//
	// got the lock, do stuff
	////////////////////////////////////////////
	////////////////////////////////////////////

	$sql = "INSERT INTO listotron2 VALUES ('','" . $thread_id . "','got lock','')";
	query($sql);
	
	$filename = dirname(__FILE__) . "/list.data";
	if(file_exists($filename)){
		$theList = unserialize(file_get_contents($filename));
		echo str_replace(" ", "&nbsp;", str_replace("\n", "<br>", print_r($theList, true)));
		exit;
	}else{
		$theList = array();
			$row = array();
			$row["row_id"] = "1";
			$row["text"] = "one";
			$row["par"] = null;
			$row["prev"] = null;
			$theList[] = $row;

			$row = array();
			$row["row_id"] = "2";
			$row["text"] = "two";
			$row["par"] = "1";
			$row["prev"] = null;
			$theList[] = $row;

			$row = array();
			$row["row_id"] = "3";
			$row["text"] = "three";
			$row["par"] = "1";
			$row["prev"] = "2";
			$theList[] = $row;

			$row = array();
			$row["row_id"] = "4";
			$row["text"] = "four";
			$row["par"] = "1";
			$row["prev"] = "3";
			$theList[] = $row;

			$row = array();
			$row["row_id"] = "5";
			$row["text"] = "five";
			$row["par"] = "1";
			$row["prev"] = "4";
			$theList[] = $row;

			$row = array();
			$row["row_id"] = "6";
			$row["text"] = "six";
			$row["par"] = "1";
			$row["prev"] = "5";
			$theList[] = $row;
		file_put_contents($filename, serialize($theList));
	}
	
	
	function array_copy($arr){
		$ret = array();
		foreach($arr as $var => $val){
			$ret[$var] = $val;
		}
		return $ret;
	}
	
	$nextId = 0;
	for($j=0;$j<count($theList);$j++){
		if($theList[$j]["row_id"] >= $nextId){
			$nextId = $theList[$j]["row_id"] + 1;
		}
	}

	$mctime = microtime();
	$NOW  = gmdate("Y-m-d H:i:s") . substr($mctime, 0, strpos($mctime, " "));
	$THEN = gmdate("Y-m-d H:i:s", time() - 1) . substr($mctime, 0, strpos($mctime, " "));
	$ret = array();
	for($i=0;$i<count($data);$i++){
		if(isset($data[$i]->edit)){
			$par = $data[$i]->par;
			$par = $par == null ? null : (int) $par;
			$prev = $data[$i]->prev;
			$prev = $prev == null ? null : (int) $prev;
			$text = (string) $data[$i]->text;
			$user_id = (string) $data[$i]->user_id;
			$row_id = (int) $data[$i]->row_id;
			$foundit = false;
			for($j=0;$j<count($theList);$j++){
				if($theList[$j]["row_id"] == $row_id){
					$foundit = true;
					$theList[$j]["par"] = $par;
					$theList[$j]["prev"] = $prev;
					$theList[$j]["text"] = $text;
					$theList[$j]["lm"] = $NOW;
					$theList[$j]["lmb"] = $user_id;
					file_put_contents($filename, serialize($theList));
					$ret[] = array_copy($theList[$j]);
					// only send back last modified info
					unset($ret["par"]);
					unset($ret["prev"]);
					unset($ret["text"]);
					$j = count($theList);
				}
			}
			if(!$foundit){
				echo "editing row: " . row_id . "\n";
				print_r($theList);
				print_r($data);
			}
		}else if(isset($data[$i]->login)){
			$out = array();
			$out["error"] = false;
			$out["dt"] = $NOW;
			$out["user_id"] = md5(rand() . md5(rand()));
			$ret[] = $out;
		}else if(isset($data[$i]->load)){
			// request to load all rows
			$out = array();
			$out["error"] = false;
			$out["dt"] = $NOW;
			if(isset($data[$i]->dt)){
				$out["rows"] = array();
				for($j=0;$j<count($theList);$j++){
					$lmb = $theList[$j]["lmb"]; // the user who modified the row
					$lm = $theList[$j]["lm"]; // the last dt the row was modified
					
					if(isset($data[$i]->lmb->$lmb) && $lm > $data[$i]->lmb->$lmb ||
					   !isset($data[$i]->lmb->$lmb) && $lm > $data[$i]->dt){
						$out["rows"][] = $theList[$j];
					}
				}
			}else{
				$out["rows"] = $theList;
			}
			$ret[] = $out;
		}else if(isset($data[$i]->addafter)){
			// request to load all rows
			$out = array();
			$out["error"] = false;
			$out["dt"] = $NOW;
			$out["rows"] = array();
			
			// find the row we're adding after
			for($j=0;$j<count($theList);$j++){
				if($theList[$j]["row_id"] == $data[$i]->row_id){
					// we're adding after this row
					$row = array();
					$row["row_id"] = (string) $nextId;
					$row["text"] = "";
					$row["par"] = $theList[$j]["par"];
					$row["prev"] = $theList[$j]["row_id"];
					$out[] = $row;
					array_splice($theList, $j+1, 0, array($row));
				}
			}
			for($j=0;$j<count($theList);$j++){
				if($theList[$j]["prev"] == $data[$i]->row_id){
					$theList[$j]["prev"] = (string) $nextId;
					$out[] = $theList[$j];
				}
			}
			file_put_contents($filename, serialize($theList));
			$nextId++;
			$ret[] = $out;
			
			print_r($theList);
			exit;
		}else{
			// the client asked for something we don't support
			$err = array();
			$err["error"] = true;
			$err["message"] = print_r($_REQUEST);
			$ret[] = $err;
		}
	}
	
	echo json_encode($ret);

	$sql = "INSERT INTO listotron2 VALUES ('','" . $thread_id . "','" . addslashes(print_r($input, true)) . "','" . addslashes(print_r($ret, true)) . "')";
	query($sql);


	////////////////////////////////////////////
	////////////////////////////////////////////
	//
	// release the lock
	flock($fp, LOCK_UN); 
	fclose($fp); 
	//
	////////////////////////////////////////////
	////////////////////////////////////////////

	exit;

}catch(Exception $e){
	// something bad happened
	$ret = array();
	$ret["error"] = true;
	$ret["message"] = $e->getMessage();
	echo json_encode($ret);
}

?>