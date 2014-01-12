<?

//
// this file is from phpicalendar
// and probably needs to be released
// under GLP or somesuch

class ICalendarParser{

	private $php_started;
	
	private $day_array;
	private $overlap_array;
	private $uid_counter;

	private $calnumber;
	
	private $this_day;
	private $this_month;
	private $this_year;
	
	private $week_start_day;
	
	private $tz_array;
	
	private $timezone;
	
	// dateOfWeek() takes a date in Ymd and a day of week in 3 letters or more
	// and returns the date of that day. (ie: "sun" or "sunday" would be acceptable values of $day but not "su")
	function dateOfWeek($Ymd, $day) {
		if (!isset($this->week_start_day)) $this->week_start_day = 'Sunday';
		$timestamp = strtotime($Ymd);
		$num = date('w', strtotime($this->week_start_day));
		$start_day_time = strtotime((date('w',$timestamp)==$num ? $this->week_start_day : ("last " . $this->week_start_day)), $timestamp);
		$ret_unixtime = strtotime($day,$start_day_time);
		// Fix for 992744
		// $ret_unixtime = strtotime('+12 hours', $ret_unixtime);
		$ret_unixtime += (12 * 60 * 60);
		$ret = date('Ymd',$ret_unixtime);
		return $ret;
	}		
	// function to compare to dates in Ymd and return the number of weeks 
	// that differ between them. requires dateOfWeek()
	function weekCompare($now, $then) {
		$sun_now = $this->dateOfWeek($now, "Sunday");
		$sun_then = $this->dateOfWeek($then, "Sunday");
		$seconds_now = strtotime($sun_now);
		$seconds_then =  strtotime($sun_then);
		$diff_weeks = round(($seconds_now - $seconds_then)/(60*60*24*7));
		return $diff_weeks;
	}
	
	// function to compare to dates in Ymd and return the number of days 
	// that differ between them.
	function dayCompare($now, $then) {
		$seconds_now = strtotime($now);
		$seconds_then =  strtotime($then);
		$diff_seconds = $seconds_now - $seconds_then;
		$diff_minutes = $diff_seconds/60;
		$diff_hours = $diff_minutes/60;
		$diff_days = round($diff_hours/24);
		
		return $diff_days;
	}
	
	// function to compare to dates in Ymd and return the number of months 
	// that differ between them.
	function monthCompare($now, $then) {
		ereg ("([0-9]{4})([0-9]{2})([0-9]{2})", $now, $date_now);
		ereg ("([0-9]{4})([0-9]{2})([0-9]{2})", $then, $date_then);
		$diff_years = $date_now[1] - $date_then[1];
		$diff_months = $date_now[2] - $date_then[2];
		if ($date_now[2] < $date_then[2]) {
			$diff_years -= 1;
			$diff_months = ($diff_months + 12) % 12;
		}
		$diff_months = ($diff_years * 12) + $diff_months;
	
		return $diff_months;
	}
	
	function yearCompare($now, $then) {
		ereg ("([0-9]{4})([0-9]{2})([0-9]{2})", $now, $date_now);
		ereg ("([0-9]{4})([0-9]{2})([0-9]{2})", $then, $date_then);
		$diff_years = $date_now[1] - $date_then[1];
		return $diff_years;
	}	
	// This function returns the calendar name for the specified calendar
	// path.
	//
	// $cal_path	= The path to the calendar file.
	function getCalendarName($cal_path) {
		// At this point, just pull the name off the file.
		return str_replace(".ics", '', basename($cal_path));
	}	
	
	// takes iCalendar 2 day format and makes it into 3 characters
	// if $txt is true, it returns the 3 letters, otherwise it returns the
	// integer of that day; 0=Sun, 1=Mon, etc.
	function two2threeCharDays($day, $txt=true) {
		switch($day) {
			case 'SU': return ($txt ? 'sun' : '0');
			case 'MO': return ($txt ? 'mon' : '1');
			case 'TU': return ($txt ? 'tue' : '2');
			case 'WE': return ($txt ? 'wed' : '3');
			case 'TH': return ($txt ? 'thu' : '4');
			case 'FR': return ($txt ? 'fri' : '5');
			case 'SA': return ($txt ? 'sat' : '6');
		}
	}


	//TZIDs in calendars often contain leading information that should be stripped
	//Example: TZID=/mozilla.org/20050126_1/Europe/Berlin
	//Need to return the last part only
	function parse_tz($data){
		$fields = explode("/",$data);
		$tz = array_pop($fields);
		$tmp = array_pop($fields);
		if (isset($tmp) && $tmp != "") $tz = "$tmp/$tz";
		return $tz;
	}
	
	// calcTime calculates the unixtime of a new offset by comparing it to the current offset
	// $have is the current offset (ie, '-0500')
	// $want is the wanted offset (ie, '-0700')
	// $time is the unixtime relative to $have
	function calcTime($have, $want, $time) {
		// adam
		// this has been overridden, so teh parser will always return GMT time
		$want = "+0000";
		
		if ($have == 'none' || $want == 'none') return $time;
		$have_secs = $this->calcOffset($have);
		$want_secs = $this->calcOffset($want);
		$diff = $want_secs - $have_secs;
		$time += $diff;
		return $time;
	}
	
	// calcOffset takes an offset (ie, -0500) and returns it in the number of seconds
	function calcOffset($offset_str) {
		$sign = substr($offset_str, 0, 1);
		$hours = substr($offset_str, 1, 2);
		$mins = substr($offset_str, 3, 2);
		$secs = ((int)$hours * 3600) + ((int)$mins * 60);
		if ($sign == '-') $secs = 0 - $secs;
		return $secs;
	}

	
	function chooseOffset($time) {
		if (!isset($this->timezone)) $this->timezone = '';
		switch ($this->timezone) {
			case '':
				$offset = 'none';
				break;
			case 'Same as Server':
				$offset = date('O', $time);
				break;
			default:
				if (is_array($this->tz_array) && array_key_exists($this->timezone, $this->tz_array)) {
					$dlst = $this->isTimeDSTHuh($time, $this->tz_array[$timezone]);
					$offset = $this->tz_array[$timezone][$dlst];
				} else {
					$offset = '+0000';
				}
		}
		return $offset;
	}
	
	function isTimeDSTHuh($time, $tzarray){
		$tzm = new TimeZoneManager();
		return (int) $tzm->isTimeDSTHuh($time, $tzarray);
		return (int) date('I', $time);
	}


	// Returns an array of the date and time extracted from the data
	// passed in. This array contains (unixtime, date, time, allday).
	//
	// $data		= A string representing a date-time per RFC2445.
	// $property	= The property being examined, e.g. DTSTART, DTEND.
	// $field		= The full field being examined, e.g. DTSTART;TZID=US/Pacific
	function extractDateTime($data, $property, $field) {
		// Initialize values.
		unset($unixtime, $date, $time, $allday);
		
		$allday = ''; #suppress error on returning undef.
		$time = '';
		$date = '';
		$uniztime = '';
		// Check for zulu time.
		$zulu_time = false;
		if (substr($data,-1) == 'Z') $zulu_time = true;
		$data = str_replace('Z', '', $data);
		
		// Remove some substrings we don't want to look at.
		$data = str_replace('T', '', $data);
		$field = str_replace(';VALUE=DATE-TIME', '', $field); 
		
		// Extract date-only values.
		if ((preg_match('/^'.$property.';VALUE=DATE/i', $field)) || (ereg ('^([0-9]{4})([0-9]{2})([0-9]{2})$', $data)))  {
			// Pull out the date value. Minimum year is 1970.
			ereg ('([0-9]{4})([0-9]{2})([0-9]{2})', $data, $dt_check);
			if ($dt_check[1] < 1970) { 
				$data = '1971'.$dt_check[2].$dt_check[3];
			}
			
			// Set the values.
			$unixtime = strtotime($data);
			$date = date('Ymd', $unixtime);
			$allday = $data;
		}		
		// Extract date-time values.
		else {
			// Pull out the timezone, or use GMT if zulu time was indicated.
			if (preg_match('/^'.$property.';TZID=/i', $field)) {
				$tz_tmp = explode('=', $field);
				$tz_dt = $this->parse_tz($tz_tmp[1]);
				unset($tz_tmp);
			} elseif ($zulu_time) {
				$tz_dt = 'GMT';
			}
	
			// Pull out the date and time values. Minimum year is 1970.
			preg_match ('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})/', $data, $regs);
			if ($regs[1] < 1970) { 
				$regs[1] = '1971';
			}
			$date = $regs[1] . $regs[2] . $regs[3];
			$time = $regs[4] . $regs[5];
			$unixtime = mktime($regs[4], $regs[5], 0, $regs[2], $regs[3], $regs[1]);
	
			// Check for daylight savings time.
			$dlst = $this->isTimeDSTHuh($unixtime, $this->tz_array[$tz_dt]);
			$server_offset_tmp = $this->chooseOffset($unixtime);
			if (isset($tz_dt)) {
				if (array_key_exists($tz_dt, $this->tz_array)) {
					$offset_tmp = $this->tz_array[$tz_dt][$dlst];
				} else {
					$offset_tmp = '+0000';
				}
			} elseif (isset($calendar_tz)) {
				if (array_key_exists($calendar_tz, $this->tz_array)) {
					$offset_tmp = $this->tz_array[$calendar_tz][$dlst];
				} else {
					$offset_tmp = '+0000';
				}
			} else {
				$offset_tmp = $server_offset_tmp;
			}
			// Set the values.
			// adam
			
			$unixtime = $this->calcTime($offset_tmp, $server_offset_tmp, $unixtime);
			$date = date('Ymd', $unixtime);
			$time = date('Hi', $unixtime);

		}
		
		// Return the results.
		return array($unixtime, $date, $time, $allday);
	}

