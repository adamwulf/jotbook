<?php

// THIS MODULDE derived from a PEAR.php.net supplied "WebDAV Stream Wrapper" module.
// 11/17/06.

// WebDAV defines some addition HTTP methods
define('HTTP_REQUEST_METHOD_COPY',      'COPY',      true);
define('HTTP_REQUEST_METHOD_MOVE',      'MOVE',      true);
define('HTTP_REQUEST_METHOD_MKCOL',     'MKCOL',     true);
define('HTTP_REQUEST_METHOD_PROPFIND',  'PROPFIND',  true);
define('HTTP_REQUEST_METHOD_PROPPATCH', 'PROPPATCH', true);
define('HTTP_REQUEST_METHOD_LOCK',      'LOCK',      true);
define('HTTP_REQUEST_METHOD_UNLOCK',    'UNLOCK',    true);


/**
 * A wrapper class for WebDAV Client requests.
 *
 * The methods can take URLs to operate on, and a set_url() call can prime the object
 * with the url to operate on.
 *
 * Some attempts to handle Location: HTTP header redirects exist, but it's not 
 * perfect.
 *
 * The underlying request object is stored in this object and the request transcript
 * can be queried for debugging and such.
 *
 */
 
/* METHODS:
    propfind($url)
    propfindall($url)
    get($url)
    put($buffer,$url)
    proppatch($xml,$url)
    dir_ls($url)
    mkdir($url)
    rmdir($url)
    rename($url_dest,$url)
    copy($url_dest,$url)
    unlink($url)
    _parse_url($url)
    _validate_hostname()
    set_user()
    set_pass()
    check_options()
    stream_lock($mode)
    _create_request_object( $request_method )
    _done_with_request_object( $req )
    _stat_from_simplexml( $sxml_object )
    _store_reqresponse( $req )
    get_reqresponse()
    _store_errmessage( $errmessage )
    get_errmessage()
*/
class HTTP_WebDAV_Client 
{
    // URL MANAGEMENT
    var $url = false;
    var $furl_changed = false;    // has it changed during processing via Location: redirect?
    var $furl_allow_basic_auth = false; // is this url using SSL encryption?
    var $url_hostname = false;    // the hostname portion of the URL for easy access.
    var $url_path = false;        // the path portion of the URL for easy access.
    
    // AUTHENTICATION
    var $user = false;  // username for authentication
    var $pass = false;  // password for authentication
    var $basicAuthUnsecure = true;  // Whether to pass basic auth insecure by default.
    var $statresult = array();

    // Different versions of the WebDAV protocol support different stuff.
    var $dav_level = array();  // WebDAV protocol levels supported by the server
    var $dav_allow = array();  // HTTP methods supported by the server

    // Directories are read with
    var $dirfiles = false;  // List of files in a directory.
    var $dirpos = 0;        // 'current' index into that array.

    var $locktoken = false;  // Lock token for webdav style locking.

    // REQUEST TRACKING
    var $reqresponse = false;  // the response status code to the last request.
    var $errmessage = null;    // an explanation of the last err.
    var $last_request = null;  // the full request object for the last request.


    // Set the URL to be used. The URL can be set here or passed in on
    // other methods.
    //  RETURNS: false on failure, true on success.
    function set_url($url) {
      if (!$this->_parse_url($url)) return false;
      return true;
    }
    
    // Set the username and passwords for use to use. We can also extract these
    // from the URL, but that is discouraged.
    function set_user( $uname ) {
      $this->user = $uname;
    }
    function set_pass( $pwrd ) {
      $this->pass = $pwrd;
    }

