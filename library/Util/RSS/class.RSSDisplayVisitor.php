<?
/**
 * an Visitor to visit RSSFeeds and RSSItems
 */
 
class RSSDisplayVisitor extends RSSVisitor{


	public function __construct(){
		parent::__construct();
	}


	public function visit($r){
		if(!($r instanceof RSSFeed) &&
		   !($r instanceof RSSItem)){
		   	throw new IllegalArgumentException("argument to " . __METHOD__ . " must be either an RSSFeed or an RSSItem");
		}
		
		if($r instanceof RSSItem){
			return $this->itemCase($r);
		}else{
			return parent::__visit($r);
		}
	}


	protected function itemCase($r){
		// check validity
		if(!strlen($r->getTitle()) &&
		   !strlen($r->getDescription())){
			throw new IllegalArgumentException("RSSItem must have either a title or a description");
		}

		// build output
		$out = "<item>\n";
		if(strlen($r->getTitle())){
			$out .= "<title>" . htmlspecialchars($r->getTitle()) . "</title>\n";
		}
		if(strlen($r->getLink())){
			$out .= "<link>" . $r->getLink() . "</link>\n";
		}
		if(strlen($r->getDescription())){
			$out .= "<description>" . htmlspecialchars($r->getDescription()) . "</description>\n";
		}
		if(strlen($r->getAuthor())){
			$out .= "<author>" . $r->getAuthor() . "</author>\n";
		}
		if(count($r->getCategories())){
			$cats = $r->getCategories();
			foreach($cats as $c){
				if(!($c instanceof RSSCategory)){
					throw new IllegalArgumentException("RSSCategory expected");
				}
				$domain = "";
				if(strlen($c->getDomain())){
					$domain = "domain='" . $c->getDomain() . "'";
				}
				$out .= "<category$domain>" . $c->getTitle() . "</category>\n";
			}
		}
		if(is_object($r->getEnclosure())){
			$enc = $r->getEnclosure();
			if(!($source instanceof RSSEnclosure)){
				throw new IllegalArgumentException("RSSCategory expected");
			}
			$out .= "<enclosure url='" . $enc->getURL() . "' length='" . $enc->getLength() . "' type='" . $enc->getType() . "' />\n";
		}
		if(is_object($r->getGUID())){
			$guid = $r->getGUID();
			if(!($source instanceof RSSGUID)){
				throw new IllegalArgumentException("RSSCategory expected");
			}
			if($guid->isPermaLink()){
				$perm = "true";
			}else{
				$perm = "false";
			}
			$out .= "<guid isPermaLink='" . $perm . "'>" . $guid->getURL() . "</guid>\n";
		}
		if(strlen($r->getPubDate())){
			$out .= "<pubDate>" . $r->getPubDate() . "</pubDate>\n";
		}
		if(is_object($r->getSource())){
			$source = $r->getSource();
			if(!($source instanceof RSSSource)){
				throw new IllegalArgumentException("RSSCategory expected");
			}
			if(strlen($source->getURL())){
				$url = " url='" . $source->getURL() . "'";
			}else{
				$url = "";
			}
			$name = $soure->getName();
			$out .= "<source$url>" . $name . "</source>";
		}
	}

	
	protected function defaultCase($r){
		if(!($r instanceof RSSFeed)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a RSSFeed");
		}
		return "<feed>";
	}

}
?>