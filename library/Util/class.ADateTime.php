<?

/**
 * represents a simple Html Document
 */
class ADateTime{
	private $year;
	private $month;
	private $day;
	private $hour;
	private $minute;
	private $sec;

	// accepts a hex color (with or without #)
	function __construct($d=false){
		if(!is_string($d) && $d !== false || is_string($d) && !ADateTime::isDateTime($d)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a datetime formatted string. given: $d");
		}
		if(is_string($d)){
			$this->year   = substr($d, 0, 4);
			$this->month  = substr($d, 5, 2);
			$this->day    = substr($d, 8, 2);
			$this->hour   = substr($d, 11, 2);
			$this->minute = substr($d, 14, 2);
			$this->sec    = substr($d, 17, 2);
		}else{
			$this->year   = date("Y");
			$this->month  = date("m");
			$this->day    = date("d");
			$this->hour   = date("H");
			$this->minute = date("i");
			$this->sec    = date("s");
		}
	}

	public function year($a = false){
		if(is_int($a)){
			$this->year = $a;
		}else if($a !== false){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		return (int)$this->year;
	}

	public function month($a = false){
		if(is_int($a)){
			$this->month = $a;
		}else if($a !== false){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		return (int)$this->month;
	}

	public function day($a = false){
		if(is_int($a)){
			$this->day = $a;
		}else if($a !== false){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		return (int)$this->day;
	}

	public function hour($a = false){
		if(is_int($a)){
			$this->hour = $a;
		}else if($a !== false){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		return (int)$this->hour;
	}

	public function minute($a = false){
		if(is_int($a)){
			$this->minute = $a;
		}else if($a !== false){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		return (int)$this->minute;
	}

	public function second($a = false){
		if(is_int($a)){
			$this->sec = $a;
		}else if($a !== false){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an int");
		}
		return (int)$this->sec;
	}
	
	public function weekday(){
		return date("w", $this->getTimeStamp());
	}

	public function getTimeStamp(){
		$time = gmmktime($this->hour, $this->minute, $this->sec, $this->month, $this->day, $this->year);
		return (int) $time;
	}
	
	/**
	 * this will return the timestamp, assuming hour, minute and second are 0
	 * IE, this will round down to the nearest day
	 */
	public function getTimeStampFloor(){
		$dt = new ADateTime(substr($this->toString(), 0, 10) . " 00:00:00");
		return $dt->getTimeStamp();
	}
	
	/**
	 * this will return the timestamp, assuming hour is 0, and minute and second are 0,
	 * and the day is +1
	 * IE, this will round up to the nearest day
	 */
	public function getTimeStampCeil(){
		return $this->getTimeStampFloor() + 24*60*60;
	}
	
	public function setTimeStamp($stamp){
		if(!is_int($stamp)){
			throw new IllegalArgumentException("argument to ". __METHOD__ . " must be an int. given: " . gettype($stamp));
		}
		$this->year   = date("Y", $stamp);
		$this->month  = date("m", $stamp);
		$this->day    = date("d", $stamp);
		$this->hour   = date("H", $stamp);
		$this->minute = date("i", $stamp);
		$this->sec    = date("s", $stamp);
	}


	// returns true if the input matches "YYYY-MM-DD HH:MM:SS" format
	public static function isDateTime($var){
		return preg_match("/^(19|20)\d\d-(0[0-9]|1[0-2])-([0-2][0-9]|3[01]) ([01][0-9]|2[0-4]):[0-5][0-9]:[0-5][0-9]\$/", $var);
	}

	public function __toString() {
		return gmdate("Y-m-d H:i:s", $this->getTimeStamp());
	}

	public function toString() {
		return $this->__toString();
	}

	public static function MIN(){
		return new ADateTime("1901-12-14 00:00:00");
	}
	
	public static function MAX(){
		return new ADateTime("2038-01-17 00:00:00");
	}
	
	public function dateEqualHuh($dt){
		if(!$dt instanceof ADateTime){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a DateTime");
		}
		return $this->year() == $dt->year() && $this->month() == $dt->month() && $this->day() == $dt->day();
	}


	/********************************
		timezone adjust functions
	********************************/
	// puts this timezone into effect
	// from GMT to GMT + timezone
	// takes daylight savings into
	// account if 2nd argument is true
	public function toTimezone($timezone, $isDST = 0){
		if(!is_double($timezone) && !is_int($timezone)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a double");
		}
		$hour_offset = floor($timezone);
		$min_offset = (int)(($timezone - $hour_offset) * 60);

		if($isDST === 0){
			$dst = (int) @date("I", $this->getTimeStamp());
			$hour_offset += $dst;
		}else{
			$hour_offset += $isDST ? 1 : 0;
		}
		
		$stamp = mktime($this->hour + $hour_offset,
						$this->minute + $min_offset,
						$this->sec,
						$this->month,
						$this->day,
						$this->year);
		$this->year  = @date("Y", $stamp);
		$this->month = @date("m", $stamp);
		$this->day   = @date("d", $stamp);

		$this->hour   = @date("H", $stamp);
		$this->minute = @date("i", $stamp);
		$this->second = @date("s", $stamp);
	}

	// takes this timezone out of effect
	// from GMT + timezone to GMT
	// takes daylight savings into
	// account
	public function toGMT($timezone, $isDST = 0){
		$hour_offset = floor($timezone);
		$min_offset = (int)(($timezone - $hour_offset) * 60);

		if($isDST === 0){
			$dst = (int) @date("I", $this->getTimeStamp());
			$hour_offset += $dst;
		}else{
			$hour_offset += $isDST ? 1 : 0;
		}

		$stamp = mktime($this->hour - $hour_offset,
						$this->minute - $min_offset,
						$this->sec,
						$this->month,
						$this->day,
						$this->year);
		$this->year  = @date("Y", $stamp);
		$this->month = @date("m", $stamp);
		$this->day   = @date("d", $stamp);

		$this->hour   = @date("H", $stamp);
		$this->minute = @date("i", $stamp);
		$this->second = @date("s", $stamp);
	}
}

?>