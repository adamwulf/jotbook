<?

class DatabaseException extends Exception {

	private $CODE = 2;

	public function __construct($message=false, $code=false){
		parent::__construct($message, $this->CODE);

	}

}


?>