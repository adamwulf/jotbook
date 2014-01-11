<?
/**
 * an Visitor to visit RSSFeeds and RSSItems
 */
 
abstract class RSSVisitor{


	public function __construct(){
	}


	public function visit($r){
		if(!($r instanceof RSSFeed) &&
		   !($r instanceof RSSItem)){
		   	throw new IllegalArgumentException("argument to " . __METHOD__ . " must be either an RSSFeed or an RSSItem");
		}
		
		$this->defaultCase($r);
	}

	
	abstract protected function defaultCase($r);

}
?>