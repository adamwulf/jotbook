<?php
//
// +---------------------------------------------------------------------------+
// | Facebook Development Platform (beta) PHP5 client                          |   
// +---------------------------------------------------------------------------+
// | Copyright (c) 2006 Facebook, Inc.                                         | 
// | All rights reserved.                                                      |
// |                                                                           |
// | Redistribution and use in source and binary forms, with or without        |
// | modification, are permitted provided that the following conditions        |
// | are met:                                                                  |
// |                                                                           |
// | 1. Redistributions of source code must retain the above copyright         |
// |    notice, this list of conditions and the following disclaimer.          |
// | 2. Redistributions in binary form must reproduce the above copyright      |
// |    notice, this list of conditions and the following disclaimer in the    |
// |    documentation and/or other materials provided with the distribution.   |
// |                                                                           |
// | THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR      |
// | IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES |
// | OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.   |
// | IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT  |
// | NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY     |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT       |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF  |
// | THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.         |
// +---------------------------------------------------------------------------+
// | For help with this library, contact developers-help@facebook.com          |
// +---------------------------------------------------------------------------+
//

/**
 * REST-based API client.
 * XXX NOTE that all of the user ids mentioned in this file are mangled.
 * @author ari
 */
class FacebookRestClient {
  public $secret;
  public $session_key;
  public $api_key;
  public $server_addr;
  public $desktop;
  public $session_secret; // not used for server apps, for desktop apps this will get set in auth_getSession

  /**
   * Create the client.
   * @param string $session_key if you haven't gotten a session key yet, leave 
   *                            this as null and then set it later by just 
   *                            directly accessing the $session_key member 
   *                            variable.
   * @param bool   $desktop     set to true if you are a desktop client
   */
  public function __construct($server_addr, $api_key, $secret, $session_key=null, $desktop=false, $throw_errors=true) {
    $this->server_addr  = $server_addr;
    $this->secret       = $secret;
    $this->session_key  = $session_key;
    $this->api_key      = $api_key;
    $this->desktop      = $desktop;
    $this->throw_errors = $throw_errors;
    $this->last_call_success = true;
    $this->last_error = array();
  }

  /**
   * Retrieves the events of the currently logged in user between the provided
   * UTC times
   * @param int $start_time UTC lower bound
   * @param int $end_time UTC upper_bound
   * @return array of friends
   */
  public function events_getInWindow($start_time, $end_time) {
    return $this->call_method('facebook.events.getInWindow',
        array('start_time' => $start_time, 'end_time' => $end_time));
  }

  /**
   * Retrieves the friends of the currently logged in user.
   * @return array of friends
   */
  public function friends_get() {
    return $this->call_method('facebook.friends.get', array());
  }

  /**
   * Retrieves the friends of the currently logged in user, who are also users
   * of the calling application.
   * @return array of friends
   */
  public function friends_getAppUsers() {
    return $this->call_method('facebook.friends.getAppUsers', array());
  }

  /**
   * Retrieves the friends of the currently logged in user,filtered by socialmap type
   * @param string link_type type denoted by string, e.g. "Coworkers"
   * @return array of friends
   */
  public function friends_getTyped($link_type) {
    return $this->call_method('facebook.friends.getTyped', array('link_type' => $link_type));
  }

  /**
   * Retrieves the requested info fields for the requested set of user.
   * @param array $users_arr an array of user ids to get info for
   * @param array $field_array an array of strings describing the info fields desired
   * @return an array of arrays of info fields, which may themselves be arrays :)
   */
  public function users_getInfo($users_arr, $field_array) {
    return $this->call_method('facebook.users.getInfo', array('users' => $users_arr, 'fields' => $field_array));
  }

  /**
   * Retrieves the photos that have been tagged as having the given user.
   * @param string $id  the id of the user in the photos.
   * @param int    $max the number of photos to get (if 0 returns all of them)
   * @returns an array of photo objects.
   */
  public function photos_getOfUser($id, $max=0) {
    return $this->call_method('facebook.photos.getOfUser', array('id'=>$id, 'max'=>$max));
  }

  /**
   * Retrieves the albums created by the given user.
   * @param string $id the id of the user whose albums you want.
   * @returns an array of album objects.
   */
  public function photos_getAlbums($id) {
    return $this->call_method('facebook.photos.getAlbums', array('id'=>$id));
  }

  /**
   * Retrieves the photos in a given album.
   * @param string $aid the id of the album you want, as returned by photos_getAlbums.
   * @param string $uid the id of the user whose albums you want.
   * @returns an array of photo objects.
   */
  public function photos_getFromAlbum($aid, $uid) {
    return $this->call_method('facebook.photos.getFromAlbum', array('aid'=>$aid, 'id'=>$uid));
  }

