<?

class QueryTableException extends Exception {

	private $CODE = 12;

	public function __construct($message=false, $code=false){
		parent::__construct($message, $this->CODE);

	}

}


?>