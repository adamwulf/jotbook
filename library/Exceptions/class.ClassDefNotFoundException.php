<?

class ClassDefNotFoundException extends Exception {

	private $CODE = 1;

	public function __construct($message=false, $code=false){
		$message = "Class $message could not be found";
		parent::__construct($message, $this->CODE);

	}

}


?>