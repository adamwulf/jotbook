<?

include "config.php";
define("ROOT", dirname(__FILE__) . "/");

include "functions.php";
include "include.classloader.php";

putenv("TZ=GMT");

if(date("Z") != 0){
	throw new Exception("server is in wrong timezone: " . date("O") . ":" . date("Z"));
}

error_reporting(E_ALL);


putenv("TZ=GMT");

if(date("Z") != 0){
	throw new Exception("server is in wrong timezone: " . date("O") . ":" . date("Z"));
}

header("Content-type: text/html; charset=UTF-8");
?>