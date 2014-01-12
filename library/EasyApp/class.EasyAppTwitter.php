<?


class EasyAppTwitter{

	private $user_id;
	private $token;	
	private $secret;
	private $screenname;
	private $avatar;
	
	public function __construct($twitter_info){
		$this->user_id = $twitter_info['id'];
		$this->token = $twitter_info['oauth_token'];
		$this->secret = $twitter_info['oauth_token_secret'];
		$this->screenname = $twitter_info['screen_name'];
		if(isset($twitter_info['avatar'])){
			$this->avatar = $twitter_info['avatar'];
		}
		$this->cb = new \Codebird\Codebird;
	    $this->cb->setToken($this->token, $this->secret);
	}
	
	
	public function screenname(){
		return $this->screenname;
	}
	
	public function avatar(){
		return $this->avatar;
	}
	
	public function userId(){
		return $this->user_id;
	}
		
	public function fetchSomeTwitterInfo(){
		$reply = $this->cb->account_verifyCredentials();
		return $reply;
	}
}





?>