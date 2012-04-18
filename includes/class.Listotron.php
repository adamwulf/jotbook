<?

class Listotron{
	
	private $filename;
	
	private $data;
	
	private $nextId;
	
	private $NOW;
	
	function __construct($filename = false){
		$this->filename = $filename;
		
		if(is_string($filename) && file_exists($filename)){
			$this->data = unserialize(file_get_contents($filename));
		}else if(is_array($filename)){
			$this->data = $filename;
		}else{
			$this->data = array();
			
			$this->data["rows"] = array();
			$this->data["users"] = array();
			
			$row = array();
			$row["row_id"] = "1";
			$row["text"] = "one";
			$row["par"] = null;
			$row["prev"] = null;
			$this->data["rows"][] = $row;
	
			$row = array();
			$row["row_id"] = "2";
			$row["text"] = "two";
			$row["par"] = "1";
			$row["prev"] = null;
			$this->data["rows"][] = $row;
	
			$row = array();
			$row["row_id"] = "3";
			$row["text"] = "three";
			$row["par"] = "1";
			$row["prev"] = "2";
			$this->data["rows"][] = $row;
	
			$row = array();
			$row["row_id"] = "4";
			$row["text"] = "four";
			$row["par"] = "1";
			$row["prev"] = "3";
			$this->data["rows"][] = $row;
	
			$row = array();
			$row["row_id"] = "5";
			$row["text"] = "five";
			$row["par"] = "1";
			$row["prev"] = "4";
			$this->data["rows"][] = $row;
	
			$row = array();
			$row["row_id"] = "6";
			$row["text"] = "six";
			$row["par"] = "1";
			$row["prev"] = "5";
			$this->data["rows"][] = $row;
			
			$this->save();
		}
		
		$this->nextId = 0;
		for($j=0;$j<count($this->data["rows"]);$j++){
			if($this->data["rows"][$j]["row_id"] >= $this->nextId){
				$this->nextId = $this->data["rows"][$j]["row_id"] + 1;
			}
		}

		$this->updateNOW();
	}
	
	
	public function getRow($row_id){
		for($i=0;$i<count($this->data["rows"]);$i++){
			if($this->data["rows"][$i]["row_id"] == $row_id){
				return $this->data["rows"][$i];
			}
		}
		return null;
	}
	
	
	/**
	 * returns all of my siblings that come after me
	 * returns an empty array if the input row is the last child
	 */
	protected function getNextSibling($row){
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				if($this->data["rows"][$i]["prev"] == $row["row_id"]){
					return $this->data["rows"][$i];
				}
			}
		}
		return null;
	}
	
	/**
	 * returns all of my siblings that come after me
	 * returns an empty array if the input row is the last child
	 */
	protected function getNextSiblings($row){
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				if($this->data["rows"][$i]["prev"] == $row["row_id"]){
					return array_merge(array($this->data["rows"][$i]), $this->getNextSiblings($this->data["rows"][$i]));
				}
			}
		}
		return array();
	}
	
	/**
	 * get's the next row from the user's perspective.
	 * this will eitehr be my first child, or if i have
	 * no children, then it'll be my next sibling.
	 * if no siblings, then my parents next sibling
	 */
	protected function getNextRow($row){
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				if($this->data["rows"][$i]["par"] == $row["row_id"] && $this->data["rows"][$i]["prev"] == null){
					return $this->data["rows"][$i];
				}
			}
		}
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				if($this->data["rows"][$i]["prev"] == $row["row_id"]){
					return $this->data["rows"][$i];
				}
			}
		}
		if($row["par"]) return $this->getNextRow($this->getRow($row["par"]));
		return null;
	}
	
	protected function getLastSibling($row){
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				if($this->data["rows"][$i]["prev"] == $row["row_id"]){
					return $this->getLastSibling($this->data["rows"][$i]);
				}
			}
		}
		return $row;
	}
	
	/**
	 * returns the last kid of the input row
	 */
	public function getLastKid($row){
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				if($this->data["rows"][$i]["par"] == $row["row_id"]){
					return $this->getLastSibling($this->data["rows"][$i]);
				}
			}
		}
		return null;
	}
	
	/**
	 * returns all kids of a row
	 */
	public function getAllKids($row){
		$rows = array();
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				if($this->data["rows"][$i]["par"] == $row["row_id"]){
					$rows[] = $this->data["rows"][$i];
				}
			}
		}
		return $rows;
	}
	
	
	public function delete($row_id, $user_id){
		$rows = array();
		
		$row = $this->getRow($row_id);
		$kids = $this->getAllKids($row);
		$lastkid = $this->getLastKid($row);
		
		// update all my kids
		for($i=0;$i<count($kids);$i++){
			if(!$this->isDeletedHuh($kids[$i])){
				if($row["prev"] != null){
					$kids[$i] = $this->updateRow($kids[$i]["row_id"], "par", $row["prev"]);
				}else{
					$kids[$i] = $this->updateRow($kids[$i]["row_id"], "par", $row["par"]);
				}
				$rows[] = $kids[$i];
			}
		}
		
		// update my sibling
		$next = $this->getNextSibling($row);
		if(is_array($next)){
			if($row["prev"] != null){
				// if my previous isnot null,
				// then set my next sibling's previous
				// to be my previous
				$next = $this->updateRow($next["row_id"], "prev", $row["prev"]);
			}else{
				// otherwise, set it to be my last kid
				$next = $this->updateRow($next["row_id"], "prev", $lastkid["row_id"]);
			}
			$rows[] = $next;
		}
		
		$row = $this->updateRow($row["row_id"], "del", $this->getNOW());
		$rows[] = $row;
		$rows = $this->updateLastModified($rows, $user_id);
		$this->save();
		
		return $rows;
	}
	
	protected function updateRow($row_id, $field, $value){
		for($i=0;$i<count($this->data["rows"]);$i++){
			if($this->data["rows"][$i]["row_id"] == $row_id){
				$this->data["rows"][$i][$field] = $value;
				return $this->data["rows"][$i];
			}
		}
		return null;
	}
	
	protected function updateLastModified($rows, $user_id){
		for($i=0;$i<count($rows);$i++){
			$rows[$i] = $this->updateRow($rows[$i]["row_id"], "lmb", $user_id);
			$rows[$i] = $this->updateRow($rows[$i]["row_id"], "lm", $this->getNOW());
		}
		return $rows;
	}
	
	protected function save(){
		if(is_string($this->filename)) @file_put_contents($this->filename, serialize($this->data));
	}
	
	/**
	 * record the user's position in the list
	 */
	public function trackUser($user_id, $row_id){
		for($i=0;$i<count($this->data["users"]);$i++){
			if($this->data["users"][$i]["user_id"] == $user_id){
				$this->data["users"][$i]["stamp"] = $this->getNOW();
				$this->data["users"][$i]["row_id"] = $row_id;
				$this->save();
				return;
			}
		}
		$newuser = array();
		$newuser["user_id"] = $user_id;
		$newuser["stamp"] = $this->getNOW();
		$newuser["row_id"] = $row_id;
		$this->data["users"][] = $newuser;

		$this->save();
	}
	
	public function getMovementSince($dt){
		$ret = array();
		for($i=0;$i<count($this->data["users"]);$i++){
			if($this->data["users"][$i]["stamp"] >= $dt){
				$ret[] = $this->data["users"][$i];
			}
		}
		return $ret;
	}
	
	/**
	 * indent the input row
	 */
	public function indent($row_id, $user_id){
		$rows = array();
		$row = $this->getRow($row_id);
		
		// if i don't have a previous sibling, then i
		// can't indent at all
		if($row["prev"] == null) return array();
		
		// if i do have a previous sibling, then
		// ineed to be added as it's last child
		// and my next sibling is now my previous's
		// next sibling
		$next = $this->getNextSibling($row);
		
		if($next && $next["prev"] == $row["row_id"]){
			// I have a next sibling that needs to be
			// update to be my previous's next sibling
			$next = $this->updateRow($next["row_id"], "prev", $row["prev"]);
			$rows[] = $next;
		}
		$prev = $this->getRow($row["prev"]);
		$lastkid = $this->getLastKid($prev);
		
		$row = $this->updateRow($row["row_id"], "par", $prev["row_id"]);
		if($lastkid){
			$row = $this->updateRow($row["row_id"], "prev", $lastkid["row_id"]);
		}else{
			$row = $this->updateRow($row["row_id"], "prev", null);
		}
		$rows[] = $row;
		
		// return the array so that my input row_id is the first
		// row in the output array
		$rows = array_reverse($rows);
		$rows = $this->updateLastModified($rows, $user_id);
		
		// save the changes
		$this->save();
		
		return $rows;
	}
	
	
	public function outdent($row_id, $user_id){
		$rows = array();
		$row = $this->getRow($row_id);
		if($row["par"] == null) return $rows;
		
		$lastkid = $this->getLastKid($row);
		// set all of my next siblings to now have me as a parent
		$siblings = $this->getNextSiblings($row);
		for($i=0;$i<count($siblings);$i++){
			if(!$this->isDeletedHuh($siblings[$i])){
				// if the previous is me, set the previous
				// to be my last child
				// set all parent's to be me
				if($siblings[$i]["prev"] == $row["row_id"] && $lastkid){
					$siblings[$i] = $this->updateRow($siblings[$i]["row_id"], "prev", $lastkid["row_id"]);
				}else if($siblings[$i]["prev"] == $row["row_id"]){
					$siblings[$i] = $this->updateRow($siblings[$i]["row_id"], "prev", null);
				}
				$siblings[$i] = $this->updateRow($siblings[$i]["row_id"], "par", $row["row_id"]);
				$rows[] = $siblings[$i];
			}
		}
		
		$par = $this->getRow($row["par"]);
		// update me to be my parent's sibling
		$parsib = $this->getNextSibling($par);
		$row = $this->updateRow($row["row_id"], "par", $par["par"]);
		$row = $this->updateRow($row["row_id"], "prev", $par["row_id"]);
		if($parsib){
			// update my parent's old next sibling to be my next sibling,
			$parsib = $this->updateRow($parsib["row_id"], "prev", $row["row_id"]);
			$rows[] = $parsib;
		}

		// put the input row at the front of the array to maintain
		// good order in the return array
		array_splice($rows, 0, 0, array($row));
		$rows = $this->updateLastModified($rows, $user_id);

		// save the changes
		$this->save();
		
		return $rows;
	}
	
	public function insertRowBefore($row_id, $user_id){
		$rows = array();
		
		for($i=0;$i<count($this->data["rows"]);$i++){
			if($this->data["rows"][$i]["row_id"] == $row_id && !$this->isDeletedHuh($this->data["rows"][$i])){
				// prep the new row
				$newrow = array();
				$newrow["row_id"] = $this->getNextId();
				$newrow["text"] = "";
				$newrow["par"] = $this->data["rows"][$i]["par"];
				$newrow["prev"] = $this->data["rows"][$i]["prev"];
				$this->data["rows"][$i]["prev"] = $newrow["row_id"];
				$rows[] = $newrow;
				$rows[] = $this->data["rows"][$i];

				// splice in a new row before this one
				array_splice($this->data["rows"], $i, 0, array($newrow));
				break;
			}
		}
		
		$rows = $this->updateLastModified($rows, $user_id);
		// save the changes
		$this->save();
		
		return $rows;
	}
		

	public function insertRowAfter($row_id, $user_id){
		$rows = array();
		$row = $this->getRow($row_id);
		$new_id = $this->getNextId();
		
		// prep the new row
		$newrow = array();
		$newrow["row_id"] = $new_id;
		$newrow["text"] = "";
		$newrow["par"] = $row["par"];
		$newrow["prev"] = $row["row_id"];
		$rows[] = $newrow;

		// find the row (if any) who's previous
		// is the input row_id
		for($i=0;$i<count($this->data["rows"]);$i++){
			if($this->data["rows"][$i]["prev"] == $row_id){
				if(!$this->isDeletedHuh($this->data["rows"][$i])){
					$this->data["rows"][$i]["prev"] = $new_id;
					$rows[] = $this->data["rows"][$i];
				}
			}
		}
		// update all the kids of the input row
		$kids = $this->getAllKids($row);
		for($i=0;$i<count($kids);$i++){
			if(!$this->isDeletedHuh($kids[$i])){
				$kids[$i] = $this->updateRow($kids[$i]["row_id"], "par", $new_id);
				$rows[] = $kids[$i];
			}
		}
		for($i=0;$i<count($this->data["rows"]);$i++){
			if($this->data["rows"][$i]["row_id"] == $row_id){
				// splice in a new row after this one
				array_splice($this->data["rows"], $i+1, 0, array($newrow));
				break;
			}
		}
		$rows = $this->updateLastModified($rows, $user_id);
		// save the changes
		$this->save();
		
		return $rows;
	}
		
		
	public function editRow($row_id, $text, $user_id){
		$row = $this->getRow($row_id);
		$row = $this->updateRow($row["row_id"], "text", $text);
		
		$rows = array();
		$rows[] = $row;
		$rows = $this->updateLastModified($rows, $user_id);
		$this->save();
		return $rows;
	}
	
	
	protected function isDeletedHuh($row){
		return isset($row["del"]);
	}
	
	/**
	 * return all rows in the list
	 */
	public function getAllRows(){
		$rows = array();
		for($i=0;$i<count($this->data["rows"]);$i++){
			if(!$this->isDeletedHuh($this->data["rows"][$i])){
				$rows[] = array_copy($this->data["rows"][$i]);
			}
		}
		return $rows;
	}
	
	/**
	 * return just the rows that have been modified
	 *
	 * this function will likely need revisiting later
	 *
	 * $lmbobj is an object with keys that are user ids and values that are timestamps
	 * this represents the client's last known modification dates for each user
	 */
	public function getAllRowsSince($lmbobj, $user_id){
		$rows = array();
		for($j=0;$j<count($this->data["rows"]);$j++){
			if(isset($this->data["rows"][$j]["lmb"]) && isset($this->data["rows"][$j]["lm"])){
				$lmb = $this->data["rows"][$j]["lmb"]; // the user who modified the row
				$lm = $this->data["rows"][$j]["lm"]; // the last dt the row was modified
				
				if( $lmb != $user_id && (isset($lmbobj->$lmb) && $lm > $lmbobj->$lmb || $lmb && !isset($lmbobj->$lmb))){
					$rows[] = $this->data["rows"][$j];
				}
			}
		}
		return $rows;
	}
	
	/**
	 * get the next available row id
	 */
	public function getNextId(){
		$ret = $this->nextId;
		$this->nextId++;
		return $ret;
	}

	/**
	 * get the current timestamp
	 */
	public function getNOW(){
		return $this->NOW;
	}
	
	public function updateNOW(){
		$mctime = microtime();
		$this->NOW = gmdate("Y-m-d H:i:s") . substr($mctime, 0, strpos($mctime, " "));
	}
	
	
	/************************************************
	 * functions used for testing and validation
	 ************************************************/
	 
	/**
	 * checks that:
	 * 1) a row's previous id is listed before it in the list
	 * 2) a row's parent is listed before it in the list
	 */
	public function isValidHuh(){
		$seen_so_far = array();
		$ok = true;
		for($j=0;$j<count($this->data["rows"]);$j++){
			$row = $this->data["rows"][$j];
			if(!isset($row["del"])){
				$seen_so_far[] = $row["row_id"];
				$ok = $ok && ($row["par"] == null  || in_array($row["par"], $seen_so_far));
				$ok = $ok && ($row["prev"] == null || in_array($row["prev"], $seen_so_far));
				if($row["prev"] != null){
					$row2 = $this->getRow($row["prev"]);
					$ok = $ok && $row2["par"] == $row["par"];
				}
	//			print_r($row);
			}
		}
		
		//
		// make sure two rows don't share the same
		// parent and previous node
		for($j=0;$j<count($this->data["rows"]);$j++){
			$row1 = $this->data["rows"][$j];
			if(!isset($row1["del"])){
				for($i=0;$i<count($this->data["rows"]);$i++){
					$row2 = $this->data["rows"][$i];
					if(!isset($row2["del"]) && $row1["row_id"] != $row2["row_id"]){
						if(($row1["par"] == $row2["par"]) && ($row1["prev"] == $row2["prev"])){
							// uh oh, the list is invalid,
							// two nodes share the same parent and
							// previous node
							$ok = false;
						}
					}
				}
			}
		}
		
		return $ok;
	}
	
	public function getHumanReadable(){
		return var_export($this->data, true);
	}

}

?>