<?

/**
 * represents an abstract document
 */
class File{

	private $location;

	public function __construct($location){
		$this->location = $location;
	}

	/**
	 * sets this file's location
	 */
	public function setLocation($location){
		$this->location = $location;
	}

	/**
	 * @return the location of this file
	 */
	public function getLocation(){
		return $this->location;
	}

	/**
	 * @return true if the file exists, false otherwise
	 */
	public function exists(){
		return file_exists($this->location);
	}
}

?>