<?
/**
* Response class to complement the Request class
*/
class HTTP_Response
{
    /**
    * Socket object
    * @var object
    */
    var $_sock;

    /**
    * Protocol
    * @var string
    */
    var $_protocol;
    
    /**
    * Return code
    * @var string
    */
    var $_code;
    
    /**
    * Response headers
    * @var array
    */
    var $_headers;

    /**
    * Cookies set in response  
    * @var array
    */
    var $_cookies;

    /**
    * Response body
    * @var string
    */
    var $_body = '';

   /**
    * Used by _readChunked(): remaining length of the current chunk
    * @var string
    */
    var $_chunkLength = 0;

   /**
    * Attached listeners
    * @var array
    */
    var $_listeners = array();

   /**
    * Bytes left to read from message-body
    * @var null|int
    */
    var $_toRead;

    /**
    * Constructor
    *
    * @param  object Net_Socket     socket to read the response from
    * @param  array                 listeners attached to request
    * @return mixed PEAR Error on error, true otherwise
    */
    function HTTP_Response(&$sock, &$listeners)
    {
        $this->_sock      =& $sock;
        $this->_listeners =& $listeners;
    }


   /**
    * Processes a HTTP response
    * 
    * This extracts response code, headers, cookies and decodes body if it 
    * was encoded in some way
    *
    * @access public
    * @param  bool      Whether to store response body in object property, set
    *                   this to false if downloading a LARGE file and using a Listener.
    *                   This is assumed to be true if body is gzip-encoded.
    * @param  bool      Whether the response can actually have a message-body.
    *                   Will be set to false for HEAD requests.
    * @throws PEAR_Error
    * @return mixed     true on success, PEAR_Error in case of malformed response
    */
    function process($saveBody = true, $canHaveBody = true)
    {
        do {
            $this->_statusline = $line = $this->_sock->readLine();
            
            if (sscanf($line, 'HTTP/%s %s', $http_version, $returncode) != 2) {
                return PEAR::raiseError('Malformed response: ' . $line);
            } else {
                $this->_protocol = 'HTTP/' . $http_version;
                $this->_code     = intval($returncode);
            }
            while ('' !== ($header = $this->_sock->readLine())) {
                $this->_processHeader($header);
            }
        } while (100 == $this->_code);

        $this->_notify('gotHeaders', $this->_headers);

        // RFC 2616, section 4.4:
        // 1. Any response message which "MUST NOT" include a message-body ... 
        // is always terminated by the first empty line after the header fields 
        // 3. ... If a message is received with both a
        // Transfer-Encoding header field and a Content-Length header field,
        // the latter MUST be ignored.
        $canHaveBody = $canHaveBody && $this->_code >= 200 && 
                       $this->_code != 204 && $this->_code != 304;

        // If response body is present, read it and decode
        $chunked = isset($this->_headers['transfer-encoding']) && ('chunked' == $this->_headers['transfer-encoding']);
        $gzipped = isset($this->_headers['content-encoding']) && ('gzip' == $this->_headers['content-encoding']);
        $hasBody = false;
        if ($canHaveBody && ($chunked || !isset($this->_headers['content-length']) || 
                0 != $this->_headers['content-length']))
        {
            if ($chunked || !isset($this->_headers['content-length'])) {
                $this->_toRead = null;
            } else {
                $this->_toRead = $this->_headers['content-length'];
            }
            while (!$this->_sock->eof() && (is_null($this->_toRead) || 0 < $this->_toRead)) {
                if ($chunked) {
                    $data = $this->_readChunked();
                } elseif (is_null($this->_toRead)) {
                    $data = $this->_sock->read(4096);
                } else {
                    $data = $this->_sock->read(min(4096, $this->_toRead));
                    $this->_toRead -= strlen($data);
                }
                if ('' == $data) {
                    break;
                } else {
                    $hasBody = true;
                    if ($saveBody || $gzipped) {
                        $this->_body .= $data;
                    }
                    $this->_notify($gzipped? 'gzTick': 'tick', $data);
                }
            }
        }

        if ($hasBody) {
            // Uncompress the body if needed
            if ($gzipped) {
                $body = $this->_decodeGzip($this->_body);
                if (PEAR::isError($body)) {
                    return $body;
                }
                $this->_body = $body;
                $this->_notify('gotBody', $this->_body);
            } else {
                $this->_notify('gotBody');
            }
        }
        return true;
    }


   /**
    * Processes the response header
    *
    * @access private
    * @param  string    HTTP header
    */
    function _processHeader($header)
    {
        if (false === strpos($header, ':')) {
            return;
        }
        list($headername, $headervalue) = explode(':', $header, 2);
        $headername  = strtolower($headername);
        $headervalue = ltrim($headervalue);
        
        if ('set-cookie' != $headername) {
            if (isset($this->_headers[$headername])) {
                $this->_headers[$headername] .= ',' . $headervalue;
            } else {
                $this->_headers[$headername]  = $headervalue;
            }
        } else {
            $this->_parseCookie($headervalue);
        }
    }


