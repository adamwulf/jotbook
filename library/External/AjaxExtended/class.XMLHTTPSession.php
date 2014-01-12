<?php

class XMLHTTPSession {

  var $_id = '';
  var $_file = '';
  var $_dir; // initialized in constructor
  var $_data = Array();
  var $_loaded = false;
  var $abort = false;

  function XMLHTTPSession($id = '', $total = 1) {
  	$this->_dir = ROOT . "javascript/ajaxextended/php.server/data/";
    $this->_setID($id);
    $this->_setTotal($total);
  }

  function _setID($id) {
    if(strlen($id) < 1) { return false; }
    if(strpos($id, ".") > -1 or strpos($id, "/") > -1) {
      return $this->throwError('Incorrect ID (HACK ATTEMPT)'); }
    $this->_id = $id;
    $this->_file = $this->_dir.$id.'.session';
  }

  function _setTotal($total = 1) {
    $new_array = ($total > 0) ? array_fill(0, $total, false) : Array();
    $this->_data = $this->_data + $new_array;
  }

  function save($data, $number = 0) {
    if(!$this->_loaded) { $this->_load(); }
    $current = intval($number);
    $total = count($this->_data);
    if($current >= $total) { $this->_setTotal($current + 1); }
    $this->_data[$current] = $data;
    $this->_save();
  }

  function _save() {
    $fp = @fopen($this->_file, 'w');
    if(!$fp) { throw new Exception('Cannot open session file: ' . $this->_file); }
    fwrite($fp, serialize($this->_data)); fclose($fp);
  }

  function _complete() {
    if(!$this->_loaded) { $this->_load(); }
    foreach($this->_data as $loaded) {
      if($loaded == false) { return false; }
    }
    return true;
  }

  function get() {
    if (!$this->_complete()) { return ''; }
    @unlink($this->_file);
    return base64_decode(implode('', $this->_data));
  }

  function _load() {
    if($this->abort) { return false; }
    if(!file_exists($this->_file)) { return $this->_data; }
    $fp = fopen($this->_file, 'r');
    if(!$fp) { return $this->throwError('Cannot open session file'); }
    $data = '';
    while(!feof($fp)) { $data .= fgets($fp, 1024); }
    fclose($fp); $this->_data = unserialize($data);
    $this->_loaded = true;
  }

  function throwError($desc) {
    $this->abort = true;
    $this->errorDescription = $desc;
    return false;
  }

}

?>