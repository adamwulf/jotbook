<?
class Sorter {
   //the array we want to sort.
   private $data;

   //the order in which we want the array to be sorted.
   // "DESC" or "ASC"
   private $aSortkey;

   // the comparator to use
   private $comp;

   
   // sort the input data with the comparator
   public function sort($data, Comparator $comp){
	return $this->sortASC($data, $comp);
   }

   public function sortASC($data, Comparator $comp){
	if(!is_array($data)){
		throw new IllegalArgumentException("First argument to Sorter must be an array");
	}
	if($comp === null){
		throw new IllegalArgumentException("Second argument to Sorter must NOT be null");
	}

	$this->data = $data;
	$this->aSortkey = "ASC";
	$this->comp = $comp;
	return $this->_sort();
   }

   public function sortDESC($data, Comparator $comp){
	if(!is_array($data)){
		throw new IllegalArgumentException("First argument to Sorter must be an array");
	}
	if($comp === null){
		throw new IllegalArgumentException("Second argument to Sorter must NOT be null");
	}

	$this->data = $data;
	$this->aSortkey = "DESC";
	$this->comp = $comp;
	return $this->_sort();
   }

   private final function compare($left, $right){
     return $this->comp->compare($left, $right);
   }
   
   private function _sort() {
	usort($this->data, array("Sorter", "compare"));
	if($this->aSortkey == "DESC"){
		$this->data = array_reverse($this->data);
	}
	return $this->data;
   }
}
?>