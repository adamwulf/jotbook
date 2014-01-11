<?

// AUTH:
class HTTPAuthorizationResponse
{
    var $authtype;  // basic or digest.
    
    var $realm;     // All the particulars passed to us from the server.
    var $nonce;
    var $opaque;
    var $stale;
    var $algorithm;
    var $noq;
    var $qop;
    var $domain;
    
    /** Return HTTP Auth header that logs user on */
    // Note: this returns just the after-colon portion of the full header which would be:
    //  Authorization: Digest....
    function generate_auth_header( $user, $password, $reqmethod, $uri )
    {
      if( $this->authtype == 'basic' ) {
        return( "Basic " . base64_encode($user . ':' . $password) );
      }
      else {
        // DIGEST
        $cnonce = (string)(rand()."TJS"); // client once-value, random string.
        $nc = "00000001";
        
        // RFC2617 3.2.2.1 Request-Digest
        $a1 = md5($user.':'.$this->realm.':'.$password);
        $a2 = md5($reqmethod.':'.$uri);
        
        if( !empty( $this->qop ) ) {
          // This from the RFC:
          //$requestdigest  = KD ( H(A1),     unq(nonce-value)
          //                                ":" nc-value
          //                                ":" unq(cnonce-value)
          //                                ":" unq(qop-value)
          //                                ":" H(A2)
          $requestdigest = md5($a1.':'.$this->nonce.':'.$nc.':'.$cnonce.':'.$this->qop.':'.$a2);
        } else {
          // This from the RFC:
          // $requestdigest  =
          //       <"> < KD ( H(A1), unq(nonce-value) ":" H(A2) ) >
          $requestdigest = md5($a1.':'.$this->nonce.':'.$a2);   
        }
        
        return('Digest '.
            'username="'. $user.            '", '.
            'realm="'.    $this->realm.     '", '.
            'qop="'.      $this->qop.       '", '.
            'algorithm="'.$this->algorithm. '", '.
            'uri="'.      $uri.             '", '.
            'nonce="'.    $this->nonce.     '", '.
            (empty($this->qop) ? '' : ('cnonce="'.   $cnonce.          '", ')).
            'nc='.        $nc.              ', '.
            (empty($this->opaque) ? '' : ('opaque="'.   $this->opaque.    '", ')).
            'response="'. $requestdigest.   '"'
        );
      }
    }
    
    // The caller wants to give us an www-authenticate: header for us
    // to create the response to.
    //  RETURNS: true for success.
    //           a string describing the problem for failure.
    
    function absorb_auth_header( $authheader ) {
        if(empty($authheader)) return( "invalid call -- unset parameter" );
        
        if( !strcasecmp( substr($authheader, 0, 5),'Basic' ) ) {
            $this->authtype = 'basic';
            return( true );
        }
        $this->authtype = 'digest';
        
        // Parse the line to get the specifics:
        if( eregi( 'realm="?([^"]+)"?', $authheader, $matchregs ) )    $this->realm = $matchregs[1];
        if( eregi( 'nonce="?([^"]+)"?', $authheader, $matchregs ) )    $this->nonce = $matchregs[1];
        if( eregi( 'opaque="?([^"]+)"?', $authheader, $matchregs ) )   $this->opaque = $matchregs[1];
        if( eregi( 'stale="?([^",]+)"?', $authheader, $matchregs ) )    $this->stale = $matchregs[1];
        if( eregi( 'algorithm="?([^,"]+)"?', $authheader, $matchregs ) )  $this->algorithm = $matchregs[1];
        if( eregi( 'noq="?([^",]+)"?', $authheader, $matchregs ) )      $this->noq = $matchregs[1];
        if( eregi( 'qop="?([^",]+)"?', $authheader, $matchregs ) )      $this->qop = $matchregs[1];
        if( eregi( 'domain="?([^"]+)"?', $authheader, $matchregs ) )   $this->domain = $matchregs[1];

        // Check that we can handle this:
        if( strcasecmp( $this->algorithm, "MD5" ) ) return( "Only MD5 digest is supported" ); // the only one we do.
        if( !empty($this->qop) && strcasecmp( $this->qop, "auth" ) ) return( "Only auth qop is supported" ); 
        
        return true;
    }
}


?>