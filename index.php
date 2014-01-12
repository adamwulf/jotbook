<?
error_reporting(E_ALL);

include "include.php";
include "functions.php";
include "config.php";
require_once ('codebird-php/src/codebird.php');
$classLoader->addToClasspath(ROOT . "json-to-mysql/");

// start session for tracking logged in user
session_start();

// create global objects

$mysql = new MySQLConn(DATABASE_HOST, DATABASE_NAME, DATABASE_USER, DATABASE_PASS);
$db = new JSONtoMYSQL($mysql);
\Codebird\Codebird::setConsumerKey(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);

$app = new EasyApp($db);







if(isset($_GET["logout"])){
	$app->logout();
    header('Location: ' . page_self_url());
    die();
}else if(isset($_GET['twitter_login'])){
	if (!isset($_SESSION['twitter_oauth_verify'])) {
		$auth_url = $app->twitterLogin(array(
	        'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
	        'state' => 'twitter_login'
	    ));
	    header('Location: ' . $auth_url);
	    die();
	}elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['twitter_oauth_verify'])) {
	
		$app->verifyLogin($_GET['oauth_verifier']);
	
	    // send to same URL, without oauth GET parameters
	    header('Location: ' . page_self_url());
	    die();
	}
}

if($app->isLoggedIn()){
	echo "logged in<br>";
	echo "<a href='" . page_self_url() . "?logout" . "'>Log Out</a><br>";
	
	echo "<br><br>";
	
	print_r($app->twitter()->fetchSomeTwitterInfo());
}else{
	echo "<a href='" . page_self_url() . "?twitter_login" . "'>";
	echo "<img src='" . page_self_url() . "images/sign-in-with-twitter-gray.png' border=0/>";
	echo "</a>";
	echo "<br><br>";
}

?>