    /** propfind() - do PROPFIND to get certain stat info.
     *    RETURNS: true for success, false for failure. Get props from this->
     */
    function propfind($url=null) 
    {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;
        
        // We don't validate hostname on all requests, but do it here because PROPFIND is used
        // for initial login authentication.
        if( !$this->_validate_hostname() ) return false;
        
        // now get the file metadata
        // we only need type, size, creation and modification date
        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_PROPFIND);
        $req->addHeader("Depth", "0");
        $req->addHeader("Content-Type", "text/xml");
        $req->addRawPostData('<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
 <prop>
  <resourcetype/>
  <getcontentlength/>
  <getlastmodified />
  <creationdate/>
 </prop>
</propfind>
');
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( "sendRequest() error: " . $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );

        $retval = false;
        // check the response code, anything but 207 indicates a problem
        switch ($req->getResponseCode()) {
        case 200: // OK but no status (?)
            $this->_store_errmessage( "HTTP simple success 200, but no stat information(?)" );
            $retval = false;
            break;
            
        case 207: // OK
            // now we have to parse the result to get the status info items
            $raw_xml = $req->getResponseBody();
            libxml_use_internal_errors( true );
            $simp_xml = simplexml_load_string( $raw_xml, null, LIBXML_NONET );
            
            $retval = $this->statresult = $this->_stat_from_simplexml( $simp_xml );
            unset($propinfo);
            break;

        case 404: // file not found
        default: 
            $retval = false;
            break;
        }
        
        $this->_done_with_request_object( $req ); // back propagate any date from the request to us.
        return $retval;
    }

    /**
     * This version gets *all* properties for the URL.
     *
     * Returns: a simple associative array with all the properties. Note that
     *  we gather the name/value pairs, but not any more complex XML structures that
     *  a WebDAV server might return.
     */
    function propfindall($url=null) {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        // now get the file metadata
        // we only need type, size, creation and modification date
        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_PROPFIND);
        $req->addHeader("Depth", "0");
        $req->addHeader("Content-Type", "text/xml");
        $req->addRawPostData('<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
 <allprop/>
</propfind>
');
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( "sendRequest() error: " . $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );

        // check the response code, anything but 207 indicates a problem
        switch ($req->getResponseCode()) {
        case 200: // OK but no status (?)
            $this->_store_errmessage( "HTTP simple success 200, but no stat information(?)" );
            break;
            
        case 207: // OK
            // now we have to parse the result to get the status info items
            $raw_xml = $req->getResponseBody();
            libxml_use_internal_errors( true );
            $simp_xml = simplexml_load_string( $raw_xml, null, LIBXML_NONET );
            
            print htmlentities( $raw_xml );
            $prop_node = $simp_xml->xpath( ".//*[local-name()='prop']" );
            $props = $prop_node[0];
            // Return an array with all the props. The DAV: is the standard webdav namespace:
            $ret_array = array();
            foreach( $props->children("DAV:") as $k => $value ) {
              $ret_array[$k] = (string)$value;  // the string cast turns the bizarre object into a plain string.
              //print "<br>$k == $value";
            }
            // Also do the global namespace just in case:
            foreach( $props->children() as $k => $value ) {
              $ret_array[$k] = (string)$value;  // the string cast turns the bizarre object into a plain string.
              //print "<br>$k == $value";
            }
            return( $ret_array );
            //print "Found props:<br>";
            //var_dump( $props );
            
            // Extract the properties from the XML:
            // Filename:   lives as as the text value of an href node.
			      //$xpath_result = $simp_xml->xpath(".//*[local-name()='href']");
			      //if( count( $xpath_result ) > 0 ) {
				    //  $statret['filename'] = urldecode( (string)$xpath_result[0] );
            //} else {
            //  continue; // skip it if they don't give us the href filename guy.
            //}
                    			
			      // fcollection. Check the resourcetype node and see if it contains a collection node.
			      //$statret['fcollection'] = false; // default.
			      //xpath_result = $sxml_object->xpath(".//*[local-name()='resourcetype']");
			      //if( count( $xpath_result ) > 0 ) {
				    //  $xpath_collection = $xpath_result[0]->xpath(".//*[local-name()='collection']" );
				    //  if( count( $xpath_collection ) > 0 ) $statret['fcollection'] = true;
			      //}
            break;

        case 404: // file not found
        default: 
            return false;
        }
    }
    

    /**
     * get()
     * Get a document or file from WebDAV.
     */
    function get($url=null) 
    {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;
    
        // create a GET request with a range
        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_GET);
        //$req->addHeader("Range", "bytes=$start-$end");

        // go! go! go!
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $data = $req->getResponseBody();
        $len  = strlen($data);

