<?

/**
 * defines and abstract logger class
 */
 
abstract class ALogger{

	public static $LOW = 0;
	public static $NORMAL = 1;
	public static $HIGH = 2;


	public abstract function log($obj, $level, $str);
	
	public abstract function longQuery($time, $sql);
	
	public abstract function clear();


}

?>