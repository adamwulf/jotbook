<?

class Controller{

	private $listotron;
	private $twitter;

	public function __construct($list, $twitter){
		
		$this->listotron = $list;
		$this->twitter = $twitter;
		
	}

	public function process($data){
		if(!is_array($data)){
			throw new NullJSONException("argument to " . __METHOD__ . " must be a json object. given: " . gettype($data));
		}
		$ret = array();
		$listotron = $this->listotron;
		for($i=0;$i<count($data);$i++){
			if(isset($data[$i]->login)){
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["user_id"] = md5(rand() . md5(rand()));
			}else if(isset($data[$i]->load)){
				// request to load all rows
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				if(isset($data[$i]->dt)){
					$out["rows"] = $listotron->getAllRowsSince($data[$i]->lmb, $data[$i]->user_id);
				}else{
					$out["rows"] = $listotron->getAllRows();
				}
			}else if(isset($data[$i]->location)){
				// request to load all rows
				$listotron->trackUser($data[$i]->user_id, $data[$i]->row_id, $this->twitter);
	
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["rows"] = $listotron->getAllRowsSince($data[$i]->lmb, $data[$i]->user_id);
			}else if(isset($data[$i]->delete_row)){
				// request to load all rows
	
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["rows"] = $listotron->delete($data[$i]->row_id, $data[$i]->user_id);
			}else if(isset($data[$i]->insert_after)){
				// insert a row after the input row
				
				$listotron->trackUser($data[$i]->user_id, $data[$i]->row_id, $this->twitter);
				
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["rows"] = $listotron->insertRowAfter($data[$i]->row_id, $data[$i]->user_id);
			}else if(isset($data[$i]->insert_before)){
				// insert a row before the input row
	
				$listotron->trackUser($data[$i]->user_id, $data[$i]->row_id, $this->twitter);
	
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["rows"] = $listotron->insertRowBefore($data[$i]->row_id, $data[$i]->user_id);
			}else if(isset($data[$i]->edit)){
				// indent a row, and return all changed rows
				$listotron->trackUser($data[$i]->user_id, $data[$i]->row_id, $this->twitter);
	
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["rows"] = $listotron->editRow($data[$i]->row_id, $data[$i]->text, $data[$i]->user_id);
			}else if(isset($data[$i]->indent)){
				// indent a row, and return all changed rows
	
				$listotron->trackUser($data[$i]->user_id, $data[$i]->row_id, $this->twitter);
	
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["rows"] = $listotron->indent($data[$i]->row_id, $data[$i]->user_id);
			}else if(isset($data[$i]->outdent)){
				// outdent a row, and return all changed rows
	
				$listotron->trackUser($data[$i]->user_id, $data[$i]->row_id, $this->twitter);
	
				$out = array();
				$out["error"] = false;
				$out["dt"] = $listotron->getNOW();
				$out["rows"] = $listotron->outdent($data[$i]->row_id, $data[$i]->user_id);
			}else{
				// the client asked for something we don't support
				$err = array();
				$err["error"] = true;
				$err["message"] = print_r($_REQUEST, true);
				$ret[] = $err;
				continue;
			}
			
			// now append any user position changes
			
			if(isset($data[$i]->dt)){
				$out["tracking"] = $listotron->getMovementSince($data[$i]->dt);
			}
			$ret[] = $out;
		}
		
		return $ret;
	}
	
}

?>