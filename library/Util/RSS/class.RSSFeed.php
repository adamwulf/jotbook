<?
/**
 * an RSS Feed
 */
 
class RSSFeed{

	protected $items;

	public function __construct(){
		$this->items = array();
	}

	public function addItem(RSSItem $e){
		$this->items[] = $e;
	}

	public function removeItem(RSSItem $e){
		$index = array_search($e, $this->items);
		if(isset($this->items[$index])){
			array_splice($this->items, $index, 1);
			return true;
		}else{
			return false;
		}
	}

	public function getItems(){
		return $this->items;
	}
	
	
	
	/**
	 * visitor pattern
	 */
	public function execute(RSSVisitor $v){
		$v->visit($this);
	}
}
 
?>