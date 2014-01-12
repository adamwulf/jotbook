<?php
// ybrowserauth.class.php4 -- a PHP4 class for using Yahoo's browser
// authentication service
//
// Author: Jason Levitt
// Date: September 18th, 2006 Version 0.98c
// Credits: Based on code by Allen Tom with additions by Dan Theurer and Zack Steinkamp
// Requirements: PHP4. The Curl extension is required.
//
// Constructor usage:
// $yourappid = 'asdafasdfadfadfa';
// $yoursecret = 'asd1234125dfasdfasdfas';
// $authObj = new YBrowserAuth($yourappid, $yoursecret);
//
// Create an authentication link:
// <a href="< ? php echo $authObj->getAuthURL(null,true); ? >">Authorize Access To My Photos</a>
//
// After the user authenticates successfully, Yahoo returns the user to the page you
// dictated when you signed up. To verify whether authentication succeeded, you need to
// validate the signature:
// if ($authObj->validate_sig()) {
//        echo 'Authentication Successful';
//	} else {
//		  echo "Authentication Failed. Error is: $authObj->sig_validation_error";
//	}
//
// The user hash will be in $authObj->userhash after validation succeeds if you
// passed hash=true in the getAuthURL call.
//
// Create an authenticated web service URL for calling a web service. Do this if you want to
// make the web service call yourself. Otherwise, use makeAuthWSgetCall below.
//  $url = 'http://photos.yahooapis.com/V1.0/listAlbums?';
// 	$url = $this->createAuthWSurl($url);
//	if ($url === false) {
//		echo 'Failed to create authenticated url. Error is: $authObj->access_credentials_error';
//	} else {
//      echo 'Use this url, and send along the $this->cookie value in an HTTP cookie header,
//            in order to make an authenticated WS call: '. $url;
// }
//
// Make an authenticated HTTP GET web service call
// $url = 'http://photos.yahooapis.com/V1.0/listAlbums?';
// $xml=$authObj->makeAuthWSgetCall($url);
// if ($xml == false) {
//      echo 'WS call setup Failed. Error is: '. $authObj->access_credentials_error;
// } else {
//      echo 'Look at response for other errors or success: '.$xml;
// }
//
// For short-term authentication, you can store the cookie value, the appid, the
// WSSID value, and the timeout value somewhere and keep doing authenticated web
// service calls until the cookie timeout value is reached (typically 1 hour) like this:
// $authObj->cookie = $storedcookie;
// $authObj->WSSID = $storedWSSID;
// $authObj->appid = $storedappid;
// $url = 'http://photos.yahooapis.com/V1.0/listAlbums?';
// $xml=$authObj->makeAuthWSgetCall($url);
// if ($xml == false) {
//      echo 'WS call setup Failed. Error is: '.$authObj->access_credentials_error;
// } else {
//      echo 'Look at response for other errors or success: '.$xml;
// }
//
// For long-term authentication, you can store the token and the timestamp ts
// value (from $_GET["ts"]) somewhere and make authenticated calls (until ts runs
// out -- typically 14 days) this way:
// $authObj->token = $storedtoken;
// $url = 'http://photos.yahooapis.com/V1.0/listAlbums?';
// $xml=$authObj->makeAuthWSgetCall($url);
// if ($xml == false) {
//      echo 'WS call setup Failed. Error is: '.$authObj->access_credentials_error;
// } else {
//      echo 'Look at response for other errors or success: '.$xml;
// }
//

error_reporting(E_ALL);

DEFINE ('WSLOGIN_PREFIX', 'https://api.login.yahoo.com/WSLogin/V1/');

class YBrowserAuth {

	var $appid;
	var $secret;
	var $timeout;
	var $token;
	var $WSSID;
	var $cookie;
	var $userhash;
	var $sig_validation_error;
	var $access_credentials_error;

	/**
	 * Constructor function. Instantiates the application id and shared secret
	 * used to authorize access.
	 * @param string $yourappid
	 * @param string $yoursecret
	 */
	function YBrowserAuth($yourappid, $yoursecret) {
		$this->appid = $yourappid;
		$this->secret = $yoursecret;
	}

	/**
	 * Create the Login URL used to fetch authentication credentials. This is the 
	 * first step in the browser authentication process.
	 *
	 * @param string $appd Optional data string, typically a session id,
	 * that Yahoo will transfer to the target application upon successful authentication
     * @param boolean $hash Optional flag. If set, the send_userhash=1 request will be 
	 * appended to the request URL so that the userhash will be returned by Yahoo! after
     * successful authentication.
	 * @return string A full URL that initiates browser-based authentication.
	 */
	function getAuthURL($appd=null, $hash=false) {
		// Add optional appdata parameter, if requested
		$appdata = (empty($appd)) ? null : '&appdata=' . urlencode($appd);
		$hashdata = ($hash) ? '&send_userhash=1' : null;
		return $this->createAuthURL(WSLOGIN_PREFIX . "wslogin?appid=" . $this->appid . $appdata . $hashdata);
	}