   /**
    * Parse a Set-Cookie header to fill $_cookies array
    *
    * @access private
    * @param  string    value of Set-Cookie header
    */
    function _parseCookie($headervalue)
    {
        $cookie = array(
            'expires' => null,
            'domain'  => null,
            'path'    => null,
            'secure'  => false
        );

        // Only a name=value pair
        if (!strpos($headervalue, ';')) {
            $pos = strpos($headervalue, '=');
            $cookie['name']  = trim(substr($headervalue, 0, $pos));
            $cookie['value'] = trim(substr($headervalue, $pos + 1));

        // Some optional parameters are supplied
        } else {
            $elements = explode(';', $headervalue);
            $pos = strpos($elements[0], '=');
            $cookie['name']  = trim(substr($elements[0], 0, $pos));
            $cookie['value'] = trim(substr($elements[0], $pos + 1));

            for ($i = 1; $i < count($elements); $i++) {
                if (false === strpos($elements[$i], '=')) {
                    $elName  = trim($elements[$i]);
                    $elValue = null;
                } else {
                    list ($elName, $elValue) = array_map('trim', explode('=', $elements[$i]));
                }
                $elName = strtolower($elName);
                if ('secure' == $elName) {
                    $cookie['secure'] = true;
                } elseif ('expires' == $elName) {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ('path' == $elName || 'domain' == $elName) {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        $this->_cookies[] = $cookie;
    }


   /**
    * Read a part of response body encoded with chunked Transfer-Encoding
    * 
    * @access private
    * @return string
    */
    function _readChunked()
    {
        // at start of the next chunk?
        if (0 == $this->_chunkLength) {
            $line = $this->_sock->readLine();
            if (preg_match('/^([0-9a-f]+)/i', $line, $matches)) {
                $this->_chunkLength = hexdec($matches[1]); 
                // Chunk with zero length indicates the end
                if (0 == $this->_chunkLength) {
                    $this->_sock->readLine(); // make this an eof()
                    return '';
                }
            } else {
                return '';
            }
        }
        $data = $this->_sock->read($this->_chunkLength);
        $this->_chunkLength -= strlen($data);
        if (0 == $this->_chunkLength) {
            $this->_sock->readLine(); // Trailing CRLF
        }
        return $data;
    }


   /**
    * Notifies all registered listeners of an event.
    * 
    * @param    string  Event name
    * @param    mixed   Additional data
    * @access   private
    * @see HTTP_Request::_notify()
    */
    function _notify($event, $data = null)
    {
        foreach (array_keys($this->_listeners) as $id) {
            $this->_listeners[$id]->update($this, $event, $data);
        }
    }


   /**
    * Decodes the message-body encoded by gzip
    *
    * The real decoding work is done by gzinflate() built-in function, this
    * method only parses the header and checks data for compliance with
    * RFC 1952  
    *
    * @access   private
    * @param    string  gzip-encoded data
    * @return   string  decoded data
    */
    function _decodeGzip($data)
    {
        $length = strlen($data);
        // If it doesn't look like gzip-encoded data, don't bother
        if (18 > $length || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            return $data;
        }
        $method = ord(substr($data, 2, 1));
        if (8 != $method) {
            return PEAR::raiseError('_decodeGzip(): unknown compression method');
        }
        $flags = ord(substr($data, 3, 1));
        if ($flags & 224) {
            return PEAR::raiseError('_decodeGzip(): reserved bits are set');
        }

        // header is 10 bytes minimum. may be longer, though.
        $headerLength = 10;
        // extra fields, need to skip 'em
        if ($flags & 4) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short');
            }
            $extraLength = unpack('v', substr($data, 10, 2));
            if ($length - $headerLength - 2 - $extraLength[1] < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short');
            }
            $headerLength += $extraLength[1] + 2;
        }
        // file name, need to skip that
        if ($flags & 8) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short');
            }
            $filenameLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $filenameLength || $length - $headerLength - $filenameLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short');
            }
            $headerLength += $filenameLength + 1;
        }
        // comment, need to skip that also
        if ($flags & 16) {
            if ($length - $headerLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short');
            }
            $commentLength = strpos(substr($data, $headerLength), chr(0));
            if (false === $commentLength || $length - $headerLength - $commentLength - 1 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short');
            }
            $headerLength += $commentLength + 1;
        }
        // have a CRC for header. let's check
        if ($flags & 1) {
            if ($length - $headerLength - 2 < 8) {
                return PEAR::raiseError('_decodeGzip(): data too short');
            }
            $crcReal   = 0xffff & crc32(substr($data, 0, $headerLength));
            $crcStored = unpack('v', substr($data, $headerLength, 2));
            if ($crcReal != $crcStored[1]) {
                return PEAR::raiseError('_decodeGzip(): header CRC check failed');
            }
            $headerLength += 2;
        }
        // unpacked data CRC and size at the end of encoded data
        $tmp = unpack('V2', substr($data, -8));
        $dataCrc  = $tmp[1];
        $dataSize = $tmp[2];

        // finally, call the gzinflate() function
        $unpacked = @gzinflate(substr($data, $headerLength, -8), $dataSize);
        if (false === $unpacked) {
            return PEAR::raiseError('_decodeGzip(): gzinflate() call failed');
        } elseif ($dataSize != strlen($unpacked)) {
            return PEAR::raiseError('_decodeGzip(): data size check failed');
        } elseif ($dataCrc != crc32($unpacked)) {
            return PEAR::raiseError('_decodeGzip(): data CRC check failed');
        }
        return $unpacked;
    }
} // End class HTTP_Response

?>