<?
/**
 * an RSS Source
 */
 
class RSSSource{


	protected $name;
	protected $url;
	
	public function __construct($name, $url=""){
		if(!is_string($name)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_string($url)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		$this->name = $name;
		$this->url = $url;
	}
	
	public function getName(){
		return (string) $this->name;
	}
	
	public function getURL(){
		return (string) $this->url;
	}



}
?>