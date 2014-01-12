<?


class EasyAppTwitter{

	private $token;	
	private $secret;
	
	public function __construct($token, $secret){
		$this->token = $token;
		$this->secret = $secret;
		$this->cb = new \Codebird\Codebird;
	    $this->cb->setToken($this->token, $this->secret);
	}
	
	
		
	public function fetchSomeTwitterInfo(){
		$reply = $this->cb->account_verifyCredentials();
		return $reply;
	}
}





?>