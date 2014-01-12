<?

/**
 * a class for managing a MySQL connection
 * and queries
 * (this class also caches certain queries)
 */
class TestPostgrConn extends PostgrConn{

	private $_query_count;

	public function __construct(){
		$this->_query_count = 0;
		$this->_expected_queries = array();
		$this->_expected_results = array();
	}
	
	public function addExpectedQuery($sql, $result){
		if(!is_string($sql)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!$result instanceof PostgrResult){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a MySQLResult");
		}
		$this->_expected_queries[] = $sql;
		$this->_expected_results[] = $result;
	}

	// queries mysql and caches the result if appropriate
	function query($sql, $verbose=false){
		$this->_query_count++;
		if(count($this->_expected_queries)){
			if(strpos($sql, $this->_expected_queries[0]) === 0){
				array_shift($this->_expected_queries);
				return array_shift($this->_expected_results);
			}else{
				throw new DatabaseException("expected " . $this->_expected_queries[0] . "\ngiven: $sql");
			}
		}else{
			throw new DatabaseException("query when not expected: $sql");
		}
	}
	
	function reset(){
		$this->_query_count = 0;
	}

	function getQueryCount(){
		return $this->_query_count;
	}

}

?>