<?

/**
 * defines and abstract logger class
 */
 
class SqlLogger extends ALogger{

	/**
	 * the mysql connection
	 */
	private $mysql;

	public function SqlLogger($mysql){
		if(!($mysql instanceof MySQLConn)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a MySQLConn");
		}
		
		$this->mysql = $mysql;
	}

	public function log($obj, $level, $str){
		if(strlen($str) > 1000){
			$small = substr($str, 0, 1000);
			$str = substr($str, 1000);
			$this->log($obj, $level, $small);
		}
		$sql = "INSERT INTO logger_logs (`stamp`,`class`,`level`,`str`) VALUES('" . addslashes(gmdate("Y-m-d H:i:s")) . "','" . get_class($obj) . "','" . addslashes($level) . "','" . addslashes($str) . "')";
		$this->mysql->query($sql);
	}

	public function longQuery($time, $sql){
		$now = new ADateTime();
		$sql2 = "INSERT INTO long_queries (`query`,`time`,`stamp`) VALUES ('" . addslashes($sql) . "', '" . addslashes($time) . "','" . addslashes(substr($now->toString(),11)) . "')";
		$this->mysql->query($sql2);
	}

	public function clear(){
		$sql = "DELETE FROM logger_logs";
		$this->mysql->query($sql);
	}

}

?>