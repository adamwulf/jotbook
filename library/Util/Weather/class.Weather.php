<?

class Weather{



	protected $city;
	protected $state;
	
	protected $weather;
	protected $temperature;
	protected $description;

	public function __construct($city, $state, $weather, $description, $temperature){
		if(!is_string($city)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_string($state)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_array($weather)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_array($temperature)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		if(!is_array($description)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be a string");
		}
		$this->city = $city;
		$this->state = $state;
		
		$this->weather = $weather;
		$this->temperature = $temperature;
		$this->description = $description;
	}


	public function getCity(){
		return $this->city;
	}
	
	public function getState(){
		return $this->state;
	}


	public function getWeather(){
		return $this->weather;
	}
	
	public function getTemperature(){
		return $this->temperature;
	}
	
	public function getDescription(){
		return $this->description;
	}
	
}


?>