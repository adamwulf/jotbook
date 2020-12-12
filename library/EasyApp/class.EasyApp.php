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
	
	// the twitter app object if logged in
	private $twitter;

	function __construct($db){
		
		$this->db = $db;
		$this->cb = new \Codebird\Codebird;
	}
	
	public function isLoggedIn(){
		if(!$this->twitter && isset($_SESSION["twitter_id"])){
			$twitter_info = $this->db->table("twitter_login")->find(array("id" => $_SESSION["twitter_id"]))->fetch_array();
			$this->twitter = new EasyAppTwitter($twitter_info);
		}
		return is_object($this->twitter);
	}
	
	public function logout(){
		$this->twitter = false;
	    session_unset();
	    session_destroy();
	    session_write_close();
	    setcookie(session_name(),'',0,'/');
	    session_regenerate_id(true);
   	}
	
	public function twitter(){
		if($this->isLoggedIn()){
			return $this->twitter;
		}
	}

	public function twitterLogin($callback_params){
	    // get the request token
	    $reply = $this->cb->oauth_requestToken($callback_params);
	    // store the token
	    $this->cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
	    $_SESSION['true_callback'] = $callback_params['true_callback'];
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
		
		$_SESSION["twitter_id"] = $reply->id;
		
		if($ret == JSONtoMYSQL::$UPDATE){
			$done = true;
		}
		
		$true_callback = isset($_SESSION['true_callback']) ? $_SESSION['true_callback'] : page_self_url();
		
		// don't allow any session oauth vars outside of here...
		unset($_SESSION['twitter_oauth_token']);
		unset($_SESSION['twitter_oauth_token_secret']);
		unset($_SESSION['twitter_oauth_verify']);
		unset($_SESSION['true_callback']);
		
		$profile = $this->twitter()->fetchSomeTwitterInfo();
		
		// get the profile image and save it to our db too
		$reply->avatar = $profile->profile_image_url;
		$ret = $this->db->save($reply, "twitter_login");
		
		return $true_callback;
	}
	
	
	public function listExistsHuh($owner_name, $list_id){
		return file_exists(DATA_LOC . "/" . $owner_name . "/" . $list_id . ".data");
	}
	
	
	public function copyList($owner_name, $list_id, $new_owner_name){
		$loc = DATA_LOC . "/" . $owner_name . "/" . $list_id . ".data";
		if(file_exists($loc)){
			$new_list_id = $list_id;
			$new_loc = DATA_LOC . "/" . $new_owner_name . "/" . $list_id . ".data";
			$count = 2;
			while(file_exists($new_loc)){
				$new_list_id = $list_id . "-" . $count;
				$new_loc = DATA_LOC . "/" . $new_owner_name . "/" . $new_list_id . ".data";
				$count ++;
			}
			return $new_list_id;
		}
		return false;
	}

}

?>