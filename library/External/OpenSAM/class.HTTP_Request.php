<?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002-2003, Richard Heyes                                |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+
//
// $Id: OurRequest.php,v 1.1 2007/08/02 06:27:21 pw Exp $
//
// HTTP_Request Class
//
// Simple example, (Fetches yahoo.com and displays it):
//
// $a = &new HTTP_Request('http://www.yahoo.com/');
// $a->sendRequest();
// echo $a->getResponseBody();
//

// ******************************************************************
// ******************************************************************
// ******************************************************************
// iNetOffice Modifications:
//
// This module was modified in 2006 to support:
//  1. Digest authentication.
//  2. Prevent sending of Basic authentication params in non-ssl
//     connections unless specifically requested by server. Prior version
//     would send Basic auth across any connection w/o prompting by
//     server.
//  3. To return new destination URLs that were found via Location:
//     redirects.
//

define('HTTP_REQUEST_METHOD_GET',     'GET',     true);
define('HTTP_REQUEST_METHOD_HEAD',    'HEAD',    true);
define('HTTP_REQUEST_METHOD_POST',    'POST',    true);
define('HTTP_REQUEST_METHOD_PUT',     'PUT',     true);
define('HTTP_REQUEST_METHOD_DELETE',  'DELETE',  true);
define('HTTP_REQUEST_METHOD_OPTIONS', 'OPTIONS', true);
define('HTTP_REQUEST_METHOD_TRACE',   'TRACE',   true);

define('HTTP_REQUEST_HTTP_VER_1_0', '1.0', true);
define('HTTP_REQUEST_HTTP_VER_1_1', '1.1', true);

class HTTP_Request {

    /**
    * Instance of Net_URL
    * @var object Net_URL
    */
    var $_url;
    
    // iNetOffice added members:
    var $_furl_changed;  // has the url been changed due to a REDIRECT?
    var $_built_uri;     // A complete URI we built for to perform the request.
    var $_authenticates; // count of authenticate responses and replies.
    var $_basicAuthUnsecure = false;    // AUTH
    
    // Debug and Diagnostics support. Record a transcript of the entire
    // request conversation.
    var $_RequestTranscript = ""; // for debugging.
    var $_fRequestTranscript = true;

    /**
    * Type of request
    * @var string
    */
    var $_method;

    /**
    * HTTP Version
    * @var string
    */
    var $_http;

    /**
    * Request headers
    * @var array
    */
    var $_requestHeaders;

    /**
    * Auth Username
    * @var string
    */
    var $_user;           // AUTH
    
    /**
    * Auth Password
    * @var string
    */
    var $_pass;

    /**
    * Socket object
    * @var object Net_Socket
    */
    var $_sock;
    
    /**
    * Proxy server
    * @var string
    */
    var $_proxy_host;
    
    /**
    * Proxy port
    * @var integer
    */
    var $_proxy_port;
    
    /**
    * Proxy username
    * @var string
    */
    var $_proxy_user;
    
    /**
    * Proxy password
    * @var string
    */
    var $_proxy_pass;

    /**
    * Post data
    * @var array
    */
    var $_postData;

   /**
    * Request body  
    * @var string
    */
    var $_body;

   /**
    * A list of methods that MUST NOT have a request body, per RFC 2616
    * @var array
    */
    var $_bodyDisallowed = array('TRACE');

   /**
    * Files to post 
    * @var array
    */
    var $_postFiles = array();

    /**
    * Connection timeout.
    * @var float
    */
    var $_timeout;
    
    /**
    * HTTP_Response object
    * @var object HTTP_Response
    */
    var $_response;
    
    /**
    * Whether to allow redirects
    * @var boolean
    */
    var $_allowRedirects;
    
    /**
    * Maximum redirects allowed
    * @var integer
    */
    var $_maxRedirects;
    
    /**
    * Current number of redirects
    * @var integer
    */
    var $_redirects;

   /**
    * Whether to append brackets [] to array variables
    * @var bool
    */
    var $_useBrackets = true;