	/**
	 * Takes a REST-style web service URL and adds the necessary parameters
	 * to turn it into an authenticated REST-style web service URL for use
	 * with Yahoo browser-based authentication.
	 *
	 * @param string $url A valid URL for a web service call minus the authentication
	 * credentials.
	 * @return false | string Returns the full URL you can use to make an authenticated
	 * web service call. Returns false on error and the error should 
	 * be in $this->access_credentials_error
	 */
	function createAuthWSurl($url) {
		// If we already have the authentication cookie, don't bother getting the
		// credentials again. If you want to force getting credentials again, you
		// can unset($this->cookie) before calling this.
		if (!isset($this->cookie)) {
			if (!$this->getAccessCredentials()) {
				return false;
			}
		}
		// Security concern -- make sure there is a question mark in the URL
		// If there's no question mark, add one.
		$url=trim($url);
		if (stristr($url,'?') == false) {
			$url .= '?';
		}

		return $url."&WSSID=$this->WSSID&appid=$this->appid";
	}

	/**
	 * Validates the signature returned by Yahoo's browser authentication
	 * services
	 *
	 * @param string $ts Optional timestamp that typically will normally be retrieved
	 * from the global $_GET array after authentication succeeds.
	 * @param string $sig Optional sig that typically will normally be retrieved
	 * from the global $_GET array after authentication succeeds.
	 * @return true | false Returns true if the sig is validated. Returns false if
	 * any error occurs. If false is returned, $this->sig_validation_error should
	 * contain a string describing the error.
	 */
	function validate_sig($ts=null, $sig=null) {
		// There might be a reason you'd want to pass in the timestamp and
		// signature yourself, but typically not.
		$ts = (empty($ts)) ? $_GET["ts"] : $ts;
		$sig = (empty($sig)) ? $_GET["sig"] : $sig;
		$this->userhash =  isset($_GET["userhash"]) ? $_GET["userhash"] : null;

		// Fetch the Request URI from the environment
		$relative_url = getenv('REQUEST_URI');
		if ($relative_url === false ) {
			$this->sig_validation_error = "Failed getting REQUEST_URI from the environment";
			return false;
		}

		// Parse the signature out of the REQUEST_URI
		$match = array();
		$preg_rv = preg_match("@^(.+)&sig=(\\w{32})$@", $relative_url, $match);

		// Only one sig should be found. If it's found, it should match the sig
		// sent by Yahoo!
		if ($preg_rv == 1) {
			if ($match[2] != $sig) {
				$this->sig_validation_error = "Invalid sig may have been passed: " . $match[2] . ", $sig ";
				return false;
			}
		} else {
			$this->sig_validation_error = "Invalid url may have been passed - relative_url: $relative_url ";
			return false;
		}

		// At this point, the url looks valid, and we pulled the sig from the url.
		// The sig was guaranteed to be the last param on the url.
		$relative_url_without_sig = $match[1];

		// Make sure your server time is within 10 minutes (600 seconds) of Yahoo's servers
		$current_time = time();
		$clock_skew  = abs($current_time - $ts);
		if ($clock_skew >= 600) {
			$this->sig_validation_error = "Invalid timestamp - clock_skew is $clock_skew seconds, current time is $current_time, ts is $ts";
			return false;
		}

		// Use the PHP md5 function to caculate the sig using your shared secret, and
		// then compare that sig to the one passed by Yahoo.
		$sig_input = $relative_url_without_sig . $this->secret;
		$calculated_sig = md5($sig_input);
		if ($calculated_sig == $sig) {
			return true;
		} else {
			$this->sig_validation_error = "calculated_sig was $calculated_sig, supplied sig was $sig, sig input was $sig_input";
			return false;
		}
	}

	/**
	 * Make an authenticated web services call using HTTP GET
	 *
	 * @param string $url The web services call minus the authentication credentials
	 * @return string | false If successful, a string is returned containing the web
	 * service response which might be XML, JSON, or some other type of text. If a curl
	 * error occurs, the error is stored in $this->access_credentials_error. Note that
     * access to the HTTP status code (for further error checking) is not provided
     * in this method.
	 */
	function makeAuthWSgetCall($url) {

		// Add the authentication credentials to the web service call
		$url = $this->createAuthWSurl($url);
		if ($url === false) {
			return false;
		}

		// Do an HTTP GET using Curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: $this->cookie"));
		$xml = curl_exec($ch);
		if (curl_errno ($ch)) {
			$this->access_credentials_error = "Curl error number:".curl_errno($ch);
			return false;
		}
		curl_close( $ch );

		return $xml;
	}