  /**
   * Retrieves the counts of unread and total messages for the current user.
   * @return an associative array with keys 'unread' and 'total'
   */
  public function messages_getCount() {
    return $this->call_method('facebook.messages.getCount', array());
  }

  /**
   * Retrieves the number of wall posts for the specified user.
   * @param string $id the id of the user to get the wall count for (leave 
   *                   blank for the count of the current user).
   * @return an int representing the number of posts
   */
  public function wall_getCount($id='') {
    return $this->call_method('facebook.wall.getCount', array('id'=>$id));
  }

  /**
   * Retrieves the number of comments on the current user's photos.
   * @return an int representing the number of comments
   */
  public function photos_getCommentCount() {
    return $this->call_method('facebook.photos.getCommentCount', array());
  }

  /**
   * Retrieves the number of pokes (unseen and total) for the current user.
   * @return an associative array with keys 'unseen' and 'total'
   */
  public function pokes_getCount() {
    return $this->call_method('facebook.pokes.getCount', array());
  }

  /**
   * Retrieves whether or not two users are friends (note: this is reflexive, 
   * the params can be swapped with no effect).
   * @param array $id1: array of ids of some length X
   * @param array $id2: array of ids of SAME length X
   * @return array of elements in {0,1} of length X, indicating whether each pair are friends
   */
  public function friends_areFriends($id1, $id2) {
    return $this->call_method('facebook.friends.areFriends', array('id1'=>$id1, 'id2'=>$id2));
  }

  /**
   * Intended for use by desktop clients.  Call this function and store the
   * result, using it first to generate the appropriate login url and then to
   * retrieve the session information.
   * @return assoc array with 'token' => the auth_token string to be passed into login.php and auth_getSession.
   */
  public function auth_createToken() {
    return $this->call_method('facebook.auth.createToken', array());
  }

  /**
   * Call this function to retrieve the session information after your user has 
   * logged in.
   * @param string $auth_token the token returned by auth_createToken or passed back to your callback_url.
   */
  public function auth_getSession($auth_token) {
    if ($this->desktop) {
      $real_server_addr = $this->server_addr;
      //$this->server_addr = str_replace('http://', 'https://', $real_server_addr);
    }
    $result = $this->call_method('facebook.auth.getSession', array('auth_token'=>$auth_token));
    $this->session_key = $result['session_key'];
    if ($this->desktop) {
      $this->session_secret = $result['secret'];
      $this->server_addr = $real_server_addr;
    }
    return $result;
  }

  /* UTILITY FUNCTIONS */

  private function call_method($method, $params) {
    $this->last_call_success = true;
    $this->last_error = array();

    $xml = $this->post_request($method, $params);
    $result = self::convert_simplexml_to_array(simplexml_load_string($xml));
    if ($GLOBALS['config']['debug']) {
      // output the raw xml and its corresponding php object, for debugging:
      print '<div style="width: 100%; clear: both;">';
      print '<div style="float: left; overflow: auto; width: 350px;"><pre>'.htmlspecialchars($xml).'</pre></div>';
      print '<div style="float: left; overflow: auto; width: 350px;"><pre>'.print_r($result, true).'</pre></div>';
      print '</div>';
    }
    if (is_array($result) && isset($result['fb_error'])) {
      $this->last_call_success = false;
      $this->last_error = $result['fb_error'];

      if ($this->throw_errors) {
        throw new Exception($result['fb_error']['msg'], $result['fb_error']['code']);
      }
    }
    return $result;
  }

  private function post_request($method, $params) {
    $params['method'] = $method;
    $params['session_key'] = $this->session_key;
    $params['api_key'] = $this->api_key;
    $params['call_id'] = microtime(true);

    $post_params = array();
    foreach ($params as $key => &$val) {
      if (is_array($val)) $val = implode(',', $val);
      $post_params[] = $key.'='.urlencode($val);
    }
    if ($this->desktop && $method != 'facebook.auth.getSession' && $method != 'facebook.auth.createToken') {
      $secret = $this->session_secret;
    } else {
      $secret = $this->secret;
    }
    $post_params[] = 'sig='.api_generate_sig($params, $secret);
    $post_string = implode('&', $post_params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->server_addr);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }

