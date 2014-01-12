<?
/**
 * a string class for standard string functions
 */
 
class String{

	private $str;
	
	public function __construct($str){
		if(!is_string($str)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		$this->str = $str;
	}

	
	public function keywords(){
		return preg_split("/[\s,]+/", $this->str);
	}

	public static function striptags($str, $allow){
		$str = strip_tags($str, $allow);
		$str = str_ireplace("onmouseover", "onmouseovr", $str);
		$str = str_ireplace("onmouseout", "onmouseot", $str);
		$str = str_ireplace("onclick", "onclck", $str);
		$str = str_ireplace("ondblclick", "ondblclck", $str);
		$str = str_ireplace("onfocus", "onfocs", $str);
		$str = str_ireplace("onblur", "onblr", $str);
		$str = str_ireplace("onchange", "onchnge", $str);
		$str = str_ireplace("onselect", "onselct", $str);
		$str = str_ireplace("onkeydown", "onkeydwn", $str);
		$str = str_ireplace("onkeyup", "onkyup", $str);
		$str = str_ireplace("onkeypress", "onkeyprss", $str);
		$str = str_ireplace("onsubmit", "onsubmt", $str);
		$str = str_ireplace("onload", "onlode", $str);
		$str = str_ireplace("onunload", "onunlode", $str);
		$str = str_ireplace("onerror", "onerr", $str);
		return $str;
	}
	
	public function toString(){
		return $this->str;
	}
}
?>