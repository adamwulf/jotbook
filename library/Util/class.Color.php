<?

/**
 * represents a simple Html Document
 */
class Color{

	private $c;

	// accepts a hex color (with or without #)
	function __construct($c){
		if(!is_string($c)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(strlen($c) == 7 && strpos($c, "#") === 0){
			$c = substr($c, 1);
		}
		if(strlen($c) != 6){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string of length 6. given length: " . strlen($c));
		}
		$this->c = $c;
	}

	public function isDark(){
		$r = substr($this->c, 0, 2);
		$g = substr($this->c, 2, 2);
		$b = substr($this->c, 4, 2);
		$r = $this->hexToInt($r);
		$g = $this->hexToInt($g);
		$b = $this->hexToInt($b);
		if (($r + $g + $b)/3 < 256/2){
			return true;
		}else{
			return false;
		}
	}
	
	public function isLight(){
		return !$this->isDark();
	}
	
	protected function hexToInt($h){
		if(!is_string($h)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(strlen($h) != 2){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string of length 2");
		}
		$sixteens = substr($h, 0, 1);
		$ones = substr($h, 1, 1);
		$decimal = 16 * $this->hexDigitToInt($sixteens) + $this->hexDigitToInt($ones);
		return $decimal;
	}
	
	protected final function hexDigitToInt($d){
		if($d == "F" || $d == "f"){
			return 15;
		}else
		if($d == "E" || $d == "e"){
			return 14;
		}else
		if($d == "D" || $d == "d"){
			return 13;
		}else
		if($d == "C" || $d == "c"){
			return 12;
		}else
		if($d == "B" || $d == "b"){
			return 11;
		}else
		if($d == "A" || $d == "a"){
			return 10;
		}else
		if($d == "9"){
			return 9;
		}else
		if($d == "8"){
			return 8;
		}else
		if($d == "7"){
			return 7;
		}else
		if($d == "6"){
			return 6;
		}else
		if($d == "5"){
			return 5;
		}else
		if($d == "4"){
			return 4;
		}else
		if($d == "3"){
			return 3;
		}else
		if($d == "2"){
			return 2;
		}else
		if($d == "1"){
			return 1;
		}else
		if($d == "0"){
			return 0;
		}else{
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string of 0-9 or A-F");
		}
	}
}

?>