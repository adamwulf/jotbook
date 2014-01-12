<?

class CannotAddUserException extends AvalancheException {

	private $CODE = 14;

	public function __construct($message=false, $code=false){
		$message = "Cannot add user: " . $message;
		parent::__construct($message, $this->CODE);

	}

}


?>