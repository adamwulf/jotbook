<?
error_reporting(E_ALL);

include "include.php";
include "functions.php";
include "config.php";
require_once ('codebird-php/src/codebird.php');
$classLoader->addToClasspath(ROOT . "json-to-mysql/");

$mysql = new MySQLConn(DATABASE_HOST, DATABASE_NAME, DATABASE_USER, DATABASE_PASS);
$db = new JSONtoMYSQL($mysql);
\Codebird\Codebird::setConsumerKey(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
$cb = \Codebird\Codebird::getInstance();


// start session for tracking logged in user
session_start();

// init a session id if we don't already have one
if(!isset($_SESSION["uuid"])){
	// all our access will be through this uuid
	$_SESSION["uuid"] = gen_uuid();
}



$sessions = $db->table("sessions");
$session_info = $sessions->find(array("session_id" => $_SESSION["uuid"]));

if(isset($_GET["logout"])){
	$sessions = $db->table("sessions");
	$session_info = $sessions->delete(array("session_id" => $_SESSION["uuid"]));
	session_unset();
    header('Location: ' . page_self_url());
    die();
}else if(isset($_GET['twitter_login'])){
	if (!isset($_SESSION['twitter_oauth_verify'])) {
	    // get the request token
	    $reply = $cb->oauth_requestToken(array(
	        'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
	        'state' => 'twitter_login'
	    ));
	
	    // store the token
	    $cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
	    $_SESSION['twitter_oauth_token'] = $reply->oauth_token;
	    $_SESSION['twitter_oauth_token_secret'] = $reply->oauth_token_secret;
	    $_SESSION['twitter_oauth_verify'] = true;
	
	    // redirect to auth website
	    $auth_url = $cb->oauth_authorize();
	    header('Location: ' . $auth_url);
	    die();
	
	}elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['twitter_oauth_verify'])) {
	
	
	    // verify the token
	    $cb->setToken($_SESSION['twitter_oauth_token'], $_SESSION['twitter_oauth_token_secret']);
	    unset($_SESSION['twitter_oauth_verify']);
	
	    // get the access token
	    $reply = $cb->oauth_accessToken(array(
	        'oauth_verifier' => $_GET['oauth_verifier']
	    ));
	
		unset($reply->httpstatus);
		$reply->id = $reply->user_id;
		$ret = $db->save($reply, "twitter_login");
		
		
		$sess = array("session_id" => $_SESSION["uuid"],
					  "twitter_id" => $reply->id);
		$ret = $db->save($sess, "sessions");
		
		if($ret == JSONtoMYSQL::$UPDATE){
			$done = true;
		}
	
	    // send to same URL, without oauth GET parameters
	    header('Location: ' . page_self_url());
	    die();
	}
}
// don't allow any session oauth vars outside of here...
unset($_SESSION['twitter_oauth_token']);
unset($_SESSION['twitter_oauth_token_secret']);
unset($_SESSION['twitter_oauth_verify']);

if($session_info->num_rows()){
	echo "logged in<br>";
	echo "<a href='" . page_self_url() . "?logout" . "'>Log Out</a><br>";
	
	echo "<br><br>";
	
	$session_info = $session_info->fetch_array();
	$twitter_info = $db->table("twitter_login")->find(array("id" => $session_info["twitter_id"]))->fetch_array();
	
	print_r($twitter_info);

	// assign access token on each page load
	$cb->setToken($twitter_info['oauth_token'], $twitter_info['oauth_token_secret']);
	
	$reply = $cb->account_verifyCredentials();
	print_r($reply);

}else{
	echo "<a href='" . page_self_url() . "?twitter_login" . "'>";
	echo "<img src='" . page_self_url() . "images/sign-in-with-twitter-gray.png' border=0/>";
	echo "</a>";
	echo "<br><br>";
}

?>