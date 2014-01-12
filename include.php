<?

include "includes/config.php";
include "includes/functions.php";
include "includes/include.classloader.php";

error_reporting(E_ALL);

define("ROOT", dirname(__FILE__) . "/");
define("LIBRARY", "library/");
define("DOMAIN", "welcome.totheinter.net");
define("COOKIENAME", "aurora");

putenv("TZ=GMT");

if(date("Z") != 0){
	throw new Exception("server is in wrong timezone: " . date("O") . ":" . date("Z"));
}

header("Content-type: text/html; charset=UTF-8");
?>