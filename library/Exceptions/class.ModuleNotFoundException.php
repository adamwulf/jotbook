<?

class ModuleNotFoundException extends AvalancheException {

	private $CODE = 4;

	public function __construct($message=false, $code=false){
		$message = "Module \"$message\" could not be loaded";
		parent::__construct($message, $this->CODE);

	}

}


?>