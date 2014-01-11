<?

class PublishException extends Exception {

	private $CODE = 12;

	public function __construct($message=false){
		parent::__construct($message, $this->CODE);

	}

}


?>