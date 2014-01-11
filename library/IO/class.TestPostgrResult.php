<?

/**
 * a class for managing a MySQL connection
 * and queries
 * (this class also caches certain queries)
 */
class TestPostgrResult extends PostgrResult{

	private $result;
	private $insert_id;
	private $affected_rows;
	private $count;


	public function __construct($rows, $insert_id=0, $affected_rows=0){
		if(!is_array($rows)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an array");
		}
		if(!is_int($insert_id)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		if(!is_int($affected_rows)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		
		$this->result = $rows;
		$this->insert_id = $insert_id;
		$this->affected_rows = $affected_rows;
		
		$this->count = count($rows);
	}

	
	function num_rows(){
		return $this->count;
	}

	function fetch_array(){
		return array_shift($this->result);
	}
	
	function insert_id(){
		return $this->insert_id;
	}

	function affected_rows(){
		return $this->affected_rows;
	}
}

?>