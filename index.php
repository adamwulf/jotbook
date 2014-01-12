<?
include "include.php";
require_once ('codebird-php/src/codebird.php');

$mysql = new MySQLConn(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
$db = new JSONtoMYSQL($mysql);
$app = new EasyApp($db);
\Codebird\Codebird::setConsumerKey(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
// start session for tracking logged in user
session_start();


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

?>
<html>
	<head>
		<script>
		if(typeof(console) == "undefined") console = {};
		if(typeof(console.log) == "undefined") console.log = function(){}
		</script>
<!--		<script type='text/javascript' src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script> -->
		<script src="/includes/jquery.js" type="text/javascript"></script>
		<script src="/includes/bajax.js" type="text/javascript"></script>
		<script src="/model.js" type="text/javascript"></script>
		<script src="/util.js" type="text/javascript"></script>
		<script src="/devview.js" type="text/javascript"></script>
		<script src="/controller.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(function(){
				var model = new $.Model();
				var view = new $.View($("#interface"));
				var controller = new $.Controller(model, view);
			});
		</script>
		<style>
		li span{
			display: block;
			padding:3px;
			height: 14px;
			font-size: 10pt;
		}
		li input{
			width: 90%;
		}
		li{
			background: url(bullet.png) no-repeat 1px 6px;
			padding-left: 10px;
		}
		ul{
			list-style: none;
		}
		#user{
			width:200px;
			float: right;
			border-left: 1px solid black;
			padding:20px;
		}
		</style>
	</head>
<body>
<div id="user">
<?
if($app->isLoggedIn()){
	echo "logged in as " . $app->twitter()->screenname() . "<br>";
	echo "<img src='" . $app->twitter()->avatar() . "'/><br>";
	echo "<a href='" . page_self_url() . "?logout" . "'>Log Out</a><br>";
}else{
	echo "<a href='" . page_self_url() . "?twitter_login" . "'>";
	echo "<img src='" . page_self_url() . "images/sign-in-with-twitter-gray.png' border=0/>";
	echo "</a>";
	echo "<br><br>";
}
?>
</div>
<div id="interface">
</div>
<input type='button' value='stop' id='stopbutton'>
<input type='button' value='refresh' id='refreshbutton'>
</body>
</html>