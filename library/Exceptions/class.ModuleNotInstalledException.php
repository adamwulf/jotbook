<?

class ModuleNotInstalledException extends AvalancheException {

	private $CODE = 5;

	public function __construct($message=false, $code=false){
		$message = "Module \"$message\" is not currently installed";
		parent::__construct($message, $this->CODE);

	}

}


?>