	function __construct(){
		$this->php_started = getmicrotime();
		$this->fillTime = "2300";
		$this->day_array = array();
		$this->overlap_array = array();
		$this->uid_counter = 0;
		$this->calnumber = 1;


		$getdate = date('Ymd', time());
		
		preg_match ("/([0-9]{4})([0-9]{2})([0-9]{2})/", $getdate, $day_array2);
		$this->this_day = $day_array2[3];
		$this->this_month = $day_array2[2];
		$this->this_year = $day_array2[1];
		
		$this->week_start_day = "Sunday";
		
		
		$this->tz_array = array();
		$this->tz_array['Africa/Addis_Ababa'] 	= array('+0300', '+0300');
		$this->tz_array['Africa/Algiers'] 		= array('+0100', '+0100');
		$this->tz_array['Africa/Asmera'] 			= array('+0300', '+0300');
		$this->tz_array['Africa/Bangui'] 			= array('+0100', '+0100');
		$this->tz_array['Africa/Blantyre'] 		= array('+0200', '+0200');
		$this->tz_array['Africa/Brazzaville'] 	= array('+0100', '+0100');
		$this->tz_array['Africa/Bujumbura'] 		= array('+0200', '+0200');
		$this->tz_array['Africa/Cairo'] 			= array('+0200', '+0300');
		$this->tz_array['Africa/Ceuta'] 			= array('+0100', '+0200');
		$this->tz_array['Africa/Dar_es_Salaam'] 	= array('+0300', '+0300');
		$this->tz_array['Africa/Djibouti']		= array('+0300', '+0300');
		$this->tz_array['Africa/Douala'] 			= array('+0100', '+0100');
		$this->tz_array['Africa/Gaborone'] 		= array('+0200', '+0200');
		$this->tz_array['Africa/Harare'] 			= array('+0200', '+0200');
		$this->tz_array['Africa/Johannesburg'] 	= array('+0200', '+0200');
		$this->tz_array['Africa/Kampala'] 		= array('+0300', '+0300');
		$this->tz_array['Africa/Khartoum'] 		= array('+0300', '+0300');
		$this->tz_array['Africa/Kigali'] 			= array('+0200', '+0200');
		$this->tz_array['Africa/Kinshasa'] 		= array('+0100', '+0100');
		$this->tz_array['Africa/Lagos'] 			= array('+0100', '+0100');
		$this->tz_array['Africa/Libreville'] 		= array('+0100', '+0100');
		$this->tz_array['Africa/Luanda'] 			= array('+0100', '+0100');
		$this->tz_array['Africa/Lubumbashi'] 		= array('+0200', '+0200');
		$this->tz_array['Africa/Lusaka'] 			= array('+0200', '+0200');
		$this->tz_array['Africa/Malabo'] 			= array('+0100', '+0100');
		$this->tz_array['Africa/Maputo'] 			= array('+0200', '+0200');
		$this->tz_array['Africa/Maseru'] 			= array('+0200', '+0200');
		$this->tz_array['Africa/Mbabane'] 		= array('+0200', '+0200');
		$this->tz_array['Africa/Mogadishu'] 		= array('+0300', '+0300');
		$this->tz_array['Africa/Nairobi'] 		= array('+0300', '+0300');
		$this->tz_array['Africa/Ndjamena'] 		= array('+0100', '+0100');
		$this->tz_array['Africa/Niamey'] 			= array('+0100', '+0100');
		$this->tz_array['Africa/Porto-Novo'] 		= array('+0100', '+0100');
		$this->tz_array['Africa/Tripoli'] 		= array('+0200', '+0200');
		$this->tz_array['Africa/Tunis'] 			= array('+0100', '+0100');
		$this->tz_array['Africa/Windhoek'] 		= array('+0200', '+0100');
		$this->tz_array['America/Adak'] 			= array('-1000', '-0900');
		$this->tz_array['America/Anchorage'] 		= array('-0900', '-0800');
		$this->tz_array['America/Anguilla'] 		= array('-0400', '-0400');
		$this->tz_array['America/Antigua'] 		= array('-0400', '-0400');
		$this->tz_array['America/Araguaina'] 		= array('-0200', '-0300');
		$this->tz_array['America/Aruba'] 			= array('-0400', '-0400');
		$this->tz_array['America/Asuncion'] 		= array('-0300', '-0400');
		$this->tz_array['America/Atka'] 			= array('-1000', '-0900');
		$this->tz_array['America/Barbados'] 		= array('-0400', '-0400');
		$this->tz_array['America/Belem'] 			= array('-0300', '-0300');
		$this->tz_array['America/Belize'] 		= array('-0600', '-0600');
		$this->tz_array['America/Boa_Vista'] 		= array('-0400', '-0400');
		$this->tz_array['America/Bogota'] 		= array('-0500', '-0500');
		$this->tz_array['America/Boise'] 			= array('-0700', '-0600');
		$this->tz_array['America/Buenos_Aires'] 	= array('-0300', '-0300');
		$this->tz_array['America/Cambridge_Bay'] 	= array('-0700', '-0600');
		$this->tz_array['America/Cancun'] 		= array('-0600', '-0500');
		$this->tz_array['America/Caracas'] 		= array('-0400', '-0400');
		$this->tz_array['America/Catamarca'] 		= array('-0300', '-0300');
		$this->tz_array['America/Cayenne'] 		= array('-0300', '-0300');
		$this->tz_array['America/Cayman'] = array('-0500', '-0500');
		$this->tz_array['America/Chicago'] = array('-0600', '-0500');
		$this->tz_array['America/Chihuahua'] = array('-0700', '-0600');
		$this->tz_array['America/Cordoba'] = array('-0300', '-0300');
		$this->tz_array['America/Costa_Rica'] = array('-0600', '-0600');
		$this->tz_array['America/Cuiaba'] = array('-0300', '-0400');
		$this->tz_array['America/Curacao'] = array('-0400', '-0400');
		$this->tz_array['America/Dawson'] = array('-0800', '-0700');
		$this->tz_array['America/Dawson_Creek'] = array('-0700', '-0700');
		$this->tz_array['America/Denver'] = array('-0700', '-0600');
		$this->tz_array['America/Detroit'] = array('-0500', '-0400');
		$this->tz_array['America/Dominica'] = array('-0400', '-0400');
		$this->tz_array['America/Edmonton'] = array('-0700', '-0600');
		$this->tz_array['America/Eirunepe'] = array('-0500', '-0500');
		$this->tz_array['America/El_Salvador'] = array('-0600', '-0600');
		$this->tz_array['America/Ensenada'] = array('-0800', '-0700');
		$this->tz_array['America/Fort_Wayne'] = array('-0500', '-0500');
		$this->tz_array['America/Fortaleza'] = array('-0300', '-0300');
		$this->tz_array['America/Glace_Bay'] = array('-0400', '-0300');
		$this->tz_array['America/Godthab'] = array('-0300', '-0200');
		$this->tz_array['America/Goose_Bay'] = array('-0400', '-0300');
		$this->tz_array['America/Grand_Turk'] = array('-0500', '-0400');
		$this->tz_array['America/Grenada'] = array('-0400', '-0400');
		$this->tz_array['America/Guadeloupe'] = array('-0400', '-0400');
		$this->tz_array['America/Guatemala'] = array('-0600', '-0600');
		$this->tz_array['America/Guayaquil'] = array('-0500', '-0500');
		$this->tz_array['America/Guyana'] = array('-0400', '-0400');
		$this->tz_array['America/Halifax'] = array('-0400', '-0300');
		$this->tz_array['America/Havana'] = array('-0500', '-0400');
		$this->tz_array['America/Hermosillo'] = array('-0700', '-0700');
		$this->tz_array['America/Indiana/Indianapolis'] = array('-0500', '-0500');
		$this->tz_array['America/Indiana/Knox'] = array('-0500', '-0500');
		$this->tz_array['America/Indiana/Marengo'] = array('-0500', '-0500');
		$this->tz_array['America/Indiana/Vevay'] = array('-0500', '-0500');
		$this->tz_array['America/Indianapolis'] = array('-0500', '-0500');
		$this->tz_array['America/Inuvik'] = array('-0700', '-0600');
		$this->tz_array['America/Iqaluit'] = array('-0500', '-0400');
		$this->tz_array['America/Jamaica'] = array('-0500', '-0500');
		$this->tz_array['America/Jujuy'] = array('-0300', '-0300');
		$this->tz_array['America/Juneau'] = array('-0900', '-0800');
		$this->tz_array['America/Kentucky/Louisville'] = array('-0500', '-0400');
		$this->tz_array['America/Kentucky/Monticello'] = array('-0500', '-0400');
		$this->tz_array['America/Knox_IN'] = array('-0500', '-0500');
		$this->tz_array['America/La_Paz'] = array('-0400', '-0400');
		$this->tz_array['America/Lima'] = array('-0500', '-0500');
		$this->tz_array['America/Los_Angeles'] = array('-0800', '-0700');
		$this->tz_array['America/Louisville'] = array('-0500', '-0400');
		$this->tz_array['America/Maceio'] = array('-0300', '-0300');
		$this->tz_array['America/Managua'] = array('-0600', '-0600');
		$this->tz_array['America/Manaus'] = array('-0400', '-0400');
		$this->tz_array['America/Martinique'] = array('-0400', '-0400');
		$this->tz_array['America/Mazatlan'] = array('-0700', '-0600');
		$this->tz_array['America/Mendoza'] = array('-0300', '-0300');
		$this->tz_array['America/Menominee'] = array('-0600', '-0500');
		$this->tz_array['America/Merida'] = array('-0600', '-0500');
		$this->tz_array['America/Mexico_City'] = array('-0600', '-0500');
		$this->tz_array['America/Miquelon'] = array('-0300', '-0200');
		$this->tz_array['America/Monterrey'] = array('-0600', '-0500');
		$this->tz_array['America/Montevideo'] = array('-0300', '-0300');
		$this->tz_array['America/Montreal'] = array('-0500', '-0400');
		$this->tz_array['America/Montserrat'] = array('-0400', '-0400');
		$this->tz_array['America/Nassau'] = array('-0500', '-0400');
		$this->tz_array['America/New_York'] = array('-0500', '-0400');
		$this->tz_array['America/Nipigon'] = array('-0500', '-0400');
		$this->tz_array['America/Nome'] = array('-0900', '-0800');
		$this->tz_array['America/Noronha'] = array('-0200', '-0200');
		$this->tz_array['America/Panama'] = array('-0500', '-0500');
		$this->tz_array['America/Pangnirtung'] = array('-0500', '-0400');
		$this->tz_array['America/Paramaribo'] = array('-0300', '-0300');
		$this->tz_array['America/Phoenix'] = array('-0700', '-0700');
		$this->tz_array['America/Port-au-Prince'] = array('-0500', '-0500');
		$this->tz_array['America/Port_of_Spain'] = array('-0400', '-0400');
		$this->tz_array['America/Porto_Acre'] = array('-0500', '-0500');
		$this->tz_array['America/Porto_Velho'] = array('-0400', '-0400');
		$this->tz_array['America/Puerto_Rico'] = array('-0400', '-0400');
		$this->tz_array['America/Rainy_River'] = array('-0600', '-0500');
		$this->tz_array['America/Rankin_Inlet'] = array('-0600', '-0500');
		$this->tz_array['America/Recife'] = array('-0300', '-0300');
		$this->tz_array['America/Regina'] = array('-0600', '-0600');
		$this->tz_array['America/Rio_Branco'] = array('-0500', '-0500');
		$this->tz_array['America/Rosario'] = array('-0300', '-0300');
		$this->tz_array['America/Santiago'] = array('-0300', '-0400');
		$this->tz_array['America/Santo_Domingo'] = array('-0400', '-0400');
		$this->tz_array['America/Sao_Paulo'] = array('-0200', '-0300');
		$this->tz_array['America/Scoresbysund'] = array('-0100', '+0000');
		$this->tz_array['America/Shiprock'] = array('-0700', '-0600');
		$this->tz_array['America/St_Johns'] = array('-031800', '-021800');
		$this->tz_array['America/St_Kitts'] = array('-0400', '-0400');
		$this->tz_array['America/St_Lucia'] = array('-0400', '-0400');
		$this->tz_array['America/St_Thomas'] = array('-0400', '-0400');
		$this->tz_array['America/St_Vincent'] = array('-0400', '-0400');
		$this->tz_array['America/Swift_Current'] = array('-0600', '-0600');
		$this->tz_array['America/Tegucigalpa'] = array('-0600', '-0600');
		$this->tz_array['America/Thule'] = array('-0400', '-0300');
		$this->tz_array['America/Thunder_Bay'] = array('-0500', '-0400');
		$this->tz_array['America/Tijuana'] = array('-0800', '-0700');
		$this->tz_array['America/Tortola'] = array('-0400', '-0400');
		$this->tz_array['America/Vancouver'] = array('-0800', '-0700');
		$this->tz_array['America/Virgin'] = array('-0400', '-0400');
		$this->tz_array['America/Whitehorse'] = array('-0800', '-0700');
		$this->tz_array['America/Winnipeg'] = array('-0600', '-0500');
		$this->tz_array['America/Yakutat'] = array('-0900', '-0800');
		$this->tz_array['America/Yellowknife'] = array('-0700', '-0600');
		$this->tz_array['Antarctica/Casey'] = array('+0800', '+0800');
		$this->tz_array['Antarctica/Davis'] = array('+0700', '+0700');
		$this->tz_array['Antarctica/DumontDUrville'] = array('+1000', '+1000');
		$this->tz_array['Antarctica/Mawson'] = array('+0600', '+0600');
		$this->tz_array['Antarctica/McMurdo'] = array('+1300', '+1200');
		$this->tz_array['Antarctica/Palmer'] = array('-0300', '-0400');
		$this->tz_array['Antarctica/South_Pole'] = array('+1300', '+1200');
		$this->tz_array['Antarctica/Syowa'] = array('+0300', '+0300');
		$this->tz_array['Antarctica/Vostok'] = array('+0600', '+0600');
		$this->tz_array['Arctic/Longyearbyen'] = array('+0100', '+0200');
		$this->tz_array['Asia/Aden'] = array('+0300', '+0300');
		$this->tz_array['Asia/Almaty'] = array('+0600', '+0700');
		$this->tz_array['Asia/Amman'] = array('+0200', '+0300');
		$this->tz_array['Asia/Anadyr'] = array('+1200', '+1300');
		$this->tz_array['Asia/Aqtau'] = array('+0400', '+0500');
		$this->tz_array['Asia/Aqtobe'] = array('+0500', '+0600');
		$this->tz_array['Asia/Ashgabat'] = array('+0500', '+0500');
		$this->tz_array['Asia/Ashkhabad'] = array('+0500', '+0500');
		$this->tz_array['Asia/Baghdad'] = array('+0300', '+0400');
		$this->tz_array['Asia/Bahrain'] = array('+0300', '+0300');
		$this->tz_array['Asia/Baku'] = array('+0400', '+0500');
		$this->tz_array['Asia/Bangkok'] = array('+0700', '+0700');
		$this->tz_array['Asia/Beirut'] = array('+0200', '+0300');
		$this->tz_array['Asia/Bishkek'] = array('+0500', '+0600');
		$this->tz_array['Asia/Brunei'] = array('+0800', '+0800');
		$this->tz_array['Asia/Calcutta'] = array('+051800', '+051800');
		$this->tz_array['Asia/Chungking'] = array('+0800', '+0800');
		$this->tz_array['Asia/Colombo'] = array('+0600', '+0600');
		$this->tz_array['Asia/Dacca'] = array('+0600', '+0600');
		$this->tz_array['Asia/Damascus'] = array('+0200', '+0300');
		$this->tz_array['Asia/Dhaka'] = array('+0600', '+0600');
		$this->tz_array['Asia/Dili'] = array('+0900', '+0900');
		$this->tz_array['Asia/Dubai'] = array('+0400', '+0400');
		$this->tz_array['Asia/Dushanbe'] = array('+0500', '+0500');
		$this->tz_array['Asia/Gaza'] = array('+0200', '+0300');
		$this->tz_array['Asia/Harbin'] = array('+0800', '+0800');
		$this->tz_array['Asia/Hong_Kong'] = array('+0800', '+0800');
		$this->tz_array['Asia/Hovd'] = array('+0700', '+0700');
		$this->tz_array['Asia/Irkutsk'] = array('+0800', '+0900');
		$this->tz_array['Asia/Istanbul'] = array('+0200', '+0300');
		$this->tz_array['Asia/Jakarta'] = array('+0700', '+0700');
		$this->tz_array['Asia/Jayapura'] = array('+0900', '+0900');
		$this->tz_array['Asia/Jerusalem'] = array('+0200', '+0300');
		$this->tz_array['Asia/Kabul'] = array('+041800', '+041800');
		$this->tz_array['Asia/Kamchatka'] = array('+1200', '+1300');
		$this->tz_array['Asia/Karachi'] = array('+0500', '+0500');
		$this->tz_array['Asia/Kashgar'] = array('+0800', '+0800');
		$this->tz_array['Asia/Katmandu'] = array('+052700', '+052700');
		$this->tz_array['Asia/Krasnoyarsk'] = array('+0700', '+0800');
		$this->tz_array['Asia/Kuala_Lumpur'] = array('+0800', '+0800');
		$this->tz_array['Asia/Kuching'] = array('+0800', '+0800');
		$this->tz_array['Asia/Kuwait'] = array('+0300', '+0300');
		$this->tz_array['Asia/Macao'] = array('+0800', '+0800');
		$this->tz_array['Asia/Magadan'] = array('+1100', '+1200');
		$this->tz_array['Asia/Manila'] = array('+0800', '+0800');
		$this->tz_array['Asia/Muscat'] = array('+0400', '+0400');
		$this->tz_array['Asia/Nicosia'] = array('+0200', '+0300');
		$this->tz_array['Asia/Novosibirsk'] = array('+0600', '+0700');
		$this->tz_array['Asia/Omsk'] = array('+0600', '+0700');
		$this->tz_array['Asia/Phnom_Penh'] = array('+0700', '+0700');
		$this->tz_array['Asia/Pyongyang'] = array('+0900', '+0900');
		$this->tz_array['Asia/Qatar'] = array('+0300', '+0300');
		$this->tz_array['Asia/Rangoon'] = array('+061800', '+061800');
		$this->tz_array['Asia/Riyadh'] = array('+0300', '+0300');
		$this->tz_array['Asia/Riyadh87'] = array('+03424', '+03424');
		$this->tz_array['Asia/Riyadh88'] = array('+03424', '+03424');
		$this->tz_array['Asia/Riyadh89'] = array('+03424', '+03424');
		$this->tz_array['Asia/Saigon'] = array('+0700', '+0700');
		$this->tz_array['Asia/Samarkand'] = array('+0500', '+0500');
		$this->tz_array['Asia/Seoul'] = array('+0900', '+0900');
		$this->tz_array['Asia/Shanghai'] = array('+0800', '+0800');
		$this->tz_array['Asia/Singapore'] = array('+0800', '+0800');
		$this->tz_array['Asia/Taipei'] = array('+0800', '+0800');
		$this->tz_array['Asia/Tashkent'] = array('+0500', '+0500');
		$this->tz_array['Asia/Tbilisi'] = array('+0400', '+0500');
		$this->tz_array['Asia/Tehran'] = array('+031800', '+041800');
		$this->tz_array['Asia/Tel_Aviv'] = array('+0200', '+0300');
		$this->tz_array['Asia/Thimbu'] = array('+0600', '+0600');
		$this->tz_array['Asia/Thimphu'] = array('+0600', '+0600');
		$this->tz_array['Asia/Tokyo'] = array('+0900', '+0900');
		$this->tz_array['Asia/Ujung_Pandang'] = array('+0800', '+0800');
		$this->tz_array['Asia/Ulaanbaatar'] = array('+0800', '+0800');
		$this->tz_array['Asia/Ulan_Bator'] = array('+0800', '+0800');
		$this->tz_array['Asia/Urumqi'] = array('+0800', '+0800');
		$this->tz_array['Asia/Vientiane'] = array('+0700', '+0700');
		$this->tz_array['Asia/Vladivostok'] = array('+1000', '+1100');
		$this->tz_array['Asia/Yakutsk'] = array('+0900', '+1000');
		$this->tz_array['Asia/Yekaterinburg'] = array('+0500', '+0600');
		$this->tz_array['Asia/Yerevan'] = array('+0400', '+0500');
		$this->tz_array['Atlantic/Azores'] = array('-0100', '+0000');
		$this->tz_array['Atlantic/Bermuda'] = array('-0400', '-0300');
		$this->tz_array['Atlantic/Canary'] = array('+0000', '+0100');
		$this->tz_array['Atlantic/Cape_Verde'] = array('-0100', '-0100');
		$this->tz_array['Atlantic/Faeroe'] = array('+0000', '+0100');
		$this->tz_array['Atlantic/Jan_Mayen'] = array('-0100', '-0100');
		$this->tz_array['Atlantic/Madeira'] = array('+0000', '+0100');
		$this->tz_array['Atlantic/South_Georgia'] = array('-0200', '-0200');
		$this->tz_array['Atlantic/Stanley'] = array('-0300', '-0400');
		$this->tz_array['Australia/ACT'] = array('+1000', '+1100');
		$this->tz_array['Australia/Adelaide'] = array('+101800', '+091800');
		$this->tz_array['Australia/Brisbane'] = array('+1000', '+1000');
		$this->tz_array['Australia/Broken_Hill'] = array('+101800', '+091800');
		$this->tz_array['Australia/Canberra'] = array('+1100', '+1000');
		$this->tz_array['Australia/Darwin'] = array('+091800', '+091800');
		$this->tz_array['Australia/Hobart'] = array('+1100', '+1000');
		$this->tz_array['Australia/LHI'] = array('+1100', '+101800');
		$this->tz_array['Australia/Lindeman'] = array('+1000', '+1000');
		$this->tz_array['Australia/Lord_Howe'] = array('+1100', '+101800');
		$this->tz_array['Australia/Melbourne'] = array('+1000', '+1100');
		$this->tz_array['Australia/NSW'] = array('+1000', '+1100');
		$this->tz_array['Australia/North'] = array('+091800', '+091800');
		$this->tz_array['Australia/Perth'] = array('+0800', '+0800');
		$this->tz_array['Australia/Queensland'] = array('+1000', '+1000');
		$this->tz_array['Australia/South'] = array('+101800', '+091800');
		$this->tz_array['Australia/Sydney'] = array('+1000', '+1100');
		$this->tz_array['Australia/Tasmania'] = array('+1000', '+1100');
		$this->tz_array['Australia/Victoria'] = array('+1000', '+1100');
		$this->tz_array['Australia/West'] = array('+0800', '+0800');
		$this->tz_array['Australia/Yancowinna'] = array('+101800', '+091800');
		$this->tz_array['Brazil/Acre'] = array('-0500', '-0500');
		$this->tz_array['Brazil/DeNoronha'] = array('-0200', '-0200');
		$this->tz_array['Brazil/East'] = array('-0200', '-0300');
		$this->tz_array['Brazil/West'] = array('-0400', '-0400');
		$this->tz_array['CET'] = array('+0100', '+0200');
		$this->tz_array['CST6CDT'] = array('-0600', '-0500');
		$this->tz_array['Canada/Atlantic'] = array('-0400', '-0300');
		$this->tz_array['Canada/Central'] = array('-0600', '-0500');
		$this->tz_array['Canada/East-Saskatchewan'] = array('-0600', '-0600');
		$this->tz_array['Canada/Eastern'] = array('-0500', '-0400');
		$this->tz_array['Canada/Mountain'] = array('-0700', '-0600');
		$this->tz_array['Canada/Newfoundland'] = array('-031800', '-021800');
		$this->tz_array['Canada/Pacific'] = array('-0800', '-0700');
		$this->tz_array['Canada/Saskatchewan'] = array('-0600', '-0600');
		$this->tz_array['Canada/Yukon'] = array('-0800', '-0700');
		$this->tz_array['Chile/Continental'] = array('-0300', '-0400');
		$this->tz_array['Chile/EasterIsland'] = array('-0500', '-0600');
		$this->tz_array['Cuba'] = array('-0500', '-0400');
		$this->tz_array['EET'] = array('+0200', '+0300');
		$this->tz_array['EST'] = array('-0500', '-0500');
		$this->tz_array['EST5EDT'] = array('-0500', '-0400');
		$this->tz_array['Egypt'] = array('+0200', '+0300');
		$this->tz_array['Eire'] = array('+0000', '+0100');
		$this->tz_array['Etc/GMT+1'] = array('-0100', '-0100');
		$this->tz_array['Etc/GMT+10'] = array('-1000', '-1000');
		$this->tz_array['Etc/GMT+11'] = array('-1100', '-1100');
		$this->tz_array['Etc/GMT+12'] = array('-1200', '-1200');
		$this->tz_array['Etc/GMT+2'] = array('-0200', '-0200');
		$this->tz_array['Etc/GMT+3'] = array('-0300', '-0300');
		$this->tz_array['Etc/GMT+4'] = array('-0400', '-0400');
		$this->tz_array['Etc/GMT+5'] = array('-0500', '-0500');
		$this->tz_array['Etc/GMT+6'] = array('-0600', '-0600');
		$this->tz_array['Etc/GMT+7'] = array('-0700', '-0700');
		$this->tz_array['Etc/GMT+8'] = array('-0800', '-0800');
		$this->tz_array['Etc/GMT+9'] = array('-0900', '-0900');
		$this->tz_array['GMT'] = array('+0000', '+0000');
		$this->tz_array['Etc/GMT-1'] = array('+0100', '+0100');
		$this->tz_array['Etc/GMT-10'] = array('+1000', '+1000');
		$this->tz_array['Etc/GMT-11'] = array('+1100', '+1100');
		$this->tz_array['Etc/GMT-12'] = array('+1200', '+1200');
		$this->tz_array['Etc/GMT-13'] = array('+1300', '+1300');
		$this->tz_array['Etc/GMT-14'] = array('+1400', '+1400');
		$this->tz_array['Etc/GMT-2'] = array('+0200', '+0200');
		$this->tz_array['Etc/GMT-3'] = array('+0300', '+0300');
		$this->tz_array['Etc/GMT-4'] = array('+0400', '+0400');
		$this->tz_array['Etc/GMT-5'] = array('+0500', '+0500');
		$this->tz_array['Etc/GMT-6'] = array('+0600', '+0600');
		$this->tz_array['Etc/GMT-7'] = array('+0700', '+0700');
		$this->tz_array['Etc/GMT-8'] = array('+0800', '+0800');
		$this->tz_array['Etc/GMT-9'] = array('+0900', '+0900');
		$this->tz_array['Europe/Amsterdam'] = array('+0100', '+0200');
		$this->tz_array['Europe/Andorra'] = array('+0100', '+0200');
		$this->tz_array['Europe/Athens'] = array('+0200', '+0300');
		$this->tz_array['Europe/Belfast'] = array('+0000', '+0100');
		$this->tz_array['Europe/Belgrade'] = array('+0100', '+0200');
		$this->tz_array['Europe/Berlin'] = array('+0100', '+0200');
		$this->tz_array['Europe/Bratislava'] = array('+0100', '+0200');
		$this->tz_array['Europe/Brussels'] = array('+0100', '+0200');
		$this->tz_array['Europe/Bucharest'] = array('+0200', '+0300');
		$this->tz_array['Europe/Budapest'] = array('+0100', '+0200');
		$this->tz_array['Europe/Chisinau'] = array('+0200', '+0300');
		$this->tz_array['Europe/Copenhagen'] = array('+0100', '+0200');
		$this->tz_array['Europe/Dublin'] = array('+0000', '+0100');
		$this->tz_array['Europe/Gibraltar'] = array('+0100', '+0200');
		$this->tz_array['Europe/Helsinki'] = array('+0200', '+0300');
		$this->tz_array['Europe/Istanbul'] = array('+0200', '+0300');
		$this->tz_array['Europe/Kaliningrad'] = array('+0200', '+0300');
		$this->tz_array['Europe/Kiev'] = array('+0200', '+0300');
		$this->tz_array['Europe/Lisbon'] = array('+0000', '+0100');
		$this->tz_array['Europe/Ljubljana'] = array('+0100', '+0200');
		$this->tz_array['Europe/London'] = array('+0000', '+0100');
		$this->tz_array['Europe/Luxembourg'] = array('+0100', '+0200');
		$this->tz_array['Europe/Madrid'] = array('+0100', '+0200');
		$this->tz_array['Europe/Malta'] = array('+0100', '+0200');
		$this->tz_array['Europe/Minsk'] = array('+0200', '+0300');
		$this->tz_array['Europe/Monaco'] = array('+0100', '+0200');
		$this->tz_array['Europe/Moscow'] = array('+0300', '+0400');
		$this->tz_array['Europe/Nicosia'] = array('+0200', '+0300');
		$this->tz_array['Europe/Oslo'] = array('+0100', '+0200');
		$this->tz_array['Europe/Paris'] = array('+0100', '+0200');
		$this->tz_array['Europe/Prague'] = array('+0100', '+0200');
		$this->tz_array['Europe/Riga'] = array('+0200', '+0300');
		$this->tz_array['Europe/Rome'] = array('+0100', '+0200');
		$this->tz_array['Europe/Samara'] = array('+0400', '+0500');
		$this->tz_array['Europe/San_Marino'] = array('+0100', '+0200');
		$this->tz_array['Europe/Sarajevo'] = array('+0100', '+0200');
		$this->tz_array['Europe/Simferopol'] = array('+0200', '+0300');
		$this->tz_array['Europe/Skopje'] = array('+0100', '+0200');
		$this->tz_array['Europe/Sofia'] = array('+0200', '+0300');
		$this->tz_array['Europe/Stockholm'] = array('+0100', '+0200');
		$this->tz_array['Europe/Tallinn'] = array('+0200', '+0200');
		$this->tz_array['Europe/Tirane'] = array('+0100', '+0200');
		$this->tz_array['Europe/Tiraspol'] = array('+0200', '+0300');
		$this->tz_array['Europe/Uzhgorod'] = array('+0200', '+0300');
		$this->tz_array['Europe/Vaduz'] = array('+0100', '+0200');
		$this->tz_array['Europe/Vatican'] = array('+0100', '+0200');
		$this->tz_array['Europe/Vienna'] = array('+0100', '+0200');
		$this->tz_array['Europe/Vilnius'] = array('+0200', '+0200');
		$this->tz_array['Europe/Warsaw'] = array('+0100', '+0200');
		$this->tz_array['Europe/Zagreb'] = array('+0100', '+0200');
		$this->tz_array['Europe/Zaporozhye'] = array('+0200', '+0300');
		$this->tz_array['Europe/Zurich'] = array('+0100', '+0200');
		$this->tz_array['GB'] = array('+0000', '+0100');
		$this->tz_array['GB-Eire'] = array('+0000', '+0100');
		$this->tz_array['HST'] = array('-1000', '-1000');
		$this->tz_array['Hongkong'] = array('+0800', '+0800');
		$this->tz_array['Indian/Antananarivo'] = array('+0300', '+0300');
		$this->tz_array['Indian/Chagos'] = array('+0500', '+0500');
		$this->tz_array['Indian/Christmas'] = array('+0700', '+0700');
		$this->tz_array['Indian/Cocos'] = array('+061800', '+061800');
		$this->tz_array['Indian/Comoro'] = array('+0300', '+0300');
		$this->tz_array['Indian/Kerguelen'] = array('+0500', '+0500');
		$this->tz_array['Indian/Mahe'] = array('+0400', '+0400');
		$this->tz_array['Indian/Maldives'] = array('+0500', '+0500');
		$this->tz_array['Indian/Mauritius'] = array('+0400', '+0400');
		$this->tz_array['Indian/Mayotte'] = array('+0300', '+0300');
		$this->tz_array['Indian/Reunion'] = array('+0400', '+0400');
		$this->tz_array['Iran'] = array('+031800', '+041800');
		$this->tz_array['Israel'] = array('+0200', '+0300');
		$this->tz_array['Jamaica'] = array('-0500', '-0500');
		$this->tz_array['Japan'] = array('+0900', '+0900');
		$this->tz_array['Kwajalein'] = array('+1200', '+1200');
		$this->tz_array['Libya'] = array('+0200', '+0200');
		$this->tz_array['MET'] = array('+0100', '+0200');
		$this->tz_array['MST'] = array('-0700', '-0700');
		$this->tz_array['MST7MDT'] = array('-0700', '-0600');
		$this->tz_array['Mexico/BajaNorte'] = array('-0800', '-0700');
		$this->tz_array['Mexico/BajaSur'] = array('-0700', '-0600');
		$this->tz_array['Mexico/General'] = array('-0600', '-0500');
		$this->tz_array['Mideast/Riyadh87'] = array('+03424', '+03424');
		$this->tz_array['Mideast/Riyadh88'] = array('+03424', '+03424');
		$this->tz_array['Mideast/Riyadh89'] = array('+03424', '+03424');
		$this->tz_array['NZ'] = array('+1300', '+1200');
		$this->tz_array['NZ-CHAT'] = array('+132700', '+122700');
		$this->tz_array['Navajo'] = array('-0700', '-0600');
		$this->tz_array['PRC'] = array('+0800', '+0800');
		$this->tz_array['PST8PDT'] = array('-0800', '-0700');
		$this->tz_array['Pacific/Apia'] = array('-1100', '-1100');
		$this->tz_array['Pacific/Auckland'] = array('+1300', '+1200');
		$this->tz_array['Pacific/Chatham'] = array('+132700', '+122700');
		$this->tz_array['Pacific/Easter'] = array('-0500', '-0600');
		$this->tz_array['Pacific/Efate'] = array('+1100', '+1100');
		$this->tz_array['Pacific/Enderbury'] = array('+1300', '+1300');
		$this->tz_array['Pacific/Fakaofo'] = array('-1000', '-1000');
		$this->tz_array['Pacific/Fiji'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Funafuti'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Galapagos'] = array('-0600', '-0600');
		$this->tz_array['Pacific/Gambier'] = array('-0900', '-0900');
		$this->tz_array['Pacific/Guadalcanal'] = array('+1100', '+1100');
		$this->tz_array['Pacific/Guam'] = array('+1000', '+1000');
		$this->tz_array['Pacific/Honolulu'] = array('-1000', '-1000');
		$this->tz_array['Pacific/Johnston'] = array('-1000', '-1000');
		$this->tz_array['Pacific/Kiritimati'] = array('+1400', '+1400');
		$this->tz_array['Pacific/Kosrae'] = array('+1100', '+1100');
		$this->tz_array['Pacific/Kwajalein'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Majuro'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Marquesas'] = array('-091800', '-091800');
		$this->tz_array['Pacific/Midway'] = array('-1100', '-1100');
		$this->tz_array['Pacific/Nauru'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Niue'] = array('-1100', '-1100');
		$this->tz_array['Pacific/Norfolk'] = array('+111800', '+111800');
		$this->tz_array['Pacific/Noumea'] = array('+1100', '+1100');
		$this->tz_array['Pacific/Pago_Pago'] = array('-1100', '-1100');
		$this->tz_array['Pacific/Palau'] = array('+0900', '+0900');
		$this->tz_array['Pacific/Pitcairn'] = array('-0800', '-0800');
		$this->tz_array['Pacific/Ponape'] = array('+1100', '+1100');
		$this->tz_array['Pacific/Port_Moresby'] = array('+1000', '+1000');
		$this->tz_array['Pacific/Rarotonga'] = array('-1000', '-1000');
		$this->tz_array['Pacific/Saipan'] = array('+1000', '+1000');
		$this->tz_array['Pacific/Samoa'] = array('-1100', '-1100');
		$this->tz_array['Pacific/Tahiti'] = array('-1000', '-1000');
		$this->tz_array['Pacific/Tarawa'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Tongatapu'] = array('+1300', '+1300');
		$this->tz_array['Pacific/Truk'] = array('+1000', '+1000');
		$this->tz_array['Pacific/Wake'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Wallis'] = array('+1200', '+1200');
		$this->tz_array['Pacific/Yap'] = array('+1000', '+1000');
		$this->tz_array['Poland'] = array('+0100', '+0200');
		$this->tz_array['Portugal'] = array('+0000', '+0100');
		$this->tz_array['ROC'] = array('+0800', '+0800');
		$this->tz_array['ROK'] = array('+0900', '+0900');
		$this->tz_array['Singapore'] = array('+0800', '+0800');
		$this->tz_array['SystemV/AST4'] = array('-0400', '-0400');
		$this->tz_array['SystemV/AST4ADT'] = array('-0400', '-0300');
		$this->tz_array['SystemV/CST6'] = array('-0600', '-0600');
		$this->tz_array['SystemV/CST6CDT'] = array('-0600', '-0500');
		$this->tz_array['SystemV/EST5'] = array('-0500', '-0500');
		$this->tz_array['SystemV/EST5EDT'] = array('-0500', '-0400');
		$this->tz_array['SystemV/HST10'] = array('-1000', '-1000');
		$this->tz_array['SystemV/MST7'] = array('-0700', '-0700');
		$this->tz_array['SystemV/MST7MDT'] = array('-0700', '-0600');
		$this->tz_array['SystemV/PST8'] = array('-0800', '-0800');
		$this->tz_array['SystemV/PST8PDT'] = array('-0800', '-0700');
		$this->tz_array['SystemV/YST9'] = array('-0900', '-0900');
		$this->tz_array['SystemV/YST9YDT'] = array('-0900', '-0800');
		$this->tz_array['Turkey'] = array('+0200', '+0300');
		$this->tz_array['US/Alaska'] = array('-0900', '-0800');
		$this->tz_array['US/Aleutian'] = array('-1000', '-0900');
		$this->tz_array['US/Arizona'] = array('-0700', '-0700');
		$this->tz_array['US/Central'] = array('-0600', '-0500');
		$this->tz_array['US/East-Indiana'] = array('-0500', '-0500');
		$this->tz_array['US/Eastern'] = array('-0500', '-0400');
		$this->tz_array['US/Hawaii'] = array('-1000', '-1000');
		$this->tz_array['US/Indiana-Starke'] = array('-0500', '-0500');
		$this->tz_array['US/Michigan'] = array('-0500', '-0400');
		$this->tz_array['US/Mountain'] = array('-0700', '-0600');
		$this->tz_array['US/Pacific'] = array('-0800', '-0700');
		$this->tz_array['US/Samoa'] = array('-1100', '-1100');
		$this->tz_array['W-SU'] = array('+0300', '+0400');
		$this->tz_array['WET'] = array('+0000', '+0100');
		
	}
	
	function getTZInfo($index){
		if(isset($this->tz_array[$index])){
			return $this->tz_array[$index];
		}else{
			return false;
		}
	}
	
	function parseFile($cal_filelist){
		$ret = array();
		foreach ($cal_filelist as $cal_key=>$filename) {
			$actual_calname = $this->getCalendarName($filename);
			// Let's see if we're doing a webcal
			$is_webcal = FALSE;
			if (substr($filename, 0, 7) == 'http://' || substr($filename, 0, 8) == 'https://' || substr($filename, 0, 9) == 'webcal://') {
				$is_webcal = TRUE;
				$cal_webcalPrefix = str_replace('http://','webcal://',$filename);
				$cal_httpPrefix = str_replace('webcal://','http://',$filename);
				$cal_httpsPrefix = str_replace('webcal://','https://',$filename);
				$cal_httpsPrefix = str_replace('http://','https://',$cal_httpsPrefix);
				$filename = $cal_httpPrefix;
//				$master_array['-4'][$this->calnumber]['webcal'] = 'yes';
				$actual_mtime = time();
			} else {
				$actual_mtime = @filemtime($filename);
			}
			
			$file_contents = file_get_contents($filename);
			if ($file_contents === FALSE){
				throw new Exception("Can't open file:" . $filename);
//					exit(error($lang['l_error_cantopen'], $filename));
			}
			
			$ret[] = $this->parse($file_contents);
		}
		return $ret;
	}
	
	function parse($file_contents){
		$parse_file = true;
			
		// We don't know the name of the calendar yet, lets wait till
		// we parse for it
		$actual_calname = "Unknown";
		
		// get the lines of the file
		$lines = explode("\n", $file_contents);
		
		if(count($lines) == 0){
			throw new Exception("Invalid icalendar file:" . $filename);
		}
		
		$nextline = $lines[0];
		if (trim($nextline) != 'BEGIN:VCALENDAR'){
			throw new Exception("Invalid icalendar file. Expected \"BEGIN:VCALENDAR\"; Got " . $nextline);
//					exit(error($lang['l_error_invalidcal'], $filename));
		}
		// remove the first line
		// since we've parsed it and verified it's a vcalendar
		array_splice($lines, 0, 1);
		
		// Set default calendar name - can be overridden by X-WR-CALNAME
		$calendar_name = $actual_calname;
		$master_array['calendar_name'] 	= $calendar_name;
		
		// read file in line by line
		// XXX end line is skipped because of the 1-line readahead
		for($line_num=0;$line_num < count($lines);$line_num++) {
			$nextline = $lines[$line_num];
			$line = $nextline;
			while($line_num < count($lines)-1 && substr($lines[$line_num+1],0,1) == " "){
				$line_num ++;
				$nextline = $lines[$line_num];
				$line = $line . substr($nextline, 1);
			}
			$line = trim($line);
			
			switch ($line) {
				case 'BEGIN:VEVENT':
					// each of these vars were being set to an empty string
					unset (
						$start_time, $end_time, $start_date, $end_date, $summary, 
						$allday_start, $allday_end, $start, $end, $the_duration, 
						$beginning, $rrule_array, $start_of_vevent, $description, $url, 
						$valarm_description, $start_unixtime, $end_unixtime, $display_end_tmp, $end_time_tmp1, 
						$recurrence_id, $uid, $class, $location, $rrule, $abs_until, $until_check,
						$until, $bymonth, $byday, $bymonthday, $byweek, $byweekno, 
						$byminute, $byhour, $bysecond, $byyearday, $bysetpos, $wkst,
						$interval, $number
					);
						
					$except_dates 	= array();
					$except_times 	= array();
					$bymonth	 	= array();
					$bymonthday 	= array();
					$first_duration = TRUE;
					$count 			= 1000000;
					$valarm_set 	= FALSE;
					$attendee		= array();
					$organizer		= array();
					
					break;
				
				case 'END:VEVENT':
					
					if (!isset($url)) $url = '';
					if (!isset($type)) $type = '';
					
					// Handle DURATION
					if (!isset($end_unixtime) && isset($the_duration)) {
						$end_unixtime 	= $start_unixtime + $the_duration;
						$end_time 	= date ('Hi', $end_unixtime);
					}else if(!isset($end_unixtime)){
						$end_unixtime 	= $start_unixtime + (60*60); // 1 hour
						$end_time 	= date ('Hi', $end_unixtime);
					}
						
//					// CLASS support
//					if (isset($class)) {
//						if ($class == 'PRIVATE') {
//							$summary ='**PRIVATE**';
//							$description ='**PRIVATE**';
//						} elseif ($class == 'CONFIDENTIAL') {
//							$summary ='**CONFIDENTIAL**';
//							$description ='**CONFIDENTIAL**';
//						}
//					}	 
					
					// make sure we have some value for $uid
					if (!isset($uid)) {
						$uid = $this->uid_counter;
						$this->uid_counter++;
						$uid_valid = false;
					} else {
						$uid_valid = true;
					}
					
					if ($uid_valid && isset($processed[$uid]) && isset($recurrence_id['date'])) {
						
						$old_start_date = $processed[$uid][0];
						$old_start_time = $processed[$uid][1];
						if (isset($recurrence_id['value']) && $recurrence_id['value'] == 'DATE') $old_start_time = '-1';
						$start_date_tmp = $recurrence_id['date'];
						if (!isset($start_date)) $start_date = $old_start_date;
						if (!isset($start_time)) $start_time = $master_array["EventList"][$uid]['event_start'];
						if (!isset($start_unixtime)) $start_unixtime = $master_array["EventList"][$uid]['start_unixtime'];
						if (!isset($end_unixtime)) $end_unixtime = $master_array["EventList"][$uid]['end_unixtime'];
						if (!isset($end_time)) $end_time = $master_array["EventList"][$uid]['event_end'];
						if (!isset($summary)) $summary = $master_array["EventList"][$uid]['event_text'];
						if (!isset($length)) $length = $master_array["EventList"][$uid]['event_length'];
						if (!isset($description)) $description = $master_array["EventList"][$uid]['description'];
						if (!isset($location)) $location = $master_array["EventList"][$uid]['location'];
						if (!isset($organizer)) $organizer = $master_array["EventList"][$uid]['organizer'];
						if (!isset($status)) $status = $master_array["EventList"][$uid]['status'];
						if (!isset($attendee)) $attendee = $master_array["EventList"][$uid]['attendee'];
						if (!isset($url)) $url = $master_array["EventList"][$uid]['url'];
						if (isset($master_array["EventList"][$uid])) {
							unset($master_array["EventList"][$uid]);  // SJBO added $uid twice here
							if (sizeof($master_array["EventList"]) == 0) {
								unset($master_array["EventList"]);
							}
						}
						
						$write_processed = false;
					} else {
						$write_processed = true;
					}
					
					if (!isset($summary)) 		$summary = '';
					if (!isset($description)) 	$description = '';
					if (!isset($status)) 		$status = '';
					if (!isset($class)) 		$class = '';
					if (!isset($location)) 		$location = '';
					
					if (isset($start_time) && isset($end_time)) {
						// Mozilla style all-day events or just really long events
						if (($end_time - $start_time) > 2345) {
							$allday_start = $start_date;
							$allday_end = ($start_date + 1);
						}
					}
					if (isset($start_unixtime,$end_unixtime) && date('Ymd',$start_unixtime) != date('Ymd',$end_unixtime)) {
						$spans_day = true;
						$bleed_check = (($start_unixtime - $end_unixtime) < (60*60*24)) ? '-1' : '0';
					} else {
						$spans_day = false;
						$bleed_check = 0;
					}
					if (isset($start_time) && $start_time != '') {
						preg_match ('/([0-9]{2})([0-9]{2})/', $start_time, $time);
						preg_match ('/([0-9]{2})([0-9]{2})/', $end_time, $time2);
						if (isset($start_unixtime) && isset($end_unixtime)) {
							$length = $end_unixtime - $start_unixtime;
						} else {
							$length = ($time2[1]*60+$time2[2]) - ($time[1]*60+$time[2]);
						}
						
//								$drawKey = drawEventTimes($start_time, $end_time);
//								preg_match ('/([0-9]{2})([0-9]{2})/', $drawKey['draw_start'], $time3);
						$hour = $time[1];
						$minute = $time[2];
					}
		
					// RECURRENCE-ID Support
					if (isset($recurrence_d)) {
						
						$recurrence_delete["$recurrence_d"]["$recurrence_t"] = $uid;
					}
						
					// handle single changes in recurring events
					// Maybe this is no longer need since done at bottom of parser? - CL 11/20/02
					if ($uid_valid && $write_processed) {
						if (!isset($hour)) $hour = 00;
						if (!isset($minute)) $minute = 00;
						$processed[$uid] = array($start_date,($hour.$minute), $type);
					}
								
					// Handling of the all day events
					if ((isset($allday_start) && $allday_start != '')) {
						$start = strtotime($allday_start);
						if ($spans_day) {
							$allday_end = date('Ymd',$end_unixtime);
						}
						if (isset($allday_end)) {
							$end = strtotime($allday_end);
						} else {
							$end = $start;
						}

						$start_date2 = date('Ymd', $start);
						$end_date2 = date('Ymd', $end);
						$master_array["EventList"][$uid]= array (
							'start_date' => $start_date2,
							'end_date' => $end_date2,
							'all_day' => true,
							'event_text' => $summary, 
							'description' => $description, 
							'location' => $location, 
							'organizer' => serialize($organizer), 
							'attendee' => serialize($attendee), 
							'calnumber' => $this->calnumber, 
							'calname' => $actual_calname, 
							'url' => $url, 
							'status' => $status, 
							'class' => $class );
						if (!$write_processed) $master_array["EventList"][$uid]['exception'] = true;
					}
					
					// Handling regular events
					if ((isset($start_time) && $start_time != '') && (!isset($allday_start) || $allday_start == '')) {
						$start_tmp = strtotime(date('Ymd',$start_unixtime));
						$end_date_tmp = date('Ymd',$end_unixtime);

//						echo "working on : " . $summary . "<br>\n";
//						echo "start: " . date("Y-m-d H:i:s", $start_unixtime) . "<br>\n";
//						echo "end: " . date("Y-m-d H:i:s", $end_unixtime) . "<br>\n";

//						while ($start_tmp < $end_unixtime) {
							$start_date_tmp = date('Ymd',$start_tmp);
							if ($start_date_tmp == $start_date) {
								$time_tmp = $hour.$minute;
								$start_time_tmp = $start_time;
							} else {
								$time_tmp = '0000';
								$start_time_tmp = '0000';
							}
							if ($start_date_tmp == $end_date_tmp) {
								$end_time_tmp = $end_time;
							} else {
								$end_time_tmp = '2400';
								$display_end_tmp = $end_time;
							}


//						echo "worked on : " . $summary . "<br>\n";
//						echo "start: " . date("Y-m-d H:i:s", $start_unixtime) . "<br>\n";
//						echo "end: " . date("Y-m-d H:i:s", $end_unixtime) . "<br>\n";
//						echo "\n";

							
							$master_array["EventList"][$uid] = array (
								'event_start' => date("Hi", $start_unixtime), 
								'event_end' => date("Hi", $end_unixtime), 
								'all_day' => false,
								'start_date' => date("Ymd", $start_unixtime),
								'end_date' => date("Ymd", $end_unixtime),
								'start_unixtime' => $start_unixtime, 
								'end_unixtime' => $end_unixtime, 
								'event_text' => $summary, 
								'event_length' => $length, 
								'event_overlap' => 0, 
								'description' => $description, 
								'status' => $status, 
								'class' => $class, 
								'spans_day' => true, 
								'location' => $location, 
								'organizer' => serialize($organizer), 
								'attendee' => serialize($attendee), 
								'calnumber' => $this->calnumber, 
								'calname' => $actual_calname, 
								'url' => $url );
							if (isset($display_end_tmp)){
								$master_array["EventList"][$uid]['display_end'] = $display_end_tmp;
							}
//									checkOverlap($start_date_tmp, $time_tmp, $uid);
//							$start_tmp = strtotime('+1 day',$start_tmp);
//						}
						if (!$write_processed) $master_array["EventList"][$uid]['exception'] = true;
					}
					
					// Handling of the recurring events, RRULE
					if (isset($rrule_array) && is_array($rrule_array)) {
						if (isset($allday_start) && $allday_start != '') {
							$hour = '-';
							$minute = '1';
							$rrule_array['START_DAY'] = $allday_start;
							$rrule_array['END_DAY'] = isset($allday_end) ? $allday_end : "";
							$rrule_array['END'] = 'end';
							$recur_start = $allday_start;
							$start_date = $allday_start;
							if (isset($allday_end)) {
								$diff_allday_days = $this->dayCompare($allday_end, $allday_start);
							 } else {
								$diff_allday_days = 1;
							}
						} else {
							$rrule_array['START_DATE'] = $start_date;
							$rrule_array['START_TIME'] = $start_time;
							$rrule_array['END_TIME'] = $end_time;
							$rrule_array['END'] = 'end';
						}
						
						$start_date_time = strtotime($start_date);
//						if (!isset($fromdate)){
//							#this should happen if not in one of the rss views
//							$this_month_start_time = strtotime($this->this_year.$this->this_month.'01');
//							$start_range_time = strtotime($this->this_year.'-01-01 -2 weeks');
//							$end_range_time = strtotime($this->this_year.'-12-31 +2 weeks');
//						}else{
								$start_range_time = strtotime("1970-01-01");			
								$end_range_time = strtotime("2038-01-17")+60*60*24; 						
//						}
						foreach ($rrule_array as $key => $val) {
							switch($key) {
								case 'FREQ':
									switch ($val) {
										case 'YEARLY':		$freq_type = 'year';	break;
										case 'MONTHLY':		$freq_type = 'month';	break;
										case 'WEEKLY':		$freq_type = 'week';	break;
										case 'DAILY':		$freq_type = 'day';		break;
										case 'HOURLY':		$freq_type = 'hour';	break;
										case 'MINUTELY':	$freq_type = 'minute';	break;
										case 'SECONDLY':	$freq_type = 'second';	break;
									}
									$master_array["EventList"][$uid]['recur'][$key] = strtolower($val);
									break;
								case 'COUNT':
									$count = $val;
									$master_array["EventList"][$uid]['recur'][$key] = $count;
									break;
								case 'UNTIL':
									$until = str_replace('T', '', $val);
									$until = str_replace('Z', '', $until);
									if (strlen($until) == 8) $until = $until.'235959';
									$abs_until = $until;
									ereg ('([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})', $until, $regs);
									$until = mktime($regs[4],$regs[5],isset($regs[6])?$regs[6]:0,$regs[2],$regs[3],$regs[1]);
									$master_array["EventList"][$uid]['recur'][$key] = date("Y-m-d H:i:s",$until);
									break;
								case 'INTERVAL':
									if ($val > 0){
									$number = $val;
									$master_array["EventList"][$uid]['recur'][$key] = $number;
									}
									break;
								case 'BYSECOND':
									$bysecond = $val;
									$bysecond = split (',', $bysecond);
									$master_array["EventList"][$uid]['recur'][$key] = $bysecond;
									break;
								case 'BYMINUTE':
									$byminute = $val;
									$byminute = split (',', $byminute);
									$master_array["EventList"][$uid]['recur'][$key] = $byminute;
									break;
								case 'BYHOUR':
									$byhour = $val;
									$byhour = split (',', $byhour);
									$master_array["EventList"][$uid]['recur'][$key] = $byhour;
									break;
								case 'BYDAY':
									$byday = $val;
									$byday = split (',', $byday);
									$master_array["EventList"][$uid]['recur'][$key] = $byday;
									break;
								case 'BYMONTHDAY':
									$bymonthday = $val;
									$bymonthday = split (',', $bymonthday);
									$master_array["EventList"][$uid]['recur'][$key] = $bymonthday;
									break;					
								case 'BYYEARDAY':
									$byyearday = $val;
									$byyearday = split (',', $byyearday);
									$master_array["EventList"][$uid]['recur'][$key] = $byyearday;
									break;
								case 'BYWEEKNO':
									$byweekno = $val;
									$byweekno = split (',', $byweekno);
									$master_array["EventList"][$uid]['recur'][$key] = $byweekno;
									break;
								case 'BYMONTH':
									$bymonth = $val;
									$bymonth = split (',', $bymonth);
									$master_array["EventList"][$uid]['recur'][$key] = $bymonth;
									break;
								case 'BYSETPOS':
									$bysetpos = $val;
									$master_array["EventList"][$uid]['recur'][$key] = $bysetpos;
									break;
								case 'WKST':
									$wkst = $val;
									$master_array["EventList"][$uid]['recur'][$key] = $wkst;
									break;
								case 'END':
	
								$recur = $master_array["EventList"][$uid]['recur'];
	
								// Modify the COUNT based on BYDAY
								if (isset($byday) && (is_array($byday)) && (isset($count))) {
									$blah = sizeof($byday);
									$count = ($count / $blah);
									unset ($blah);
								}
							
								if (!isset($number)) $number = 1;
								// if $until isn't set yet, we set it to the end of our range we're looking at
								
								if (!isset($until)) $until = $end_range_time;
								if (!isset($abs_until)) $abs_until = date('YmdHis', $end_range_time);
								$end_date_time = $until;
								$start_range_time_tmp = $start_range_time;
								$end_range_time_tmp = $end_range_time;
								
								// If the $end_range_time is less than the $start_date_time, or $start_range_time is greater
								// than $end_date_time, we may as well forget the whole thing
								// It doesn't do us any good to spend time adding data we aren't even looking at
								// this will prevent the year view from taking way longer than it needs to
								if ($end_range_time_tmp >= $start_date_time && $start_range_time_tmp <= $end_date_time) {
								
									// if the beginning of our range is less than the start of the item, we may as well set it equal to it
									if ($start_range_time_tmp < $start_date_time){
										$start_range_time_tmp = $start_date_time;
									}	
									if ($end_range_time_tmp > $end_date_time) $end_range_time_tmp = $end_date_time;
						
									// initialize the time we will increment
									$next_range_time = $start_range_time_tmp;
									
									// FIXME: This is a hack to fix repetitions with $interval > 1 
									if ($count > 1 && $number > 1) $count = 1 + ($count - 1) * $number; 
									
									$count_to = 0;
									// start at the $start_range and go until we hit the end of our range.
									if(!isset($wkst)) $wkst='SU';
									$wkst3char = $this->two2threeCharDays($wkst);
	
									$recur_data = array();
//									while (($next_range_time >= $start_range_time_tmp) && ($next_range_time <= $end_range_time_tmp) && ($count_to != $count)) {
										$func = $freq_type.'Compare';
										$diff = $this->$func(date('Ymd',$next_range_time), $start_date);
										if ($diff < $count) {
											if ($diff % $number == 0) {
												$interval = $number;
												switch ($rrule_array['FREQ']) {
													case 'DAILY':
														$next_date_time = $next_range_time;
														$recur_data[] = $next_date_time;
														break;
													case 'WEEKLY':
														// Populate $byday with the default day if it's not set.
														if (!isset($byday)) {
															$byday[] = strtoupper(substr(date('D', $start_date_time), 0, 2));
														}
														if (is_array($byday)) {
															foreach($byday as $day) {
																if(count($recur_data) > 0) break;
																$day = $this->two2threeCharDays($day);	
																#need to find the first day of the appropriate week.
																#dateOfweek uses weekstartday as a global variable. This has to be changed to $wkst, 
																#but then needs to be reset for other functions
																$week_start_day_tmp = $this->week_start_day;
																$this->week_start_day = $wkst3char;
																
																$the_sunday = $this->dateOfWeek(date("Ymd",$next_range_time), $wkst3char);
																$next_date_time = strtotime($day,strtotime($the_sunday)) + (12 * 60 * 60);
																$this->week_start_day = $week_start_day_tmp; #see above reset to global value
																
																#reset $next_range_time to first instance in this week.
																if ($next_date_time < $next_range_time){ 
																	$next_range_time = $next_date_time; 
																}
																// Since this renders events from $next_range_time to $next_range_time + 1 week, I need to handle intervals
																// as well. This checks to see if $next_date_time is after $day_start (i.e., "next week"), and thus
																// if we need to add $interval weeks to $next_date_time.
																if ($next_date_time > strtotime($this->week_start_day, $next_range_time) && $interval > 1) {
																#	$next_date_time = strtotime('+'.($interval - 1).' '.$freq_type, $next_date_time);
																}
																$recur_data[] = $next_date_time;
															}
														}
														break;
													case 'MONTHLY':
														if (empty($bymonth)) $bymonth = array(1,2,3,4,5,6,7,8,9,10,11,12);
														$next_range_time = strtotime(date('Y-m-01', $next_range_time));
														$next_date_time = $next_range_time;
														if (isset($bysetpos)){
															/* bysetpos code from dustinbutler
															start on day 1 or last day. 
															if day matches any BYDAY the count is incremented. 
															SETPOS = 4, need 4th match 
															SETPOS = -1, need 1st match 
															*/ 
															$year = date('Y', $next_range_time); 
															$month = date('m', $next_range_time); 
															if ($bysetpos > 0) { 
																$next_day = '+1 day'; 
																$day = 1; 
															} else { 
																$next_day = '-1 day'; 
																$day = $totalDays[$month]; 
															} 
															$day = mktime(0, 0, 0, $month, $day, $year); 
															$countMatch = 0; 
															while ($countMatch != abs($bysetpos)) { 
																/* Does this day match a BYDAY value? */ 
																$thisDay = $day; 
																$textDay = strtoupper(substr(date('D', $thisDay), 0, 2)); 
																if (in_array($textDay, $byday)) { 
																	$countMatch++; 
																} 
																$day = strtotime($next_day, $thisDay); 
															} 
															$recur_data[] = $thisDay; 
														}elseif ((isset($bymonthday)) && (!isset($byday))) {
															foreach($bymonthday as $day) {
																if(count($recur_data) > 0) break;
																if ($day < 0) $day = ((date('t', $next_range_time)) + ($day)) + 1;
																$year = date('Y', $next_range_time);
																$month = date('m', $next_range_time);
																if (checkdate($month,$day,$year)) {
																	$next_date_time = mktime(0,0,0,$month,$day,$year);
																	$recur_data[] = $next_date_time;
																}
															}
														} elseif (is_array($byday)) {
															foreach($byday as $day) {
																if(count($recur_data) > 0) break;
																ereg ('([-\+]{0,1})?([0-9]{1})?([A-Z]{2})', $day, $byday_arr);
																//Added for 2.0 when no modifier is set
																if ($byday_arr[2] != '') {
																	$nth = $byday_arr[2]-1;
																} else {
																	$nth = 0;
																}
																$on_day = $this->two2threeCharDays($byday_arr[3]);
																$on_day_num = $this->two2threeCharDays($byday_arr[3],false);
																if ((isset($byday_arr[1])) && ($byday_arr[1] == '-')) {
																	$last_day_tmp = date('t',$next_range_time);
																	$next_range_time = strtotime(date('Y-m-'.$last_day_tmp, $next_range_time));
																	$last_tmp = (date('w',$next_range_time) == $on_day_num) ? '' : 'last ';
																	$next_date_time = strtotime($last_tmp.$on_day, $next_range_time) - $nth * 604800;
																	$month = date('m', $next_date_time);
																	if (in_array($month, $bymonth)) {
																		$recur_data[] = $next_date_time;
																	}
																	#reset next_range_time to start of month
																	$next_range_time = strtotime(date('Y-m-'.'1', $next_range_time));
	
																} elseif (isset($bymonthday) && (!empty($bymonthday))) {
																	// This supports MONTHLY where BYDAY and BYMONTH are both set
																	foreach($bymonthday as $day) {
																		$year 	= date('Y', $next_range_time);
																		$month 	= date('m', $next_range_time);
																		if (checkdate($month,$day,$year)) {
																			$next_date_time = mktime(0,0,0,$month,$day,$year);
																			$daday = strtolower(strftime("%a", $next_date_time));
																			if ($daday == $on_day && in_array($month, $bymonth)) {
																				$recur_data[] = $next_date_time;
																			}
																		}
																	}
																} elseif ((isset($byday_arr[1])) && ($byday_arr[1] != '-')) {
																	$next_date_time = strtotime($on_day, $next_range_time) + $nth * 604800;
																	$month = date('m', $next_date_time);
																	if (in_array($month, $bymonth)) {
																		$recur_data[] = $next_date_time;
																	}
																}
																$next_date = date('Ymd', $next_date_time);
															}
														}
														break;
													case 'YEARLY':
														if ((!isset($bymonth)) || (sizeof($bymonth) == 0)) {
															$m = date('m', $start_date_time);
															$bymonth = array("$m");
														}	
	
														foreach($bymonth as $month) {
															if(count($recur_data) > 0) break;
															// Make sure the month & year used is within the start/end_range.
															if ($month < date('m', $next_range_time)) {
																$year = date('Y', $next_range_time);
															} else {
																$year = date('Y', $next_range_time);
															}
															if (isset($bysetpos)){
																/* bysetpos code from dustinbutler
																start on day 1 or last day. 
																if day matches any BYDAY the count is incremented. 
																SETPOS = 4, need 4th match 
																SETPOS = -1, need 1st match 
																*/ 
																if ($bysetpos > 0) { 
																	$next_day = '+1 day'; 
																	$day = 1; 
																} else { 
																	$next_day = '-1 day'; 
																	$day = date("t",$month); 
																} 
																$day = mktime(12, 0, 0, $month, $day, $year); 
																$countMatch = 0; 
																while ($countMatch != abs($bysetpos)) { 
																	/* Does this day match a BYDAY value? */ 
																	$thisDay = $day;
																	$textDay = strtoupper(substr(date('D', $thisDay), 0, 2)); 
																	if (in_array($textDay, $byday)) { 
																		$countMatch++; 
																	} 
																	$day = strtotime($next_day, $thisDay); 
																} 
																$recur_data[] = $thisDay; 															
															}
															if ((isset($byday)) && (is_array($byday))) {
																$checkdate_time = mktime(0,0,0,$month,1,$year);
																foreach($byday as $day) {
																	ereg ('([-\+]{0,1})?([0-9]{1})?([A-Z]{2})', $day, $byday_arr);
																	if ($byday_arr[2] != '') {
																		$nth = $byday_arr[2]-1;
																	} else {
																		$nth = 0;
																	}
																	$on_day = $this->two2threeCharDays($byday_arr[3]);
																	$on_day_num = $this->two2threeCharDays($byday_arr[3],false);
																	if ($byday_arr[1] == '-') {
																		$last_day_tmp = date('t',$checkdate_time);
																		$checkdate_time = strtotime(date('Y-m-'.$last_day_tmp, $checkdate_time));
																		$last_tmp = (date('w',$checkdate_time) == $on_day_num) ? '' : 'last ';
																		$next_date_time = strtotime($last_tmp.$on_day.' -'.$nth.' week', $checkdate_time);
																	} else {															
																		$next_date_time = strtotime($on_day.' +'.$nth.' week', $checkdate_time);
																	}
																}
															} else {
																$day 	= date('d', $start_date_time);
																$next_date_time = mktime(0,0,0,$month,$day,$year);
																//echo date('Ymd',$next_date_time).$summary.'<br>';
															}
															$recur_data[] = $next_date_time;
														}
														if (isset($byyearday)) {
															foreach ($byyearday as $yearday) {
																if(count($recur_data) > 0) break;
																ereg ('([-\+]{0,1})?([0-9]{1,3})', $yearday, $byyearday_arr);
																if ($byyearday_arr[1] == '-') {
																	$ydtime = mktime(0,0,0,12,31,$this->this_year);
																	$yearnum = $byyearday_arr[2] - 1;
																	$next_date_time = strtotime('-'.$yearnum.' days', $ydtime);
																} else {
																	$ydtime = mktime(0,0,0,1,1,$this->this_year);
																	$yearnum = $byyearday_arr[2] - 1;
																	$next_date_time = strtotime('+'.$yearnum.' days', $ydtime);
																}
																$recur_data[] = $next_date_time;
															}
														} 
														break;
													default:
														// anything else we need to end the loop
														$next_range_time = $end_range_time_tmp + 100;
														$count_to = $count;
												}
											} else {
												$interval = 1;
											}
											$next_range_time = strtotime('+'.$interval.' '.$freq_type, $next_range_time);
										} else {
											// end the loop because we aren't going to write this event anyway
											$count_to = $count;
										}
										// use the same code to write the data instead of always changing it 5 times						
										if (isset($recur_data) && is_array($recur_data)) {
											$recur_data_hour = @substr($start_time,0,2);
											$recur_data_minute = @substr($start_time,2,2);
											foreach($recur_data as $recur_data_time) {
												$recur_data_year = date('Y', $recur_data_time);
												$recur_data_month = date('m', $recur_data_time);
												$recur_data_day = date('d', $recur_data_time);
												$recur_data_date = $recur_data_year.$recur_data_month.$recur_data_day;
	
												if (($recur_data_time > $start_date_time) && ($recur_data_time <= $end_date_time) && ($count_to != $count) && !in_array($recur_data_date, $except_dates)) {
													if (isset($allday_start) && $allday_start != '') {
														$start_time2 = $recur_data_time;
														$end_time2 = strtotime('+'.$diff_allday_days.' days', $recur_data_time);
														while ($start_time2 < $end_time2) {
															$start_date2 = date('Ymd', $start_time2);
															$master_array["EventList"][$uid] = array ('event_text' => $summary,
																						'description' => $description,
																						'location' => $location,
																						'organizer' => serialize($organizer),
																						'attendee' => serialize($attendee),
																						'calnumber' => $this->calnumber,
																						'calname' => $actual_calname,
																						'url' => $url,
																						'all_day' => true,
																						'status' => $status,
																						'class' => $class,
																						'recur' => $recur );
															$start_time2 = strtotime('+1 day', $start_time2);
														}
													} else {
														$start_unixtime_tmp = mktime($recur_data_hour,$recur_data_minute,0,$recur_data_month,$recur_data_day,$recur_data_year);
														$end_unixtime_tmp = $start_unixtime_tmp + $length;
														
														$start_tmp = strtotime(date('Ymd',$start_unixtime_tmp));
														$end_date_tmp = date('Ymd',$end_unixtime_tmp);
														while ($start_tmp < $end_unixtime_tmp) {
															$start_date_tmp = date('Ymd',$start_tmp);
															if ($start_date_tmp == $recur_data_year.$recur_data_month.$recur_data_day) {
																$time_tmp = $hour.$minute;
																$start_time_tmp = $start_time;
															} else {
																$time_tmp = '0000';
																$start_time_tmp = '0000';
															}
															if ($start_date_tmp == $end_date_tmp) {
																$end_time_tmp = $end_time;
															} else {
																$end_time_tmp = '2400';
																$display_end_tmp = $end_time;
															}
															
															// Let's double check the until to not write past it
															$until_check = $start_date_tmp.$time_tmp.'00';
															if ($abs_until > $until_check) {
																$master_array["EventList"][$uid] = array (
																	'event_start' => $start_time_tmp, 
																	'event_end' => $end_time_tmp, 
																	'start_unixtime' => $start_unixtime_tmp, 
																	'end_unixtime' => $end_unixtime_tmp, 
																	'start_date' => date("Ymd", $start_unixtime_tmp),
																	'end_date' => date("Ymd", $end_unixtime_tmp),
																	'event_text' => $summary, 
																	'all_day' => false,
																	'event_length' => $length, 
																	'event_overlap' => 0, 
																	'description' => $description, 
																	'status' => $status, 
																	'class' => $class, 
																	'spans_day' => true, 
																	'location' => $location, 
																	'organizer' => serialize($organizer), 
																	'attendee' => serialize($attendee), 
																	'calnumber' => $this->calnumber, 
																	'calname' => $actual_calname, 
																	'url' => $url, 
																	'recur' => $recur);
																if (isset($display_end_tmp)){
																	$master_array["EventList"][$uid]['display_end'] = $display_end_tmp;
																}
//																			checkOverlap($start_date_tmp, $time_tmp, $uid);
															}
															$start_tmp = strtotime('+1 day',$start_tmp);
														}
													}
												}
											}
											unset($recur_data);
										}
//									} // end while
								}
							}	
						}
					}
	
					// This should remove any exdates that were missed.
					// Added for version 0.9.5 modified in 2.22 remove anything that doesn't have an event_start
					if (is_array($except_dates)) {
						foreach ($except_dates as $key => $value) {
							if (isset ($master_array[$value])){
								foreach ($master_array[$value] as $time => $value2){
									if (!isset($value2[$uid]['event_start'])){
							unset($master_array[$value][$uid]);
								}
							}
						}
					}
					}
					
				   // Clear event data now that it's been saved.
				   unset($start_time, $start_time_tmp, $end_time, $end_time_tmp, $start_unixtime, $start_unixtime_tmp, $end_unixtime, $end_unixtime_tmp, $summary, $length, $description, $status, $class, $location, $organizer, $attendee);
	
					break;
				case 'END:VTODO':
//					if ((!isset($vtodo_priority) || !$vtodo_priority) && (isset($status) && $status == 'COMPLETED')) {
//						$vtodo_sort = 11;
//					} elseif (!isset($vtodo_priority) || !$vtodo_priority) { 
//						$vtodo_sort = 10;
//					} else {
//						$vtodo_sort = isset($vtodo_priority) ? $vtodo_priority : 0;
//					}
					
//					// CLASS support
//					if (isset($class)) {
//						if ($class == 'PRIVATE') {
//							$summary = '**PRIVATE**';
//							$description = '**PRIVATE**';
//						} elseif ($class == 'CONFIDENTIAL') {
//							$summary = '**CONFIDENTIAL**';
//							$description = '**CONFIDENTIAL**';
//						}
//					}
					
					$due_date = isset($due_date) ? $due_date : "";
					$due_time = isset($due_time) ? $due_time : "";
					$completed_date = isset($completed_date) ? $completed_date : "";
					$completed_time = isset($completed_time) ? $completed_time : "";
					$vtodo_priority = isset($vtodo_priority) ? $vtodo_priority : "";
					$status = isset($status) ? $status : "";
					$class = isset($class) ? $class : "";
					$vtodo_categories = isset($vtodo_categories) ? $vtodo_categories : "";
					$description = isset($description) ? $description : "";
					
					$master_array['TodoList'][$uid] = array ('vtodo_text' => $summary, 'due_date'=> $due_date, 'due_time'=> $due_time, 'completed_date' => $completed_date, 'completed_time' => $completed_time, 'priority' => $vtodo_priority, 'status' => $status, 'class' => $class, 'categories' => $vtodo_categories, 'description' => $description, 'calname' => $actual_calname);
					unset ($start_date, $start_time, $due_date, $due_time, $completed_date, $completed_time, $vtodo_priority, $status, $class, $vtodo_categories, $summary, $description);
					$vtodo_set = FALSE;
					
					break;
					
				case 'BEGIN:VTODO':
					$vtodo_set = TRUE;
					break;
				case 'BEGIN:VALARM':
					$valarm_set = TRUE;
					break;
				case 'END:VALARM':
					$valarm_set = FALSE;
					break;
					
				default:
			
					unset ($field, $data, $prop_pos, $property);
					if (ereg ("([^:]+):(.*)", $line, $line)){
						$field = $line[1];
						$data = $line[2];
						
						$property = $field;
						$prop_pos = strpos($property,';');
						if ($prop_pos !== false) $property = substr($property,0,$prop_pos);
						$property = strtoupper($property);
						
						switch ($property) {
							
							// Start VTODO Parsing
							//
							case 'DUE':
								$datetime = $this->extractDateTime($data, $property, $field);
								$due_date = $datetime[1];
								$due_time = $datetime[2];
								break;
								
							case 'COMPLETED':
								$datetime = $this->extractDateTime($data, $property, $field);
								$completed_date = $datetime[1];
								$completed_time = $datetime[2];
								break;
								
							case 'PRIORITY':
								$vtodo_priority = "$data";
								break;
								
							case 'STATUS':
								$status = "$data";
								break;
								
							case 'CLASS':
								$class = "$data";
								break;
								
							case 'CATEGORIES':
								$vtodo_categories = "$data";
								break;
							//
							// End VTODO Parsing				
								
							case 'DTSTART':
								$datetime = $this->extractDateTime($data, $property, $field);
								$start_unixtime = $datetime[0];
								$start_date = $datetime[1];
								$start_time = $datetime[2];
								$allday_start = $datetime[3];
								break;
								
							case 'DTEND':
								$datetime = $this->extractDateTime($data, $property, $field);
								$end_unixtime = $datetime[0];
								$end_date = $datetime[1];
								$end_time = $datetime[2];
								$allday_end = $datetime[3];
								break;
								
							case 'EXDATE':
								$data = split(",", $data);
								foreach ($data as $exdata) {
									$exdata = str_replace('T', '', $exdata);
									$exdata = str_replace('Z', '', $exdata);
									preg_match ('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})/', $exdata, $regs);
									$except_dates[] = $regs[1] . $regs[2] . $regs[3];
									// Added for Evolution, since they dont think they need to tell me which time to exclude.
									if (($regs[4] == '') && ($start_time != '')) { 
										$except_times[] = $start_time;
									} else {
										$except_times[] = $regs[4] . $regs[5];
									}
								}
								break;
								
							case 'SUMMARY':
								$data = str_replace("\\n", "\n", $data);
								$data = str_replace("\\t", "\t", $data);
								$data = str_replace("\\r", "\r", $data);
//								$data = str_replace('$', '&#36;', $data);
								$data = stripslashes($data);
//								$data = htmlentities(urlencode($data));
								if (!isset($valarm_set) || $valarm_set == FALSE) { 
									$summary = $data;
								} else {
									$valarm_summary = $data;
								}
								break;
								
							case 'DESCRIPTION':
								$data = str_replace("\\n", "\n", $data);
								$data = str_replace("\\t", "\t", $data);
								$data = str_replace("\\r", "\r", $data);
//								$data = str_replace('$', '&#36;', $data);
								$data = stripslashes($data);
//								$data = htmlentities(urlencode($data));
								if (!isset($valarm_set) || $valarm_set == FALSE) { 
									$description = $data;
								} else {
									$valarm_description = $data;
								}
								break;
								
							case 'RECURRENCE-ID':
								$parts = explode(';', $field);
								foreach($parts as $part) {
									$eachval = split('=',$part);
									if ($eachval[0] == 'RECURRENCE-ID') {
										// do nothing
									} elseif ($eachval[0] == 'TZID') {
										$recurrence_id['tzid'] = $this->parse_tz($eachval[1]);
									} elseif ($eachval[0] == 'RANGE') {
										$recurrence_id['range'] = $eachval[1];
									} elseif ($eachval[0] == 'VALUE') {
										$recurrence_id['value'] = $eachval[1];
									} else {
										$recurrence_id[] = $eachval[1];
									}
								}
								unset($parts, $part, $eachval);
								
								$data = str_replace('T', '', $data);
								$data = str_replace('Z', '', $data);
								ereg ('([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})', $data, $regs);
								$recurrence_id['date'] = $regs[1] . $regs[2] . $regs[3];
								$recurrence_id['time'] = $regs[4] . $regs[5];
					
								$recur_unixtime = mktime($regs[4], $regs[5], 0, $regs[2], $regs[3], $regs[1]);
					
								$server_offset_tmp = $this->chooseOffset($recur_unixtime);
								if (isset($recurrence_id['tzid'])) {
									$tz_tmp = $recurrence_id['tzid'];
									$dlst = $this->isTimeDSTHuh($recur_unixtime, $this->tz_array[$tz_tmp]);
									$offset_tmp = $this->tz_array[$tz_tmp][$dlst];
								} elseif (isset($calendar_tz)) {
									$dlst = $this->isTimeDSTHuh($recur_unixtime, $this->tz_array[$calendar_tz]);
									$offset_tmp = $this->tz_array[$calendar_tz][$dlst];
								} else {
									$offset_tmp = $server_offset_tmp;
								}
								$recur_unixtime = $this->calcTime($offset_tmp, $server_offset_tmp, $recur_unixtime);
								$recurrence_id['date'] = date('Ymd', $recur_unixtime);
								$recurrence_id['time'] = date('Hi', $recur_unixtime);
								$recurrence_d = date('Ymd', $recur_unixtime);
								$recurrence_t = date('Hi', $recur_unixtime);
								unset($server_offset_tmp);
								break;
								
							case 'UID':
								$uid = $data;
								break;
							case 'X-WR-CALNAME':
								$actual_calname = $data;
								$master_array['calendar_name'] = $actual_calname;
//										$cal_displaynames[$cal_key] = $actual_calname; #correct the default calname based on filename
								break;
							case 'X-WR-TIMEZONE':
								$calendar_tz = $this->parse_tz($data);
								$master_array['calendar_tz'] = $calendar_tz;
								break;
							case 'DURATION':
								if (($first_duration == TRUE) && (!stristr($field, '=DURATION'))) {
									ereg ('^P([0-9]{1,2}[W])?([0-9]{1,2}[D])?([T]{0,1})?([0-9]{1,2}[H])?([0-9]{1,2}[M])?([0-9]{1,2}[S])?', $data, $duration); 
									$weeks 			= str_replace('W', '', $duration[1]); 
									$days 			= str_replace('D', '', $duration[2]); 
									$hours 			= str_replace('H', '', $duration[4]); 
									$minutes 		= str_replace('M', '', $duration[5]); 
									$seconds 		= str_replace('S', '', $duration[6]); 
									$the_duration 	= ($weeks * 60 * 60 * 24 * 7) + ($days * 60 * 60 * 24) + ($hours * 60 * 60) + ($minutes * 60) + ($seconds);
									$first_duration = FALSE;
								}	
								break;
							case 'RRULE':
								$data = str_replace ('RRULE:', '', $data);
								$rrule = split (';', $data);
								foreach ($rrule as $recur) {
									ereg ('(.*)=(.*)', $recur, $regs);
									$rrule_array[$regs[1]] = $regs[2];
								}
								break;
							case 'ATTENDEE':
								$field 		= str_replace("ATTENDEE;CN=", "", $field);
								$data 		= str_replace ("mailto:", "", $data);
								$attendee[] = array ('name' => stripslashes($field), 'email' => stripslashes($data));
								break;
							case 'ORGANIZER':
								$field 		 = str_replace("ORGANIZER;CN=", "", $field);
								$data 		 = str_replace ("mailto:", "", $data);
								$organizer[] = array ('name' => stripslashes($field), 'email' => stripslashes($data));
								break;
							case 'LOCATION':
								$data = str_replace("\\n", "<br />", $data);
								$data = str_replace("\\t", "&nbsp;", $data);
								$data = str_replace("\\r", "<br />", $data);
								$data = stripslashes($data);
								$location = $data;
								break;
							case 'URL':
								$url = $data;
								break;
						}
						// end default case
					} // end if
			} // end switch
		} // end for
//		if (!isset($master_array['-3'][$this->calnumber])) $master_array['-3'][$this->calnumber] = $actual_calname;
		$this->calnumber = $this->calnumber + 1;
	
		// Sort the array by absolute date.
		if (isset($master_array) && is_array($master_array)) { 
			ksort($master_array);
			reset($master_array);
			
			// sort the sub (day) arrays so the times are in order
			foreach (array_keys($master_array) as $k) {
				if (isset($master_array[$k]) && is_array($master_array[$k])) {
					ksort($master_array[$k]);
					reset($master_array[$k]);
				}
			}
		}
		
		return $master_array;

	}

//If you want to see the values in the arrays, uncomment below.

//print '<pre>';
//print_r($master_array);
//print_r($this->overlap_array);
//print_r($this->day_array);
//print_r($rrule_array);
//print_r($recurrence_delete);
//print_r($cal_displaynames);
//print_r($cal_filelist);
//print '</pre>';

} // end class
?>