  public static function convert_simplexml_to_array($sxml) {
    $arr = array();
    if ($sxml) {
      foreach ($sxml as $k => $v) {
        if ($v['id']) {
          $arr[(string)$v['id']] = self::convert_simplexml_to_array($v);
        } else if (substr($k, -4) == '_elt') {
          $arr[] = self::convert_simplexml_to_array($v);
        } else {
          $arr[$k] = self::convert_simplexml_to_array($v);
        }
      }
      /* now the only attribute we ever set is 'id' which is handled above
         foreach($sxml->attributes() as $k=>$v) {
         $arr[$k] = (string)$v;
         }
       */
    }
    if (sizeof($arr) > 0) {
      return $arr;
    } else {
      return (string)utf8_decode($sxml);
    } 
  }
}

// Supporting methods and values------
/**
 * Generate a signature for the API call.  Should be copied into the client
 * library and also used on the server to validate signatures.
 *
 * @author ari
 */
function api_generate_sig($params_array, $secret) {
  $str = '';

  ksort($params_array);
  foreach ($params_array as $k=>$v) {
    if ($k != 'sig')
      $str .= "$k=$v";
  }
  $str .= $secret;

  return md5($str);
}

/**
 * Error codes and descriptions for the Facebook API.
 *
 * Version: 
 * Last Modified:
 */

define(API_EC_SUCCESS, 0);

/*
 * GENERAL ERRORS
 */
define(API_EC_UNKNOWN, 1);
define(API_EC_SERVICE, 2);
define(API_EC_METHOD, 3);
define(API_EC_TOO_MANY_CALLS, 4);
define(API_EC_BAD_IP, 5);

/*
 * PARAMETER ERRORS
 */
define(API_EC_PARAM, 100);
define(API_EC_PARAM_API_KEY, 101);
define(API_EC_PARAM_SESSION_KEY, 102);
define(API_EC_PARAM_CALL_ID, 103);
define(API_EC_PARAM_SIGNATURE, 104);
define(API_EC_PARAM_USER_ID, 110);
define(API_EC_PARAM_USER_FIELD, 111);
define(API_EC_PARAM_SOCIAL_FIELD, 112);
define(API_EC_PARAM_ALBUM_ID, 120);

/*
 * USER PERMISSIONS ERRORS
 */
define(API_EC_PERMISSION, 200);
define(API_EC_PERMISSION_USER, 210);
define(API_EC_PERMISSION_ALBUM, 220);
define(API_EC_PERMISSION_PHOTO, 221);

/*
 * DATA EDIT ERRORS
 */
define(API_EC_EDIT, 300);
define(API_EC_EDIT_USER_DATA, 310);
define(API_EC_EDIT_PHOTO, 320);




$api_error_descriptions = array(
    API_EC_SUCCESS           => 'Success',
    API_EC_UNKNOWN           => 'An unknown error occurred',
    API_EC_SERVICE           => 'Service temporarily unavailable',
    API_EC_METHOD            => 'Unknown method',
    API_EC_TOO_MANY_CALLS    => 'Application request limit reached',
    API_EC_BAD_IP            => 'Unauthorized source IP address',
    API_EC_PARAM             => 'Invalid parameter',
    API_EC_PARAM_API_KEY     => 'Invalid API key',
    API_EC_PARAM_SESSION_KEY => 'Session key invalid or no longer valid',
    API_EC_PARAM_CALL_ID     => 'Call_id must be greater than previous',
    API_EC_PARAM_SIGNATURE   => 'Incorrect signature',
    API_EC_PARAM_USER_ID     => 'Invalid user id',
    API_EC_PARAM_USER_FIELD  => 'Invalid user info field',
    API_EC_PARAM_SOCIAL_FIELD => 'Invalid user field',
    API_EC_PARAM_ALBUM_ID    => 'Invalid album id',
    API_EC_PERMISSION        => 'Permissions error',
    API_EC_PERMISSION_USER   => 'User not visible',
    API_EC_PERMISSION_ALBUM  => 'Album not visible',
    API_EC_PERMISSION_PHOTO  => 'Photo not visible',
    API_EC_EDIT              => 'Edit failure',
    API_EC_EDIT_USER_DATA    => 'User data edit failure',
    API_EC_EDIT_PHOTO        => 'Photo edit failure'
    );

$profile_field_array = array(
    "about_me",
    "affiliations",
    "birthday" ,
    "books" ,
    "clubs" ,
    "current_location" ,
    "first_name" ,
    "gender" ,
    "hometown_location" ,
    "hs_info" ,
    "interests" ,
    "last_name" ,
    "meeting_for" ,
    "meeting_sex" ,
    "movies" ,
    "music" ,
    "name" ,
    "political" ,
    "pic" ,
    "quote" ,
    "relationship_status" ,
    "school_info" ,
    "significant_other_id" ,
    "tv",
    "work_history");
?>