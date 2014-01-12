<?

/**
 * represents a simple Html Document
 */
class SQLCache{
	private $cache;
	private $verbose = false;
	
	function __construct(){
		$this->reset();
	}
	
	function put($sql, $result) {
		try{
			$index = $this->getTableName($sql);
		}catch(QueryTableException $e){
			$index = 0;
		}
		if(!isset($this->cache[$index])){
			$this->cache[$index] = new HashTable();
		}
		if($this->verbose) echo" > putting $sql to [$index]<br>";
		$this->cache[$index]->put($sql, $result);
	}

	function get($sql){
		try{
			$index = $this->getTableName($sql);
		}catch(QueryTableException $e){
			$index = 0;
		}
		if(!isset($this->cache[$index])){
			$this->cache[$index] = new HashTable();
		}
		if($this->verbose) echo" < getting $sql from [$index]<br>";
		return $this->cache[$index]->get($sql);
	}

	// send in an INSERT or UPDATE to clear the tablename
	// or send in a SQL to clear
	function clear($sql){
		try{
			$index = $this->getTableName($sql);
		}catch(QueryTableException $e){
			$this->reset();
			return;
		}
		// if it's a select that we're clearing, just clear that entry
		// if it's an insert or update, then clear all selects related
		// to it also.
		if(strpos($sql, "SELECT") === 0){
			if($this->verbose) echo" - clearing out SELECT: $sql<br>";
			$this->cache[$index]->clear($sql);
		}else{
			if($this->verbose) echo" - clearing table [$index]<br>";
			$this->cache[$index] = new HashTable();
			$this->cache[0] = new HashTable();
		}
	}

	function reset(){
		if($this->verbose) echo" * RESETTING<br>";
		$this->table = array();
	}
	
	
	// parse out the tablename from a query
	public static function getTableName($sql){
		if(!is_string($sql)){
			throw new IllegalArgumentException($sql);
		}
		if(strpos($sql, "SELECT") === 0){
			$start = strpos($sql, "FROM") + 5;
			$sql = trim(substr($sql, $start));
			$white   = strpos($sql, " ");
			$comma   = strpos($sql, ",");
			if(is_int($comma) && $comma < $white){
				throw new QueryTableException("multiple tables in SELECT query");
			}else{
				return substr($sql,0,$white);
			}			
		}else if(strpos($sql, "UPDATE") === 0){
			$start = strpos($sql, "UPDATE") + 7;
			$sql = trim(substr($sql, $start));
			$end = strpos($sql, "SET");
			$table = trim(substr($sql,0,$end));
			$table = trim($table, "`");
			return $table;
		}else if(strpos($sql, "INSERT") === 0){
			$start = strpos($sql, "INTO") + 5;
			$sql = trim(substr($sql, $start));
			$end = strpos($sql, " ");
			$table = trim(substr($sql,0,$end));
			$table = trim($table, "`");
			return $table;
		}else if(strpos($sql, "DELETE") === 0){
			$start = strpos($sql, "FROM") + 5;
			$sql = trim(substr($sql, $start));
			$end = strpos($sql, " ");
			$table = trim(substr($sql,0,$end));
			$table = trim($table, "`");
			return $table;
		}else if(strpos($sql, "DROP") === 0){
			$start = strpos($sql, "TABLE") + 6;
			$sql = trim(substr($sql, $start));
			$end = strpos($sql, " ");
			$table = trim(substr($sql,0,$end));
			$table = trim($table, "`");
			return $table;
		}else if(strpos($sql, "ALTER") === 0){
			$start = strpos($sql, "TABLE") + 6;
			$sql = trim(substr($sql, $start));
			$end = strpos($sql, " ");
			$table = trim(substr($sql,0,$end));
			$table = trim($table, "`");
			return $table;
		}else if(strpos($sql, "CREATE") === 0){
			$start = strpos($sql, "TABLE") + 6;
			$sql = trim(substr($sql, $start));
			$end = strpos($sql, " ");
			$table = trim(substr($sql,0,$end));
			$table = trim($table, "`");
			return $table;
		}else{
			throw new QueryTableException("SQL is not a SELECT, UPDATE, DELETE, or INSERT");
		}
	}
}

?>