	/**
	 * Make an authenticated web services call using HTTP POST
	 *
	 * @param string $url The web services call minus the authentication credentials
	 * @return string | false If successful, a string is returned containing the web
	 * service response which might be XML, JSON, or some other type of text. If a curl
	 * error occurs, the error is stored in $this->access_credentials_error. Note that
     * access to the HTTP status code (for further error checking) is not provided
     * in this method.
	 */
	function makeAuthWSpostCall($url) {

		$url = $this->createAuthWSurl($url);
		if ($url === false) {
			return false;
		}

		$parts = parse_url($url);

		$prefix = $parts["scheme"]."://".$parts["host"].$parts["path"];

		$ch = curl_init($prefix);
		curl_setopt ($ch, CURLOPT_POST, true);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $parts["query"]);
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array("Cookie: $this->cookie"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$xml = curl_exec( $ch );
		if (curl_errno( $ch )) {
			$this->access_credentials_error = "Curl error number:".curl_errno($ch);
			return false;
		}
		curl_close( $ch );

		return $xml;
	}

	/**
	 * This method is used by getAccessCredentials to fetch all the
	 * values necessary to make an authenticated web service call
	 *
	 * @param string $yourtoken Typically, you would not pass this in. It's sent by Yahoo
	 * as part of the response from successful user authentication
	 * @return string Returns a full URL that is used to retrieve all the authentication 
	 * values
	 */
	function getAccessURL() {
		// If the app is making a call by manually setting $this->token, we want to check for that
		$this->token = (isset($this->token)) ? $this->token : $_GET["token"];
		return $this->createAuthURL(WSLOGIN_PREFIX . "wspwtoken_login?token=$this->token&appid=$this->appid");
	}

	/**
	 * A utility function used to build authenticated URLs
	 *
	 * @param string $url
	 * @return string The URL with authentication credentials added to it
	 */
	function createAuthURL($url) {
		// Take apart the URL
		$parts = parse_url($url);
		// Get the current time
		$ts = time();
		// Re-build the path and query with the timestamp added
		$relative_uri = "";
		$relative_uri .= $parts["path"];
		$relative_uri .= "?" . $parts["query"] . "&ts=$ts";
		// Generate the sig
		$sig = md5($relative_uri . $this->secret);
		// Build the signed URL
		$signed_url = $parts["scheme"] . "://" .  $parts["host"] . $relative_uri . "&sig=$sig";
		return $signed_url;
	}

	/**
	 * Fetches all the authentication values for use in making authenticated
	 * web service calls
	 *
	 * @return true | false Returns true if all the authentication values are 
	 * successfully fetched. Returns false if anything else happens and the error
	 * is in $this->access_credentials_error
	 */
	function getAccessCredentials() {
		// Get the wspwtoken_login URL
		$url = $this->getAccessURL();

		// Do an HTTP GET to get the values
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$xml = curl_exec( $ch );

		if (curl_errno($ch)) {
			$this->access_credentials_error = "Curl error number:".curl_errno($ch);
			return false;
		}
		curl_close($ch);

		// Check in the returned XML for an error
		$match_array = array();
		if (preg_match("@<ErrorCode>(.+)</ErrorCode>@", $xml, $match_array) == 1) {
			$this->access_credentials_error = "Error code returned in XML response: $match_array[1]";
			return false;
		}

		// Get the cookie
		if (preg_match("@(Y=.*)@", $xml, $match_array) == 1) {
			$this->cookie = $match_array[1];
		} else {
			$this->access_credentials_error = "No cookie found";
			return false;
		}

		// Get the WSSID - Web Services Session ID. Used to avoid replay attacks
		$match_array = array();
		if (preg_match("@<WSSID>(.+)</WSSID>@", $xml, $match_array) == 1) {
			$this->WSSID = $match_array[1];
		} else {
			$this->access_credentials_error = "No WSSID found";
			return false;
		}

		// Get the timeout value. This is the length of time, in seconds, that
		// The cookie value is valid. Usually 3600 seconds (1 hour).
		$match_array = array();
		if (preg_match( "@<Timeout>(.+)</Timeout>@", $xml, $match_array) == 1) {
			$this->timeout = $match_array[1];
		} else {
			$this->access_credentials_error = "No timeout found";
			return false;
		}

		return true;
	}

}
?>