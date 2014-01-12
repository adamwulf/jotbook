<?

/**
 * defines a logger that doesn't do anything
 * should be used in production, so it won't slow
 * down anything
 */
 
class MuteLogger extends ALogger{

	public function log($obj, $level, $str){
		// noop
	}
	
	public function longQuery($time, $sql){
		// noop
	}
	
	public function clear(){
		// noop
	}
}

?>