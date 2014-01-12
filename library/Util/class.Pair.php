<?

/**
 * represents a simple Html Document
 */
class Pair{

	private $f;
	private $s;

	function __construct($f, $s){
		$this->f = $f;
		$this->s = $s;
	}

	public function getFirst(){
		return $this->f;
	}
	
	public function getSecond(){
		return $this->s;
	}
}

?>