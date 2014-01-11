<?

define("ROOT", dirname(__FILE__) . "/");
define("LIBRARY", "library/");
define("SHOSTURL", "http://welcome.totheinter.net/apps/metrics/");
define("HOSTURL", "http://welcome.totheinter.net/apps/metrics/");
define("DOMAIN", "welcome.totheinter.net");
define("COOKIENAME", "aurora");

define("SMTP_HOST", "mail.aurora2.com");
define("SMTP_USER", "bot+aurora2.com");
define("SMTP_PASS", "r4tb4ndit");

putenv("TZ=GMT");

if(date("Z") != 0){
	throw new Exception("server is in wrong timezone: " . date("O") . ":" . date("Z"));
}

//define("GOOGLE_ANALYTICS", "UA-180672-3");

function getmicrotime(){ 
    return microtime(true); 
//    return ((float)$usec + (float)$sec); 
}

    

include_once ROOT . "include.classloader.php";
// include ROOT . "include.all.php";



header("Content-type: text/html; charset=UTF-8");

?>