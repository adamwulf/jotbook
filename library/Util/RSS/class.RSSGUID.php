<?
/**
 * an RSS Source
 */
 
class RSSGUID{


	protected $perm;
	protected $url;
	
	public function __construct($url, $perm=true){
		if(!is_string($url)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_bool($perm)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a boolean");
		}
		$this->url = $url;
		$this->perm = $perm;
	}
	
	public function getURL(){
		return (string) $this->url;
	}
	
	public function isPermaLink(){
		return (boolean) $this->perm;
	}



}
?>