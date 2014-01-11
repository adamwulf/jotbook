<?

/**
 * a class for managing a MySQL connection
 * and queries
 * (this class also caches certain queries)
 */
class PostgrResult{

	private $result;
	private $insert_id;
	private $affected_rows;


	public function __construct($link, $result){
		$this->result = $result;
	}

	
	function num_rows(){
		return pg_num_rows($this->result);
	}

	function fetch_array(){
		return pg_fetch_array($this->result);
	}
	
	function insert_id(){
		return @pg_last_oid($$this->result);
	}

	function affected_rows(){
		return @pg_affected_rows($$this->result);
	}
}

?>