        // lets see what happened
        switch ($req->getResponseCode()) {
        case 200: 
            // server doesn't support range requests 
            // TODO we should add some sort of cacheing here
            //$data = substr($data, $start, $count);
            //  -- iNetWord - whole data is what we want.
            break;

        case 206:
            // server supports range requests
            break;

        case 416:
            // reading beyond end of file is not an error
            $data = "";
            break;

        default: 
            return false;
        }

        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        // thats it!
        return $data;
    }

    /**
     * put()
     * put a file on the webdav server.
     */
    function put($buffer,$url=null) 
    {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        // create a partial PUT request
        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_PUT);
        if ($this->locktoken) {
            $req->addHeader("If", "(<{$this->locktoken}>)");
        }
        $req->addRawPostData($buffer);

        // go! go! go!
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        // check result
        switch ($req->getResponseCode()) {
        case 200:
        case 201:
        case 204:
            return true;
            
        default: 
            return false;
        }
    }

    /**
     * Set some properties for the URL.
     * Returns: true on success, false on failure.
     */
    function proppatch( $proppatch_xml, $url=null ) {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        // now get the file metadata
        // we only need type, size, creation and modification date
        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_PROPPATCH);
        $req->addHeader("Content-Type", "text/xml");
        $req->addRawPostData( $proppatch_xml );
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( "sendRequest() error: " . $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );

        // check the response code, anything but 207 indicates a problem
        switch ($req->getResponseCode()) {
        case 207: // OK
        case 200: // OK 
        case 204: // OK
            return( true );
            break;

        default: 
            $this->_store_errmessage( "HTTP Error status code" );
            return false;
        }
    }


    /**
     * dir_ls() method
     * Do a PROPFIND just so as to get a list of files in the given directory.
     */
    function dir_ls($url=null) 
    {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        // rewrite the request URL
        if (!$this->_validate_hostname()) return false;

        // now read the directory
        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_PROPFIND);
        $req->addHeader("Depth", "1");
        $req->addHeader("Content-Type", "text/xml");
        $req->addRawPostData('<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
 <prop>
  <resourcetype/>
  <getcontentlength/>
  <creationdate/>
  <getlastmodified/>
 </prop>
</propfind>
');
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() . "::" . $req->_RequestTranscript );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        switch ($req->getResponseCode()) {
        case 207: // multistatus content
            $this->dirfiles = array();
            
            $raw_xml = $req->getResponseBody();
            libxml_use_internal_errors( true );
            $simp_xml = simplexml_load_string( $raw_xml, null, LIBXML_NONET );
            
            // Each file is in a <response> node, with possible unknown namespace prefix.
            // This expression gets an array of <response> nodes:
            $responses = $simp_xml->xpath("//*[local-name()='response']");
            
            // Loop over each response and get the particulars out of it:
            foreach( $responses as $cc => $vv ) {
					    $this->dirfiles[] = $this->_stat_from_simplexml( $vv );
            }
            return $this->dirfiles;

        default: 
            // any other response state indicates an error
            error_log("file not found");
            return false;
        }
    }

    /**
     * mkdir() method
     * Make a directory at the passed URL.
     */
    function mkdir($url=null) {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_MKCOL);
        if ($this->locktoken) {
            $req->addHeader("If", "(<{$this->locktoken}>)");
        }
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        // check the response code, anything but 201 indicates a problem
        $respcode = $req->getResponseCode();
        switch ($respcode) {
        case 201:
            return true;
        default:
            $this->_store_errmessage( "mkdir failed - failure status return: ". $respcode );
            return false;
        }
    }


    /**
     * rmdir() method
     * Remove the directory at the given URL.
     */
    function rmdir($url=null) {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_DELETE);
        if ($this->locktoken) {
            $req->addHeader("If", "(<{$this->locktoken}>)");
        }
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        // check the response code, anything but 204 indicates a problem
        $respcode = $req->getResponseCode();
        switch ($respcode) {
        case 204:
            return true;
        default:
            $this->_store_errmessage( "rmdir failed - failure status return: ". $respcode );
            return false;
        }
    }
     

    /**
     * rename() method
     * Rename one file to another.
     */
    function rename($url_newname, $url=null) {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_MOVE);
        if ($this->locktoken) {
            $req->addHeader("If", "(<{$this->locktoken}>)");
        }
        if (!$this->_parse_url($url_newname)) return false;
        $req->addHeader("Destination", $this->url);
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        // check the response code, anything but 207 indicates a problem
        $respcode = $req->getResponseCode();
        switch ($respcode) {
        case 201:
        case 204:
            return true;
        default:
            $this->_store_errmessage( "rename failed - failure status return: ". $respcode );
            return false;
        }
    }
    
     /**
     * copy() method
     * copy files on the webdav server.
     */
    function copy($url_dest, $url=null) {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_COPY);
        if ($this->locktoken) {
            $req->addHeader("If", "(<{$this->locktoken}>)");
        }
        if (!$this->_parse_url($url_dest)) return false;
        $req->addHeader("Destination", $this->url);
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        // check the response code, anything but 207 indicates a problem
        $respcode = $req->getResponseCode();
        switch ($respcode) {
        case 201:
        case 204:
            return true;
        default:
            $this->_store_errmessage( "copy failed - failure status return: ". $respcode );
            return false;
        }
    }
     

    /**
     * unlink() method
     * delete a file.
     */
    function unlink($url=null) 
    {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;

        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_DELETE);
        if ($this->locktoken) {
            $req->addHeader("If", "(<{$this->locktoken}>)");
        }
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        $this->_store_reqresponse( $req );
        $this->_done_with_request_object( $req ); // back propagate any data from the request to us.

        switch ($req->getResponseCode()) {
        case 200:
        case 204: // ok
        case 207:
            return true;
        default: 
            return false;
        }
    }
        

    /**
     * Helper function for URL analysis
     */
    function _parse_url($url) 
    {
        // rewrite the WebDAV url as a plain HTTP url
        $url_parsed = parse_url($url);

        // detect whether plain or SSL-encrypted transfer is requested
        $this->furl_allow_basic_auth = false; // until we know otherwise.
        switch ($url_parsed['scheme']) {
        case "webdav":
            $url_parsed['scheme'] = "http";
            break;
            
        case "webdavs":
            $url_parsed['scheme'] = "https";
            $this->furl_allow_basic_auth = true;
            break;
            
        case "httpb":  // This is a custom mechanism where, for us only, httpb: means to use
                       // www-authenticate basic authentication with every request. We normally only
                       // do that with https: for security, but some servers want it on http:, like
                       // MS Sharepoint sometimes.
            $url_parsed['scheme'] = "http";
            $this->furl_allow_basic_auth = true;
            break;
                       
                    
        case "https":
            $this->furl_allow_basic_auth = true;
        case "http":
            break;    // these are fine as it is.
            
        default:
            $this->errmessage = "only 'webdav:' and 'webdavs:' are supported, not '$url[scheme]:'";
            error_log( $this->errmessage );
            return false;
        }

        // if a TCP port is specified we have to add it after the host
        if (isset($url_parsed['port'])) {
            $url_parsed['host'] .= ":$url_parsed[port]";
        }

        // store the plain path for possible later use
        $this->url_path = array_key_exists( "path", $url_parsed ) ? $url_parsed["path"] : "";

        // now we can put together the new URL
        $this->url = "$url_parsed[scheme]://$url_parsed[host]" . $this->url_path;
        
        // Add in the query component:
        if( !empty( $url_parsed['query'] ) ) $this->url .= ("?" . $url_parsed['query']);

        // extract authentication information
        if (isset($url_parsed['user'])) {
            $this->set_user( urldecode($url_parsed['user']) );
        }
        if (isset($url_parsed['pass'])) {
            $this->set_pass( urldecode($url_parsed['pass']) );
        }
        
        // Save away hostname for easy error messages:
        $this->url_hostname = $url_parsed['host'];
        
        return true;
    }
    
    // Validate that the hostname as an accessible address. This should more properly
    // be done in $req->sendRequest but the underlying PHP fsockopen() routine does
    // not return error code info when the hostname is invalid. More PHP error
    // return bogosity.
    //  RETURNS: false if invalid, true if valid. Err message is put in this object.
    function _validate_hostname() {
        $got_an_address = gethostbyname( $this->url_hostname );
        if( $got_an_address == $this->url_hostname ) { // this is how gethostbyname() indicates error. Ouch.
          $this->_store_errmessage( "Server ".$this->url_hostname." is not available" );
          return( false );
        }
        return( true );
    }
    
    /**
     * Helper function for WebDAV OPTIONS detection
     *
     * @return boolean:
     *    true  -- all is good. Server supports the DAV stuff we need.
     *    false -- core failure.
     */
    function check_options($url=null) 
    {
        // process the request URL
        if ($url && !$this->_parse_url($url)) return false;
        
        // now check OPTIONS reply for WebDAV response headers
        $req = $this->_create_request_object(HTTP_REQUEST_METHOD_OPTIONS);
        $err = $req->sendRequest();
        if( PEAR::isError($err) ) {
          $this->_store_errmessage( $err->getMessage() );
          return( false );
        }
        
        $this->_store_reqresponse( $req );
        if ($req->getResponseCode() != 200) {
            return false;
        }

        // get the supported DAV levels and extensions
        $dav = $req->getResponseHeader("DAV");
        $this->dav_level = array();
        foreach (explode(",", $dav) as $level) {
            $this->dav_level[trim($level)] = true;
        }
        if (!isset($this->dav_level["1"])) {
            // we need at least DAV Level 1 conformance
            $this->_store_errmessage( "DAV protocol Level 1 required" );
            return false;
        }
        
        // get the supported HTTP methods
        // TODO these are not checked for WebDAV compliance yet
        $allow = $req->getResponseHeader("Allow");
        $this->dav_allow = array();
        foreach (explode(",", $allow) as $method) {
            $this->dav_allow[trim($method)] = true;
        }

        return true;
    }


    /**
     * Stream handler interface lock() method (experimental ...)
     *
     * @access private
     * @return bool    true on success else false
     */
    function stream_lock($mode) {
        /* TODO:
           - think over how to refresh locks
         */
        
        $ret = false;

        // LOCK is only supported by DAV Level 2
        if (!isset($this->dav_level["2"])) {
            return false;
        }

        switch ($mode & ~LOCK_NB) {
        case LOCK_UN:
            if ($this->locktoken) {
                $req = $this->_create_request_object(HTTP_REQUEST_METHOD_UNLOCK);
                $req->addHeader("Lock-Token", "<{$this->locktoken}>");
                $err = $req->sendRequest();
                if( PEAR::isError($err) ) {
                  $this->_store_errmessage( $err->getMessage() );
                  return( false );
                }
                $this->_store_reqresponse( $req );

                $ret = $req->getResponseCode() == 204;
            }
            break;

        case LOCK_SH:
        case LOCK_EX:
            $body = sprintf('<?xml version="1.0" encoding="utf-8" ?> 
<D:lockinfo xmlns:D="DAV:"> 
 <D:lockscope><D:%s/></D:lockscope> 
 <D:locktype><D:write/></D:locktype> 
 <D:owner>%s</D:owner> 
</D:lockinfo>'
                            , ($mode & LOCK_SH) ? "shared" : "exclusive"
                            , get_class($this) // TODO better owner string
                            );
            $req = $this->_create_request_object(HTTP_REQUEST_METHOD_LOCK);
            if ($this->locktoken) { // needed for refreshing a lock
                $req->addHeader("Lock-Token", "<{$this->locktoken}>");
            }
            $req->addHeader("Timeout","Infinite, Second-4100000000");
            $req->addHeader("Content-Type", 'text/xml; charset="utf-8"');
            $req->addRawPostData($body);
            $err = $req->sendRequest();
            if( PEAR::isError($err) ) {
              $this->_store_errmessage( $err->getMessage() );
              return( false );
            }
            $this->_store_reqresponse( $req );

            $ret = $req->getResponseCode() == 200;          

            if ($ret) {
                $propinfo = &new HTTP_WebDAV_Client_parse_lock_response($req->getResponseBody());               
                $this->locktoken = $propinfo->locktoken;
                // TODO deal with timeout
            }
            break;
            
        default:
            break;
        }

        return $ret;
    }
    
    // This helper centralizes the creation of the HTTP request objects we use.
    function _create_request_object( $request_method ) {
      $req_options = array( 'allowRedirects' => true );
      if( $this->basicAuthUnsecure ) $req_options['basicAuthUnsecure'] = true;
      $req = &new HTTP_Request($this->url, $req_options );
      $req->setMethod( $request_method );
      
      // Set auth user and password. Note that our version of Request.php will not pass
      // the basic auth params over non-ssl connections unless specifically instructed
      // to via a basicAuthUnsecure flag on creation.
      if ( !empty($this->user) && !empty($this->pass)) {
          //$req->_RequestTranscript .= "USER: -".$this->user."-, PASS: -".$this->pass."-";
          $req->setAuthCredentials($this->user, @$this->pass);          
      }
      $this->last_request = $req;  // for everyone to examine.
      return( $req );
    }
    
    // We sometimes want to return the new URL when a redirect takes place. This routine
    // manages that.
    function _done_with_request_object( $req ) {
      if( $req->_furl_changed ) {
        $this->furl_changed = true;
        $this->url = $req->_url->getURL();
      }
      // If the contacted server requested Basic auth over insecure, then note that too:
      $this->basicAuthUnsecure = $req->_basicAuthUnsecure;
    }

    // This helper builds the stat array info from a SimpleXML element object:
    // RETURNS: the stat[] array object, compatible with the native linux filesystem
    //   one. It also has a 'filename' entry for the readdir() mode of operation.
    function _stat_from_simplexml( $sxml_object ) {
      $statret = array();
      
      // Filename:   lives as as the text value of an href node.
			$xpath_result = $sxml_object->xpath(".//*[local-name()='href']");
			if( count( $xpath_result ) > 0 ) {
				$statret['filename'] = urldecode( (string)$xpath_result[0] );
      } else {
        continue; // skip it if they don't give us the href filename guy.
      }
              			
			// fcollection. Check the resourcetype node and see if it contains a collection node.
			$statret['fcollection'] = false; // default.
			$xpath_result = $sxml_object->xpath(".//*[local-name()='resourcetype']");
			if( count( $xpath_result ) > 0 ) {
				$xpath_collection = $xpath_result[0]->xpath(".//*[local-name()='collection']" );
				if( count( $xpath_collection ) > 0 ) $statret['fcollection'] = true;
			}
			
      // Contentlength:   lives as as the text value of an getcontentlength node.
			$xpath_result = $sxml_object->xpath(".//*[local-name()='getcontentlength']");
			if( count( $xpath_result ) > 0 ) {
				$statret['size'] = (string)$xpath_result[0];
			}
			
			// This handy helper takes care of all our data/time needs.
			$dateTime_obj = new SOAP_Type_dateTime();
			
      // Creationdate:   lives as as the text value of an href node.
			$xpath_result = $sxml_object->xpath(".//*[local-name()='creationdate']");
			if( count( $xpath_result ) > 0 ) {
			  // Until we know otherwise, mtime is the ctime by default.
				$statret['mtime'] = $statret['ctime'] = $dateTime_obj->toUnixtime( (string)$xpath_result[0] );
			}
			
      // lastmodified:   lives as as the text value of an href node.
			$xpath_result = $sxml_object->xpath(".//*[local-name()='getlastmodified']");
			if( count( $xpath_result ) > 0 ) {
			  // Note: the date format for getlastmodified differs from that of creationdate. getlastmodified uses
			  //  uses rfc2616 formats.
				$statret['mtime'] = strtotime( (string)$xpath_result[0] );
				// Be flexible. Fill in ctime if none actually given:
				if( !array_key_exists( 'ctime', $statret ) ) $statret['ctime'] = $statret['mtime'];
			}
			
			return( $statret );
    }
    
    
    // We store all request responses for subsequent processing and reporting:
    function _store_reqresponse( $req ) {
      $this->reqresponse = $req->getResponseCode();
      //trace( 'debug', "WebDAV: store reqresponse() response status code == ".$this->reqresponse );
    }
    
    function get_reqresponse() {
      return( $this->reqresponse );
    }
    
    function _store_errmessage( $errmessage ) {
      $this->errmessage = $errmessage;
    }
    
    // Figure out a message the describes what went wrong:
    function get_errmessage() {
      if( $this->errmessage ) return( $this->errmessage );
      if( $this->reqresponse ) {
        if( $this->reqresponse < 200 || $this->reqresponse >= 300 ) return( "HTTP Error Status $this->reqresponse" );
        else return( "HTTP Success $this->reqresponse" );
      }
      return( "unknown error" );
    }
}

?>