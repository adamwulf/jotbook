<?
// run a query on mysql,
// and throw an exception if there's
// a problem
function query($sql){
	global $mysql;
	$result = mysql_query($sql, $mysql);
	if(mysql_error($mysql)){
		throw new Exception("mysql error");
	}
	return $result;
}


function readFormValue($var, $data){
	if(isset($data[$var])){
		$val = $data[$var];
		if(is_array($val) && get_magic_quotes_gpc()){
			$arr = $val;
			$ret = array();
			foreach($arr as $var => $val){
				$ret[$var] = stripslashes($val);
			}
			$val = $ret;
		}else if(get_magic_quotes_gpc()){
			$val = stripslashes($val);
		}
		return $val;
	}else{
		return false;
	}
}



function getLock(){
	global $fp;

	$lockfile = dirname(__FILE__) . "/../list.data.lock";
	$fp = @fopen($lockfile,'a');
	$retries = 0; 
	$max_retries = 100; 

	if (!$fp) { 
		throw new Exception("cannot lock file");
	} 

	// keep trying to get a lock as long as possible 
	while(!flock($fp, LOCK_EX) and $retries <= $max_retries){ 
		if($retries > 0) { 
			usleep(rand(1, 10000)); 
		} 
		$retries ++;
	}
	// couldn't get the lock, give up 
	if ($retries == $max_retries) { 
		throw new Exception("cannot lock file");
	}

}

function releaseLock(){
	global $fp;
	flock($fp, LOCK_UN); 
	fclose($fp); 
}




function array_copy($arr){
	$ret = array();
	foreach($arr as $var => $val){
		$ret[$var] = $val;
	}
	return $ret;
}

	
	
/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 *
 * @return string Indented version of the original JSON string.
 */
function indentJSON($json) {

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;
        
        // If this character is the end of an element, 
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element, 
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }
            
            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        $prevChar = $char;
    }

    return $result;
}

?>