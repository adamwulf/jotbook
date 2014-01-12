<?

class SkinNotFoundException extends AvalancheException {

	private $CODE = 6;

	public function __construct($message=false, $code=false){
		$message = "Skin \"$message\" could not be loaded";
		parent::__construct($message, $this->CODE);

	}

}


?>