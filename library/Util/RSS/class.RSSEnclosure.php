<?
/**
 * an RSS Source
 */
 
class RSSEnclosure{


	protected $url;
	protected $length;
	protected $type;
	
	public function __construct($url, $length, $type){
		if(!is_string($url)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_int($length)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a int");
		}
		if(!is_string($type)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		$this->url = $url;
		$this->length = $length;
		$this->type = $type;
	}
	
	public function getURL(){
		return (string) $this->url;
	}
	
	public function getLength(){
		return (int) $this->length;
	}

	public function getType(){
		return (string) $this->type;
	}
}
?>