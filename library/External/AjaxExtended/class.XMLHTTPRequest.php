<?php

class XMLHTTPRequest extends HTTP_Request {

  function getAllResponseHeaders() {
    return $this->_response->_headers;
  }

}

?>