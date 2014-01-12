<?


class UndergroundWeather{


	protected $mysql;

	protected $city = "";
	protected $state = "";
	protected $weather = array();
	protected $temperature = array();
	protected $description = array();

	public function __construct($model){
		if(!($model instanceof Aurora)){
			throw new IllegalArgumentException("argument to " . __METHOD__ . " must be an MySQLConn");
		}
		$this->model = $model;
		$this->mysql = $model->getMysql();
	}



	public function getWeather($zip){
		$d = new ADateTime();
		$d->hour($d->hour() - 1);
		// lets try to get the weather from the database first
		$sql = "SELECT * FROM `weather_cache` WHERE zip = '" . addslashes($zip) . "' AND stamp >= '" . addslashes($d->toString()) . "'";
		$result = $this->mysql->query($sql);
		if($row = $result->fetch_array()){
		
			// database is good to go
			if(strpos($row["xml"], "City not found") !== false){
				// error :( zip not found
				throw new WeatherException("zip code not found");
			}
			$xml = $row["xml"];
			return $this->processXML($zip, $xml);
		}else{
			$xml = file_get_contents("http://xml.weather.yahoo.com/forecastrss?p=" . urlencode($zip) . "&u=f");
			$sql = "SELECT COUNT(*) AS total FROM weather_cache WHERE zip='" . addslashes($zip) . "'";
			$result = $this->mysql->query($sql);
			if(strlen($xml) > 0){
				$row = $result->fetch_array();
				$row["total"] = (int) $row["total"];
				if($row["total"] > 0){
					$d = new ADateTime();
					$sql = "UPDATE weather_cache SET stamp='" . addslashes($d->toString()) . "', xml='" . addslashes($xml) . "' WHERE zip='" . addslashes($zip) . "'";
					$result = $this->mysql->query($sql);
				}else{
					$d = new ADateTime();
					$sql = "INSERT INTO weather_cache (`stamp`,`xml`,`zip`) VALUES ('" . addslashes($d->toString()) . "', '" . addslashes($xml) . "','" . addslashes($zip) . "')";
					$result = $this->mysql->query($sql);
				}
				return $this->processXML($zip, $xml);
			}else{
				throw new WeatherException("error fetching weather. try again soon.");
			}
		}

	}
	
	
	private function processXML($zip, $xml){
		$this->model->getLogger()->log($this->model, ALogger::$NORMAL, "getting weather for: " . $zip . ":" . $xml);

		$xml = simplexml_load_string($xml);

		$city = "unknown";
		$state = "";

		foreach ($xml->xpath('//channel/yweather:location') as $location) {
			$attributes = $location->attributes();
			$city = "";
			$state = "";
			foreach($attributes as $k=>$v) {
				if($k == "city"){
					$city = (string) $v;
				}else
				if($k == "region"){
					$state = (string) $v;
				}
			}
		}
		
		$this->city = $city;
		$this->state = $state;
		
		foreach($xml->channel->item as $item){
			$date = "";
			$code = "";
			foreach ($item->xpath('//yweather:condition') as $condition) {
				$attributes = $condition->attributes();
				$code = -1;
				foreach($attributes as $k=>$v) {
					if($k == "date"){
						$date = (string) $v;
						$date = substr($date, 0, strlen($date) - 4);
						$date = strtotime($date);
						$date = new ADateTime(date("Y-m-d 00:00:00", $date));
					}else
					if($k == "code"){
						$code = (int) (string) $v;
					}
				}
			}
			$this->weather[] = array($date, $code);

			foreach ($item->xpath('//yweather:units') as $forecast) {
				$attributes = $forecast->attributes();
				$unit = "F";
				foreach($attributes as $k=>$v) {
					if($k == "temperature"){
						$unit = (string) $v;
					}
				}
			}

			foreach ($item->xpath('//yweather:condition') as $forecast) {
				$attributes = $forecast->attributes();
				$temperature = -1;
				$text = "";
				foreach($attributes as $k=>$v) {
					if($k == "temp"){
						$temperature = (int) (string) $v;
					}else
					if($k == "text"){
						$text = (string) $v;
					}
				}
				$this->temperature[] = array($date, $temperature, $temperature, $unit);
				$this->description[] = array($date, $text);
			}


			$today = $date;
			if(!is_object($today)){
				$today = new ADateTime();
				$today->hour(0);
				$today->minute(0);
				$today->second(0);
			}
			
			
			foreach ($item->xpath('//yweather:forecast') as $forecast) {
				$attributes = $forecast->attributes();
				$date = false;
				$code = -1;
				$low = -1;
				$high = -1;
				$text = "";
				foreach($attributes as $k=>$v) {
					if($k == "date"){
						$date = strtotime((string) $v);
						$date = new ADateTime(gmdate("Y-m-d 00:00:00", $date));
					}else
					if($k == "code"){
						$code = (int) (string) $v;
					}else
					if($k == "low"){
						$low = (int) (string) $v;
					}else
					if($k == "high"){
						$high = (int) (string) $v;
					}else
					if($k == "text"){
						$text = (string) $v;
					}
				}
				if(!is_object($date)){
					$date = $today;
				}
				if($date->toString() != $today->toString()){
					$this->weather[] = array($date, $code);
					$this->temperature[] = array($date, $low, $high, $unit);
					$this->description[] = array($date, $text);
				}
			}


		}			
		
		$ret = new Weather($this->city, $this->state, $this->weather, $this->description, $this->temperature);
		return $ret;
	}




}



?>