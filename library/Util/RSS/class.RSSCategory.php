<?
/**
 * an RSS Source
 */
 
class RSSCategory{


	protected $title;
	protected $domain;
	
	public function __construct($title, $domain=""){
		if(!is_string($title)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_string($domain)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		$this->title = $title;
		$this->domain = $domain;
	}
	
	public function getTitle(){
		return (string) $this->title;
	}
	
	public function getDomain(){
		return (string) $this->domain;
	}



}
?>