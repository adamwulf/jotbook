<?

class IllegalArgumentException extends Exception {

	private $CODE = 3;

	public function __construct($message=false){
		parent::__construct($message, $this->CODE);

	}

}


?>