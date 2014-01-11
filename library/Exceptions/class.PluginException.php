<?

class PluginException extends Exception {

	private $CODE = 13;

	public function __construct($message=false){
		parent::__construct($message, $this->CODE);

	}

}


?>