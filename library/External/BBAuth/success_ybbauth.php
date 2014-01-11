<?php
// success_ybbauth.php -- Test Yahoo! Browser-Based Authentication
//
// Author: Jason Levitt
// Date: September 18th, 2006 
// Version 1.2
//

error_reporting(E_ALL);

// Edit these. Change the values to your Application ID and Secret
define("APPID", 'jpxxxxxxxxxxQzmP0.gah2H8uZjVdsUhM-');
define("SECRET", '0xxxxxxxxxxx70114b20ac420ae6c252');


// Include the proper class file
$v = phpversion();
if ($v[0] == '4') {
	include("ybrowserauth.class.php4");
} elseif ($v[0] == '5') {
	include("ybrowserauth.class.php5");
} else {
	die('Error: could not find the bbauth PHP class file.');
}

function CreateContent() {

	$authObj = new YBrowserAuth(APPID, SECRET);

	// If Yahoo! isn't sending the token, then we aren't coming back from an
	// authentication attempt
	if (empty($_GET["token"])) {
		echo 'You have not authorized access to your Yahoo! Photos account yet<br /><br />';
		echo '<a href="'.$authObj->getAuthURL(null, true).'">Click here to authorize</a>';
		return;
	}

	// Validate the sig
	if ($authObj->validate_sig()) {
		echo '<h2>BBauth authentication Successful</h2>';
		if (isset($authObj->userhash)) {
			echo '<h3>The user hash is: '.$authObj->userhash.'</h3>';
		} else {
			echo '<h3>No user hash was returned (you did not request one?)</h3>';
		}
	} else {
		die('<h1>BBauth authentication Failed</h1> Possible error msg is in $sig_validation_error:<br />'. $authObj->sig_validation_error);
	}

	// Make an authenticated web service call on behalf of the user
	$xml=$authObj->makeAuthWSgetCall('http://photos.yahooapis.com/V1.0/listAlbums?');

	if ($xml == false) {
		die('<h1>WS CALL Failed:</h1> Possible errors are in $sig_validation_error: '. $authObj->sig_validation_error .
		'<br />or in $access_credentials_error: '. $authObj->access_credentials_error);
	}

	echo '<b>Timeout value is:</b> '. $authObj->timeout .'<br />';
	echo '<b>Token value is:</b> '. $authObj->token .'<br />';
	echo '<b>WSSID value is:</b> '. $authObj->WSSID .'<br />';
	echo '<b>Cookie value is:</b> '. $authObj->cookie .'<br /><br /><br />';
	echo '<b>The Web Service call appeared to succeed. The full XML response, which lists
          information about the photo albums in your Yahoo! Photos account, is below:</b><br /><br />';
	echo htmlspecialchars($xml, ENT_QUOTES);
	return;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Your Yahoo! Photos Albums</title>
</head>
<body>
<div>
 <h1>Test BBauth With Yahoo! Photos</h1>
  <div>
  <?php CreateContent(); ?>
  </div>
</div>
</body>
</html>
