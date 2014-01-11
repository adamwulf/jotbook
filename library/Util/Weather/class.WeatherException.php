<?

class WeatherException extends Exception {

	private $CODE = 24;

	public function __construct($message=false, $code=false){
		$message = "Weather Exception: " . $message;
		parent::__construct($message, $this->CODE);

	}

}


?>