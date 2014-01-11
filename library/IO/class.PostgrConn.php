<?

/**
 * a class for managing a MySQL connection
 * and queries
 * (this class also caches certain queries)
 */
class PostgrConn{

	private $host;
	private $database;
	private $user;
	private $pass;
	private $timer;

	/**
	 * optional logger
	 */
	private $logger;

	// the link, or false if none
	private $_postgr_link;
	private $_query_count;


	public function __construct($h, $db, $u, $p, $log = null){
		$this->host = $h;
		$this->database = $db;
		$this->user = $u;
		$this->pass = $p;
		$this->_query_count = 0;
		$this->_postgr_link = false;
		$this->_query_cache = new HashTable();
		if($log !== null && !($log instanceof ALogger)){
			throw new IllegalArgumentException("optional argument to " . __METHOD__ . " must be an ALogger");
		}
		$this->logger = $log;
	}

	// queries mysql and caches the result if appropriate
	function query($sql, $verbose=false){
		return new TestPostgrResult(array());
		
		$sql = trim($sql);
		if($this->_postgr_link === false){
			$this->_postgr_link = pg_connect("host=" . $this->host . " user=" . $this->user . " password=" . $this->pass . " dbname=" . $this->database);
			pg_query($this->_postgr_link, "SET NAMES 'utf8'");
		}
		if($this->_postgr_link === false){
			throw new DatabaseException("could not connect to MySQL");
		};

		if($this->_query_cache->get($sql)){
			if($verbose)echo "found in cache<br>";
			$result = $this->_query_cache->get($sql);
			if(pg_num_rows($result)){
				if($verbose) echo ": seeking to 0";
				pg_result_seek($result, 0);
			}
			$ret = new PostgrResult($this->_postgr_link, $result);
			if($verbose) echo "<br>";
		}else{
			if($verbose) echo "not in cache";
			$this->_query_count++;
			/**
			 * this following line should be run once per connection to postgres
			 *
			 * i'm running it before every query. I can probably optimize this
			 * to run once per connection, but I need to do some thorough testing...
			 *
			 * http://dev.mysql.com/doc/refman/5.0/en/charset-connection.html
			 */
			pg_query($this->_postgr_link, "SET NAMES 'utf8'");
			$timer = new Timer();
			$timer->start();
			$result = pg_query($this->_postgr_link, $sql);
			$ret = new PostgrResult($this->_postgr_link, $result);
			$timer->stop();
			$time = $timer->read();
			
			if(is_object($this->logger)){
				$this->logger->log($this, ALogger::$LOW, $sql);
			}
			
			/**
			 * the query is too long! oh noes!
			 */
			if($time > .1){
				/**
				 * save the query to the DB, so I can look at it later
				 */
				if(is_object($this->logger)){
					$this->logger->longQuery($time, $sql);
				}
			}
			
			if(pg_last_error($this->_postgr_link)){
				if($verbose) echo "postgr_error: " . pg_last_error($this->_postgr_link) . "<br>";
				throw new DatabaseException(pg_last_error($this->_postgr_link));
			}
			if(strpos($sql, "SELECT") === 0){
				if($verbose) echo ": select: $sql<br><br>";
				$this->_query_cache->put($sql, $result);
			}else{
				if($verbose) echo ": not select: $sql<br>";
				if($verbose) echo "clearing cache<br>";
				$this->_query_cache->reset();
			}

		}
		return $ret;
	}
	
	function reset(){
		$this->_query_count = 0;
		$this->_query_cache->reset();
	}

	function getQueryCount(){
		return $this->_query_count;
	}
	
	function close(){
		if(!is_bool($this->_postgr_link)){
			return @pg_close($this->_postgr_link);
		}else{
			return false;
		}
	}

}

?>