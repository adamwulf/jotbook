<?

// throw when the script wants to header("Location")
class RedirectException extends Exception {

	private $CODE = 3;

	public function __construct($url=false, $code=false){
		parent::__construct($url, $this->CODE);

	}

}


?>