   /**
    * Attached listeners
    * @var array
    */
    var $_listeners = array();

   /**
    * Whether to save response body in response object property  
    * @var bool
    */
    var $_saveBody = true;

   /**
    * Timeout for reading from socket (array(seconds, microseconds))
    * @var array
    */
    var $_readTimeout = null;

   /**
    * Options to pass to Net_Socket::connect. See stream_context_create
    * @var array
    */
    var $_socketOptions = null;
    
    /**
    * Constructor
    *
    * Sets up the object
    * @param    string  The url to fetch/access
    * @param    array   Associative array of parameters which can have the following keys:
    * <ul>
    *   <li>method         - Method to use, GET, POST etc (string)</li>
    *   <li>http           - HTTP Version to use, 1.0 or 1.1 (string)</li>
    *   <li>user           - Auth username (string)</li>
    *   <li>pass           - Auth password (string)</li>
    *   <li>proxy_host     - Proxy server host (string)</li>
    *   <li>proxy_port     - Proxy server port (integer)</li>
    *   <li>proxy_user     - Proxy auth username (string)</li>
    *   <li>proxy_pass     - Proxy auth password (string)</li>
    *   <li>timeout        - Connection timeout in seconds (float)</li>
    *   <li>allowRedirects - Whether to follow redirects or not (bool)</li>
    *   <li>maxRedirects   - Max number of redirects to follow (integer)</li>
    *   <li>useBrackets    - Whether to append [] to array variable names (bool)</li>
    *   <li>saveBody       - Whether to save response body in response object property (bool)</li>
    *   <li>readTimeout    - Timeout for reading / writing data over the socket (array (seconds, microseconds))</li>
    *   <li>socketOptions  - Options to pass to Net_Socket object (array)</li>
    *   <li>basicAuthUnsecure - allow Basic www-authenticate over non SSL connection.
    * </ul>
    * @access public
    */
    function HTTP_Request($url = '', $params = array())
    {
        $this->_method         =  HTTP_REQUEST_METHOD_GET;
        $this->_http           =  HTTP_REQUEST_HTTP_VER_1_1;
        $this->_requestHeaders = array();
        $this->_postData       = array();
        $this->_body           = null;

        $this->_user = null;
        $this->_pass = null;

        $this->_proxy_host = null;
        $this->_proxy_port = null;
        $this->_proxy_user = null;
        $this->_proxy_pass = null;

        $this->_allowRedirects = false;
        $this->_maxRedirects   = 3;
        $this->_redirects      = 0;
        $this->_authenticates  = 0;  // iNetOffice - count of authenticate requests/replies.

        $this->_timeout  = null;
        $this->_response = null;

        foreach ($params as $key => $value) {
            $this->{'_' . $key} = $value;
        }

        if (!empty($url)) {
            $this->setURL($url);
        }

        // Default useragent
        $this->addHeader('User-Agent', 'PEAR HTTP_Request class ( http://pear.php.net/ ), modified for OpenSAM');

        // We don't do keep-alives by default
        $this->addHeader('Connection', 'close');

        // iNetOffice: WE NOW DO THIS WHEN APPROPRIATE IN THE sendRequest().
        // Basic authentication
        //if (!empty($this->_user)) {
        //    $this->addHeader('Authorization', 'Basic ' . base64_encode($this->_user . ':' . $this->_pass));
        //}

        // Proxy authentication (see bug #5913)
        if (!empty($this->_proxy_user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($this->_proxy_user . ':' . $this->_proxy_pass));
        }

        // Use gzip encoding if possible
        // Avoid gzip encoding if using multibyte functions (see #1781)
        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && extension_loaded('zlib') &&
            0 == (2 & ini_get('mbstring.func_overload'))) {

            $this->addHeader('Accept-Encoding', 'gzip');
        }
    }
    
    /**
    * Generates a Host header for HTTP/1.1 requests
    *
    * @access private
    * @return string
    */
    function _generateHostHeader()
    {
        if ($this->_url->port != 80 AND strcasecmp($this->_url->protocol, 'http') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port != 443 AND strcasecmp($this->_url->protocol, 'https') == 0) {
            $host = $this->_url->host . ':' . $this->_url->port;

        } elseif ($this->_url->port == 443 AND strcasecmp($this->_url->protocol, 'https') == 0 AND strpos($this->_url->url, ':443') !== false) {
            $host = $this->_url->host . ':' . $this->_url->port;
        
        } else {
            $host = $this->_url->host;
        }

        return $host;
    }
    
    /**
    * Resets the object to its initial state (DEPRECATED).
    * Takes the same parameters as the constructor.
    *
    * @param  string $url    The url to be requested
    * @param  array  $params Associative array of parameters
    *                        (see constructor for details)
    * @access public
    * @deprecated deprecated since 1.2, call the constructor if this is necessary
    */
    function reset($url, $params = array())
    {
        $this->HTTP_Request($url, $params);
    }

    /**
    * Sets the URL to be requested
    *
    * @param  string The url to be requested
    * @access public
    */
    function setURL($url)
    {
        $this->_url = &new Net_URL($url, $this->_useBrackets);

        if (!empty($this->_url->user) || !empty($this->_url->pass)) {
            $this->setAuthCredentials($this->_url->user, $this->_url->pass);
        }

        if (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http) {
            $this->addHeader('Host', $this->_generateHostHeader());
        }
        
        // set '/' instead of empty path rather than check later (see bug #8662)
        if (empty($this->_url->path)) {
            $this->_url->path = '/';
        } 
    }
    
   /**
    * Returns the current request URL  
    *
    * @return   string  Current request URL
    * @access   public
    */
    function getUrl($url)
    {
        return empty($this->_url)? '': $this->_url->getUrl();
    }

    /**
    * Sets a proxy to be used
    *
    * @param string     Proxy host
    * @param int        Proxy port
    * @param string     Proxy username
    * @param string     Proxy password
    * @access public
    */
    function setProxy($host, $port = 8080, $user = null, $pass = null)
    {
        $this->_proxy_host = $host;
        $this->_proxy_port = $port;
        $this->_proxy_user = $user;
        $this->_proxy_pass = $pass;

        if (!empty($user)) {
            $this->addHeader('Proxy-Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
        }
    }

    /**
    * Sets basic authentication parameters
    *
    * @param string     Username
    * @param string     Password     AUTH
    */
    function setAuthCredentials($user, $pass)
    {
        $this->_user = $user;
        $this->_pass = $pass;

        // THIS IS now delayed to sendReqeust() since only then do we know if we can do this
        //  securely over SSL.
        //$this->addHeader('Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
    }

    /**
    * Sets the method to be used, GET, POST etc.
    *
    * @param string     Method to use. Use the defined constants for this
    * @access public
    */
    function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
    * Sets the HTTP version to use, 1.0 or 1.1
    *
    * @param string     Version to use. Use the defined constants for this
    * @access public
    */
    function setHttpVer($http)
    {
        $this->_http = $http;
    }

    /**
    * Adds a request header
    *
    * @param string     Header name
    * @param string     Header value
    * @access public
    */
    function addHeader($name, $value)
    {
        $this->_requestHeaders[strtolower($name)] = $value;
    }

    /**
    * Removes a request header
    *
    * @param string     Header name to remove
    * @access public
    */
    function removeHeader($name)
    {
        if (isset($this->_requestHeaders[strtolower($name)])) {
            unset($this->_requestHeaders[strtolower($name)]);
        }
    }

    /**
    * Adds a querystring parameter
    *
    * @param string     Querystring parameter name
    * @param string     Querystring parameter value
    * @param bool       Whether the value is already urlencoded or not, default = not
    * @access public
    */
    function addQueryString($name, $value, $preencoded = false)
    {
        $this->_url->addQueryString($name, $value, $preencoded);
    }    
    
    /**
    * Sets the querystring to literally what you supply
    *
    * @param string     The querystring data. Should be of the format foo=bar&x=y etc
    * @param bool       Whether data is already urlencoded or not, default = already encoded
    * @access public
    */
    function addRawQueryString($querystring, $preencoded = true)
    {
        $this->_url->addRawQueryString($querystring, $preencoded);
    }

    /**
    * Adds postdata items
    *
    * @param string     Post data name
    * @param string     Post data value
    * @param bool       Whether data is already urlencoded or not, default = not
    * @access public
    */
    function addPostData($name, $value, $preencoded = false)
    {
        if ($preencoded) {
            $this->_postData[$name] = $value;
        } else {
            $this->_postData[$name] = $this->_arrayMapRecursive('urlencode', $value);
        }
    }

   /**
    * Recursively applies the callback function to the value
    * 
    * @param    mixed   Callback function
    * @param    mixed   Value to process
    * @access   private
    * @return   mixed   Processed value
    */
    function _arrayMapRecursive($callback, $value)
    {
        if (!is_array($value)) {
            return call_user_func($callback, $value);
        } else {
            $map = array();
            foreach ($value as $k => $v) {
                $map[$k] = $this->_arrayMapRecursive($callback, $v);
            }
            return $map;
        }
    }

   /**
    * Adds a file to upload
    * 
    * This also changes content-type to 'multipart/form-data' for proper upload
    * 
    * @access public
    * @param  string    name of file-upload field
    * @param  mixed     file name(s)
    * @param  mixed     content-type(s) of file(s) being uploaded
    * @return bool      true on success
    * @throws PEAR_Error
    */
    function addFile($inputName, $fileName, $contentType = 'application/octet-stream')
    {
        if (!is_array($fileName) && !is_readable($fileName)) {
            return PEAR::raiseError("File '{$fileName}' is not readable");
        } elseif (is_array($fileName)) {
            foreach ($fileName as $name) {
                if (!is_readable($name)) {
                    return PEAR::raiseError("File '{$name}' is not readable");
                }
            }
        }
        $this->addHeader('Content-Type', 'multipart/form-data');
        $this->_postFiles[$inputName] = array(
            'name' => $fileName,
            'type' => $contentType
        );
        return true;
    }

    /**
    * Adds raw postdata (DEPRECATED)
    *
    * @param string     The data
    * @param bool       Whether data is preencoded or not, default = already encoded
    * @access public
    * @deprecated       deprecated since 1.3.0, method setBody() should be used instead
    */
    function addRawPostData($postdata, $preencoded = true)
    {
        $this->_body = $preencoded ? $postdata : urlencode($postdata);
    }

   /**
    * Sets the request body (for POST, PUT and similar requests)
    *
    * @param    string  Request body
    * @access   public
    */
    function setBody($body)
    {
        $this->_body = $body;
    }

    /**
    * Clears any postdata that has been added (DEPRECATED). 
    * 
    * Useful for multiple request scenarios.
    *
    * @access public
    * @deprecated deprecated since 1.2
    */
    function clearPostData()
    {
        $this->_postData = null;
    }

    /**
    * Appends a cookie to "Cookie:" header
    * 
    * @param string $name cookie name
    * @param string $value cookie value
    * @access public
    */
    function addCookie($name, $value)
    {
        $cookies = isset($this->_requestHeaders['cookie']) ? $this->_requestHeaders['cookie']. '; ' : '';
        $this->addHeader('Cookie', $cookies . $name . '=' . $value);
    }
    
    /**
    * Clears any cookies that have been added (DEPRECATED). 
    * 
    * Useful for multiple request scenarios
    *
    * @access public
    * @deprecated deprecated since 1.2
    */
    function clearCookies()
    {
        $this->removeHeader('Cookie');
    }

    /**
    * Sends the request
    *
    * @access public
    * @param  bool   Whether to store response body in Response object property,
    *                set this to false if downloading a LARGE file and using a Listener
    * @return mixed  PEAR error on error, true otherwise
    */
    function sendRequest($saveBody = true)
    {
        if (!($this->_url instanceof Net_URL)) {
            return PEAR::raiseError('No URL given.');
        }

        $host = isset($this->_proxy_host) ? $this->_proxy_host : $this->_url->host;
        $port = isset($this->_proxy_port) ? $this->_proxy_port : $this->_url->port;

        // Make note of secure connection status for multiple uses:
        $fSecure = (strcasecmp($this->_url->protocol, 'https') == 0);
        // 4.3.0 supports SSL connections using OpenSSL. The function test determines
        // we running on at least 4.3.0
        if ( $fSecure AND function_exists('file_get_contents') AND extension_loaded('openssl')) {
            if (isset($this->_proxy_host)) {
                return PEAR::raiseError('HTTPS proxies are not supported.');
            }
            $host = 'ssl://' . $host;
        }
        
        // AUTH:
        // Add authenticate header if we are ssl or if the caller has approved basic auth
        // over non-ssl, and only if there is not already an Authorization header:
        if( !empty( $this->_user ) && !empty( $this->_pass ) 
         && ($this->_basicAuthUnsecure || $fSecure) 
 			   && empty( $this->_requestHeaders['authorization'] ) ) {
          $this->addHeader('Authorization', 'Basic ' . base64_encode($this->_user . ':' . $this->_pass));
        }

        // magic quotes may fuck up file uploads and chunked response processing
        $magicQuotes = ini_get('magic_quotes_runtime');
        ini_set('magic_quotes_runtime', false);

        // RFC 2068, section 19.7.1: A client MUST NOT send the Keep-Alive 
        // connection token to a proxy server...
        if (isset($this->_proxy_host) && !empty($this->_requestHeaders['connection']) &&
            'Keep-Alive' == $this->_requestHeaders['connection'])
        {
            $this->removeHeader('connection');
        }

        // iNetOffice: support transcript record for diagnoses:
        if( $this->_fRequestTranscript ) {
          $this->_RequestTranscript .= "\n\n###### WebDAV Transcript --- CONNECTING $host, port: $port\n";
        }
        
        $keepAlive = (HTTP_REQUEST_HTTP_VER_1_1 == $this->_http && empty($this->_requestHeaders['connection'])) ||
                     (!empty($this->_requestHeaders['connection']) && 'Keep-Alive' == $this->_requestHeaders['connection']);
        $sockets   = &PEAR::getStaticProperty('HTTP_Request', 'sockets');
        $sockKey   = $host . ':' . $port;
        unset($this->_sock);

        // There is a connected socket in the "static" property?
        if ($keepAlive && !empty($sockets[$sockKey]) &&
            !empty($sockets[$sockKey]->fp)) 
        {
            $this->_sock =& $sockets[$sockKey];
            $err = null;
        } else {
            $this->_notify('connect');
            $this->_sock =& new Net_Socket();
            $err = $this->_sock->connect($host, $port, null, $this->_timeout, $this->_socketOptions);
        }
        PEAR::isError($err) or $err = $this->_sock->write($this->_buildRequest());

        if (!PEAR::isError($err)) {
            if (!empty($this->_readTimeout)) {
                $this->_sock->setTimeout($this->_readTimeout[0], $this->_readTimeout[1]);
            }

            $this->_notify('sentRequest');

            // Read the response
            $this->_response = &new HTTP_Response($this->_sock, $this->_listeners);

            $err = $this->_response->process(
                $this->_saveBody && $saveBody,
                HTTP_REQUEST_METHOD_HEAD != $this->_method
            );
		     if ($keepAlive) {
                $keepAlive = (isset($this->_response->_headers['content-length'])
                              || (isset($this->_response->_headers['transfer-encoding'])
                                  && strtolower($this->_response->_headers['transfer-encoding']) == 'chunked'));
                if ($keepAlive) {
                    if (isset($this->_response->_headers['connection'])) {
                        $keepAlive = strtolower($this->_response->_headers['connection']) == 'keep-alive';
                    } else {
                        $keepAlive = 'HTTP/'.HTTP_REQUEST_HTTP_VER_1_1 == $this->_response->_protocol;
                    }
                }
            }
            
            // iNetOffice transcript debug support:
            if( $this->_fRequestTranscript ) {
              $headers_str = "";
              if(is_array($this->_response->_headers)){
				  foreach( $this->_response->_headers as $hdr => $hval ) {
					$headers_str .= $hdr . ": " . $hval . "\n";
				  }
              }
              $this->_RequestTranscript .= "\n###### WebDAV Transcript -- RESPONSE:\n" 
  				     . $this->_response->_statusline . "\n" . $headers_str;
							if( strlen( $this->_response->_body ) < 10000 ) {
								$this->_RequestTranscript .= $this->_response->_body;
							}
            }
        }

        ini_set('magic_quotes_runtime', $magicQuotes);

        if (PEAR::isError($err)) {
            return $err;
        }

        if (!$keepAlive) {
            $this->disconnect();
        // Store the connected socket in "static" property
        } elseif (empty($sockets[$sockKey]) || empty($sockets[$sockKey]->fp)) {
            $sockets[$sockKey] =& $this->_sock;
        }

        // AUTH:
        // Check for authentication header.
        // Build the appropriate Authorization: header if possible.
        // For Digest auth, we have to combine the password with the
        // nonce value and resend it, if we have a username and password.
        if( $this->getResponseCode() == 401 && $this->_authenticates < 2 
         && !empty( $this->_user ) && !empty( $this->_pass ) ) {
          // The HTTPAuthorizationResponse object parses and tracks all the details
          // of HTTP Authentication header requests and responses.
          $aresponse = new HTTPAuthorizationResponse;
          $ar_ret = $aresponse->absorb_auth_header( $this->_response->_headers['www-authenticate'] );
          if( $ar_ret !== true ) {
            // Error
            return( PEAR::raiseError($ar_ret) );
          }
          if( $aresponse->authtype == 'basic' ) $this->_basicAuthUnsecure = true; // take note so clients can check this too.
          $a_resp_header = $aresponse->generate_auth_header( $this->_user, $this->_pass, $this->_method, $this->_built_uri );
          $this->addHeader('Authorization',$a_resp_header);
          ++$this->_authenticates;
          return( $this->sendRequest($saveBody) );
        }

        // Check for redirection
        if (    $this->_allowRedirects
            AND $this->_redirects <= $this->_maxRedirects
            AND $this->getResponseCode() > 300
            AND $this->getResponseCode() < 399
            AND !empty($this->_response->_headers['location'])) {

            $redirect = $this->_response->_headers['location'];

            // Absolute URL
            if (preg_match('/^https?:\/\//i', $redirect)) {
                $this->_url = &new Net_URL($redirect);
                $this->_furl_changed = true; // iNetOffice: make note that a redirect url-changed has happened.
                $this->addHeader('Host', $this->_generateHostHeader());
            // Absolute path
            } elseif ($redirect{0} == '/') {
                $this->_url->path = $redirect;
                $this->_furl_changed = true; // iNetOffice: make note that a redirect url-changed has happened.
            
            // Relative path
            } elseif (substr($redirect, 0, 3) == '../' OR substr($redirect, 0, 2) == './') {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $redirect = Net_URL::resolvePath($redirect);
                $this->_url->path = $redirect;
                $this->_furl_changed = true;  // iNetOffice: make note that a redirect url-changed has happened.
                
            // Filename, no path
            } else {
                if (substr($this->_url->path, -1) == '/') {
                    $redirect = $this->_url->path . $redirect;
                } else {
                    $redirect = dirname($this->_url->path) . '/' . $redirect;
                }
                $this->_url->path = $redirect;
                $this->_furl_changed = true;  // iNetOffice: make note that a redirect url-changed has happened.
            }

            $this->_redirects++;
            return $this->sendRequest($saveBody);

        // Too many redirects
        } elseif ($this->_allowRedirects AND $this->_redirects > $this->_maxRedirects) {
            return PEAR::raiseError('Too many redirects');
        }

        return true;
    }

    /**
     * Disconnect the socket, if connected. Only useful if using Keep-Alive.
     *
     * @access public
     */
    function disconnect()
    {
        if (!empty($this->_sock) && !empty($this->_sock->fp)) {
            $this->_notify('disconnect');
            $this->_sock->disconnect();
        }
    }
    
    /**
    * Returns the response code
    *
    * @access public
    * @return mixed     Response code, false if not set
    */
    function getResponseCode()
    {
        return isset($this->_response->_code) ? $this->_response->_code : false;
    }

    /**
    * Returns either the named header or all if no name given
    *
    * @access public
    * @param string     The header name to return, do not set to get all headers
    * @return mixed     either the value of $headername (false if header is not present)
    *                   or an array of all headers
    */
    function getResponseHeader($headername = null)
    {
        if (!isset($headername)) {
            return isset($this->_response->_headers)? $this->_response->_headers: array();
        } else {
            $headername = strtolower($headername);
            return isset($this->_response->_headers[$headername]) ? $this->_response->_headers[$headername] : false;
        }
    }

    /**
    * Returns the body of the response
    *
    * @access public
    * @return mixed     response body, false if not set
    */
    function getResponseBody()
    {
        return isset($this->_response->_body) ? $this->_response->_body : false;
    }

    /**
    * Returns cookies set in response
    * 
    * @access public
    * @return mixed     array of response cookies, false if none are present
    */
    function getResponseCookies()
    {
        return isset($this->_response->_cookies) ? $this->_response->_cookies : false;
    }

    /**
    * Builds the request string
    *
    * @access private
    * @return string The request string
    */
    function _buildRequest()
    {
        $separator = ini_get('arg_separator.output');
        ini_set('arg_separator.output', '&');
        $querystring = ($querystring = $this->_url->getQueryString()) ? '?' . $querystring : '';
        ini_set('arg_separator.output', $separator);

        $host = isset($this->_proxy_host) ? $this->_url->protocol . '://' . $this->_url->host : '';
        $port = (isset($this->_proxy_host) AND $this->_url->port != 80) ? ':' . $this->_url->port : '';
        $path = (empty($this->_url->path)? '/': $this->_url->path) . $querystring;
        $url  = $host . $port . $path;

        $request = $this->_method . ' ' . $url . ' HTTP/' . $this->_http . "\r\n";
        $this->_built_uri = $url;  // store for future investigation and use in www-authenticate repsonses.

        if (in_array($this->_method, $this->_bodyDisallowed) ||
            (empty($this->_body) && (HTTP_REQUEST_METHOD_POST != $this->_method ||
             (empty($this->_postData) && empty($this->_postFiles)))))
        {

            $this->removeHeader('Content-Type');
        } else {
            if (empty($this->_requestHeaders['content-type'])) {
                // Add default content-type
                $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
            } elseif ('multipart/form-data' == $this->_requestHeaders['content-type']) {
                $boundary = 'HTTP_Request_' . md5(uniqid('request') . microtime());
                $this->addHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
            }
        }

        // Request Headers
        if (!empty($this->_requestHeaders)) {
            foreach ($this->_requestHeaders as $name => $value) {
                $canonicalName = implode('-', array_map('ucfirst', explode('-', $name)));
                $request      .= $canonicalName . ': ' . $value . "\r\n";
            }
        }

		$request = str_replace("Connection:  close", "Connection: close", $request);
		$request = str_replace("Connection: close\r\n", "", $request);
		$request .= "Connection: close\r\n\r\n";

        // No post data or wrong method, so simply add a final CRLF
        if (in_array($this->_method, $this->_bodyDisallowed) || 
            (HTTP_REQUEST_METHOD_POST != $this->_method && empty($this->_body))) {

            $request .= "\r\n";

        // Post data if it's an array
        } elseif (HTTP_REQUEST_METHOD_POST == $this->_method && 
                  (!empty($this->_postData) || !empty($this->_postFiles))) {

            // "normal" POST request
            if (!isset($boundary)) {
                $postdata = implode('&', array_map(
                    create_function('$a', 'return $a[0] . \'=\' . $a[1];'), 
                    $this->_flattenArray('', $this->_postData)
                ));

            // multipart request, probably with file uploads
            } else {
                $postdata = '';
                if (!empty($this->_postData)) {
                    $flatData = $this->_flattenArray('', $this->_postData);
                    foreach ($flatData as $item) {
                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $item[0] . '"';
                        $postdata .= "\r\n\r\n" . urldecode($item[1]) . "\r\n";
                    }
                }
                foreach ($this->_postFiles as $name => $value) {
                    if (is_array($value['name'])) {
                        $varname       = $name . ($this->_useBrackets? '[]': '');
                    } else {
                        $varname       = $name;
                        $value['name'] = array($value['name']);
                    }
                    foreach ($value['name'] as $key => $filename) {
                        $fp   = fopen($filename, 'r');
                        $data = fread($fp, filesize($filename));
                        fclose($fp);
                        $basename = basename($filename);
                        $type     = is_array($value['type'])? @$value['type'][$key]: $value['type'];

                        $postdata .= '--' . $boundary . "\r\n";
                        $postdata .= 'Content-Disposition: form-data; name="' . $varname . '"; filename="' . $basename . '"';
                        $postdata .= "\r\nContent-Type: " . $type;
                        $postdata .= "\r\n\r\n" . $data . "\r\n";
                    }
                }
                $postdata .= '--' . $boundary . "--\r\n";
            }
//            $request .= 'Content-Length: ' . strlen($postdata) . "\r\n\r\n";
            $request .= $postdata;

        // Explicitly set request body
        } elseif (!empty($this->_body)) {

//            $request .= 'Content-Length: ' . strlen($this->_body) . "\r\n\r\n";
            $request .= $this->_body;
        }
        
        // Add to the Request transcript:
        if( $this->_fRequestTranscript ) {
          $this->_RequestTranscript .= $request;
        }
        
 		
        return $request;
    }

   /**
    * Helper function to change the (probably multidimensional) associative array
    * into the simple one.
    *
    * @param    string  name for item
    * @param    mixed   item's values
    * @return   array   array with the following items: array('item name', 'item value');
    */
    function _flattenArray($name, $values)
    {
        if (!is_array($values)) {
            return array(array($name, $values));
        } else {
            $ret = array();
            foreach ($values as $k => $v) {
                if (empty($name)) {
                    $newName = $k;
                } elseif ($this->_useBrackets) {
                    $newName = $name . '[' . $k . ']';
                } else {
                    $newName = $name;
                }
                $ret = array_merge($ret, $this->_flattenArray($newName, $v));
            }
            return $ret;
        }
    }


   /**
    * Adds a Listener to the list of listeners that are notified of
    * the object's events
    * 
    * @param    object   HTTP_Request_Listener instance to attach
    * @return   boolean  whether the listener was successfully attached
    * @access   public
    */
    function attach(&$listener)
    {
        if (!($listener instanceof HTTP_Request_Listener)) {
            return false;
        }
        $this->_listeners[$listener->getId()] =& $listener;
        return true;
    }


   /**
    * Removes a Listener from the list of listeners 
    * 
    * @param    object   HTTP_Request_Listener instance to detach
    * @return   boolean  whether the listener was successfully detached
    * @access   public
    */
    function detach(&$listener)
    {
        if (!($listener instanceof HTTP_Request_Listener) || 
            !isset($this->_listeners[$listener->getId()])) {
            return false;
        }
        unset($this->_listeners[$listener->getId()]);
        return true;
    }


   /**
    * Notifies all registered listeners of an event.
    * 
    * Events sent by HTTP_Request object
    * - 'connect': on connection to server
    * - 'sentRequest': after the request was sent
    * - 'disconnect': on disconnection from server
    * 
    * Events sent by HTTP_Response object
    * - 'gotHeaders': after receiving response headers (headers are passed in $data)
    * - 'tick': on receiving a part of response body (the part is passed in $data)
    * - 'gzTick': on receiving a gzip-encoded part of response body (ditto)
    * - 'gotBody': after receiving the response body (passes the decoded body in $data if it was gzipped)
    * 
    * @param    string  Event name
    * @param    mixed   Additional data
    * @access   private
    */
    function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }
}


?>