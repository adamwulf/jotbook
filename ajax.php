<?

include "include.php";
require_once ('codebird-php/src/codebird.php');

$mysql = new MySQLConn(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
$db = new JSONtoMYSQL($mysql);
$app = new EasyApp($db);
\Codebird\Codebird::setConsumerKey(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
// start session for tracking logged in user
session_start();

//$mysql = mysql_connect(DB_HOST, DB_USER , DB_PASSWORD);
//mysql_select_db(DB_NAME, $mysql);

try{

	$my_name = "list";
	if($app->isLoggedIn()){
		$my_name = $app->twitter()->screenname();
	}
	$list_id = readFormValue("list_id", $_REQUEST);
	$owner_name = readFormValue("owner_name", $_REQUEST);
	

	// generate filenames
	$filename = DATA_LOC . "/" . $owner_name . "/" . $list_id . ".data";
	$lock_filename = DATA_LOC . "/" . $owner_name . "/" . $list_id . ".data.lock";
	
	// check permissions
	// only create lists in the anonymous space
	// or in your username, though can access anywhere
	if(!($owner_name == "list" || $owner_name == $my_name)){
		if(!$app->listExistsHuh($owner_name, $list_id)){
			header('HTTP/1.0 401 Unauthorized');
			exit;
		}
	}

	//
	// at this point, the list either exists already
	// or we're authorized to create it
	
	//
	// get the JSON input and parse
	$json_in = "";
	if(get_magic_quotes_gpc()){
		$json_in = stripslashes($_REQUEST["data"]);
	}else{
		$json_in = $_REQUEST["data"];
	}
	$data = json_decode($json_in);

	////////////////////////////////////////////
	////////////////////////////////////////////
	//
	// get the list data
	// and lock the file
	
	getLock($lock_filename);	
	
	//
	// got the lock, do stuff
	////////////////////////////////////////////
	////////////////////////////////////////////

	$list = new Listotron($filename);
	
	$error_file = "error." . time() . ".log";
	
	$ok_before = $list->isValidHuh();
	$txt_before = $list->getHumanReadable();
	
	if(!$ok_before){
		releaseLock();
		echo "not ok!";
		exit;
	}
	
	
	$control = new Controller($list, $app->twitter());
	$ret = $control->process($data);

	$ok_after = $list->isValidHuh();
	$txt_after = $list->getHumanReadable();
	
	if(!$ok_after){
		file_put_contents(dirname(__FILE__) . "/error/" . $error_file, "Angry after:" . 
											  "\n\nState Before:\n" . $txt_before . 
											  "\n\nState After:\n" . $txt_after . 
											  "\n\nRequest[data]:\n" . var_export($data, true) . 
											  "\n\nResponse:\n" . var_export($ret, true));
	}else if(count($ret) == 0){
		file_put_contents(dirname(__FILE__) . "/error/" . $error_file, "not returning any responses for input:" . 
											  "\n\nState Before:\n" . $txt_before . 
											  "\n\nState After:\n" . $txt_after . 
											  "\n\nRequest[data]:\n" . var_export($data, true) . 
											  "\n\nResponse:\n" . var_export($ret, true));
	}else{
		foreach($ret as $r){
			if(count($r) == 0){
				file_put_contents(dirname(__FILE__) . "/error/" . $error_file, "returning empty response:" . 
											  "\n\nState Before:\n" . $txt_before . 
											  "\n\nState After:\n" . $txt_after . 
											  "\n\nRequest:\n" . var_export($_REQUEST, true) . 
											  "\n\nResponse:\n" . var_export($ret, true));
			}
		}
	}

	$ret = json_encode($ret);
	
	echo $ret;


	////////////////////////////////////////////
	////////////////////////////////////////////
	//
	// release the lock
	
	releaseLock();

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