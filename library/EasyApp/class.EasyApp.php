<?

/**
 * The barebones application, using twitter oauth for
 * user management
 */
class EasyApp{

	// json to mysql
	private $db;
	
	// codebird twitter
	private $cb;
	
	// session info
	private $session_info;
	
	// the twitter app object if logged in
	private $twitter;

	function __construct($db){
		$this->db = $db;
		$this->cb = new \Codebird\Codebird;
		$this->initSession();
	}
	
	private function initSession(){
		// init a session id if we don't already have one
		if(!isset($_SESSION["uuid"])){
			// all our access will be through this uuid
			$_SESSION["uuid"] = gen_uuid();
		}
	}
	
	private function sessionId(){
		// init session if needed
		$this->initSession();
		// return id
		return $_SESSION["uuid"];
	}
	
	private function sessionInfo(){
		if(!$this->session_info){
			$sessions = $this->db->table("sessions");
			$this->session_info = $sessions->find(array("session_id" => $this->sessionId()))->fetch_array();
		}
		return $this->session_info;
	}
	
	public function isLoggedIn(){
		if(!$this->twitter && is_array($this->sessionInfo())){
			$session_info = $this->sessionInfo();
			$twitter_info = $this->db->table("twitter_login")->find(array("id" => $session_info["twitter_id"]))->fetch_array();
			$this->twitter = new EasyAppTwitter($twitter_info['oauth_token'], $twitter_info['oauth_token_secret']);
		}
		return is_object($this->twitter);
	}
	
	public function logout(){
		$sessions = $this->db->table("sessions");
		$sessions->delete(array("session_id" => $_SESSION["uuid"]));
		$this->session_info = false;
		$this->twitter = false;
		session_unset();
	}
	
	public function twitter(){
		$this->isLoggedIn(); // check login and setup twitter if need-be
		return $this->twitter;
	}

	public function twitterLogin($callback_params){
	    // get the request token
	    $reply = $this->cb->oauth_requestToken($callback_params);
	    // store the token
	    $this->cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
	    $_SESSION['twitter_oauth_token'] = $reply->oauth_token;
	    $_SESSION['twitter_oauth_token_secret'] = $reply->oauth_token_secret;
	    $_SESSION['twitter_oauth_verify'] = true;
	    // redirect to auth website
	    return $this->cb->oauth_authorize();
	}
	
	public function verifyLogin($oauth_verifier){
	    // verify the token
	    $this->cb->setToken($_SESSION['twitter_oauth_token'], $_SESSION['twitter_oauth_token_secret']);
	    unset($_SESSION['twitter_oauth_verify']);
	
	    // get the access token
	    $reply = $this->cb->oauth_accessToken(array(
	        'oauth_verifier' => $oauth_verifier
	    ));
	
		unset($reply->httpstatus);
		$reply->id = $reply->user_id;
		$ret = $this->db->save($reply, "twitter_login");
		
		$sess = array("session_id" => $_SESSION["uuid"],
					  "twitter_id" => $reply->id);
		$ret = $this->db->save($sess, "sessions");
		
		if($ret == JSONtoMYSQL::$UPDATE){
			$done = true;
		}
		// don't allow any session oauth vars outside of here...
		unset($_SESSION['twitter_oauth_token']);
		unset($_SESSION['twitter_oauth_token_secret']);
		unset($_SESSION['twitter_oauth_verify']);
		
		
		$profile = $this->twitter()->fetchSomeTwitterInfo();
		
		// get the profile image and save it to our db too
		$reply->avatar = $profile->profile_image_url;
		$ret = $this->db->save($reply, "twitter_login");
	}
	
	

}

?>