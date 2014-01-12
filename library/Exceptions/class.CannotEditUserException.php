<?

class CannotEditUserException extends AvalancheException {

	private $CODE = 14;

	public function __construct($message=false, $code=false){
		$message = "Cannot edit user: " . $message;
		parent::__construct($message, $this->CODE);

	}

}


?>