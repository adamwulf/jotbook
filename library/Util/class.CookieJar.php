<?
/**
 * handles setting, getting cookies
 */
 
class CookieJar{

	private $prefix;
	
	private $secure;
	
	
	public function __construct($prefix){
		if(!is_string($prefix)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		$this->prefix = $prefix . "_";
		$this->secure = substr(SHOSTURL, 0, 5) == "https";
	}

	/**
	 * set a cookie
	 */
	public function setCookie($name, $value, $remember=false){
		$name = $this->prefix . $name;
		// expire in a year
		$expire = time() + 3600*24*365;
		if(!$remember){
			$expire = 0;
		}
		// set path to root of site, so all of promotro can read it
		$path = "/";
		// we dont' require https, so set secure to 0
		$secure = $this->secure;
		if(!headers_sent()){
			// update global array
			$_COOKIE[$name] = $value;
			// find the domain of the site
			$domain = $_SERVER["HTTP_HOST"];
			if($domain == "localhost"){
				$domain = "";
			}
			// if it's not an ip
			if(!ereg("[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}", $domain)){
				// look for the only '.'
				while(substr_count($domain, ".") > 1){
					$domain = substr($domain, strpos($domain, ".")+1);
				}
			}
			
			/////////////////////////////////////////////////////////////////////////////////////////////
			/////////////////////////////////////////////////////////////////////////////////////////////
			/////////////////////////////  IE fails sometimes trying to set a cookie with "" value //////
			/////////////////////////////////////////////////////////////////////////////////////////////
			/////////////////////////////////////////////////////////////////////////////////////////////
			$ret = setcookie($name , $value, $expire, $path, $domain, $secure);
			return $ret;
		}else{
			return false;
		}
	}

	/**
	 * removes a cookie
	 */
	public function deleteCookie($name){
		$name = $this->prefix . $name;
		//set a cookie to expire two days ago
		if(isset($_COOKIE[$name])){
			unset($_COOKIE[$name]);
			$value = "0";
			$expire = time()-3600*48;
			$path = "/";
			// find the domain of the site
			$domain = $_SERVER["HTTP_HOST"];
			if($domain == "localhost"){
				$domain = "";
			}
			// look for the only '.'
			while(substr_count($domain, ".") > 1){
				$domain = substr($domain, strpos($domain, ".")+1);
			}
			// set secure
			$secure = $this->secure;
			setcookie($name , $value, $expire, $path, $domain, $secure);
		}
	}

	/**
	 * gets the value of the cookie
	 */
	public function getCookie($cook){
		$cook = $this->prefix . $cook;
		if(isset($_COOKIE[$cook])){
			return $_COOKIE[$cook];
		}else if(isset($_REQUEST[$cook])){
			return $_REQUEST[$cook];
		}else{
			return false;
		}
	}
}
?>