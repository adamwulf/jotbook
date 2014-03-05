<?

include "include.php";
require_once ('codebird-php/src/codebird.php');

$mysql = new MySQLConn(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD);
$db = new JSONtoMYSQL($mysql);
$app = new EasyApp($db);
\Codebird\Codebird::setConsumerKey(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
// start session for tracking logged in user
session_start();


//
// create list in our account if we're logged in,
// otherwise as an anonymous list
if(isset($_GET["create_list"])){

	$list_id = $_GET["create_list"];
	
	$list_id = str_replace(" ", "-", $list_id);
	
	$list_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $list_id);
	
	if(!$list_id){
		header("Location: /");
		exit;
	}
	
	if($app->isLoggedIn()){
		header("Location: http://jotbook.net/" . $app->twitter()->screenname() . "/" . $list_id . "/");
	}else{
		header("Location: http://jotbook.net/list/" . $list_id . "/");
	}
	exit;
}



$list_id = readFormValue("list_id", $_REQUEST);
$owner_name = readFormValue("owner_name", $_REQUEST);

if(!strlen($owner_name)){
	// default owner of lists is just public "list"
	$owner_name = "list";
}


$my_lists = array();
if($app->isLoggedIn()){
	$list_table = $db->table("accessed_lists");
	$list_table->delete(array("twitter_id" => $app->twitter()->userId(), "list_id" => $list_id, "owner_name" => $owner_name));
	if($list_id){
		$db->save(array("twitter_id" => $app->twitter()->userId(), "list_id" => $list_id, "owner_name" => $owner_name, "stamp" => time()), "accessed_lists");
	}

	if(isset($_GET["forget"])){
		$list_id = readFormValue("list_id", $_REQUEST);
		$list_table = $db->table("accessed_lists");
		$list_table->delete(array("twitter_id" => $app->twitter()->userId(), "list_id" => $list_id, "owner_name" => $owner_name));
	}

	$list_table = $db->table("accessed_lists");
	$result = $list_table->find(array("twitter_id" => $app->twitter()->userId()));
	$rows = array();
	while($row = $result->fetch_array()){
		$stamp[]  = $row['list_id'] . "/" . $row['owner_name'];
    	$rows[] = $row;
	}
	
	array_multisort($stamp, SORT_ASC, $rows);
	
	$my_lists = $rows;
}



if($app->isLoggedIn() && isset($_GET["forget"])){
	if(count($my_lists)){
	    header('Location: ' . '/' . $my_lists[0]["owner_name"] . '/' . $my_lists[0]["list_id"] . '/');
	}else{
	    header('Location: ' . '/');
	}
	die();
}else if(isset($_GET["logout"])){
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
		<script src="/includes/model.js" type="text/javascript"></script>
		<script src="/includes/util.js" type="text/javascript"></script>
		<script src="/includes/devview.js" type="text/javascript"></script>
		<script src="/includes/controller.js" type="text/javascript"></script>
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
			background: url(/includes/bullet.png) no-repeat 1px 6px;
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
			min-height: 200px;
		}
		#interface{
			margin-right: 240px;
		}
		#users div img{
			width:20px;
			height:20px;
			float:left;
			margin-right:10px;
		}
		#users div{
			line-height:20px;
			height:20px;
			margin-bottom:2px;
		}
		#user_box{
			display: none;
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
	echo "<br><br>";
	
	
	echo "<div id='user_box'>";
	echo "<b>People</b><br>";
	echo "<div id='users'></div>";
	echo "<br>";
	echo "</div>";

	echo "<b>My Lists</b><br>";
	for($i=0;$i<count($my_lists);$i++){
		if($my_lists[$i]["list_id"] != ""){
			if($list_id == $my_lists[$i]["list_id"] && $owner_name == $my_lists[$i]["owner_name"]){
				echo $list_id . "<br>";
			}else{
				echo "<a href='/" . $my_lists[$i]["owner_name"] . "/" . urlencode($my_lists[$i]["list_id"]) . "'>" . $my_lists[$i]["list_id"] . "</a><br>";
			}
		}
	}
	
	echo "<br><br>";
	echo "<a href='?forget'>remove list from my lists</a><br>";
	echo "(list won't be deleted)<br>";

}else{
	echo "<a href='" . page_self_url() . "?twitter_login" . "'>";
	echo "<img src='" . page_self_url() . "images/sign-in-with-twitter-gray.png' border=0/>";
	echo "</a>";
	echo "<br>(optional)<br><br>";


	echo "<div id='user_box'>";
	echo "<b>Active People</b><br>";
	echo "<div id='users'></div>";
	echo "<br>";
	echo "</div>";
}
	
if($list_id){
	echo "<br><br>";
	$url = "http://jotbook.net/list/" . $list_id;
	echo "Access this list anytime at <a href='$url'>$url</a>.";
	echo "<br><br>";
	echo "Share this URL with others to edit this list together!";
	echo "<br><br>";
	echo "Make New List:<br>";
	echo "<form action=/ method=GET>";
	echo "<input name=create_list type=text style='width:120px'><input type='submit' value='Go' style='width:40px;'/>";
	echo "</form>";
}
?>
</div>

<?
if(!$list_id){
?>
	<div style='padding:100px;margin-right:240px;'>
	<b>JotBook.net</b> lets you easily create and edit lists together with others.
	Each list is given a permanent URL. Share that URL with others to collaborate on your list!
	<br><br>
	<form action="/">
	http://jotbook.net/list/<input type=text name=create_list size=30 placeholder="your list name"/>
	<input type=submit value=Go>
	</form>
	
	<br><br><br><br><br><br><br><br>
	JotBook is open source - find it at <a href='https://github.com/adamwulf/jotbook'>https://github.com/adamwulf/jotbook</a>.
	<br><br>
	Jotbook was created by <a href='https://twitter.com/adamwulf'>Adam Wulf</a> and <a href='https://twitter.com/buckwilson'>Buck Wilson</a> many years ago
	as a prototype for realtime list collaboration. What you see here is the first exploratory phase of building out the service, which we later
	abandoned. As unrefined as it is, I've found it useful for quick note taking, so I've opened it up for wider use. Feel free to write some notes
	and lists here, or download the source below to run on your own server or tinker with. Have fun!<br>- Adam<br><br>
	</div>
<?	
}else{
?>

<div id="interface">
</div>
<?
if($list_id){
?>
<br><br><br><br>
<div style='font-size:10pt'>
(Click a row to edit. Up/Down arrows to move between rows. Tab and Shift+Tab to indent, unindent. Enter to add new rows. Esc to finish editing.)
<br><br>
Contact <a href='https://twitter.com/adamwulf'>@adamwulf</a> with questions or comments about JotBook.net.
</div>
<?
}
?>
<!--
<input type='button' value='stop' id='stopbutton'>
<input type='button' value='refresh' id='refreshbutton'>
-->
<?
}
?>

<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-180672-20', 'jotbook.net');
  ga('send', 'pageview');

</script>
</body>
</html>