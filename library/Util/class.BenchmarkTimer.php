<?

/**
 * represents a simple Html Document
 */
class BenchmarkTimer extends Timer{

	function __construct(){
		parent::__construct();
	}

	function start(){
		$ru = getrusage();
		$currentTime = $ru['ru_stime.tv_sec'] +
			   round($ru['ru_stime.tv_usec']/1000000, 4);
		$this->start = $currentTime;
		$this->stop = false;
	}

	function stop(){
		$ru = getrusage();
		$currentTime = $ru['ru_stime.tv_sec'] +
			   round($ru['ru_stime.tv_usec']/1000000, 4);
		$this->stop = $currentTime;
	}

	function read(){
		if(is_numeric($this->stop) &&
		   is_numeric($this->start) &&
		   ($this->stop > $this->start)){
			return ($this->stop - $this->start);
		}else
		if(is_numeric($this->start)){
			$ru = getrusage();
			$currentTime = $ru['ru_stime.tv_sec'] +
			   round($ru['ru_stime.tv_usec']/1000000, 4);
			return ($currentTime - $this->start);
		}else{
			return 0;
		}
	}
}

?>